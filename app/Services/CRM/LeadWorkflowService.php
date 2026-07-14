<?php

declare(strict_types=1);

namespace App\Services\CRM;

use App\Models\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeadWorkflowService
{
    private const WRITABLE_FIELDS = [
        'lead_status',
        'priority',
        'company_name',
        'contact_person',
        'designation',
        'email',
        'phone',
        'whatsapp',
        'website',
        'industry',
        'business_type',
        'company_size',
        'business_address',
        'latitude',
        'longitude',
        'google_maps_link',
        'assigned_employee_id',
        'lead_source',
        'estimated_value',
        'expected_closing_date',
        'next_follow_up_date',
        'requirements_summary',
        'internal_notes',
        'is_active',
    ];

    public function __construct(
        private readonly LeadCustomerService $customers,
        private readonly LeadStatusService $statuses,
    ) {
    }

    public function create(array $data): Lead
    {
        $this->validate($data);

        return DB::transaction(function () use ($data): Lead {
            $payload = Arr::only($data, self::WRITABLE_FIELDS);
            $payload['lead_code'] = Lead::nextLeadCode();
            $payload['lead_status'] = $payload['lead_status'] ?? 'New';
            $payload['converted_customer_id'] = null;

            $this->statuses->assertKnown($payload['lead_status']);

            $lead = Lead::query()->create($payload);

            $this->customers->synchronizeLocked($lead);

            return $lead->refresh()->load('convertedCustomer');
        }, attempts: 3);
    }

    public function update(Lead $lead, array $data): Lead
    {
        $this->validate($data, updating: true);

        return DB::transaction(function () use ($lead, $data): Lead {
            $lockedLead = Lead::query()
                ->lockForUpdate()
                ->findOrFail($lead->getKey());

            $payload = Arr::only($data, self::WRITABLE_FIELDS);
            $targetStatus = (string) (
                $payload['lead_status']
                ?? $lockedLead->lead_status
            );

            unset($payload['lead_status']);

            if ($payload !== []) {
                $lockedLead->update($payload);
            }

            $this->statuses->transitionLocked(
                $lockedLead,
                $targetStatus,
            );

            $this->customers->synchronizeLocked($lockedLead);

            return $lockedLead->refresh()->load('convertedCustomer');
        }, attempts: 3);
    }

    private function validate(
        array $data,
        bool $updating = false,
    ): void {
        $prefix = $updating ? 'sometimes' : 'required';

        Validator::make($data, [
            'contact_person' => "{$prefix}|string|max:255",
            'email' => "{$prefix}|email|max:255",
            'phone' => "{$prefix}|string|max:50",
            'lead_status' =>
                'sometimes|string|in:New,Contacted,Qualified,Meeting Scheduled,Proposal Sent,Negotiation,Won,Lost',
            'estimated_value' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ])->validate();
    }
}
