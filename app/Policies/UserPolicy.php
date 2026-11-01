<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('users.view') && $this->sameBranchNonAdmin($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.update') && $this->sameBranchNonAdmin($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    /** Quản lý chỉ quản lý nhân viên chi nhánh mình và không đụng tới tài khoản quản trị/quản lý khác. */
    private function sameBranchNonAdmin(User $user, User $target): bool
    {
        return $target->branch_id === $user->branch_id
            && ! $target->hasAnyRole([Role::SuperAdmin->value, Role::Manager->value]);
    }
}
