<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use App\Services\Documents\DocumentService;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Finance\InvoiceService;
use App\Services\Finance\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvoiceCollectionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_invoice_snapshots_quotation_and_prevents_duplicates(): void
    {
        [$customer, $lead, $quotation] = $this->salesContext('SNAP', 1200);

        QuotationItem::query()->create([
            'quotation_id' => $quotation->id,
            'description' => 'Website development',
            'quantity' => 1,
            'unit_price' => 1200,
            'discount' => 0,
            'line_total' => 1200,
            'sort_order' => 1,
        ]);

        $invoice = app(InvoiceService::class)->createFromQuotation($quotation);

        $this->assertSame($quotation->id, $invoice->quotation_id);
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame($lead->id, $invoice->lead_id);
        $this->assertSame('Draft', $invoice->status);
        $this->assertSame('INV-00001', $invoice->invoice_no);
        $this->assertCount(1, $invoice->items);
        $this->assertEqualsWithDelta(1200, (float) $invoice->grand_total, 0.001);

        try {
            app(InvoiceService::class)->createFromQuotation($quotation);
            $this->fail('Expected duplicate invoice validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quotation_id', $exception->errors());
        }

        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_issuing_paid_invoice_completes_quotation_and_lead(): void
    {
        [, $lead, $quotation] = $this->salesContext('PAID', 500);

        Payment::query()->create([
            'payment_no' => 'PAY-PAID-0001',
            'quotation_id' => $quotation->id,
            'customer_id' => $quotation->customer_id,
            'amount' => 500,
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
            'receipt_generated' => false,
            'is_active' => true,
        ]);

        $invoice = app(InvoiceService::class)->createFromQuotation($quotation);
        $this->assertSame('Draft', $invoice->status);
        $this->assertEqualsWithDelta(500, (float) $invoice->total_paid, 0.001);
        $this->assertDatabaseCount('payment_allocations', 1);

        $this->mockInvoiceDocumentGeneration();
        $invoice = app(InvoiceService::class)->issue($invoice);

        $this->assertSame('Paid', $invoice->status);
        $this->assertNotNull($invoice->invoice_uuid);
        $this->assertNotNull($invoice->verification_hash);
        Storage::disk('local')->assertExists($invoice->invoice_pdf);
        $this->assertSame('Completed', $quotation->refresh()->status);
        $this->assertSame('Won', $lead->refresh()->lead_status);
    }

    public function test_payment_lifecycle_allocates_moves_deletes_and_restores(): void
    {
        [, , $firstQuotation] = $this->salesContext('MOVE-A', 1000);
        [, , $secondQuotation] = $this->salesContext('MOVE-B', 1000);

        $firstInvoice = app(InvoiceService::class)->createFromQuotation($firstQuotation);
        $secondInvoice = app(InvoiceService::class)->createFromQuotation($secondQuotation);

        $this->mockInvoiceDocumentGeneration(2);
        $firstInvoice = app(InvoiceService::class)->issue($firstInvoice);
        $secondInvoice = app(InvoiceService::class)->issue($secondInvoice);
        $this->mockReceiptGeneration(2);

        $payment = app(PaymentService::class)->create([
            'quotation_id' => $firstQuotation->id,
            'amount' => 300,
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertSame('Partially Paid', $firstInvoice->refresh()->status);

        $payment = app(PaymentService::class)->update($payment, [
            'quotation_id' => $secondQuotation->id,
            'amount' => 300,
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseMissing('payment_allocations', [
            'payment_id' => $payment->id,
            'invoice_id' => $firstInvoice->id,
        ]);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'invoice_id' => $secondInvoice->id,
            'amount' => 300,
        ]);
        $this->assertSame('Issued', $firstInvoice->refresh()->status);
        $this->assertSame('Partially Paid', $secondInvoice->refresh()->status);

        app(PaymentService::class)->delete($payment);
        $this->assertSame('Issued', $secondInvoice->refresh()->status);

        app(PaymentService::class)->restore($payment);
        $this->assertSame('Partially Paid', $secondInvoice->refresh()->status);
    }

    public function test_void_invoice_blocks_new_payment(): void
    {
        [, , $quotation] = $this->salesContext('VOID', 600);
        $invoice = app(InvoiceService::class)->createFromQuotation($quotation);
        $invoice = app(InvoiceService::class)->void(
            $invoice,
            'Customer cancelled before issue.',
        );

        $this->assertSame('Void', $invoice->status);

        $this->mock(
            DocumentService::class,
            fn (MockInterface $mock) => $mock->shouldNotReceive('generateReceipt'),
        );

        try {
            app(PaymentService::class)->create([
                'quotation_id' => $quotation->id,
                'amount' => 100,
                'payment_method' => 'Cash',
                'payment_date' => now()->toDateString(),
            ]);
            $this->fail('Expected void invoice payment validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quotation_id', $exception->errors());
        }

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_invoice_policy_and_private_download_use_existing_permissions(): void
    {
        [, , $quotation] = $this->salesContext('SEC', 700);
        $invoice = app(InvoiceService::class)->createFromQuotation($quotation);
        $this->mockInvoiceDocumentGeneration();
        $invoice = app(InvoiceService::class)->issue($invoice);

        $user = User::factory()->create();
        Permission::findOrCreate('quotations.view', 'web');
        Permission::findOrCreate('quotations.approve', 'web');
        $user->givePermissionTo(['quotations.view', 'quotations.approve']);

        $this->assertTrue($user->can('viewAny', Invoice::class));
        $this->assertTrue($user->can('issue', $invoice));
        $this->assertTrue($user->can('download', $invoice));
        $this->assertFalse($user->can('void', $invoice));

        $this->get(route('finance.invoices.download', $invoice))
            ->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('finance.invoices.download', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
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
            'contact_person' => 'Invoice Tester',
            'primary_email' => strtolower($suffix) . '@example.com',
            'primary_phone' => '9999999999',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_code' => 'LEAD-' . $suffix,
            'lead_status' => 'Proposal Sent',
            'company_name' => 'Lead ' . $suffix,
            'contact_person' => 'Invoice Tester',
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

    private function mockInvoiceDocumentGeneration(int $times = 1): void
    {
        $this->mock(
            InvoiceDocumentService::class,
            function (MockInterface $mock) use ($times): void {
                $mock->shouldReceive('generate')
                    ->times($times)
                    ->andReturnUsing(function (Invoice $invoice): string {
                        $path = 'invoices/' . $invoice->invoice_no . '.pdf';
                        Storage::disk('local')->put($path, 'invoice pdf');
                        return $path;
                    });
            },
        );
    }

    private function mockReceiptGeneration(int $times): void
    {
        $this->mock(
            DocumentService::class,
            function (MockInterface $mock) use ($times): void {
                $mock->shouldReceive('generateReceipt')
                    ->times($times)
                    ->andReturnUsing(function (Payment $payment): string {
                        $path = 'receipts/' . $payment->receipt_number . '.pdf';
                        Storage::disk('local')->put($path, 'receipt pdf');
                        $payment->update(['receipt_pdf' => $path]);
                        return $path;
                    });
            },
        );
    }
}
