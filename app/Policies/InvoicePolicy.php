<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view')
            || $user->can('quotations.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('invoices.create')
            || $user->can('quotations.edit');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $invoice->status === 'Draft'
            && $this->create($user);
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.share')
            || $user->can('quotations.approve');
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $invoice->issued_at !== null
            && $invoice->status !== 'Void'
            && (
                $user->can('invoices.share')
                || $user->can('quotations.send')
            );
    }

    public function createCreditNote(User $user, Invoice $invoice): bool
    {
        return $invoice->issued_at !== null
            && $invoice->status !== 'Void'
            && $user->can('invoices.create');
    }

    public function refund(User $user, Invoice $invoice): bool
    {
        return $invoice->issued_at !== null
            && $invoice->status !== 'Void'
            && $user->can('payments.verify');
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $user->can('quotations.delete');
    }

    public function download(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.download')
            || $user->can('quotations.view');
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
