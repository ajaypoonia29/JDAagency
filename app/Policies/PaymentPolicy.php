<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->can('payments.verify');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->can('payments.verify');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('payments.verify');
    }

    public function restore(User $user, Payment $payment): bool
    {
        return $user->can('payments.verify');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('payments.verify');
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return $user->can('payments.verify');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('payments.verify');
    }

    public function downloadReceipt(User $user, Payment $payment): bool
    {
        return $user->can('receipts.download');
    }

    public function downloadStatement(User $user, Payment $payment): bool
    {
        return $user->can('receipts.download');
    }

    public function sendReceipt(User $user, Payment $payment): bool
    {
        return $user->can('receipts.create');
    }
}
