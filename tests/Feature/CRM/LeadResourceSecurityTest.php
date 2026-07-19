<?php

declare(strict_types=1);

namespace Tests\Feature\CRM;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\User;
use App\Services\CRM\LeadWorkflowService;
use App\Support\CRM\LeadAssignmentAccess;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class LeadResourceSecurityTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        Filament::setCurrentPanel(
            Filament::getPanel('admin'),
        );
    }

    public function test_sales_user_create_page_forces_own_active_employee(): void
    {
        $user = $this->salesUser();

        $employee = $this->createEmployee(
            $user,
            true,
        );

        Livewire::actingAs($user)
            ->test(CreateLead::class)
            ->assertOk()
            ->assertFormFieldDisabled(
                'assigned_employee_id',
            )
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $lead = Lead::query()->sole();

        $this->assertSame(
            $employee->getKey(),
            $lead->assigned_employee_id,
        );

        $this->assertSame(
            1,
            Customer::query()->count(),
        );
    }

    public function test_sales_user_without_active_employee_cannot_create(): void
    {
        $user = $this->salesUser();

        $this->assertFalse(
            $user->can('create', Lead::class),
        );

        $this->actingAs($user)
            ->get(
                LeadResource::getUrl('create'),
            )
            ->assertForbidden();

        $this->assertSame(
            0,
            Lead::query()->count(),
        );
    }

    public function test_manager_can_create_unassigned_lead(): void
    {
        $manager = $this->managerUser();

        Livewire::actingAs($manager)
            ->test(CreateLead::class)
            ->assertOk()
            ->assertFormFieldEnabled(
                'assigned_employee_id',
            )
            ->fillForm(
                $this->formData([
                    'assigned_employee_id' => null,
                ]),
            )
            ->call('create')
            ->assertHasNoFormErrors();

        $lead = Lead::query()->sole();

        $this->assertNull(
            $lead->assigned_employee_id,
        );
    }

    public function test_manager_cannot_assign_inactive_or_deleted_employee(): void
    {
        $manager = $this->managerUser();

        $inactiveEmployee = $this->createEmployee(
            User::factory()->create(),
            false,
        );

        $deletedEmployee = $this->createEmployee(
            User::factory()->create(),
            true,
        );

        $deletedEmployeeId =
            (int) $deletedEmployee->getKey();

        $deletedEmployee->delete();

        foreach ([
            (int) $inactiveEmployee->getKey(),
            $deletedEmployeeId,
        ] as $employeeId) {
            Livewire::actingAs($manager)
                ->test(CreateLead::class)
                ->fillForm(
                    $this->formData([
                        'assigned_employee_id' =>
                            $employeeId,
                    ]),
                )
                ->call('create')
                ->assertHasFormErrors([
                    'assigned_employee_id',
                ]);
        }

        $this->assertSame(
            0,
            Lead::query()->count(),
        );

        $this->assertSame(
            0,
            Customer::query()->count(),
        );
    }

    public function test_sales_user_list_contains_only_own_leads(): void
    {
        $user = $this->salesUser();

        $employee = $this->createEmployee(
            $user,
            true,
        );

        $otherEmployee = $this->createEmployee(
            User::factory()->create(),
            true,
        );

        $ownLead = $this->createLead(
            $employee,
        );

        $otherLead = $this->createLead(
            $otherEmployee,
        );

        $unassignedLead = $this->createLead(
            null,
        );

        Livewire::actingAs($user)
            ->test(ListLeads::class)
            ->assertOk()
            ->assertCanSeeTableRecords(
                collect([$ownLead]),
            )
            ->assertCanNotSeeTableRecords(
                collect([
                    $otherLead,
                    $unassignedLead,
                ]),
            )
            ->assertCountTableRecords(1);

        $this->assertTrue(
            $user->can('view', $ownLead),
        );

        $this->assertFalse(
            $user->can('view', $otherLead),
        );
    }

    public function test_sales_user_cannot_open_other_users_lead(): void
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

        $otherLead = $this->createLead(
            $otherEmployee,
        );

        $this->assertFalse(
            $user->can('update', $otherLead),
        );

        $this->actingAs($user)
            ->get(
                LeadResource::getUrl(
                    'edit',
                    [
                        'record' =>
                            $otherLead->getKey(),
                    ],
                ),
            )
            ->assertNotFound();
    }

    public function test_sales_user_can_edit_own_lead_without_reassignment(): void
    {
        $user = $this->salesUser();

        $employee = $this->createEmployee(
            $user,
            true,
        );

        $lead = $this->createLead(
            $employee,
        );

        Livewire::actingAs($user)
            ->test(
                EditLead::class,
                [
                    'record' =>
                        $lead->getKey(),
                ],
            )
            ->assertOk()
            ->assertFormFieldDisabled(
                'assigned_employee_id',
            )
            ->fillForm([
                'contact_person' =>
                    'Updated Sales Contact',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $lead->refresh();

        $this->assertSame(
            'Updated Sales Contact',
            $lead->contact_person,
        );

        $this->assertSame(
            $employee->getKey(),
            $lead->assigned_employee_id,
        );
    }

    public function test_manager_can_view_and_edit_any_lead(): void
    {
        $manager = $this->managerUser();

        $employee = $this->createEmployee(
            User::factory()->create(),
            true,
        );

        $lead = $this->createLead(
            $employee,
        );

        Livewire::actingAs($manager)
            ->test(ListLeads::class)
            ->assertOk()
            ->assertCanSeeTableRecords(
                collect([$lead]),
            );

        Livewire::actingAs($manager)
            ->test(
                EditLead::class,
                [
                    'record' =>
                        $lead->getKey(),
                ],
            )
            ->assertOk()
            ->assertFormFieldEnabled(
                'assigned_employee_id',
            );

        $this->assertTrue(
            $manager->can('update', $lead),
        );
    }

    public function test_assignment_guard_rejects_sales_user_tampering(): void
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

        foreach ([
            $otherEmployee->getKey(),
            null,
        ] as $submittedAssignment) {
            try {
                LeadAssignmentAccess::
                    enforceWriteAssignment(
                        [
                            'assigned_employee_id' =>
                                $submittedAssignment,
                        ],
                        $user,
                    );

                $this->fail(
                    'Tampered assignment should be rejected.',
                );
            } catch (
                ValidationException $exception
            ) {
                $this->assertArrayHasKey(
                    'assigned_employee_id',
                    $exception->errors(),
                );
            }
        }
    }

    public function test_service_forces_sales_assignment_and_rejects_tampering(): void
    {
        $user = $this->salesUser();

        $employee = $this->createEmployee(
            $user,
            true,
        );

        $otherEmployee = $this->createEmployee(
            User::factory()->create(),
            true,
        );

        $this->actingAs($user);

        $lead = app(LeadWorkflowService::class)
            ->create($this->formData());

        $this->assertSame(
            $employee->getKey(),
            $lead->assigned_employee_id,
        );

        try {
            app(LeadWorkflowService::class)
                ->create(
                    $this->formData([
                        'assigned_employee_id' =>
                            $otherEmployee->getKey(),
                    ]),
                );

            $this->fail(
                'Sales assignment tampering should be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'assigned_employee_id',
                $exception->errors(),
            );
        }

        $this->assertSame(
            1,
            Lead::query()->count(),
        );

        $this->assertSame(
            1,
            Customer::query()->count(),
        );
    }

    public function test_service_rejects_sales_user_without_active_employee(): void
    {
        $user = $this->salesUser();

        $this->actingAs($user);

        try {
            app(LeadWorkflowService::class)
                ->create($this->formData());

            $this->fail(
                'A sales user without an active employee should be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'assigned_employee_id',
                $exception->errors(),
            );
        }

        $this->assertSame(
            0,
            Lead::query()->count(),
        );

        $this->assertSame(
            0,
            Customer::query()->count(),
        );
    }

    public function test_service_allows_manager_active_or_unassigned_assignment(): void
    {
        $manager = $this->managerUser();

        $employee = $this->createEmployee(
            User::factory()->create(),
            true,
        );

        $this->actingAs($manager);

        $assignedLead = app(LeadWorkflowService::class)
            ->create(
                $this->formData([
                    'assigned_employee_id' =>
                        $employee->getKey(),
                ]),
            );

        $unassignedLead = app(LeadWorkflowService::class)
            ->create(
                $this->formData([
                    'assigned_employee_id' => null,
                ]),
            );

        $this->assertSame(
            $employee->getKey(),
            $assignedLead->assigned_employee_id,
        );

        $this->assertNull(
            $unassignedLead->assigned_employee_id,
        );
    }

    public function test_service_rejects_manager_inactive_or_deleted_assignment(): void
    {
        $manager = $this->managerUser();

        $inactiveEmployee = $this->createEmployee(
            User::factory()->create(),
            false,
        );

        $deletedEmployee = $this->createEmployee(
            User::factory()->create(),
            true,
        );

        $deletedEmployeeId =
            (int) $deletedEmployee->getKey();

        $deletedEmployee->delete();

        $this->actingAs($manager);

        foreach ([
            (int) $inactiveEmployee->getKey(),
            $deletedEmployeeId,
        ] as $employeeId) {
            try {
                app(LeadWorkflowService::class)
                    ->create(
                        $this->formData([
                            'assigned_employee_id' =>
                                $employeeId,
                        ]),
                    );

                $this->fail(
                    'Inactive or deleted assignment should be rejected.',
                );
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey(
                    'assigned_employee_id',
                    $exception->errors(),
                );
            }
        }

        $this->assertSame(
            0,
            Lead::query()->count(),
        );

        $this->assertSame(
            0,
            Customer::query()->count(),
        );
    }

    public function test_unauthenticated_service_rejects_assignment_but_allows_unassigned_create(): void
    {
        $employee = $this->createEmployee(
            User::factory()->create(),
            true,
        );

        try {
            app(LeadWorkflowService::class)
                ->create(
                    $this->formData([
                        'assigned_employee_id' =>
                            $employee->getKey(),
                    ]),
                );

            $this->fail(
                'Unauthenticated assignment should be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'assigned_employee_id',
                $exception->errors(),
            );
        }

        $lead = app(LeadWorkflowService::class)
            ->create($this->formData());

        $this->assertNull(
            $lead->assigned_employee_id,
        );

        $this->assertSame(
            1,
            Lead::query()->count(),
        );

        $this->assertSame(
            1,
            Customer::query()->count(),
        );
    }

    public function test_unauthenticated_service_update_preserves_existing_assignment(): void
    {
        $employee = $this->createEmployee(
            User::factory()->create(),
            true,
        );

        $lead = $this->createLead($employee);

        $updatedLead = app(LeadWorkflowService::class)
            ->update(
                $lead,
                [
                    'contact_person' =>
                        'System Updated Contact',
                ],
            );

        $this->assertSame(
            'System Updated Contact',
            $updatedLead->contact_person,
        );

        $this->assertSame(
            $employee->getKey(),
            $updatedLead->assigned_employee_id,
        );
    }
    private function managerUser(): User
    {
        $user = $this->userWithPermissions();

        $role = Role::findOrCreate(
            'Admin',
            'web',
        );

        $user->assignRole($role);

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        return $user->refresh();
    }

    private function salesUser(): User
    {
        return $this->userWithPermissions();
    }

    private function userWithPermissions(): User
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

        $user->givePermissionTo(
            $permissions,
        );

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
        $sequence = ++$this->sequence;

        return Employee::query()->create(
            array_merge([
                'user_id' => $user->getKey(),
                'employee_code' =>
                    Employee::nextEmployeeCode(),
                'full_name' =>
                    "Resource Employee {$sequence}",
                'email' =>
                    "resource-employee-{$sequence}@example.com",
                'phone' => sprintf(
                    '8%09d',
                    $sequence,
                ),
                'is_active' => $active,
            ], $overrides),
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createLead(
        ?Employee $employee,
        array $overrides = [],
    ): Lead {
        $sequence = ++$this->sequence;

        return Lead::query()->create(
            array_merge([
                'lead_code' => sprintf(
                    'LEAD-RS-%04d',
                    $sequence,
                ),
                'lead_status' => 'New',
                'priority' => 'Medium',
                'company_name' =>
                    "Resource Lead {$sequence}",
                'contact_person' =>
                    "Resource Contact {$sequence}",
                'email' =>
                    "resource-lead-{$sequence}@example.com",
                'phone' => sprintf(
                    '7%09d',
                    $sequence,
                ),
                'assigned_employee_id' =>
                    $employee?->getKey(),
                'estimated_value' => 30000,
                'is_active' => true,
            ], $overrides),
        );
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function formData(
        array $overrides = [],
    ): array {
        $sequence = ++$this->sequence;

        return array_merge([
            'lead_status' => 'New',
            'priority' => 'Medium',
            'company_name' =>
                "Resource Form Company {$sequence}",
            'contact_person' =>
                "Resource Form Contact {$sequence}",
            'email' =>
                "resource-form-{$sequence}@example.com",
            'phone' => sprintf(
                '9%09d',
                $sequence,
            ),
            'estimated_value' => '30000',
            'is_active' => true,
        ], $overrides);
    }
}