<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_lifecycle_recalculates_quotation_ledgers(): void
    {
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-TEST-0001',
            'customer_type' => 'Business',
            'customer_status' => 'Active',
            'display_name' => 'Ledger Test Customer',
            'contact_person' => 'Ledger Tester',
            'primary_email' => 'ledger-test@example.com',
            'primary_phone' => '9999999999',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $firstQuotation = Quotation::query()->create([
            'quotation_code' => 'QT-TEST-0001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'status' => 'Draft',
            'subtotal' => 1000,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_applicable' => false,
            'tax_percentage' => 18,
            'tax' => 0,
            'grand_total' => 1000,
            'is_active' => true,
        ]);

        $secondQuotation = Quotation::query()->create([
            'quotation_code' => 'QT-TEST-0002',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'status' => 'Draft',
            'subtotal' => 500,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_applicable' => false,
            'tax_percentage' => 18,
            'tax' => 0,
            'grand_total' => 500,
            'is_active' => true,
        ]);

        $this->assertLedger(
            $firstQuotation,
            paid: 0,
            balance: 1000,
            status: 'Unpaid',
        );

        $payment = Payment::query()->create([
            'payment_no' => 'PAY-TEST-0001',
            'quotation_id' => $firstQuotation->id,
            'customer_id' => $customer->id,
            'amount' => 400,
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
            'receipt_generated' => false,
            'whatsapp_sent' => false,
            'email_sent' => false,
            'is_active' => true,
            'verification_hash' => 'ledger-test-hash',
        ]);

        $this->assertLedger(
            $firstQuotation,
            paid: 400,
            balance: 600,
            status: 'Partially Paid',
        );

        $payment->update([
            'amount' => 1000,
        ]);

        $this->assertLedger(
            $firstQuotation,
            paid: 1000,
            balance: 0,
            status: 'Paid',
        );

        $payment->update([
            'quotation_id' => $secondQuotation->id,
            'amount' => 200,
        ]);

        $this->assertLedger(
            $firstQuotation,
            paid: 0,
            balance: 1000,
            status: 'Unpaid',
        );

        $this->assertLedger(
            $secondQuotation,
            paid: 200,
            balance: 300,
            status: 'Partially Paid',
        );

        $payment->delete();

        $this->assertSoftDeleted($payment);

        $this->assertLedger(
            $secondQuotation,
            paid: 0,
            balance: 500,
            status: 'Unpaid',
        );

        $payment->restore();

        $this->assertLedger(
            $secondQuotation,
            paid: 200,
            balance: 300,
            status: 'Partially Paid',
        );

        $secondQuotation->update([
            'grand_total' => 200,
        ]);

        $this->assertLedger(
            $secondQuotation,
            paid: 200,
            balance: 0,
            status: 'Paid',
        );

        $this->assertSame(
            route('receipt.verify', [
                'hash' => 'ledger-test-hash',
            ]),
            $payment->verificationUrl(),
        );

        $payment->forceDelete();

        $this->assertDatabaseMissing('payments', [
            'id' => $payment->id,
        ]);

        $this->assertLedger(
            $secondQuotation,
            paid: 0,
            balance: 200,
            status: 'Unpaid',
        );
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

        $this->assertSame(
            $status,
            $quotation->payment_status,
        );
    }
}
