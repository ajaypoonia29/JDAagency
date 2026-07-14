<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quotations.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('quotations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('quotations.edit');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $invoice->status === 'Draft'
            && $user->can('quotations.edit');
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->can('quotations.approve');
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $user->can('quotations.delete');
    }

    public function download(User $user, Invoice $invoice): bool
    {
        return $user->can('quotations.view');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
