<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.edit');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('customers.delete');
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('customers.delete');
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('customers.delete');
    }
}
