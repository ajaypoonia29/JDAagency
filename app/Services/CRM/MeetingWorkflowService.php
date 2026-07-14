<?php

declare(strict_types=1);

namespace App\Services\CRM;

use App\Models\Lead;
use App\Models\Meeting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MeetingWorkflowService
{
    private const WRITABLE_FIELDS = [
        'assigned_employee_id',
        'meeting_title',
        'meeting_type',
        'meeting_date',
        'meeting_time',
        'expected_duration',
        'business_address',
        'latitude',
        'longitude',
        'google_maps_link',
        'business_photo',
        'status',
        'outcome',
        'meeting_notes',
        'is_active',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const STATUS_TRANSITIONS = [
        'Scheduled' => [
            'Confirmed',
            'Completed',
            'Cancelled',
            'Rescheduled',
            'No Show',
        ],
        'Confirmed' => [
            'Completed',
            'Cancelled',
            'Rescheduled',
            'No Show',
        ],
        'Rescheduled' => [
            'Scheduled',
            'Confirmed',
            'Completed',
            'Cancelled',
            'No Show',
        ],
        'No Show' => [
            'Rescheduled',
        ],
        'Completed' => [],
        'Cancelled' => [],
    ];

    public function __construct(
        private readonly LeadCustomerService $customers,
        private readonly LeadStatusService $leadStatuses,
    ) {
    }

    public function create(array $data): Meeting
    {
        $this->validate($data);

        return DB::transaction(function () use ($data): Meeting {
            $lead = Lead::query()
                ->lockForUpdate()
                ->findOrFail((int) $data['lead_id']);

            if ($lead->lead_status === 'Lost') {
                throw ValidationException::withMessages([
                    'lead_id' =>
                        'A meeting cannot be scheduled for a lost lead. Reopen the lead through an authorized workflow first.',
                ]);
            }

            $customer = $this->customers
                ->synchronizeLocked($lead);

            $this->assertSubmittedIdentity(
                $data,
                'customer_id',
                $customer->getKey(),
            );

            $payload = Arr::only($data, self::WRITABLE_FIELDS);
            $payload['meeting_code'] = Meeting::nextMeetingCode();
            $payload['lead_id'] = $lead->getKey();
            $payload['customer_id'] = $customer->getKey();
            $payload['assigned_employee_id'] =
                $payload['assigned_employee_id']
                ?? $lead->assigned_employee_id;
            $payload['status'] = $payload['status'] ?? 'Scheduled';
            $payload['outcome'] = $payload['outcome'] ?? 'Pending';

            $this->assertOutcomeConsistency(
                $payload['status'],
                $payload['outcome'],
            );

            $meeting = Meeting::query()->create($payload);

            $this->leadStatuses->advanceLocked(
                $lead,
                'Meeting Scheduled',
            );

            $this->applyCompletedOutcome($meeting, $lead);

            return $meeting->refresh()->load([
                'lead',
                'customer',
            ]);
        }, attempts: 3);
    }

    public function update(
        Meeting $meeting,
        array $data,
    ): Meeting {
        $this->validate($data, updating: true);

        return DB::transaction(function () use (
            $meeting,
            $data,
        ): Meeting {
            $lockedMeeting = Meeting::query()
                ->lockForUpdate()
                ->findOrFail($meeting->getKey());

            $lead = Lead::query()
                ->lockForUpdate()
                ->findOrFail($lockedMeeting->lead_id);

            $this->assertSubmittedIdentity(
                $data,
                'lead_id',
                $lockedMeeting->lead_id,
            );
            $this->assertSubmittedIdentity(
                $data,
                'customer_id',
                $lockedMeeting->customer_id,
            );

            $payload = Arr::only($data, self::WRITABLE_FIELDS);
            $nextStatus = (string) (
                $payload['status']
                ?? $lockedMeeting->status
            );
            $nextOutcome = (string) (
                $payload['outcome']
                ?? $lockedMeeting->outcome
            );

            $this->assertStatusTransition(
                (string) $lockedMeeting->status,
                $nextStatus,
            );
            $this->assertOutcomeConsistency(
                $nextStatus,
                $nextOutcome,
            );

            if ($payload !== []) {
                $lockedMeeting->update($payload);
            }

            $this->applyCompletedOutcome(
                $lockedMeeting,
                $lead,
            );

            return $lockedMeeting->refresh()->load([
                'lead',
                'customer',
            ]);
        }, attempts: 3);
    }

    private function applyCompletedOutcome(
        Meeting $meeting,
        Lead $lead,
    ): void {
        if (
            $meeting->status === 'Completed'
            && $meeting->outcome === 'Not Interested'
        ) {
            $this->leadStatuses->transitionLocked(
                $lead,
                'Lost',
            );
        }
    }

    private function assertStatusTransition(
        string $current,
        string $target,
    ): void {
        if ($current === $target) {
            return;
        }

        if (! in_array(
            $target,
            self::STATUS_TRANSITIONS[$current] ?? [],
            true,
        )) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Meeting status cannot transition from %s to %s.',
                    $current,
                    $target,
                ),
            ]);
        }
    }

    private function assertOutcomeConsistency(
        string $status,
        string $outcome,
    ): void {
        if ($status === 'Completed' && $outcome === 'Pending') {
            throw ValidationException::withMessages([
                'outcome' =>
                    'A completed meeting must have a recorded outcome.',
            ]);
        }

        if ($status !== 'Completed' && $outcome !== 'Pending') {
            throw ValidationException::withMessages([
                'outcome' =>
                    'Meeting outcomes can only be finalized when the meeting is completed.',
            ]);
        }
    }

    private function assertSubmittedIdentity(
        array $data,
        string $field,
        int|string|null $expected,
    ): void {
        if (
            ! array_key_exists($field, $data)
            || blank($data[$field])
            || (string) $data[$field] === (string) $expected
        ) {
            return;
        }

        throw ValidationException::withMessages([
            $field =>
                'The selected relationship does not belong to this workflow record.',
        ]);
    }

    private function validate(
        array $data,
        bool $updating = false,
    ): void {
        $required = $updating ? 'sometimes' : 'required';

        Validator::make($data, [
            'lead_id' => $updating
                ? 'sometimes|integer|exists:leads,id'
                : 'required|integer|exists:leads,id',
            'meeting_title' => "{$required}|string|max:255",
            'meeting_type' =>
                "{$required}|string|in:Office,Client Site,Online,Phone",
            'meeting_date' => "{$required}|date",
            'meeting_time' => "{$required}",
            'status' =>
                'sometimes|string|in:Scheduled,Confirmed,Completed,Cancelled,Rescheduled,No Show',
            'outcome' =>
                'sometimes|string|in:Pending,Interested,Not Interested,Follow-up Required,Quotation Required,Converted',
            'expected_duration' =>
                'sometimes|nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ])->validate();
    }
}
