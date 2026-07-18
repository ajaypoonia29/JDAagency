<?php

declare(strict_types=1);

namespace Tests\Feature\CRM;

use App\Filament\Pages\SalesWorkspace;
use App\Livewire\SalesWorkspaceFinance;
use App\Livewire\SalesWorkspaceQuotation;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Quotation;
use App\Models\User;
use App\Services\CRM\LeadWorkflowService;
use App\Services\CRM\MeetingWorkflowService;
use App\Services\CRM\SalesJourneyService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SalesWorkspaceUatRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }

    public function test_meeting_outcome_action_and_completed_display(): void
    {
        $user = $this->authorizedUser();

        [$lead, $meeting] =
            $this->meetingContext();

        Filament::setCurrentPanel(
            Filament::getPanel('admin'),
        );

        Livewire::actingAs($user)
            ->test(SalesWorkspace::class)
            ->set(
                'selectedLeadId',
                (int) $lead->getKey(),
            )
            ->call(
                'completeMeeting',
                $meeting->getKey(),
                'quotation_required',
            )
            ->assertHasNoErrors()
            ->assertDispatched(
                'sales-workspace-updated',
            )
            ->assertSee('Meeting Completed')
            ->assertSee('Quotation Required');

        $meeting = $meeting->refresh();
        $lead = $lead->refresh();

        $this->assertSame(
            'Completed',
            $meeting->status,
        );

        $this->assertSame(
            'Quotation Required',
            $meeting->outcome,
        );

        $this->assertSame(
            'Meeting Scheduled',
            $lead->lead_status,
        );

        $this->assertSame(
            'Meeting Completed',
            app(SalesJourneyService::class)
                ->displayLeadStatus($lead),
        );

        $workspaceView = file_get_contents(
            resource_path(
                'views/filament/pages/'
                . 'sales-workspace.blade.php',
            ),
        );

        $this->assertIsString($workspaceView);

        $this->assertStringContainsString(
            "'quotation_required' => 'Quotation Required'",
            $workspaceView,
        );

        $this->assertStringContainsString(
            'wire:click="completeMeeting(',
            $workspaceView,
        );

        $this->assertStringContainsString(
            '$outcomeKey',
            $workspaceView,
        );

        $this->assertStringNotContainsString(
            '@js($outcome)',
            $workspaceView,
        );
    }
    public function test_finance_workspace_refreshes_after_sibling_event(): void
    {
        $user = $this->authorizedUser();

        [$lead, $quotation] =
            $this->financeContext(
                'FINANCE-REFRESH',
            );

        $component = Livewire::actingAs($user)
            ->test(
                SalesWorkspaceFinance::class,
                [
                    'leadId' => $lead->getKey(),
                ],
            )
            ->assertStatus(200)
            ->assertSee('Approved')
            ->assertSee('Quotation balance');

        $quotation->forceFill([
            'status' => 'Completed',
            'payment_status' => 'Paid',
            'total_paid' => 1000,
            'balance_due' => 0,
        ])->saveQuietly();

        $component
            ->dispatch('sales-workspace-updated')
            ->assertSee('Completed')
            ->assertDontSee('Approved');

        $quotation->refresh();

        $this->assertSame(
            'Completed',
            $quotation->status,
        );

        $this->assertSame(
            'Paid',
            $quotation->payment_status,
        );

        $this->assertEqualsWithDelta(
            0,
            (float) $quotation->balance_due,
            0.001,
        );
    }

    public function test_quotation_workspace_refreshes_after_sibling_event(): void
    {
        $user = $this->authorizedUser();

        [$lead, $quotation] =
            $this->financeContext(
                'QUOTATION-REFRESH',
            );

        $component = Livewire::actingAs($user)
            ->test(
                SalesWorkspaceQuotation::class,
                [
                    'leadId' => $lead->getKey(),
                ],
            )
            ->assertStatus(200)
            ->assertSee('Approved')
            ->assertSee('Unpaid');

        $quotation->forceFill([
            'status' => 'Completed',
            'payment_status' => 'Paid',
            'total_paid' => 1000,
            'balance_due' => 0,
        ])->saveQuietly();

        $component
            ->dispatch('sales-workspace-updated')
            ->assertSee('Completed')
            ->assertSee('Paid')
            ->assertDontSee('Unpaid');
    }

    public function test_adjustment_editors_include_scroll_targets(): void
    {
        $financeView = file_get_contents(
            resource_path(
                'views/livewire/'
                . 'sales-workspace-finance.blade.php',
            ),
        );

        $this->assertIsString($financeView);

        $this->assertStringContainsString(
            'id="aswf-credit-note-editor"',
            $financeView,
        );

        $this->assertStringContainsString(
            'id="aswf-refund-editor"',
            $financeView,
        );

        $this->assertSame(
            2,
            substr_count(
                $financeView,
                'scrollIntoView',
            ),
        );

        $this->assertStringContainsString(
            'scroll-margin-top: 5rem;',
            $financeView,
        );
    }

    /**
     * @return array{0: Lead, 1: Meeting}
     */
    private function meetingContext(): array
    {
        $lead = app(LeadWorkflowService::class)
            ->create([
                'lead_status' => 'Contacted',
                'priority' => 'Medium',
                'company_name' =>
                    'Phase 11 Meeting Regression',
                'contact_person' =>
                    'Phase 11 Tester',
                'email' =>
                    'phase11-meeting@example.com',
                'phone' => '7777777777',
                'whatsapp' => '7777777777',
                'industry' => 'Services',
                'business_type' => 'Agency',
                'estimated_value' => 25000,
                'requirements_summary' =>
                    'Meeting action regression coverage.',
                'is_active' => true,
            ]);

        $meeting = app(MeetingWorkflowService::class)
            ->create([
                'lead_id' => $lead->getKey(),
                'customer_id' =>
                    $lead->converted_customer_id,
                'meeting_title' =>
                    'Phase 11 Regression Meeting',
                'meeting_type' => 'Online',
                'meeting_date' =>
                    now()->addDay()->toDateString(),
                'meeting_time' => '11:00:00',
                'expected_duration' => 30,
                'status' => 'Scheduled',
                'outcome' => 'Pending',
                'meeting_notes' =>
                    'Regression test meeting.',
                'is_active' => true,
            ]);

        return [
            $lead->refresh(),
            $meeting->refresh(),
        ];
    }

    /**
     * @return array{0: Lead, 1: Quotation}
     */
    private function financeContext(
        string $suffix,
    ): array {
        $customer = Customer::query()->create([
            'customer_code' => 'CUS-' . $suffix,
            'customer_type' => 'Business',
            'customer_status' => 'Active',
            'display_name' =>
                'Workspace Customer ' . $suffix,
            'contact_person' => 'Finance Tester',
            'primary_email' =>
                strtolower($suffix)
                . '@example.com',
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
            'estimated_value' => 1000,
            'is_active' => true,
        ]);

        $quotation = Quotation::query()->create([
            'quotation_code' => 'QT-' . $suffix,
            'lead_id' => $lead->getKey(),
            'customer_id' => $customer->getKey(),
            'quotation_date' => '2026-07-16',
            'valid_until' => '2026-07-31',
            'status' => 'Approved',
            'subtotal' => 1000,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_applicable' => false,
            'tax_percentage' => 18,
            'tax' => 0,
            'grand_total' => 1000,
            'total_paid' => 0,
            'balance_due' => 1000,
            'payment_status' => 'Unpaid',
            'customer_notes' => null,
            'internal_notes' => null,
            'is_active' => true,
        ]);

        return [
            $lead->refresh(),
            $quotation->refresh(),
        ];
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
}