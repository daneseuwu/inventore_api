<?php

namespace App\Policies;

use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, mixed $model = null): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isEmployee();
    }

    public function update(User $user, mixed $model = null): bool
    {
        return $user->isAdmin() || ($user->isEmployee() && is_object($model) && method_exists($model, 'isDraft') && $model->isDraft());
    }

    public function delete(User $user, mixed $model = null): bool
    {
        return is_object($model) && method_exists($model, 'isDraft') && $model->isDraft() && ($user->isAdmin() || $user->isEmployee());
    }

    public function confirm(User $user, mixed $model = null): bool
    {
        return $user->isAdmin() || $user->isEmployee();
    }
}
