<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Quotation;
use App\Models\Refund;
use App\Models\User;
use App\Services\Documents\CreditNoteDocumentService;
use App\Services\Documents\DocumentService;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Documents\RefundDocumentService;
use App\Services\Finance\CreditNoteService;
use App\Services\Finance\InvoiceService;
use App\Services\Finance\PaymentService;
use App\Services\Finance\RefundService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinancialAdjustmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_credit_note_updates_invoice_and_quotation_ledgers(): void
    {
        [, , $quotation] = $this->salesContext('CREDIT', 1000);
        $invoice = $this->issuedInvoice($quotation);
        $this->mockCreditNoteDocuments();

        $creditNote = app(CreditNoteService::class)->createAndIssue(
            $invoice,
            [
                'amount' => 200,
                'reason' => 'Scope reduction.',
                'description' => 'Removed service scope',
            ],
        );

        $invoice->refresh();
        $quotation->refresh();

        $this->assertSame('Issued', $creditNote->status);
        $this->assertEqualsWithDelta(200, (float) $invoice->credited_total, 0.001);
        $this->assertEqualsWithDelta(800, (float) $invoice->net_total, 0.001);
        $this->assertEqualsWithDelta(800, (float) $invoice->balance_due, 0.001);
        $this->assertEqualsWithDelta(800, (float) $quotation->balance_due, 0.001);
        Storage::disk('local')->assertExists($creditNote->credit_note_pdf);

        try {
            app(CreditNoteService::class)->create($invoice, [
                'amount' => 801,
                'reason' => 'Excess credit attempt.',
            ]);
            $this->fail('Expected excessive credit validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }

        app(CreditNoteService::class)->void(
            $creditNote,
            'Credit was entered against the wrong scope.',
        );

        $this->assertSame('Void', $creditNote->refresh()->status);
        $this->assertSame(
            'credit note Void',
            Storage::disk('local')->get($creditNote->credit_note_pdf),
        );
        $this->assertEqualsWithDelta(0, (float) $invoice->refresh()->credited_total, 0.001);
        $this->assertEqualsWithDelta(1000, (float) $invoice->net_total, 0.001);
    }

    public function test_refund_reopens_financial_balance_without_reopening_crm(): void
    {
        [, $lead, $quotation] = $this->salesContext('REFUND', 1000);
        $invoice = $this->issuedInvoice($quotation);
        $payment = $this->payment($quotation, 1000);
        $this->mockRefundDocuments();

        $this->assertSame('Paid', $invoice->refresh()->status);
        $this->assertSame('Completed', $quotation->refresh()->status);
        $this->assertSame('Won', $lead->refresh()->lead_status);

        $refund = app(RefundService::class)->process($invoice, [
            'payment_id' => $payment->id,
            'amount' => 300,
            'refund_method' => 'Original Method',
            'reason' => 'Partial service refund.',
        ]);

        $invoice->refresh();

        $this->assertSame('Processed', $refund->status);
        $this->assertEqualsWithDelta(300, (float) $invoice->refunded_total, 0.001);
        $this->assertEqualsWithDelta(700, (float) $invoice->total_paid, 0.001);
        $this->assertEqualsWithDelta(300, (float) $invoice->balance_due, 0.001);
        $this->assertSame('Partially Paid', $invoice->status);
        $this->assertSame('Completed', $quotation->refresh()->status);
        $this->assertSame('Won', $lead->refresh()->lead_status);

        try {
            app(PaymentService::class)->delete($payment);
            $this->fail('Expected refunded payment deletion to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment_id', $exception->errors());
        }

        app(RefundService::class)->cancel(
            $refund,
            'Refund transfer was rejected and reversed.',
        );

        $this->assertSame('Cancelled', $refund->refresh()->status);
        $this->assertSame(
            'refund Cancelled',
            Storage::disk('local')->get($refund->refund_pdf),
        );
        $this->assertSame('Paid', $invoice->refresh()->status);
        $this->assertEqualsWithDelta(0, (float) $invoice->refunded_total, 0.001);
        $this->assertEqualsWithDelta(0, (float) $invoice->balance_due, 0.001);
    }

    public function test_credit_linked_refund_is_limited_and_keeps_invoice_settled(): void
    {
        [, , $quotation] = $this->salesContext('LINKED', 1000);
        $invoice = $this->issuedInvoice($quotation);
        $payment = $this->payment($quotation, 1000);
        $this->mockCreditNoteDocuments();
        $this->mockRefundDocuments();

        $creditNote = app(CreditNoteService::class)->createAndIssue(
            $invoice,
            [
                'amount' => 200,
                'reason' => 'Post-payment discount.',
            ],
        );

        $refund = app(RefundService::class)->process($invoice, [
            'payment_id' => $payment->id,
            'credit_note_id' => $creditNote->id,
            'amount' => 200,
            'refund_method' => 'Bank Transfer',
            'reason' => 'Return the credited amount.',
        ]);

        $invoice->refresh();

        $this->assertSame('Paid', $invoice->status);
        $this->assertEqualsWithDelta(800, (float) $invoice->net_total, 0.001);
        $this->assertEqualsWithDelta(800, (float) $invoice->total_paid, 0.001);
        $this->assertEqualsWithDelta(0, (float) $invoice->balance_due, 0.001);
        $this->assertSame($creditNote->id, $refund->credit_note_id);

        try {
            app(RefundService::class)->process($invoice, [
                'payment_id' => $payment->id,
                'credit_note_id' => $creditNote->id,
                'amount' => 1,
                'refund_method' => 'Bank Transfer',
                'reason' => 'Duplicate linked refund.',
            ]);
            $this->fail('Expected credit-linked over-refund to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }
    }

    public function test_payment_allocations_reject_nonpositive_and_duplicate_rows(): void
    {
        [, , $quotation] = $this->salesContext('ALLOC', 500);
        $invoice = $this->issuedInvoice($quotation);
        $payment = $this->payment($quotation, 100);
        $allocation = PaymentAllocation::query()->firstOrFail();

        try {
            $allocation->update(['amount' => 0]);
            $this->fail('Expected nonpositive allocation validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }

        $this->assertEqualsWithDelta(
            100,
            (float) $allocation->refresh()->amount,
            0.001,
        );

        try {
            PaymentAllocation::query()->create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => 100,
            ]);
            $this->fail('Expected duplicate allocation constraint to fail.');
        } catch (QueryException) {
            $this->assertDatabaseCount('payment_allocations', 1);
        }
    }

    public function test_adjustment_policies_and_private_downloads_use_existing_permissions(): void
    {
        [, , $quotation] = $this->salesContext('SECURE', 500);
        $invoice = $this->issuedInvoice($quotation);
        $payment = $this->payment($quotation, 500);
        $this->mockCreditNoteDocuments();
        $this->mockRefundDocuments();

        $creditNote = app(CreditNoteService::class)->createAndIssue(
            $invoice,
            ['amount' => 50, 'reason' => 'Service adjustment.'],
        );
        $refund = app(RefundService::class)->process($invoice, [
            'payment_id' => $payment->id,
            'credit_note_id' => $creditNote->id,
            'amount' => 50,
            'refund_method' => 'Original Method',
            'reason' => 'Return credit amount.',
        ]);

        $user = User::factory()->create();

        foreach ([
            'invoices.view',
            'invoices.create',
            'invoices.download',
            'invoices.share',
            'payments.view',
            'payments.verify',
            'receipts.download',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo([
            'invoices.view',
            'invoices.create',
            'invoices.download',
            'invoices.share',
            'payments.view',
            'payments.verify',
            'receipts.download',
        ]);

        $this->assertTrue($user->can('download', $creditNote));
        $this->assertTrue($user->can('download', $refund));
        $this->assertTrue($user->can('cancel', $refund));

        $this->get(route('finance.credit-notes.download', $creditNote))
            ->assertRedirect(route('login'));
        $this->get(route('finance.refunds.download', $refund))
            ->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('finance.credit-notes.download', $creditNote))
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff');
        $this->actingAs($user)
            ->get(route('finance.refunds.download', $refund))
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    /** @return array{0: Customer, 1: Lead, 2: Quotation} */
    private function salesContext(string $suffix, float $total): array
    {
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-' . $suffix,
            'customer_type' => 'Business',
            'customer_status' => 'Active',
            'display_name' => 'Customer ' . $suffix,
            'contact_person' => 'Finance Tester',
            'primary_email' => strtolower($suffix) . '@example.com',
            'primary_phone' => '9999999999',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_code' => 'LEAD-' . $suffix,
            'lead_status' => 'Proposal Sent',
            'company_name' => 'Lead ' . $suffix,
            'contact_person' => 'Finance Tester',
            'email' => strtolower($suffix) . '.lead@example.com',
            'phone' => '8888888888',
            'converted_customer_id' => $customer->id,
            'is_active' => true,
        ]);

        $quotation = Quotation::query()->create([
            'quotation_code' => 'QT-' . $suffix,
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'status' => 'Approved',
            'subtotal' => $total,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_applicable' => false,
            'tax_percentage' => 18,
            'tax' => 0,
            'grand_total' => $total,
            'is_active' => true,
        ]);

        return [$customer, $lead, $quotation];
    }

    private function issuedInvoice(Quotation $quotation): Invoice
    {
        $this->mock(
            InvoiceDocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('generate')
                    ->zeroOrMoreTimes()
                    ->andReturnUsing(function (Invoice $invoice): string {
                        $path = 'invoices/' . $invoice->invoice_no . '.pdf';
                        Storage::disk('local')->put($path, 'invoice pdf');
                        return $path;
                    });
            },
        );

        $invoice = app(InvoiceService::class)
            ->createFromQuotation($quotation);

        return app(InvoiceService::class)->issue($invoice);
    }

    private function payment(Quotation $quotation, float $amount): Payment
    {
        $this->mock(
            DocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('generateReceipt')
                    ->once()
                    ->andReturnUsing(function (Payment $payment): string {
                        $path = 'receipts/' . $payment->receipt_number . '.pdf';
                        Storage::disk('local')->put($path, 'receipt pdf');
                        $payment->forceFill(['receipt_pdf' => $path])->save();
                        return $path;
                    });
            },
        );

        return app(PaymentService::class)->create([
            'quotation_id' => $quotation->id,
            'amount' => $amount,
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
        ]);
    }

    private function mockCreditNoteDocuments(): void
    {
        $this->mock(
            CreditNoteDocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('generate')
                    ->zeroOrMoreTimes()
                    ->andReturnUsing(function (CreditNote $creditNote): string {
                        $path = 'credit-notes/' . $creditNote->credit_note_no . '.pdf';
                        Storage::disk('local')->put(
                            $path,
                            'credit note ' . $creditNote->status,
                        );
                        return $path;
                    });
            },
        );
    }

    private function mockRefundDocuments(): void
    {
        $this->mock(
            RefundDocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('generate')
                    ->zeroOrMoreTimes()
                    ->andReturnUsing(function (Refund $refund): string {
                        $path = 'refunds/' . $refund->refund_no . '.pdf';
                        Storage::disk('local')->put(
                            $path,
                            'refund ' . $refund->status,
                        );
                        return $path;
                    });
            },
        );
    }
}
