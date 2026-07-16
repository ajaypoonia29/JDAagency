<?php

declare(strict_types=1);

namespace Tests\Feature\CRM;

use App\Livewire\SalesWorkspaceFinance;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use App\Services\CRM\SalesJourneyService;
use App\Services\Documents\DocumentService;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Finance\InvoiceService;
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