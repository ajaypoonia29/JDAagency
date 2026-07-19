<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Mail\InvoiceMail;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Communication\EmailService;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Finance\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class InvoiceDeliveryOverdueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->createCompanyProfile();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_invoice_email_delivery_is_tracked_and_resendable(): void
    {
        Mail::fake();

        $invoice = $this->issuedInvoice(
            $this->quotation('MAIL', 750),
        );

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertTrue(EmailService::sendInvoice($invoice));
        $invoice->refresh();

        $this->assertTrue($invoice->email_sent);
        $this->assertSame(1, $invoice->email_send_count);
        $this->assertSame('mail@example.com', $invoice->last_sent_to);
        $this->assertNotNull($invoice->email_sent_at);
        $this->assertSame($user->id, $invoice->email_sent_by);
        $this->assertNull($invoice->last_delivery_error);
        Mail::assertSent(InvoiceMail::class, 1);

        $this->assertTrue(EmailService::sendInvoice($invoice));
        $this->assertSame(2, $invoice->refresh()->email_send_count);
        Mail::assertSent(InvoiceMail::class, 2);
    }

    public function test_failed_invoice_delivery_records_the_reason(): void
    {
        Mail::fake();

        $quotation = $this->quotation('NO-MAIL', 300);
        $quotation->customer->update(['primary_email' => '']);
        $invoice = $this->issuedInvoice($quotation);

        $this->assertFalse(EmailService::sendInvoice($invoice));
        $invoice->refresh();

        $this->assertFalse($invoice->email_sent);
        $this->assertNotNull($invoice->last_delivery_attempt_at);
        $this->assertStringContainsString(
            'primary email address',
            (string) $invoice->last_delivery_error,
        );
        Mail::assertNothingSent();
    }

    public function test_scheduled_refresh_marks_open_invoice_overdue(): void
    {
        Carbon::setTestNow('2026-07-14 09:00:00');

        $invoice = app(InvoiceService::class)->createFromQuotation(
            $this->quotation('OVERDUE', 900),
            [
                'invoice_date' => '2026-07-14',
                'due_date' => '2026-07-14',
            ],
        );

        $this->mockInvoiceDocument();
        $invoice = app(InvoiceService::class)->issue($invoice);
        $this->assertSame('Issued', $invoice->status);

        Carbon::setTestNow('2026-07-15 09:00:00');

        $this->artisan('finance:refresh-overdue-invoices')
            ->expectsOutput(
                'Invoice status refresh complete: 1 status change(s).',
            )
            ->assertSuccessful();

        $this->assertSame('Overdue', $invoice->refresh()->status);
    }

    private function quotation(string $suffix, float $total): Quotation
    {
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-' . $suffix,
            'customer_type' => 'Business',
            'customer_status' => 'Active',
            'display_name' => 'Customer ' . $suffix,
            'contact_person' => 'Delivery Tester',
            'primary_email' => 'mail@example.com',
            'primary_phone' => '9999999999',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        return Quotation::query()->create([
            'quotation_code' => 'QT-' . $suffix,
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
    }

    private function issuedInvoice(Quotation $quotation): Invoice
    {
        $invoice = app(InvoiceService::class)
            ->createFromQuotation($quotation);

        $this->mockInvoiceDocument();

        return app(InvoiceService::class)->issue($invoice);
    }

    private function mockInvoiceDocument(): void
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
    }

    private function createCompanyProfile(): void
    {
        CompanyProfile::query()->create([
            'company_name' => 'AgencyOS Test Company',
            'primary_color' => '#2563eb',
            'secondary_color' => '#1e293b',
            'invoice_prefix' => 'INV',
            'receipt_prefix' => 'REC',
            'starting_invoice_number' => 1001,
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'd-m-Y',
            'mail_mailer' => 'array',
            'mail_from_name' => 'AgencyOS Test Company',
            'mail_from_email' => 'billing@example.com',
            'is_active' => true,
        ]);
    }
}
