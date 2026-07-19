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

class PaymentMutationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_recalculates_ledger_and_preserves_receipt_identity(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $customer = $this->createCustomer('CUS-MUT-0001');
        $otherCustomer = $this->createCustomer('CUS-MUT-0002');
        $quotation = $this->createQuotation(
            customer: $customer,
            code: 'QT-MUT-0001',
            grandTotal: 1000,
        );
        $payment = $this->createPayment(
            quotation: $quotation,
            amount: 400,
            number: 'PAY-MUT-0001',
            receipt: 'RCT-MUT-0001',
        );

        Storage::disk('public')->put(
            $payment->receipt_pdf,
            'old receipt',
        );

        $this->mockReceiptRegeneration('updated receipt');

        $updated = app(PaymentService::class)->update($payment, [
            'quotation_id' => $quotation->id,
            'customer_id' => $otherCustomer->id,
            'amount' => 700,
            'payment_method' => 'Bank Transfer',
            'transaction_reference' => 'TXN-MUT-1',
            'payment_date' => now()->toDateString(),
            'notes' => 'Updated payment',
            'is_active' => true,
            'payment_no' => 'TAMPERED-PAYMENT',
            'receipt_number' => 'TAMPERED-RECEIPT',
            'receipt_uuid' => 'tampered-uuid',
            'verification_hash' => 'tampered-hash',
        ]);

        $this->assertSame($customer->id, $updated->customer_id);
        $this->assertSame('PAY-MUT-0001', $updated->payment_no);
        $this->assertSame('RCT-MUT-0001', $updated->receipt_number);
        $this->assertSame('uuid-PAY-MUT-0001', $updated->receipt_uuid);
        $this->assertSame('hash-PAY-MUT-0001', $updated->verification_hash);
        $this->assertEqualsWithDelta(700, (float) $updated->amount, 0.001);
        $this->assertSame(
            'updated receipt',
            Storage::disk('public')->get($updated->receipt_pdf),
        );

        $this->assertLedger(
            $quotation,
            paid: 700,
            balance: 300,
            status: 'Partially Paid',
        );

        $updated->update([
            'receipt_uuid' => 'direct-tamper',
            'verification_hash' => 'direct-tamper',
        ]);
        $updated->refresh();

        $this->assertSame('uuid-PAY-MUT-0001', $updated->receipt_uuid);
        $this->assertSame('hash-PAY-MUT-0001', $updated->verification_hash);
    }

    public function test_update_rejects_overpayment_without_changing_payment_or_ledger(): void
    {
        $customer = $this->createCustomer('CUS-MUT-0003');
        $quotation = $this->createQuotation(
            customer: $customer,
            code: 'QT-MUT-0002',
            grandTotal: 1000,
        );
        $payment = $this->createPayment(
            quotation: $quotation,
            amount: 400,
            number: 'PAY-MUT-0002',
            receipt: 'RCT-MUT-0002',
        );
        $this->createPayment(
            quotation: $quotation,
            amount: 300,
            number: 'PAY-MUT-0003',
            receipt: 'RCT-MUT-0003',
        );

        $this->mock(
            DocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldNotReceive('generateReceipt');
            },
        );

        try {
            app(PaymentService::class)->update($payment, [
                'quotation_id' => $quotation->id,
                'amount' => 800,
                'payment_method' => 'Cash',
                'payment_date' => now()->toDateString(),
                'is_active' => true,
            ]);

            $this->fail('Expected update overpayment validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }

        $payment->refresh();
        $this->assertEqualsWithDelta(400, (float) $payment->amount, 0.001);

        $this->assertLedger(
            $quotation,
            paid: 700,
            balance: 300,
            status: 'Partially Paid',
        );
    }

    public function test_moving_payment_recalculates_both_quotations_and_customer(): void
    {
        $firstCustomer = $this->createCustomer('CUS-MUT-0004');
        $secondCustomer = $this->createCustomer('CUS-MUT-0005');
        $firstQuotation = $this->createQuotation(
            customer: $firstCustomer,
            code: 'QT-MUT-0003',
            grandTotal: 1000,
        );
        $secondQuotation = $this->createQuotation(
            customer: $secondCustomer,
            code: 'QT-MUT-0004',
            grandTotal: 500,
        );
        $payment = $this->createPayment(
            quotation: $firstQuotation,
            amount: 400,
            number: 'PAY-MUT-0004',
            receipt: 'RCT-MUT-0004',
        );
        $this->createPayment(
            quotation: $secondQuotation,
            amount: 100,
            number: 'PAY-MUT-0005',
            receipt: 'RCT-MUT-0005',
        );

        $this->mockReceiptRegeneration('moved receipt');

        $updated = app(PaymentService::class)->update($payment, [
            'quotation_id' => $secondQuotation->id,
            'amount' => 300,
            'payment_method' => 'UPI',
            'payment_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->assertSame($secondQuotation->id, $updated->quotation_id);
        $this->assertSame($secondCustomer->id, $updated->customer_id);

        $this->assertLedger(
            $firstQuotation,
            paid: 0,
            balance: 1000,
            status: 'Unpaid',
        );
        $this->assertLedger(
            $secondQuotation,
            paid: 400,
            balance: 100,
            status: 'Partially Paid',
        );
    }

    public function test_failed_receipt_regeneration_rolls_back_and_restores_old_file(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $customer = $this->createCustomer('CUS-MUT-0006');
        $quotation = $this->createQuotation(
            customer: $customer,
            code: 'QT-MUT-0005',
            grandTotal: 1000,
        );
        $payment = $this->createPayment(
            quotation: $quotation,
            amount: 400,
            number: 'PAY-MUT-0006',
            receipt: 'RCT-MUT-0006',
        );

        Storage::disk('public')->put(
            $payment->receipt_pdf,
            'original receipt bytes',
        );

        $this->mock(
            DocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('generateReceipt')
                    ->once()
                    ->andReturnUsing(function (Payment $payment): never {
                        Storage::disk('public')->put(
                            $payment->receipt_pdf,
                            'corrupted replacement',
                        );

                        throw new RuntimeException(
                            'Simulated receipt regeneration failure.',
                        );
                    });
            },
        );

        try {
            app(PaymentService::class)->update($payment, [
                'quotation_id' => $quotation->id,
                'amount' => 500,
                'payment_method' => 'Cash',
                'payment_date' => now()->toDateString(),
                'is_active' => true,
            ]);

            $this->fail('Expected receipt regeneration to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated receipt regeneration failure.',
                $exception->getMessage(),
            );
        }

        $payment->refresh();
        $this->assertEqualsWithDelta(400, (float) $payment->amount, 0.001);
        $this->assertSame(
            'original receipt bytes',
            Storage::disk('public')->get($payment->receipt_pdf),
        );

        $this->assertLedger(
            $quotation,
            paid: 400,
            balance: 600,
            status: 'Partially Paid',
        );
    }

    public function test_delete_restore_and_force_delete_keep_ledger_and_files_consistent(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $customer = $this->createCustomer('CUS-MUT-0007');
        $quotation = $this->createQuotation(
            customer: $customer,
            code: 'QT-MUT-0006',
            grandTotal: 1000,
        );
        $payment = $this->createPayment(
            quotation: $quotation,
            amount: 400,
            number: 'PAY-MUT-0007',
            receipt: 'RCT-MUT-0007',
        );
        $payment->update([
            'statement_pdf' => 'statements/PAY-MUT-0007.pdf',
        ]);

        Storage::disk('public')->put($payment->receipt_pdf, 'receipt');
        Storage::disk('public')->put($payment->statement_pdf, 'statement');

        $service = app(PaymentService::class);

        $this->assertTrue($service->delete($payment));
        $this->assertSoftDeleted($payment);
        $this->assertLedger($quotation, 0, 1000, 'Unpaid');
        Storage::disk('public')->assertExists($payment->receipt_pdf);

        $this->assertTrue($service->restore($payment));
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'deleted_at' => null,
        ]);
        $this->assertLedger($quotation, 400, 600, 'Partially Paid');

        $this->assertTrue($service->forceDelete($payment));
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertLedger($quotation, 0, 1000, 'Unpaid');
        Storage::disk('public')->assertMissing('receipts/RCT-MUT-0007.pdf');
        Storage::disk('public')->assertMissing('statements/PAY-MUT-0007.pdf');
    }

    public function test_restore_is_rejected_when_it_would_overpay_quotation(): void
    {
        $customer = $this->createCustomer('CUS-MUT-0008');
        $quotation = $this->createQuotation(
            customer: $customer,
            code: 'QT-MUT-0007',
            grandTotal: 1000,
        );
        $deletedPayment = $this->createPayment(
            quotation: $quotation,
            amount: 400,
            number: 'PAY-MUT-0008',
            receipt: 'RCT-MUT-0008',
        );

        app(PaymentService::class)->delete($deletedPayment);

        $this->createPayment(
            quotation: $quotation,
            amount: 700,
            number: 'PAY-MUT-0009',
            receipt: 'RCT-MUT-0009',
        );

        try {
            app(PaymentService::class)->restore($deletedPayment);

            $this->fail('Expected restore overpayment validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }

        $this->assertSoftDeleted($deletedPayment);
        $this->assertLedger(
            $quotation,
            paid: 700,
            balance: 300,
            status: 'Partially Paid',
        );
    }

    private function mockReceiptRegeneration(string $contents): void
    {
        $this->mock(
            DocumentService::class,
            function (MockInterface $mock) use ($contents): void {
                $mock->shouldReceive('generateReceipt')
                    ->once()
                    ->andReturnUsing(
                        function (Payment $payment) use ($contents): string {
                            $path = 'receipts/'
                                . $payment->receipt_number
                                . '.pdf';

                            Storage::disk('public')->put($path, $contents);
                            $payment->update(['receipt_pdf' => $path]);

                            return $path;
                        },
                    );
            },
        );
    }

    private function createCustomer(string $code): Customer
    {
        return Customer::query()->create([
            'customer_code' => $code,
            'customer_type' => 'Business',
            'customer_status' => 'Active',
            'display_name' => $code,
            'contact_person' => 'Payment Mutation Tester',
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

    private function createPayment(
        Quotation $quotation,
        float $amount,
        string $number,
        string $receipt,
    ): Payment {
        return Payment::query()->create([
            'payment_no' => $number,
            'quotation_id' => $quotation->id,
            'customer_id' => $quotation->customer_id,
            'amount' => $amount,
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
            'receipt_generated' => true,
            'receipt_number' => $receipt,
            'receipt_uuid' => 'uuid-' . $number,
            'verification_hash' => 'hash-' . $number,
            'receipt_pdf' => 'receipts/' . $receipt . '.pdf',
            'receipt_generated_at' => now(),
            'whatsapp_sent' => false,
            'email_sent' => false,
            'is_active' => true,
        ]);
    }

    private function assertLedger(
        Quotation $quotation,
        float $paid,
        float $balance,
        string $status,
    ): void {
        $quotation->refresh();

        $this->assertEqualsWithDelta(
            $paid,
            (float) $quotation->total_paid,
            0.001,
        );
        $this->assertEqualsWithDelta(
            $balance,
            (float) $quotation->balance_due,
            0.001,
        );
        $this->assertSame($status, $quotation->payment_status);
    }
}
