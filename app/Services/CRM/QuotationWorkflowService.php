<?php

declare(strict_types=1);

namespace App\Services\CRM;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Quotation;
use App\Services\Communication\EmailService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuotationWorkflowService
{
    private const WRITABLE_FIELDS = [
        'assigned_employee_id',
        'quotation_date',
        'valid_until',
        'subtotal',
        'discount_type',
        'discount_value',
        'tax_applicable',
        'tax_percentage',
        'tax',
        'grand_total',
        'customer_notes',
        'internal_notes',
        'is_active',
    ];

    public function __construct(
        private readonly LeadCustomerService $customers,
        private readonly LeadStatusService $leadStatuses,
    ) {
    }

    public function create(array $data): Quotation
    {
        $this->validate($data);

        return DB::transaction(function () use ($data): Quotation {
            [$lead, $customer, $meeting] =
                $this->resolveContext($data);

            $this->assertSubmittedIdentity(
                $data,
                'lead_id',
                $lead?->getKey(),
            );
            $this->assertSubmittedIdentity(
                $data,
                'customer_id',
                $customer->getKey(),
            );
            $this->assertSubmittedIdentity(
                $data,
                'meeting_id',
                $meeting?->getKey(),
            );

            $payload = Arr::only($data, self::WRITABLE_FIELDS);
            $payload['quotation_code'] =
                Quotation::nextQuotationCode();
            $payload['lead_id'] = $lead?->getKey();
            $payload['customer_id'] = $customer->getKey();
            $payload['meeting_id'] = $meeting?->getKey();
            $payload['assigned_employee_id'] =
                $payload['assigned_employee_id']
                ?? $meeting?->assigned_employee_id
                ?? $lead?->assigned_employee_id;
            $payload['status'] = 'Draft';
            $payload['approved_at'] = null;
            $payload['approved_by'] = null;
            $payload['quotation_sent_at'] = null;
            $payload['quotation_sent_by'] = null;
            $payload['quotation_send_count'] = 0;
            $payload['last_sent_to'] = null;

            return Quotation::query()
                ->create($payload)
                ->refresh()
                ->load([
                    'lead',
                    'customer',
                    'meeting',
                ]);
        }, attempts: 3);
    }

    public function update(
        Quotation $quotation,
        array $data,
    ): Quotation {
        $this->validate($data, updating: true);

        return DB::transaction(function () use (
            $quotation,
            $data,
        ): Quotation {
            $lockedQuotation = Quotation::query()
                ->lockForUpdate()
                ->findOrFail($quotation->getKey());

            $this->assertSubmittedIdentity(
                $data,
                'lead_id',
                $lockedQuotation->lead_id,
            );
            $this->assertSubmittedIdentity(
                $data,
                'customer_id',
                $lockedQuotation->customer_id,
            );
            $this->assertSubmittedIdentity(
                $data,
                'meeting_id',
                $lockedQuotation->meeting_id,
            );

            $this->assertStoredRelationships(
                $lockedQuotation,
            );

            $payload = Arr::only($data, self::WRITABLE_FIELDS);

            if (array_key_exists('grand_total', $payload)) {
                $paid = (float) $lockedQuotation
                    ->payments()
                    ->sum('amount');

                if ((float) $payload['grand_total'] < $paid) {
                    throw ValidationException::withMessages([
                        'grand_total' =>
                            'The quotation total cannot be reduced below the amount already paid.',
                    ]);
                }
            }

            if ($payload !== []) {
                $lockedQuotation->update($payload);
            }

            return $lockedQuotation->refresh()->load([
                'lead',
                'customer',
                'meeting',
            ]);
        }, attempts: 3);
    }

    public function approve(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use (
            $quotation,
        ): Quotation {
            $lockedQuotation = Quotation::query()
                ->lockForUpdate()
                ->findOrFail($quotation->getKey());

            $this->assertStoredRelationships(
                $lockedQuotation,
            );

            if ($lockedQuotation->status !== 'Draft') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only draft quotations can be approved.',
                ]);
            }

            if ((float) $lockedQuotation->grand_total <= 0) {
                throw ValidationException::withMessages([
                    'grand_total' =>
                        'A quotation must have a positive total before approval.',
                ]);
            }

            $lockedQuotation->update([
                'status' => 'Approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

            return $lockedQuotation->refresh();
        }, attempts: 3);
    }

    public function send(Quotation $quotation): bool
    {
        $quotation = $quotation->fresh([
            'customer',
            'lead',
            'meeting',
        ]);

        if (! $quotation) {
            return false;
        }

        $this->assertSendable($quotation);

        if (! EmailService::sendQuotation($quotation)) {
            return false;
        }

        $this->recordSent($quotation);

        return true;
    }

    public function recordSent(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use (
            $quotation,
        ): Quotation {
            $lockedQuotation = Quotation::query()
                ->lockForUpdate()
                ->findOrFail($quotation->getKey());

            $this->assertSendable($lockedQuotation);
            $this->assertStoredRelationships(
                $lockedQuotation,
            );

            if ($lockedQuotation->status === 'Approved') {
                $lockedQuotation->update([
                    'status' => 'Sent',
                ]);
            }

            if ($lockedQuotation->lead_id) {
                $lead = Lead::query()
                    ->lockForUpdate()
                    ->findOrFail($lockedQuotation->lead_id);

                $this->leadStatuses->advanceLocked(
                    $lead,
                    'Proposal Sent',
                );
            }

            return $lockedQuotation->refresh();
        }, attempts: 3);
    }

    private function assertSendable(
        Quotation $quotation,
    ): void {
        if (! in_array(
            $quotation->status,
            ['Approved', 'Sent', 'Accepted', 'Completed'],
            true,
        )) {
            throw ValidationException::withMessages([
                'status' =>
                    'The quotation must be approved before it can be sent.',
            ]);
        }
    }

    /**
     * @return array{0: ?Lead, 1: Customer, 2: ?Meeting}
     */
    private function resolveContext(array $data): array
    {
        $meeting = null;
        $lead = null;
        $customer = null;

        if (filled($data['meeting_id'] ?? null)) {
            $meeting = Meeting::query()
                ->lockForUpdate()
                ->findOrFail((int) $data['meeting_id']);

            if (
                $meeting->status !== 'Completed'
                || ! in_array(
                    $meeting->outcome,
                    ['Quotation Required', 'Converted'],
                    true,
                )
            ) {
                throw ValidationException::withMessages([
                    'meeting_id' =>
                        'A quotation can only be generated from a completed meeting that requires a quotation.',
                ]);
            }

            $lead = Lead::query()
                ->lockForUpdate()
                ->findOrFail($meeting->lead_id);

            if ($lead->lead_status === 'Lost') {
                throw ValidationException::withMessages([
                    'lead_id' =>
                        'A quotation cannot be created for a lost lead.',
                ]);
            }

            $customer = $this->customers
                ->synchronizeLocked($lead);

            if (
                $meeting->customer_id
                && (int) $meeting->customer_id !== (int) $customer->id
            ) {
                throw ValidationException::withMessages([
                    'meeting_id' =>
                        'The meeting customer does not match the lead customer.',
                ]);
            }

            if (! $meeting->customer_id) {
                $meeting->updateQuietly([
                    'customer_id' => $customer->id,
                ]);
            }
        } elseif (filled($data['lead_id'] ?? null)) {
            $lead = Lead::query()
                ->lockForUpdate()
                ->findOrFail((int) $data['lead_id']);

            if ($lead->lead_status === 'Lost') {
                throw ValidationException::withMessages([
                    'lead_id' =>
                        'A quotation cannot be created for a lost lead.',
                ]);
            }

            $customer = $this->customers
                ->synchronizeLocked($lead);
        } elseif (filled($data['customer_id'] ?? null)) {
            $customer = Customer::query()
                ->lockForUpdate()
                ->findOrFail((int) $data['customer_id']);
        }

        if (! $customer) {
            throw ValidationException::withMessages([
                'customer_id' =>
                    'Select a customer, lead, or completed meeting for the quotation.',
            ]);
        }

        if ($customer->customer_status === 'Blacklisted') {
            throw ValidationException::withMessages([
                'customer_id' =>
                    'A quotation cannot be created for a blacklisted customer.',
            ]);
        }

        return [$lead, $customer, $meeting];
    }

    private function assertStoredRelationships(
        Quotation $quotation,
    ): void {
        if ($quotation->meeting_id) {
            $meeting = Meeting::query()
                ->lockForUpdate()
                ->findOrFail($quotation->meeting_id);

            if (
                (int) $meeting->lead_id !== (int) $quotation->lead_id
                || (int) $meeting->customer_id
                    !== (int) $quotation->customer_id
            ) {
                throw ValidationException::withMessages([
                    'meeting_id' =>
                        'The quotation relationships no longer match its meeting.',
                ]);
            }
        }

        if ($quotation->lead_id) {
            $lead = Lead::query()
                ->lockForUpdate()
                ->findOrFail($quotation->lead_id);

            if (
                (int) $lead->converted_customer_id
                    !== (int) $quotation->customer_id
            ) {
                throw ValidationException::withMessages([
                    'customer_id' =>
                        'The quotation customer does not match its lead.',
                ]);
            }
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
            'quotation_date' => "{$required}|date",
            'grand_total' => "{$required}|numeric|min:0",
            'subtotal' => 'sometimes|numeric|min:0',
            'discount_value' => 'sometimes|numeric|min:0',
            'tax' => 'sometimes|numeric|min:0',
            'tax_percentage' => 'sometimes|numeric|min:0',
            'tax_applicable' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'lead_id' => 'sometimes|nullable|integer|exists:leads,id',
            'customer_id' =>
                'sometimes|nullable|integer|exists:customers,id',
            'meeting_id' =>
                'sometimes|nullable|integer|exists:meetings,id',
        ])->validate();
    }
}
