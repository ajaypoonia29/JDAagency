<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Documents\PaymentDocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinanceAuthorizationDocumentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guest_cannot_download_receipt(): void
    {
        $payment = $this->createPayment();

        $this->get(route(
            'finance.payments.receipt.download',
            $payment,
        ))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_download_receipt(): void
    {
        $payment = $this->createPayment();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route(
                'finance.payments.receipt.download',
                $payment,
            ))
            ->assertForbidden();
    }

    public function test_authorized_download_moves_legacy_receipt_to_private_storage(): void
    {
        $payment = $this->createPayment();
        $user = $this->userWithPermissions([
            'receipts.download',
        ]);

        Storage::disk('public')->put(
            $payment->receipt_pdf,
            'legacy receipt',
        );

        $response = $this->actingAs($user)
            ->get(route(
                'finance.payments.receipt.download',
                $payment,
            ));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/pdf',
        );
        $response->assertHeader(
            'x-content-type-options',
            'nosniff',
        );

        Storage::disk('local')
            ->assertExists($payment->receipt_pdf);
        Storage::disk('public')
            ->assertMissing($payment->receipt_pdf);
    }

    public function test_authorized_user_can_download_private_statement(): void
    {
        $payment = $this->createPayment();
        $user = $this->userWithPermissions([
            'receipts.download',
        ]);

        Storage::disk('local')->put(
            $payment->statement_pdf,
            'private statement',
        );

        $this->actingAs($user)
            ->get(route(
                'finance.payments.statement.download',
                $payment,
            ))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_payment_and_quotation_policies_use_existing_permissions(): void
    {
        $payment = $this->createPayment();
        $quotation = $payment->quotation;
        $user = $this->userWithPermissions([
            'payments.view',
            'payments.verify',
            'receipts.create',
            'quotations.view',
            'quotations.approve',
            'quotations.send',
            'payments.create',
        ]);

        $this->assertTrue(
            $user->can('viewAny', Payment::class)
        );
        $this->assertTrue(
            $user->can('update', $payment)
        );
        $this->assertTrue(
            $user->can('sendReceipt', $payment)
        );
        $this->assertFalse(
            $user->can('downloadReceipt', $payment)
        );

        $this->assertTrue(
            $user->can('viewAny', Quotation::class)
        );
        $this->assertTrue(
            $user->can('approve', $quotation)
        );
        $this->assertTrue(
            $user->can('send', $quotation)
        );
        $this->assertTrue(
            $user->can('receivePayment', $quotation)
        );
        $this->assertFalse(
            $user->can('update', $quotation)
        );
    }

    public function test_privatization_command_supports_dry_run_and_migration(): void
    {
        $payment = $this->createPayment();

        Storage::disk('public')->put(
            $payment->receipt_pdf,
            'receipt',
        );
        Storage::disk('public')->put(
            $payment->statement_pdf,
            'statement',
        );

        $this->artisan(
            'finance:privatize-documents',
            ['--dry-run' => true],
        )
            ->expectsOutput(
                'Dry run complete: 2 legacy public document(s) found.'
            )
            ->assertSuccessful();

        Storage::disk('public')
            ->assertExists($payment->receipt_pdf);

        $this->artisan('finance:privatize-documents')
            ->expectsOutput(
                'Privatization complete: 2 document(s) moved.'
            )
            ->assertSuccessful();

        Storage::disk('local')
            ->assertExists($payment->receipt_pdf);
        Storage::disk('local')
            ->assertExists($payment->statement_pdf);
        Storage::disk('public')
            ->assertMissing($payment->receipt_pdf);
        Storage::disk('public')
            ->assertMissing($payment->statement_pdf);
    }

    public function test_document_storage_rejects_traversal_paths(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PaymentDocumentStorage::class)
            ->absolutePath('../secrets.txt');
    }

    /**
     * @param array<int, string> $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function createPayment(): Payment
    {
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-SEC-0001',
            'customer_type' => 'Business',
            'customer_status' => 'Active',
            'display_name' => 'Security Test Customer',
            'contact_person' => 'Security Tester',
            'primary_email' => 'security@example.com',
            'primary_phone' => '9999999999',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $quotation = Quotation::query()->create([
            'quotation_code' => 'QT-SEC-0001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'status' => 'Approved',
            'subtotal' => 1000,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_applicable' => false,
            'tax_percentage' => 18,
            'tax' => 0,
            'grand_total' => 1000,
            'is_active' => true,
        ]);

        return Payment::query()->create([
            'payment_no' => 'PAY-SEC-0001',
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'amount' => 500,
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
            'receipt_generated' => true,
            'receipt_number' => 'RCT-SEC-0001',
            'receipt_uuid' => '11111111-1111-4111-8111-111111111111',
            'verification_hash' => hash('sha256', 'security-test'),
            'receipt_pdf' => 'receipts/RCT-SEC-0001.pdf',
            'statement_pdf' =>
                'payment-statements/STATEMENT-RCT-SEC-0001.pdf',
            'is_active' => true,
        ]);
    }
}
