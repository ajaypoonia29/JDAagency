<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Support\CRM\LeadAssignmentAccess;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leads.view');
    }

    public function view(
        User $user,
        Lead $lead,
    ): bool {
        return $user->can('leads.view')
            && LeadAssignmentAccess::canAccessLead(
                $user,
                $lead,
            );
    }

    public function create(User $user): bool
    {
        if (! $user->can('leads.create')) {
            return false;
        }

        return LeadAssignmentAccess::canManage($user)
            || LeadAssignmentAccess::currentActiveEmployeeId(
                $user,
            ) !== null;
    }

    public function update(
        User $user,
        Lead $lead,
    ): bool {
        return $user->can('leads.edit')
            && LeadAssignmentAccess::canAccessLead(
                $user,
                $lead,
            );
    }

    public function delete(
        User $user,
        Lead $lead,
    ): bool {
        return $user->can('leads.delete')
            && LeadAssignmentAccess::canAccessLead(
                $user,
                $lead,
            );
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('leads.delete');
    }

    public function restore(
        User $user,
        Lead $lead,
    ): bool {
        return $user->can('leads.delete')
            && LeadAssignmentAccess::canAccessLead(
                $user,
                $lead,
            );
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('leads.delete');
    }

    public function forceDelete(
        User $user,
        Lead $lead,
    ): bool {
        return $user->can('leads.delete')
            && LeadAssignmentAccess::canAccessLead(
                $user,
                $lead,
            );
    }

    public function forceDeleteAny(
        User $user,
    ): bool {
        return $user->can('leads.delete');
    }

    public function scheduleMeeting(
        User $user,
        Lead $lead,
    ): bool {
        return $user->can('leads.edit')
            && LeadAssignmentAccess::canAccessLead(
                $user,
                $lead,
            )
            && ! $lead->trashed()
            && $lead->lead_status !== 'Lost'
            && filled(
                $lead->converted_customer_id,
            );
    }
}