<?php

declare(strict_types=1);

namespace Tests\Feature\CRM;

use App\Livewire\SalesWorkspaceFinance;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Refund;
use App\Models\User;
use App\Services\CRM\SalesJourneyService;
use App\Services\Documents\CreditNoteDocumentService;
use App\Services\Documents\DocumentService;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Documents\RefundDocumentService;
use App\Services\Finance\CreditNoteService;
use App\Services\Finance\InvoiceService;
use App\Services\Finance\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SalesWorkspaceFinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }

    public function test_workspace_creates_invoice_draft(): void
    {
        $user = $this->authorizedUser();

        [$lead, $quotation] = $this->salesContext(
            'CREATE',
            1000,
        );

        Livewire::actingAs($user)
            ->test(
                SalesWorkspaceFinance::class,
                [
                    'leadId' => $lead->getKey(),
                ],
            )
            ->assertStatus(200)
            ->assertSee('Create Invoice')
            ->call('openInvoiceEditor')
            ->assertSet('invoiceEditorOpen', true)
            ->set('invoiceDate', '2026-07-15')
            ->set('dueDate', '2026-07-22')
            ->set(
                'invoiceCustomerNotes',
                'Workspace customer note',
            )
            ->set(
                'invoiceInternalNotes',
                'Workspace internal note',
            )
            ->call('saveInvoice')
            ->assertHasNoErrors()
            ->assertSet('invoiceEditorOpen', false);

        $invoice = Invoice::query()->firstOrFail();

        $this->assertSame(
            $quotation->getKey(),
            $invoice->quotation_id,
        );

        $this->assertSame(
            $lead->getKey(),
            $invoice->lead_id,
        );

        $this->assertSame(
            'Draft',
            $invoice->status,
        );

        $this->assertSame(
            'Workspace customer note',
            $invoice->customer_notes,
        );

        $this->assertSame(
            'Workspace internal note',
            $invoice->internal_notes,
        );

        $this->assertEqualsWithDelta(
            1000,
            (float) $invoice->grand_total,
            0.001,
        );

        $this->assertEqualsWithDelta(
            1000,
            (float) $invoice->balance_due,
            0.001,
        );

        $this->assertSame(
            1,
            $invoice->items()->count(),
        );
    }

    public function test_paid_draft_is_recommended_and_reconciled_when_issued(): void
    {
        $user = $this->authorizedUser();

        [$lead, $quotation] = $this->salesContext(
            'RECONCILE',
            500,
        );

        Payment::query()->create([
            'payment_no' => 'PAY-RECONCILE-0001',
            'quotation_id' => $quotation->getKey(),
            'customer_id' => $quotation->customer_id,
            'amount' => 500,
            'payment_method' => 'UPI',
            'payment_date' => '2026-07-15',
            'receipt_generated' => false,
            'is_active' => true,
        ]);

        $invoice = app(InvoiceService::class)
            ->createFromQuotation($quotation);

        $this->assertSame(
            'Draft',
            $invoice->status,
        );

        $this->assertEqualsWithDelta(
            500,
            (float) $invoice->total_paid,
            0.001,
        );

        $lead->forceFill([
            'lead_status' => 'Won',
        ])->save();

        $journeyLead = $lead->refresh()->load([
            'meetings',
            'quotations.payments',
            'quotations.invoice',
            'invoices',
        ]);

        $nextAction = app(SalesJourneyService::class)
            ->nextAction($journeyLead);

        $this->assertSame(
            'issue-invoice',
            $nextAction['key'],
        );

        $this->assertStringContainsString(
            'reconcile',
            strtolower($nextAction['description']),
        );

        $this->actingAs($user);

        $this->mockInvoiceDocumentGeneration();

        Livewire::actingAs($user)
            ->test(
                SalesWorkspaceFinance::class,
                [
                    'leadId' => $lead->getKey(),
                ],
            )
            ->assertStatus(200)
            ->assertSee('Issue and Reconcile')
            ->call('issueInvoice')
            ->assertHasNoErrors();

        $invoice->refresh();

        $this->assertNotNull(
            $invoice->issued_at,
        );

        $this->assertSame(
            'Paid',
            $invoice->status,
        );

        $this->assertEqualsWithDelta(
            0,
            (float) $invoice->balance_due,
            0.001,
        );

        $this->assertSame(
            'Completed',
            $quotation->refresh()->status,
        );

        $this->assertSame(
            'Won',
            $lead->refresh()->lead_status,
        );

        Storage::disk('local')->assertExists(
            $invoice->invoice_pdf,
        );
    }

    public function test_workspace_records_and_allocates_partial_payment(): void
    {
        $user = $this->authorizedUser();

        [$lead, $quotation] = $this->salesContext(
            'PARTIAL',
            1000,
        );

        $this->actingAs($user);

        $invoice = app(InvoiceService::class)
            ->createFromQuotation($quotation);

        $this->mockInvoiceDocumentGeneration();

        $invoice = app(InvoiceService::class)
            ->issue($invoice);

        $this->assertSame(
            'Issued',
            $invoice->status,
        );

        $this->mockReceiptGeneration();

        Livewire::actingAs($user)
            ->test(
                SalesWorkspaceFinance::class,
                [
                    'leadId' => $lead->getKey(),
                ],
            )
            ->assertStatus(200)
            ->assertSee('Record Payment')
            ->call('openPaymentEditor')
            ->assertSet(
                'paymentAmount',
                '1000.00',
            )
            ->set('paymentAmount', '400.00')
            ->set('paymentMethod', 'Bank Transfer')
            ->set(
                'paymentReference',
                'UTR-PHASE9C1-0001',
            )
            ->set('paymentDate', '2026-07-15')
            ->set(
                'paymentNotes',
                'Partial collection from workspace.',
            )
            ->call('recordPayment')
            ->assertHasNoErrors()
            ->assertSet('paymentEditorOpen', false);

        $payment = Payment::query()->firstOrFail();

        $this->assertSame(
            $quotation->getKey(),
            $payment->quotation_id,
        );

        $this->assertEqualsWithDelta(
            400,
            (float) $payment->amount,
            0.001,
        );

        $this->assertSame(
            'Bank Transfer',
            $payment->payment_method,
        );

        $this->assertSame(
            'UTR-PHASE9C1-0001',
            $payment->transaction_reference,
        );

        $this->assertDatabaseHas(
            'payment_allocations',
            [
                'payment_id' => $payment->getKey(),
                'invoice_id' => $invoice->getKey(),
                'amount' => 400,
            ],
        );

        $invoice->refresh();

        $this->assertSame(
            'Partially Paid',
            $invoice->status,
        );

        $this->assertEqualsWithDelta(
            400,
            (float) $invoice->total_paid,
            0.001,
        );

        $this->assertEqualsWithDelta(
            600,
            (float) $invoice->balance_due,
            0.001,
        );

        $quotation->refresh();

        $this->assertSame(
            'Partially Paid',
            $quotation->payment_status,
        );

        $this->assertEqualsWithDelta(
            600,
            (float) $quotation->balance_due,
            0.001,
        );

        Storage::disk('local')->assertExists(
            $payment->receipt_pdf,
        );
    }

    public function test_workspace_issues_credit_note_and_displays_adjustment(): void
    {
        $user = $this->authorizedUser();

        [$lead, $quotation] = $this->salesContext(
            'CREDIT-ADJUSTMENT',
            1000,
        );

        $this->actingAs($user);
        $this->mockAdjustmentDocumentGeneration();

        $invoice = app(InvoiceService::class)
            ->createFromQuotation($quotation);

        $invoice = app(InvoiceService::class)
            ->issue($invoice);

        $component = Livewire::actingAs($user)
            ->test(
                SalesWorkspaceFinance::class,
                [
                    'leadId' => $lead->getKey(),
                ],
            )
            ->assertStatus(200)
            ->assertSee('Issue Credit Note')
            ->call('openCreditNoteEditor')
            ->assertSet('creditNoteEditorOpen', true)
            ->assertSet('creditNoteAmount', '1000.00')
            ->set('creditNoteAmount', '200.00')
            ->set('creditNoteTax', '20.00')
            ->set('creditNoteIssueDate', '2026-07-16')
            ->set(
                'creditNoteDescription',
                'Reduced implementation scope',
            )
            ->set(
                'creditNoteReason',
                'Customer removed one deliverable.',
            )
            ->call('issueCreditNote')
            ->assertHasNoErrors()
            ->assertSet('creditNoteEditorOpen', false);

        $creditNote = CreditNote::query()
            ->firstOrFail();

        $this->assertSame(
            $invoice->getKey(),
            $creditNote->invoice_id,
        );

        $this->assertSame(
            'Issued',
            $creditNote->status,
        );

        $this->assertEqualsWithDelta(
            200,
            (float) $creditNote->grand_total,
            0.001,
        );

        $this->assertEqualsWithDelta(
            20,
            (float) $creditNote->tax,
            0.001,
        );

        $this->assertSame(
            'Customer removed one deliverable.',
            $creditNote->reason,
        );

        $invoice->refresh();
        $quotation->refresh();

        $this->assertEqualsWithDelta(
            200,
            (float) $invoice->credited_total,
            0.001,
        );

        $this->assertEqualsWithDelta(
            800,
            (float) $invoice->net_total,
            0.001,
        );

        $this->assertEqualsWithDelta(
            800,
            (float) $invoice->balance_due,
            0.001,
        );

        $this->assertEqualsWithDelta(
            800,
            (float) $quotation->balance_due,
            0.001,
        );

        Storage::disk('local')->assertExists(
            $creditNote->credit_note_pdf,
        );

        $component
            ->assertSee('Financial adjustments')
            ->assertSee($creditNote->credit_note_no)
            ->assertSee('Customer removed one deliverable.')
            ->assertSee('Download Credit Note');
    }

    public function test_workspace_processes_credit_linked_refund_and_displays_timeline(): void
    {
        $user = $this->authorizedUser();

        [$lead, $quotation] = $this->salesContext(
            'REFUND-ADJUSTMENT',
            1000,
        );

        $this->actingAs($user);
        $this->mockAdjustmentDocumentGeneration();

        $invoice = app(InvoiceService::class)
            ->createFromQuotation($quotation);

        $invoice = app(InvoiceService::class)
            ->issue($invoice);

        $this->mockReceiptGeneration();

        $payment = app(PaymentService::class)
            ->create([
                'quotation_id' =>
                    $quotation->getKey(),
                'amount' => 1000,
                'payment_method' =>
                    'Bank Transfer',
                'transaction_reference' =>
                    'UTR-REFUND-ADJUSTMENT',
                'payment_date' =>
                    '2026-07-16',
            ]);

        $creditNote = app(CreditNoteService::class)
            ->createAndIssue(
                $invoice,
                [
                    'amount' => 200,
                    'issue_date' => '2026-07-16',
                    'description' =>
                        'Post-payment scope credit',
                    'reason' =>
                        'Return the removed scope value.',
                ],
            );

        $component = Livewire::actingAs($user)
            ->test(
                SalesWorkspaceFinance::class,
                [
                    'leadId' => $lead->getKey(),
                ],
            )
            ->assertStatus(200)
            ->assertSee('Process Refund')
            ->call('openRefundEditor')
            ->assertSet('refundEditorOpen', true)
            ->assertSet(
                'refundPaymentId',
                $payment->getKey(),
            )
            ->assertSet('refundAmount', '1000.00')
            ->set(
                'refundCreditNoteId',
                $creditNote->getKey(),
            )
            ->set('refundAmount', '200.00')
            ->set(
                'refundMethod',
                'Bank Transfer',
            )
            ->set(
                'refundReference',
                'RF-UTR-0001',
            )
            ->set('refundDate', '2026-07-16')
            ->set(
                'refundReason',
                'Refund the credited scope value.',
            )
            ->call('processRefund')
            ->assertHasNoErrors()
            ->assertSet('refundEditorOpen', false);

        $refund = Refund::query()->firstOrFail();

        $this->assertSame(
            $invoice->getKey(),
            $refund->invoice_id,
        );

        $this->assertSame(
            $payment->getKey(),
            $refund->payment_id,
        );

        $this->assertSame(
            $creditNote->getKey(),
            $refund->credit_note_id,
        );

        $this->assertSame(
            'Processed',
            $refund->status,
        );

        $this->assertEqualsWithDelta(
            200,
            (float) $refund->amount,
            0.001,
        );

        $this->assertSame(
            'RF-UTR-0001',
            $refund->transaction_reference,
        );

        $invoice->refresh();

        $this->assertSame(
            'Paid',
            $invoice->status,
        );

        $this->assertEqualsWithDelta(
            800,
            (float) $invoice->net_total,
            0.001,
        );

        $this->assertEqualsWithDelta(
            800,
            (float) $invoice->total_paid,
            0.001,
        );

        $this->assertEqualsWithDelta(
            200,
            (float) $invoice->refunded_total,
            0.001,
        );

        $this->assertEqualsWithDelta(
            0,
            (float) $invoice->balance_due,
            0.001,
        );

        Storage::disk('local')->assertExists(
            $refund->refund_pdf,
        );

        $component
            ->assertSee('Financial adjustments')
            ->assertSee($creditNote->credit_note_no)
            ->assertSee($refund->refund_no)
            ->assertSee($payment->payment_no)
            ->assertSee('Refund the credited scope value.')
            ->assertSee('Download Refund');

        $invoice->forceFill([
            'email_sent' => true,
            'email_sent_at' => now(),
            'last_sent_to' =>
                'timeline-recipient@example.test',
        ])->saveQuietly();

        $timelineLead = $lead->fresh([
            'meetings',
            'quotations.payments',
            'invoices.creditNotes',
            'invoices.refunds',
        ]);

        $this->assertInstanceOf(
            Lead::class,
            $timelineLead,
        );

        $timeline = collect(
            app(SalesJourneyService::class)
                ->timeline($timelineLead),
        );

        $titles = $timeline
            ->pluck('title')
            ->all();

        $this->assertContains(
            'Invoice sent',
            $titles,
        );
        $this->assertContains(
            'Credit note issued',
            $titles,
        );
        $this->assertContains(
            'Refund processed',
            $titles,
        );
        $this->assertContains(
            'Payment received',
            $titles,
        );

        $paymentEvent = $timeline
            ->firstWhere('type', 'payment');

        $this->assertIsArray($paymentEvent);
        $this->assertTrue(
            $payment->created_at->equalTo(
                $paymentEvent['at'],
            ),
        );
    }

    public function test_workspace_hides_and_blocks_adjustment_actions_without_permissions(): void
    {
        $authorized = $this->authorizedUser();

        [$lead, $quotation] = $this->salesContext(
            'ADJUSTMENT-PERMISSIONS',
            500,
        );

        $this->actingAs($authorized);
        $this->mockAdjustmentDocumentGeneration();

        $invoice = app(InvoiceService::class)
            ->createFromQuotation($quotation);

        app(InvoiceService::class)->issue($invoice);

        $this->mockReceiptGeneration();

        app(PaymentService::class)->create([
            'quotation_id' => $quotation->getKey(),
            'amount' => 500,
            'payment_method' => 'UPI',
            'payment_date' => '2026-07-16',
        ]);

        $viewer = $this->adjustmentViewer();

        Livewire::actingAs($viewer)
            ->test(
                SalesWorkspaceFinance::class,
                [
                    'leadId' => $lead->getKey(),
                ],
            )
            ->assertStatus(200)
            ->assertSee('Financial adjustments')
            ->assertDontSee('Issue Credit Note')
            ->assertDontSee('Process Refund');

        Livewire::actingAs($viewer)
            ->test(
                SalesWorkspaceFinance::class,
                [
                    'leadId' => $lead->getKey(),
                ],
            )
            ->call('openCreditNoteEditor')
            ->assertForbidden();

        Livewire::actingAs($viewer)
            ->test(
                SalesWorkspaceFinance::class,
                [
                    'leadId' => $lead->getKey(),
                ],
            )
            ->call('openRefundEditor')
            ->assertForbidden();

        $this->assertDatabaseCount(
            'credit_notes',
            0,
        );

        $this->assertDatabaseCount(
            'refunds',
            0,
        );
    }

    public function test_commercial_summary_uses_invoice_ledger_values(): void
    {
        [$lead, $quotation] = $this->salesContext(
            'SUMMARY',
            1000,
        );

        $invoice = app(InvoiceService::class)
            ->createFromQuotation($quotation);

        $invoice->forceFill([
            'status' => 'Partially Paid',
            'issued_at' => now(),
            'credited_total' => 200,
            'refunded_total' => 100,
            'net_total' => 800,
            'total_paid' => 300,
            'balance_due' => 500,
        ])->saveQuietly();

        $journeyLead = $lead->refresh()->load([
            'meetings',
            'quotations.payments',
            'quotations.invoice',
            'invoices',
        ]);

        $summary = app(SalesJourneyService::class)
            ->summary($journeyLead);

        $this->assertEqualsWithDelta(
            1000,
            $summary['quotation_total'],
            0.001,
        );

        $this->assertEqualsWithDelta(
            800,
            $summary['invoiced_total'],
            0.001,
        );

        $this->assertEqualsWithDelta(
            200,
            $summary['credited_total'],
            0.001,
        );

        $this->assertEqualsWithDelta(
            100,
            $summary['refunded_total'],
            0.001,
        );

        $this->assertEqualsWithDelta(
            300,
            $summary['paid_total'],
            0.001,
        );

        $this->assertEqualsWithDelta(
            500,
            $summary['balance_total'],
            0.001,
        );

        $nextAction = app(SalesJourneyService::class)
            ->nextAction($journeyLead);

        $this->assertSame(
            'receive-payment',
            $nextAction['key'],
        );
    }

    private function authorizedUser(): User
    {
        $permissions = [
            'leads.view',
            'leads.edit',
            'quotations.view',
            'quotations.edit',
            'quotations.approve',
            'quotations.send',
            'invoices.view',
            'invoices.create',
            'invoices.share',
            'invoices.download',
            'payments.view',
            'payments.create',
            'payments.verify',
            'receipts.create',
            'receipts.download',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate(
                $permission,
                'web',
            );
        }

        $role = Role::findOrCreate(
            'Admin',
            'web',
        );

        $user = User::factory()->create();

        $user->assignRole($role);
        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        return $user->refresh();
    }

    private function adjustmentViewer(): User
    {
        $permissions = [
            'leads.view',
            'quotations.view',
            'invoices.view',
            'payments.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate(
                $permission,
                'web',
            );
        }

        $role = Role::findOrCreate(
            'Admin',
            'web',
        );

        $user = User::factory()->create();

        $user->assignRole($role);
        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        return $user->refresh();
    }

    /**
     * @return array{0: Lead, 1: Quotation}
     */
    private function salesContext(
        string $suffix,
        float $total,
    ): array {
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-' . $suffix,
            'customer_type' => 'Business',
            'customer_status' => 'Active',
            'display_name' =>
                'Workspace Customer ' . $suffix,
            'contact_person' => 'Finance Tester',
            'primary_email' =>
                strtolower($suffix) . '@example.com',
            'primary_phone' => '9999999999',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_code' => 'LEAD-' . $suffix,
            'lead_status' => 'Proposal Sent',
            'company_name' =>
                'Workspace Lead ' . $suffix,
            'contact_person' => 'Finance Tester',
            'email' =>
                strtolower($suffix)
                . '.lead@example.com',
            'phone' => '8888888888',
            'converted_customer_id' =>
                $customer->getKey(),
            'estimated_value' => $total,
            'is_active' => true,
        ]);

        $quotation = Quotation::query()->create([
            'quotation_code' => 'QT-' . $suffix,
            'lead_id' => $lead->getKey(),
            'customer_id' => $customer->getKey(),
            'quotation_date' => '2026-07-15',
            'valid_until' => '2026-07-30',
            'status' => 'Approved',
            'subtotal' => $total,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_applicable' => false,
            'tax_percentage' => 18,
            'tax' => 0,
            'grand_total' => $total,
            'customer_notes' =>
                'Approved workspace quotation.',
            'is_active' => true,
        ]);

        QuotationItem::query()->create([
            'quotation_id' => $quotation->getKey(),
            'description' =>
                'Phase 9C1 finance workspace service',
            'quantity' => 1,
            'unit_price' => $total,
            'discount' => 0,
            'line_total' => $total,
            'sort_order' => 1,
        ]);

        return [
            $lead->refresh(),
            $quotation->refresh(),
        ];
    }

    private function mockAdjustmentDocumentGeneration(): void
    {
        $this->mock(
            InvoiceDocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('generate')
                    ->zeroOrMoreTimes()
                    ->andReturnUsing(
                        function (Invoice $invoice): string {
                            $path =
                                'invoices/'
                                . $invoice->invoice_no
                                . '.pdf';

                            Storage::disk('local')->put(
                                $path,
                                'invoice '
                                . $invoice->status,
                            );

                            return $path;
                        },
                    );
            },
        );

        $this->mock(
            CreditNoteDocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('generate')
                    ->zeroOrMoreTimes()
                    ->andReturnUsing(
                        function (
                            CreditNote $creditNote,
                        ): string {
                            $path =
                                'credit-notes/'
                                . $creditNote->credit_note_no
                                . '.pdf';

                            Storage::disk('local')->put(
                                $path,
                                'credit note '
                                . $creditNote->status,
                            );

                            return $path;
                        },
                    );
            },
        );

        $this->mock(
            RefundDocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('generate')
                    ->zeroOrMoreTimes()
                    ->andReturnUsing(
                        function (Refund $refund): string {
                            $path =
                                'refunds/'
                                . $refund->refund_no
                                . '.pdf';

                            Storage::disk('local')->put(
                                $path,
                                'refund '
                                . $refund->status,
                            );

                            return $path;
                        },
                    );
            },
        );
    }

    private function mockInvoiceDocumentGeneration(): void
    {
        $this->mock(
            InvoiceDocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('generate')
                    ->once()
                    ->andReturnUsing(
                        function (Invoice $invoice): string {
                            $path =
                                'invoices/'
                                . $invoice->invoice_no
                                . '.pdf';

                            Storage::disk('local')->put(
                                $path,
                                'invoice pdf',
                            );

                            return $path;
                        },
                    );
            },
        );
    }

    private function mockReceiptGeneration(): void
    {
        $this->mock(
            DocumentService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('generateReceipt')
                    ->once()
                    ->andReturnUsing(
                        function (Payment $payment): string {
                            $path =
                                'receipts/'
                                . $payment->receipt_number
                                . '.pdf';

                            Storage::disk('local')->put(
                                $path,
                                'receipt pdf',
                            );

                            $payment->forceFill([
                                'receipt_pdf' => $path,
                            ])->saveQuietly();

                            return $path;
                        },
                    );
            },
        );
    }
}