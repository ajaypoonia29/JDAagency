<?php

declare(strict_types=1);

namespace Tests\Feature\CRM;

use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Quotation;
use App\Models\Service;
use App\Services\CRM\LeadWorkflowService;
use App\Services\CRM\MeetingWorkflowService;
use App\Services\CRM\SalesWorkspaceQuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalesWorkspaceQuotationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_creates_draft_with_trusted_totals_and_items(): void
    {
        [$lead, $meeting] = $this->journey();
        $service = $this->service();

        $quotation = app(
            SalesWorkspaceQuotationService::class,
        )->saveDraft(
            $lead,
            $meeting,
            null,
            $this->quotationData($service),
        );

        $this->assertSame('Draft', $quotation->status);
        $this->assertSame($lead->id, $quotation->lead_id);
        $this->assertSame($meeting->id, $quotation->meeting_id);
        $this->assertSame(
            $lead->converted_customer_id,
            $quotation->customer_id,
        );

        $this->assertSame(1, $quotation->items()->count());
        $this->assertSame('1900.00', $quotation->subtotal);
        $this->assertSame('307.80', $quotation->tax);
        $this->assertSame('2017.80', $quotation->grand_total);
        $this->assertSame('0.00', $quotation->total_paid);
        $this->assertSame('2017.80', $quotation->balance_due);
        $this->assertSame('Unpaid', $quotation->payment_status);
        $this->assertSame(
            'Thank you for your interest.',
            $quotation->customer_notes,
        );
        $this->assertSame(
            'Workspace test quotation.',
            $quotation->internal_notes,
        );

        $item = $quotation->items()->firstOrFail();

        $this->assertSame($service->id, $item->service_id);
        $this->assertSame('2.00', $item->quantity);
        $this->assertSame('1000.00', $item->unit_price);
        $this->assertSame('100.00', $item->discount);
        $this->assertSame('1900.00', $item->line_total);
    }

    public function test_workspace_rejects_line_discount_above_line_gross(): void
    {
        [$lead, $meeting] = $this->journey();
        $service = $this->service();

        $data = $this->quotationData($service);
        $data['items'][0]['discount'] = 2500;

        try {
            app(
                SalesWorkspaceQuotationService::class,
            )->saveDraft(
                $lead,
                $meeting,
                null,
                $data,
            );

            $this->fail(
                'An excessive line discount should be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'items.0.discount',
                $exception->errors(),
            );
        }

        $this->assertSame(
            0,
            Quotation::query()->count(),
        );
    }

    public function test_workspace_updates_existing_draft_and_replaces_items_atomically(): void
    {
        [$lead, $meeting] = $this->journey();
        $service = $this->service();

        $workflow = app(
            SalesWorkspaceQuotationService::class,
        );

        $quotation = $workflow->saveDraft(
            $lead,
            $meeting,
            null,
            $this->quotationData($service),
        );

        $originalItemId = $quotation
            ->items()
            ->firstOrFail()
            ->id;

        $data = $this->quotationData($service);
        $data['items'][0]['quantity'] = 3;
        $data['items'][0]['discount'] = 0;
        $data['discount_type'] = 'fixed';
        $data['discount_value'] = 0;
        $data['tax_applicable'] = false;

        $quotation = $workflow->saveDraft(
            $lead,
            $meeting,
            $quotation,
            $data,
        );

        $this->assertSame(1, $quotation->items()->count());
        $this->assertSame('3000.00', $quotation->subtotal);
        $this->assertSame('0.00', $quotation->tax);
        $this->assertSame('3000.00', $quotation->grand_total);
        $this->assertSame('3000.00', $quotation->balance_due);

        $newItem = $quotation->items()->firstOrFail();

        $this->assertNotSame(
            $originalItemId,
            $newItem->id,
        );
        $this->assertSame('3.00', $newItem->quantity);
        $this->assertSame('3000.00', $newItem->line_total);
    }

    public function test_workspace_approves_saved_draft(): void
    {
        [$lead, $meeting] = $this->journey();
        $service = $this->service();

        $workflow = app(
            SalesWorkspaceQuotationService::class,
        );

        $quotation = $workflow->saveDraft(
            $lead,
            $meeting,
            null,
            $this->quotationData($service),
        );

        $quotation = $workflow->approve(
            $lead,
            $quotation,
        );

        $this->assertSame(
            'Approved',
            $quotation->status,
        );
        $this->assertNotNull($quotation->approved_at);
    }

    public function test_workspace_rejects_nonqualifying_meeting(): void
    {
        $lead = $this->lead();

        $meeting = app(MeetingWorkflowService::class)
            ->create($this->meetingData($lead));

        $service = $this->service();

        try {
            app(
                SalesWorkspaceQuotationService::class,
            )->saveDraft(
                $lead,
                $meeting,
                null,
                $this->quotationData($service),
            );

            $this->fail(
                'A scheduled meeting should not create a quotation.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'meeting_id',
                $exception->errors(),
            );
        }

        $this->assertSame(
            0,
            Quotation::query()->count(),
        );
    }

    /**
     * @return array{0: Lead, 1: Meeting}
     */
    private function journey(): array
    {
        $lead = $this->lead();

        $meeting = app(MeetingWorkflowService::class)
            ->create($this->meetingData($lead));

        $meeting = app(MeetingWorkflowService::class)
            ->update($meeting, [
                'status' => 'Completed',
                'outcome' => 'Quotation Required',
            ]);

        return [$lead->refresh(), $meeting->refresh()];
    }

    private function lead(): Lead
    {
        return app(LeadWorkflowService::class)
            ->create([
                'lead_status' => 'Qualified',
                'priority' => 'Medium',
                'company_name' => 'Workspace Test Company',
                'contact_person' => 'Workspace Tester',
                'email' => 'workspace@example.com',
                'phone' => '9999999999',
                'whatsapp' => '9999999999',
                'industry' => 'Services',
                'business_type' => 'Agency',
                'estimated_value' => 50000,
                'requirements_summary' =>
                    'Inline quotation workflow',
                'is_active' => true,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function meetingData(Lead $lead): array
    {
        return [
            'lead_id' => $lead->id,
            'customer_id' => $lead->converted_customer_id,
            'meeting_title' =>
                'Workspace Quotation Meeting',
            'meeting_type' => 'Online',
            'meeting_date' =>
                now()->addDay()->toDateString(),
            'meeting_time' => '11:00:00',
            'expected_duration' => 30,
            'status' => 'Scheduled',
            'outcome' => 'Pending',
            'is_active' => true,
        ];
    }

    private function service(): Service
    {
        return Service::query()->create([
            'service_code' => 'SER-TEST-0001',
            'service_name' => 'Workspace Service',
            'category' => 'Consulting',
            'description' =>
                'Inline quotation service',
            'standard_price' => 1000,
            'gst_applicable' => true,
            'gst_percentage' => 18,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function quotationData(
        Service $service,
    ): array {
        return [
            'quotation_date' => now()->toDateString(),
            'valid_until' =>
                now()->addDays(15)->toDateString(),
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'tax_applicable' => true,
            'tax_percentage' => 18,
            'customer_notes' =>
                'Thank you for your interest.',
            'internal_notes' =>
                'Workspace test quotation.',
            'items' => [
                [
                    'service_id' => $service->id,
                    'description' =>
                        'Inline quotation service',
                    'quantity' => 2,
                    'unit_price' => 1000,
                    'discount' => 100,
                ],
            ],
        ];
    }
}