<?php

namespace App\Policies;

/** Chỉ super admin (Gate::before) quản lý chi nhánh. */
class BranchPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'branches';
    }
}
