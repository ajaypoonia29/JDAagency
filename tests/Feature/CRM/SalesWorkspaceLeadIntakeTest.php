<?php

declare(strict_types=1);

namespace Tests\Feature\CRM;

use App\Filament\Pages\SalesWorkspace;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\User;
use App\Support\CRM\LeadOptionCatalog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SalesWorkspaceLeadIntakeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        Filament::setCurrentPanel(
            Filament::getPanel('admin'),
        );
    }

    public function test_authorized_user_creates_and_selects_linked_lead(): void
    {
        $user = $this->authorizedUser();

        $component = Livewire::actingAs($user)
            ->test(SalesWorkspace::class)
            ->assertSee('+ Add Lead')
            ->call('openLeadForm')
            ->assertSet('showLeadForm', true)
            ->assertSet('leadPriority', 'Medium')
            ->set('leadCompanyName', 'Phase 12 Studio')
            ->set('leadContactPerson', 'Ajay Poonia')
            ->set('leadDesignation', 'Owner')
            ->set(
                'leadEmail',
                'phase12-intake@example.com',
            )
            ->set('leadPhone', '9000001212')
            ->set('leadWhatsapp', '9000001212')
            ->set('leadSource', 'Google Search')
            ->set('leadPriority', 'High')
            ->set(
                'leadIndustry',
                'Advertising & Marketing',
            )
            ->set('leadBusinessType', 'Agency')
            ->set('leadCompanySize', '1-10')
            ->set('leadEstimatedValue', '50000')
            ->set(
                'leadRequirementsSummary',
                'Inline lead intake regression coverage.',
            )
            ->call('createLead')
            ->assertHasNoErrors()
            ->assertSet('showLeadForm', false)
            ->assertSet('search', '')
            ->assertSet('status', 'all');

        $lead = Lead::query()->sole();
        $customer = Customer::query()->sole();

        $this->assertSame(
            $customer->getKey(),
            $lead->converted_customer_id,
        );

        $this->assertSame('New', $lead->lead_status);
        $this->assertSame('High', $lead->priority);
        $this->assertSame(
            'Phase 12 Studio',
            $lead->company_name,
        );
        $this->assertSame(
            'phase12-intake@example.com',
            $lead->email,
        );

        $this->assertSame(
            'Owner',
            $lead->designation,
        );

        $this->assertSame(
            'Google Search',
            $lead->lead_source,
        );

        $this->assertSame(
            'Advertising & Marketing',
            $lead->industry,
        );

        $this->assertSame(
            'Agency',
            $lead->business_type,
        );

        $this->assertSame(
            '1-10',
            $lead->company_size,
        );

        $this->assertEqualsWithDelta(
            50000,
            (float) $lead->estimated_value,
            0.001,
        );

        $component
            ->assertSet(
                'selectedLeadId',
                $lead->getKey(),
            )
            ->assertSee($lead->lead_code)
            ->assertSee('Contact the lead');
    }

    public function test_standard_catalog_drives_both_lead_forms(): void
    {
        $this->assertSame(
            'Google Search (Organic)',
            LeadOptionCatalog::leadSources()[
                'Google Search'
            ],
        );

        $this->assertSame(
            'Chief Executive Officer (CEO)',
            LeadOptionCatalog::designations()[
                'Chief Executive Officer'
            ],
        );

        $this->assertSame(
            "1\u{2013}10 employees",
            LeadOptionCatalog::companySizes()[
                '1-10'
            ],
        );

        $this->assertSame(
            "Up to \u{20B9}30,000",
            LeadOptionCatalog::estimatedValues()[
                '30000'
            ],
        );

        $this->assertArrayHasKey(
            '53100',
            LeadOptionCatalog::estimatedValues(
                53100,
            ),
        );

        $leadForm = file_get_contents(
            app_path(
                'Filament/Resources/Leads/Schemas/'
                . 'LeadForm.php',
            ),
        );

        $workspaceView = file_get_contents(
            resource_path(
                'views/filament/pages/'
                . 'sales-workspace.blade.php',
            ),
        );

        $this->assertIsString($leadForm);
        $this->assertIsString($workspaceView);

        foreach ([
            'LeadOptionCatalog::designations',
            'LeadOptionCatalog::industries',
            'LeadOptionCatalog::businessTypes',
            'LeadOptionCatalog::companySizes',
            'LeadOptionCatalog::leadSources',
            'LeadOptionCatalog::estimatedValues',
        ] as $catalogCall) {
            $this->assertStringContainsString(
                $catalogCall,
                $leadForm,
            );
        }

        foreach ([
            'leadDesignationOptions',
            'leadIndustryOptions',
            'leadBusinessTypeOptions',
            'leadCompanySizeOptions',
            'leadSourceOptions',
            'leadEstimatedValueOptions',
        ] as $workspaceOption) {
            $this->assertStringContainsString(
                $workspaceOption,
                $workspaceView,
            );
        }
    }

    public function test_non_privileged_user_is_forced_to_active_employee(): void
    {
        $user = $this->salesUser();

        $employee = $this->createEmployee(
            $user,
            true,
        );

        Livewire::actingAs($user)
            ->test(SalesWorkspace::class)
            ->call('openLeadForm')
            ->assertSet(
                'leadAssignedEmployeeId',
                $employee->getKey(),
            )
            ->assertSee($employee->full_name)
            ->assertSee(
                'Assigned to your employee profile.',
            )
            ->set(
                'leadCompanyName',
                'Assigned Sales Studio',
            )
            ->set('leadContactPerson', 'Sales Agent')
            ->set(
                'leadEmail',
                'assigned-sales@example.com',
            )
            ->set('leadPhone', '9000001717')
            ->set('leadSource', 'Self Generated')
            ->set(
                'leadIndustry',
                'Consulting & Professional Services',
            )
            ->set(
                'leadBusinessType',
                'Private Limited Company',
            )
            ->set('leadCompanySize', '11-50')
            ->set('leadEstimatedValue', '70000')
            ->call('createLead')
            ->assertHasNoErrors();

        $lead = Lead::query()->sole();

        $this->assertSame(
            $employee->getKey(),
            $lead->assigned_employee_id,
        );
    }

    public function test_non_privileged_user_cannot_tamper_assignment(): void
    {
        $user = $this->salesUser();

        $this->createEmployee(
            $user,
            true,
        );

        $otherEmployee = $this->createEmployee(
            User::factory()->create(),
            true,
        );

        Livewire::actingAs($user)
            ->test(SalesWorkspace::class)
            ->call('openLeadForm')
            ->set(
                'leadAssignedEmployeeId',
                $otherEmployee->getKey(),
            )
            ->set('leadCompanyName', 'Tampered Lead')
            ->set('leadContactPerson', 'Sales Agent')
            ->set(
                'leadEmail',
                'tampered-assignment@example.com',
            )
            ->set('leadPhone', '9000001818')
            ->set('leadEstimatedValue', '50000')
            ->call('createLead')
            ->assertHasErrors([
                'leadAssignedEmployeeId',
            ]);

        $this->assertSame(
            0,
            Lead::query()->count(),
        );

        $this->assertSame(
            0,
            Customer::query()->count(),
        );
    }

    public function test_non_privileged_user_without_active_employee_cannot_create(): void
    {
        $user = $this->salesUser();

        $this->assertFalse(
            $user->can('create', Lead::class),
        );

        Livewire::actingAs($user)
            ->test(SalesWorkspace::class)
            ->call('openLeadForm')
            ->assertStatus(403);

        $this->assertSame(
            0,
            Lead::query()->count(),
        );

        $this->assertSame(
            0,
            Customer::query()->count(),
        );
    }

    public function test_privileged_user_rejects_inactive_employee_assignment(): void
    {
        $user = $this->authorizedUser();

        $inactiveEmployee = $this->createEmployee(
            User::factory()->create(),
            false,
        );

        Livewire::actingAs($user)
            ->test(SalesWorkspace::class)
            ->call('openLeadForm')
            ->set(
                'leadAssignedEmployeeId',
                $inactiveEmployee->getKey(),
            )
            ->set(
                'leadCompanyName',
                'Inactive Assignment Studio',
            )
            ->set('leadContactPerson', 'Ajay Poonia')
            ->set(
                'leadEmail',
                'inactive-assignment@example.com',
            )
            ->set('leadPhone', '9000002020')
            ->set('leadEstimatedValue', '50000')
            ->call('createLead')
            ->assertHasErrors([
                'leadAssignedEmployeeId',
            ]);

        $this->assertSame(
            0,
            Lead::query()->count(),
        );

        $this->assertSame(
            0,
            Customer::query()->count(),
        );
    }

    public function test_existing_customer_is_reused_without_overwrite(): void
    {
        $user = $this->authorizedUser();

        $customer = $this->createCustomer([
            'company_name' =>
                'Authoritative Customer Name',
            'display_name' =>
                'Authoritative Customer Name',
            'primary_email' =>
                'existing-phase12@example.com',
            'primary_phone' => '9000001313',
        ]);

        Livewire::actingAs($user)
            ->test(SalesWorkspace::class)
            ->call('openLeadForm')
            ->set(
                'leadCompanyName',
                'Different Lead Name',
            )
            ->set('leadContactPerson', 'Ajay Poonia')
            ->set(
                'leadEmail',
                'existing-phase12@example.com',
            )
            ->set('leadPhone', '9000001313')
            ->set('leadEstimatedValue', '30000')
            ->call('createLead')
            ->assertHasNoErrors();

        $lead = Lead::query()->sole();

        $customer->refresh();

        $this->assertSame(
            $customer->getKey(),
            $lead->converted_customer_id,
        );

        $this->assertSame(
            1,
            Customer::query()->count(),
        );

        $this->assertSame(
            'Authoritative Customer Name',
            $customer->company_name,
        );
    }

    public function test_customer_identity_conflict_maps_to_inline_error(): void
    {
        $user = $this->authorizedUser();

        $this->createCustomer([
            'customer_code' => 'CUS-PH12-EMAIL',
            'primary_email' =>
                'conflict-phase12@example.com',
            'primary_phone' => '9000001414',
        ]);

        $this->createCustomer([
            'customer_code' => 'CUS-PH12-PHONE',
            'primary_email' =>
                'other-phase12@example.com',
            'primary_phone' => '9000001515',
        ]);

        Livewire::actingAs($user)
            ->test(SalesWorkspace::class)
            ->call('openLeadForm')
            ->set('leadCompanyName', 'Conflict Lead')
            ->set('leadContactPerson', 'Ajay Poonia')
            ->set(
                'leadEmail',
                'conflict-phase12@example.com',
            )
            ->set('leadPhone', '9000001515')
            ->set('leadEstimatedValue', '30000')
            ->call('createLead')
            ->assertHasErrors(['leadEmail'])
            ->assertSet('showLeadForm', true)
            ->assertSet(
                'leadEmail',
                'conflict-phase12@example.com',
            );

        $this->assertSame(
            0,
            Lead::query()->count(),
        );

        $this->assertSame(
            2,
            Customer::query()->count(),
        );
    }

    public function test_invalid_values_are_preserved(): void
    {
        $user = $this->authorizedUser();

        Livewire::actingAs($user)
            ->test(SalesWorkspace::class)
            ->call('openLeadForm')
            ->set(
                'leadCompanyName',
                'Preserved Company',
            )
            ->set('leadContactPerson', '')
            ->set('leadEmail', 'not-an-email')
            ->set('leadPhone', '')
            ->set('leadEstimatedValue', '-1')
            ->call('createLead')
            ->assertHasErrors([
                'leadContactPerson',
                'leadEmail',
                'leadPhone',
                'leadEstimatedValue',
            ])
            ->assertSet('showLeadForm', true)
            ->assertSet(
                'leadCompanyName',
                'Preserved Company',
            )
            ->assertSet(
                'leadEmail',
                'not-an-email',
            );

        $this->assertSame(
            0,
            Lead::query()->count(),
        );

        $this->assertSame(
            0,
            Customer::query()->count(),
        );
    }

    public function test_create_permission_controls_button_and_action(): void
    {
        $user = $this->viewerOnlyUser();

        Livewire::actingAs($user)
            ->test(SalesWorkspace::class)
            ->assertDontSee('+ Add Lead')
            ->call('openLeadForm')
            ->assertForbidden();

        $this->assertSame(
            0,
            Lead::query()->count(),
        );
    }

    public function test_view_has_duplicate_submission_guards(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/filament/pages/'
                . 'sales-workspace.blade.php',
            ),
        );

        $this->assertIsString($view);

        $this->assertSame(
            1,
            substr_count(
                $view,
                'wire:submit="createLead"',
            ),
        );

        $this->assertSame(
            1,
            substr_count(
                $view,
                '+ Add Lead',
            ),
        );

        $this->assertSame(
            1,
            substr_count(
                $view,
                'id="asw-lead-intake"',
            ),
        );

        $this->assertGreaterThanOrEqual(
            4,
            substr_count(
                $view,
                'wire:target="createLead"',
            ),
        );

        $this->assertGreaterThanOrEqual(
            2,
            substr_count(
                $view,
                'wire:loading.attr="disabled"',
            ),
        );
    }

    private function authorizedUser(): User
    {
        $permissions = [
            'leads.view',
            'leads.create',
            'leads.edit',
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

    private function salesUser(): User
    {
        $permissions = [
            'leads.view',
            'leads.create',
            'leads.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate(
                $permission,
                'web',
            );
        }

        $user = User::factory()->create();

        $user->givePermissionTo($permissions);

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        return $user->refresh();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createEmployee(
        User $user,
        bool $active,
        array $overrides = [],
    ): Employee {
        $sequence =
            Employee::withTrashed()->count() + 1;

        return Employee::query()->create(
            array_merge([
                'user_id' => $user->getKey(),
                'employee_code' =>
                    Employee::nextEmployeeCode(),
                'full_name' =>
                    "Sales Employee {$sequence}",
                'email' =>
                    "sales-employee-{$sequence}@example.com",
                'phone' => sprintf(
                    '8%09d',
                    $sequence,
                ),
                'is_active' => $active,
            ], $overrides),
        );
    }

    private function viewerOnlyUser(): User
    {
        Permission::findOrCreate(
            'leads.view',
            'web',
        );

        $user = User::factory()->create();

        $user->givePermissionTo(
            'leads.view',
        );

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        return $user->refresh();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createCustomer(
        array $overrides = [],
    ): Customer {
        return Customer::query()->create(
            array_merge([
                'customer_code' =>
                    'CUS-PH12-0001',
                'customer_type' => 'Business',
                'customer_status' => 'Lead',
                'company_name' =>
                    'Phase 12 Customer',
                'display_name' =>
                    'Phase 12 Customer',
                'contact_person' => 'Ajay Poonia',
                'primary_email' =>
                    'phase12-customer@example.com',
                'primary_phone' => '9000001616',
                'currency' => 'INR',
                'is_active' => true,
            ], $overrides),
        );
    }
}