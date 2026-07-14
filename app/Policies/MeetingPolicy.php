<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leads.view');
    }

    public function view(User $user, Meeting $meeting): bool
    {
        return $user->can('leads.view');
    }

    public function create(User $user): bool
    {
        return $user->can('leads.edit');
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->can('leads.edit');
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->can('leads.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('leads.delete');
    }

    public function restore(User $user, Meeting $meeting): bool
    {
        return $user->can('leads.delete');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('leads.delete');
    }

    public function forceDelete(User $user, Meeting $meeting): bool
    {
        return $user->can('leads.delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('leads.delete');
    }
}
