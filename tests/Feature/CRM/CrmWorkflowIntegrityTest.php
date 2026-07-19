<?php

declare(strict_types=1);

namespace Tests\Feature\CRM;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Quotation;
use App\Models\User;
use App\Services\CRM\LeadWorkflowService;
use App\Services\CRM\MeetingWorkflowService;
use App\Services\CRM\QuotationWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CrmWorkflowIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }

    public function test_lead_creation_atomically_creates_and_links_customer(): void
    {
        $lead = app(LeadWorkflowService::class)
            ->create($this->leadData());

        $this->assertNotNull($lead->converted_customer_id);
        $this->assertSame(1, Lead::query()->count());
        $this->assertSame(1, Customer::query()->count());
        $this->assertSame(
            'CRM Test Company',
            $lead->convertedCustomer->display_name,
        );
        $this->assertSame(
            'crm@example.com',
            $lead->convertedCustomer->primary_email,
        );
    }

    public function test_existing_customer_is_linked_without_overwriting_existing_data(): void
    {
        $customer = $this->createCustomer([
            'company_name' => 'Authoritative Company',
            'display_name' => 'Authoritative Company',
            'industry' => null,
        ]);

        $lead = app(LeadWorkflowService::class)
            ->create($this->leadData([
                'company_name' => 'Different Lead Company',
                'industry' => 'Technology',
            ]));

        $customer->refresh();

        $this->assertSame(
            $customer->id,
            $lead->converted_customer_id,
        );
        $this->assertSame(
            'Authoritative Company',
            $customer->company_name,
        );
        $this->assertSame(
            'Technology',
            $customer->industry,
        );
        $this->assertSame(1, Customer::query()->count());
    }

    public function test_conflicting_customer_identifiers_roll_back_lead_creation(): void
    {
        $this->createCustomer([
            'customer_code' => 'CUS-CONFLICT-EMAIL',
            'primary_email' => 'crm@example.com',
            'primary_phone' => '1111111111',
        ]);
        $this->createCustomer([
            'customer_code' => 'CUS-CONFLICT-PHONE',
            'primary_email' => 'other@example.com',
            'primary_phone' => '9999999999',
        ]);

        try {
            app(LeadWorkflowService::class)
                ->create($this->leadData());

            $this->fail(
                'A customer identity conflict should have been rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'email',
                $exception->errors(),
            );
        }

        $this->assertSame(0, Lead::query()->count());
        $this->assertSame(2, Customer::query()->count());
    }

    public function test_lead_status_cannot_regress_or_reopen_terminal_status(): void
    {
        $lead = app(LeadWorkflowService::class)
            ->create($this->leadData([
                'lead_status' => 'Proposal Sent',
            ]));

        try {
            app(LeadWorkflowService::class)
                ->update($lead, [
                    'lead_status' => 'Qualified',
                ]);

            $this->fail(
                'A backward lead transition should have been rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'lead_status',
                $exception->errors(),
            );
        }

        $this->assertSame(
            'Proposal Sent',
            $lead->fresh()->lead_status,
        );
    }

    public function test_meeting_creation_derives_relationships_and_advances_lead(): void
    {
        $lead = app(LeadWorkflowService::class)
            ->create($this->leadData([
                'lead_status' => 'Qualified',
            ]));

        $meeting = app(MeetingWorkflowService::class)
            ->create($this->meetingData($lead));

        $this->assertSame($lead->id, $meeting->lead_id);
        $this->assertSame(
            $lead->converted_customer_id,
            $meeting->customer_id,
        );
        $this->assertSame(
            'Meeting Scheduled',
            $lead->fresh()->lead_status,
        );
    }

    public function test_meeting_rejects_mismatched_customer_and_rolls_back(): void
    {
        $lead = app(LeadWorkflowService::class)
            ->create($this->leadData());

        $otherCustomer = $this->createCustomer([
            'customer_code' => 'CUS-OTHER-0001',
            'primary_email' => 'other-customer@example.com',
            'primary_phone' => '8888888888',
        ]);

        try {
            app(MeetingWorkflowService::class)
                ->create($this->meetingData($lead, [
                    'customer_id' => $otherCustomer->id,
                ]));

            $this->fail(
                'A mismatched meeting customer should have been rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'customer_id',
                $exception->errors(),
            );
        }

        $this->assertSame(0, Meeting::query()->count());
        $this->assertSame(
            'New',
            $lead->fresh()->lead_status,
        );
    }

    public function test_completed_not_interested_meeting_marks_lead_lost(): void
    {
        $lead = app(LeadWorkflowService::class)
            ->create($this->leadData());

        $meeting = app(MeetingWorkflowService::class)
            ->create($this->meetingData($lead));

        app(MeetingWorkflowService::class)
            ->update($meeting, [
                'status' => 'Completed',
                'outcome' => 'Not Interested',
            ]);

        $this->assertSame(
            'Lost',
            $lead->fresh()->lead_status,
        );
    }

    public function test_quotation_from_meeting_derives_identity_and_waits_until_send(): void
    {
        $lead = app(LeadWorkflowService::class)
            ->create($this->leadData([
                'lead_status' => 'Qualified',
            ]));

        $meeting = app(MeetingWorkflowService::class)
            ->create($this->meetingData($lead));

        $meeting = app(MeetingWorkflowService::class)
            ->update($meeting, [
                'status' => 'Completed',
                'outcome' => 'Quotation Required',
            ]);

        $quotation = app(QuotationWorkflowService::class)
            ->create($this->quotationData([
                'meeting_id' => $meeting->id,
                'lead_id' => $lead->id,
                'customer_id' => $lead->converted_customer_id,
            ]));

        $this->assertSame($meeting->id, $quotation->meeting_id);
        $this->assertSame($lead->id, $quotation->lead_id);
        $this->assertSame(
            $lead->converted_customer_id,
            $quotation->customer_id,
        );
        $this->assertSame('Draft', $quotation->status);
        $this->assertSame(
            'Meeting Scheduled',
            $lead->fresh()->lead_status,
        );

        $quotation = app(QuotationWorkflowService::class)
            ->approve($quotation);

        app(QuotationWorkflowService::class)
            ->recordSent($quotation);

        $this->assertSame(
            'Sent',
            $quotation->fresh()->status,
        );
        $this->assertSame(
            'Proposal Sent',
            $lead->fresh()->lead_status,
        );
    }

    public function test_quotation_rejects_mismatched_meeting_relationships(): void
    {
        $lead = app(LeadWorkflowService::class)
            ->create($this->leadData());
        $meeting = app(MeetingWorkflowService::class)
            ->create($this->meetingData($lead));
        $meeting = app(MeetingWorkflowService::class)
            ->update($meeting, [
                'status' => 'Completed',
                'outcome' => 'Quotation Required',
            ]);

        $otherLead = app(LeadWorkflowService::class)
            ->create($this->leadData([
                'email' => 'second@example.com',
                'phone' => '7777777777',
                'company_name' => 'Second Company',
            ]));

        try {
            app(QuotationWorkflowService::class)
                ->create($this->quotationData([
                    'meeting_id' => $meeting->id,
                    'lead_id' => $otherLead->id,
                    'customer_id' =>
                        $otherLead->converted_customer_id,
                ]));

            $this->fail(
                'Mismatched quotation relationships should be rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'lead_id',
                $exception->errors(),
            );
        }

        $this->assertSame(0, Quotation::query()->count());
    }

    public function test_crm_policies_use_existing_permissions(): void
    {
        $user = $this->userWithPermissions([
            'leads.view',
            'leads.edit',
            'customers.view',
        ]);

        $employee = Employee::query()->create([
            'user_id' => $user->getKey(),
            'employee_code' =>
                Employee::nextEmployeeCode(),
            'full_name' => 'CRM Policy Employee',
            'email' =>
                'crm-policy-employee@example.com',
            'phone' => '9888888888',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $lead = app(LeadWorkflowService::class)
            ->create($this->leadData());

        $meeting = app(MeetingWorkflowService::class)
            ->create($this->meetingData($lead));

        $customer = $lead->convertedCustomer;

        $this->assertSame(
            $employee->getKey(),
            $lead->assigned_employee_id,
        );

        $this->assertTrue(
            $user->can('viewAny', Lead::class),
        );

        $this->assertTrue(
            $user->can('update', $lead),
        );

        $this->assertTrue(
            $user->can('scheduleMeeting', $lead),
        );

        $this->assertTrue(
            $user->can('viewAny', Meeting::class),
        );

        $this->assertTrue(
            $user->can('update', $meeting),
        );

        $this->assertTrue(
            $user->can('view', $customer),
        );

        $this->assertFalse(
            $user->can('update', $customer),
        );
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function leadData(array $overrides = []): array
    {
        return array_merge([
            'lead_status' => 'New',
            'priority' => 'Medium',
            'company_name' => 'CRM Test Company',
            'contact_person' => 'CRM Tester',
            'email' => 'crm@example.com',
            'phone' => '9999999999',
            'whatsapp' => '9999999999',
            'industry' => 'Services',
            'business_type' => 'Agency',
            'estimated_value' => 50000,
            'requirements_summary' => 'CRM workflow hardening',
            'is_active' => true,
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function meetingData(
        Lead $lead,
        array $overrides = [],
    ): array {
        return array_merge([
            'lead_id' => $lead->id,
            'customer_id' => $lead->converted_customer_id,
            'meeting_title' => 'CRM Workflow Meeting',
            'meeting_type' => 'Online',
            'meeting_date' => now()->addDay()->toDateString(),
            'meeting_time' => '11:00:00',
            'expected_duration' => 30,
            'status' => 'Scheduled',
            'outcome' => 'Pending',
            'is_active' => true,
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function quotationData(
        array $overrides = [],
    ): array {
        return array_merge([
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'subtotal' => 1000,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'tax_applicable' => false,
            'tax_percentage' => 18,
            'tax' => 0,
            'grand_total' => 1000,
            'is_active' => true,
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createCustomer(
        array $overrides = [],
    ): Customer {
        return Customer::query()->create(array_merge([
            'customer_code' => 'CUS-EXISTING-0001',
            'customer_type' => 'Business',
            'customer_status' => 'Lead',
            'company_name' => 'CRM Test Company',
            'display_name' => 'CRM Test Company',
            'contact_person' => 'CRM Tester',
            'primary_email' => 'crm@example.com',
            'primary_phone' => '9999999999',
            'currency' => 'INR',
            'is_active' => true,
        ], $overrides));
    }

    /**
     * @param array<int, string> $permissions
     */
    private function userWithPermissions(
        array $permissions,
    ): User {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }
}
