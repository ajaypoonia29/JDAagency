<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Quotation;
use App\Services\Documents\DocumentService;
use App\Services\Finance\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class AtomicPaymentCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_creation_is_completed_inside_the_service(): void
    {
        $customer = $this->createCustomer('CUS-ATOMIC-0001');
        $otherCustomer = $this->createCustomer('CUS-ATOMIC-0002');
        $quotation = $this->createQuotation(
            customer: $customer,
            code: 'QT-ATOMIC-0001',
            grandTotal: 1000,
        );

        $this->mock(
            DocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('generateReceipt')
                    ->once()
                    ->andReturnUsing(function (Payment $payment): string {
                        $path = 'receipts/' . $payment->receipt_number . '.pdf';

                        $payment->update([
                            'receipt_pdf' => $path,
                        ]);

                        return $path;
                    });
            },
        );

        $payment = app(PaymentService::class)->create([
            'quotation_id' => $quotation->id,
            'customer_id' => $otherCustomer->id,
            'amount' => 400,
            'payment_method' => 'UPI',
            'transaction_reference' => 'TXN-ATOMIC-1',
            'payment_date' => now()->toDateString(),
            'notes' => 'Atomic creation test',
            'is_active' => true,
            'receipt_generated' => false,
            'verification_hash' => 'submitted-value-must-be-ignored',
        ]);

        $this->assertSame($quotation->id, $payment->quotation_id);
        $this->assertSame($customer->id, $payment->customer_id);
        $this->assertTrue($payment->receipt_generated);
        $this->assertNotNull($payment->receipt_uuid);
        $this->assertNotNull($payment->verification_hash);
        $this->assertNotSame(
            'submitted-value-must-be-ignored',
            $payment->verification_hash,
        );
        $this->assertSame(
            'receipts/' . $payment->receipt_number . '.pdf',
            $payment->receipt_pdf,
        );

        $quotation->refresh();

        $this->assertEqualsWithDelta(
            400,
            (float) $quotation->total_paid,
            0.001,
        );
        $this->assertEqualsWithDelta(
            600,
            (float) $quotation->balance_due,
            0.001,
        );
        $this->assertSame(
            'Partially Paid',
            $quotation->payment_status,
        );
    }

    public function test_overpayment_is_rejected_before_a_payment_is_created(): void
    {
        $customer = $this->createCustomer('CUS-ATOMIC-0003');
        $quotation = $this->createQuotation(
            customer: $customer,
            code: 'QT-ATOMIC-0002',
            grandTotal: 1000,
        );

        $this->mock(
            DocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldNotReceive('generateReceipt');
            },
        );

        try {
            app(PaymentService::class)->create([
                'quotation_id' => $quotation->id,
                'amount' => 1000.01,
                'payment_method' => 'Cash',
                'payment_date' => now()->toDateString(),
            ]);

            $this->fail('Expected overpayment validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'amount',
                $exception->errors(),
            );
        }

        $this->assertDatabaseCount('payments', 0);

        $quotation->refresh();

        $this->assertEqualsWithDelta(
            0,
            (float) $quotation->total_paid,
            0.001,
        );
        $this->assertEqualsWithDelta(
            1000,
            (float) $quotation->balance_due,
            0.001,
        );
        $this->assertSame('Unpaid', $quotation->payment_status);
    }

    public function test_receipt_failure_rolls_back_payment_and_removes_file(): void
    {
        Storage::fake('public');

        $customer = $this->createCustomer('CUS-ATOMIC-0004');
        $quotation = $this->createQuotation(
            customer: $customer,
            code: 'QT-ATOMIC-0003',
            grandTotal: 1000,
        );

        $failedReceiptPath = null;

        $this->mock(
            DocumentService::class,
            function (MockInterface $mock) use (
                &$failedReceiptPath,
            ): void {
                $mock->shouldReceive('generateReceipt')
                    ->once()
                    ->andReturnUsing(
                        function (Payment $payment) use (
                            &$failedReceiptPath,
                        ): never {
                            $failedReceiptPath = 'receipts/'
                                . $payment->receipt_number
                                . '.pdf';

                            Storage::disk('public')->put(
                                $failedReceiptPath,
                                'partial receipt',
                            );

                            throw new RuntimeException(
                                'Simulated receipt generation failure.',
                            );
                        },
                    );
            },
        );

        try {
            app(PaymentService::class)->create([
                'quotation_id' => $quotation->id,
                'amount' => 250,
                'payment_method' => 'Bank Transfer',
                'payment_date' => now()->toDateString(),
            ]);

            $this->fail('Expected receipt generation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated receipt generation failure.',
                $exception->getMessage(),
            );
        }

        $this->assertNotNull($failedReceiptPath);
        Storage::disk('public')->assertMissing($failedReceiptPath);

        $this->assertDatabaseCount('payments', 0);

        $quotation->refresh();

        $this->assertEqualsWithDelta(
            0,
            (float) $quotation->total_paid,
            0.001,
        );
        $this->assertEqualsWithDelta(
            1000,
            (float) $quotation->balance_due,
            0.001,
        );
        $this->assertSame('Unpaid', $quotation->payment_status);
    }

    private function createCustomer(string $code): Customer
    {
        return Customer::query()->create([
            'customer_code' => $code,
            'customer_type' => 'Business',
            'customer_status' => 'Active',
            'display_name' => $code,
            'contact_person' => 'Atomic Payment Tester',
            'primary_email' => strtolower($code) . '@example.com',
            'primary_phone' => '9999999999',
            'currency' => 'INR',
            'is_active' => true,
        ]);
    }

    private function createQuotation(
        Customer $customer,
        string $code,
        float $grandTotal,
    ): Quotation {
        return Quotation::query()->create([
            'quotation_code' => $code,
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'status' => 'Draft',
            'subtotal' => $grandTotal,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_applicable' => false,
            'tax_percentage' => 18,
            'tax' => 0,
            'grand_total' => $grandTotal,
            'is_active' => true,
        ]);
    }
}
