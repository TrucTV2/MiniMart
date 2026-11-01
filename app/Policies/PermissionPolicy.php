<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy chung map các ability của Filament sang permission dạng "{prefix}.{action}".
 */
abstract class PermissionPolicy
{
    abstract protected function prefix(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->prefix().'.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can($this->prefix().'.view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->prefix().'.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->prefix().'.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->prefix().'.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can($this->prefix().'.delete');
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->can($this->prefix().'.delete');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can($this->prefix().'.delete');
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
