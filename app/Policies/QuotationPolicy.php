<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quotations.view');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('quotations.create');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.edit');
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('quotations.delete');
    }

    public function restore(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.delete');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('quotations.delete');
    }

    public function forceDelete(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('quotations.delete');
    }

    public function approve(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.approve');
    }

    public function send(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.send');
    }

    public function receivePayment(User $user, Quotation $quotation): bool
    {
        return $user->can('payments.create');
    }

    public function download(User $user, Quotation $quotation): bool
    {
        return $user->can('quotations.view');
    }
}
