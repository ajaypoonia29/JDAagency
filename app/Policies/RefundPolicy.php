<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    public function view(User $user, Refund $refund): bool
    {
        return $user->can('payments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payments.verify');
    }

    public function cancel(User $user, Refund $refund): bool
    {
        return $refund->status === 'Processed'
            && $user->can('payments.verify');
    }

    public function download(User $user, Refund $refund): bool
    {
        return $user->can('receipts.download');
    }

    public function update(User $user, Refund $refund): bool
    {
        return false;
    }

    public function delete(User $user, Refund $refund): bool
    {
        return false;
    }

    public function restore(User $user, Refund $refund): bool
    {
        return false;
    }

    public function forceDelete(User $user, Refund $refund): bool
    {
        return false;
    }
}
