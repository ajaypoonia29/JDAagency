<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leads.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->can('leads.view');
    }

    public function create(User $user): bool
    {
        return $user->can('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('leads.edit');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('leads.delete');
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('leads.delete');
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('leads.delete');
    }

    public function scheduleMeeting(User $user, Lead $lead): bool
    {
        return $user->can('leads.edit')
            && ! $lead->trashed()
            && $lead->lead_status !== 'Lost'
            && filled($lead->converted_customer_id);
    }
}
