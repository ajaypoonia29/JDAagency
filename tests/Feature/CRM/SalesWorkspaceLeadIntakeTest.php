<?php

declare(strict_types=1);

namespace Tests\Feature\CRM;

use App\Filament\Pages\SalesWorkspace;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
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
            ->set(
                'leadEmail',
                'phase12-intake@example.com',
            )
            ->set('leadPhone', '9000001212')
            ->set('leadWhatsapp', '9000001212')
            ->set('leadSource', 'Sales Workspace')
            ->set('leadPriority', 'High')
            ->set('leadIndustry', 'Marketing')
            ->set('leadBusinessType', 'Agency')
            ->set('leadEstimatedValue', '53100')
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

        $this->assertEqualsWithDelta(
            53100,
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
            ->set('leadEstimatedValue', '25000')
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
            ->set('leadEstimatedValue', '1000')
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