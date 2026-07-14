<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CreditNote;
use App\Models\User;

class CreditNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, CreditNote $creditNote): bool
    {
        return $user->can('invoices.view');
    }

    public function create(User $user): bool
    {
        return $user->can('invoices.create');
    }

    public function issue(User $user, CreditNote $creditNote): bool
    {
        return $creditNote->status === 'Draft'
            && $user->can('invoices.share');
    }

    public function void(User $user, CreditNote $creditNote): bool
    {
        return $creditNote->status === 'Issued'
            && $user->can('invoices.create');
    }

    public function download(User $user, CreditNote $creditNote): bool
    {
        return $user->can('invoices.download');
    }

    public function update(User $user, CreditNote $creditNote): bool
    {
        return false;
    }

    public function delete(User $user, CreditNote $creditNote): bool
    {
        return false;
    }

    public function restore(User $user, CreditNote $creditNote): bool
    {
        return false;
    }

    public function forceDelete(User $user, CreditNote $creditNote): bool
    {
        return false;
    }
}
