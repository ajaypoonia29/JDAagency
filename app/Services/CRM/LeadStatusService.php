<?php

declare(strict_types=1);

namespace App\Services\CRM;

use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadStatusService
{
    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'New' => [
            'Contacted',
            'Qualified',
            'Meeting Scheduled',
            'Proposal Sent',
            'Negotiation',
            'Won',
            'Lost',
        ],
        'Contacted' => [
            'Qualified',
            'Meeting Scheduled',
            'Proposal Sent',
            'Negotiation',
            'Won',
            'Lost',
        ],
        'Qualified' => [
            'Meeting Scheduled',
            'Proposal Sent',
            'Negotiation',
            'Won',
            'Lost',
        ],
        'Meeting Scheduled' => [
            'Proposal Sent',
            'Negotiation',
            'Won',
            'Lost',
        ],
        'Proposal Sent' => [
            'Negotiation',
            'Won',
            'Lost',
        ],
        'Negotiation' => [
            'Won',
            'Lost',
        ],
        'Won' => [],
        'Lost' => [],
    ];

    /**
     * @var array<string, int>
     */
    private const PROGRESS_RANK = [
        'New' => 10,
        'Contacted' => 20,
        'Qualified' => 30,
        'Meeting Scheduled' => 40,
        'Proposal Sent' => 50,
        'Negotiation' => 60,
        'Won' => 70,
    ];

    public function transition(Lead $lead, string $target): Lead
    {
        return DB::transaction(function () use ($lead, $target): Lead {
            $lockedLead = Lead::query()
                ->lockForUpdate()
                ->findOrFail($lead->getKey());

            $this->transitionLocked($lockedLead, $target);

            return $lockedLead->refresh();
        }, attempts: 3);
    }

    public function transitionLocked(Lead $lead, string $target): void
    {
        $target = trim($target);
        $current = (string) $lead->lead_status;

        $this->assertKnown($target);

        if ($current === $target) {
            return;
        }

        if (! in_array(
            $target,
            self::ALLOWED_TRANSITIONS[$current] ?? [],
            true,
        )) {
            throw ValidationException::withMessages([
                'lead_status' => sprintf(
                    'Lead status cannot move backward or reopen automatically (%s → %s).',
                    $current,
                    $target,
                ),
            ]);
        }

        $lead->update([
            'lead_status' => $target,
        ]);
    }

    /**
     * Advance an automatic workflow status without ever regressing it.
     */
    public function advanceLocked(Lead $lead, string $target): void
    {
        $target = trim($target);
        $current = (string) $lead->lead_status;

        $this->assertKnown($target);

        if (
            $current === $target
            || in_array($current, ['Won', 'Lost'], true)
        ) {
            return;
        }

        if (
            isset(self::PROGRESS_RANK[$current], self::PROGRESS_RANK[$target])
            && self::PROGRESS_RANK[$current] >= self::PROGRESS_RANK[$target]
        ) {
            return;
        }

        $this->transitionLocked($lead, $target);
    }

    public function assertKnown(string $status): void
    {
        if (! array_key_exists($status, self::ALLOWED_TRANSITIONS)) {
            throw ValidationException::withMessages([
                'lead_status' => 'The selected lead status is invalid.',
            ]);
        }
    }
}
