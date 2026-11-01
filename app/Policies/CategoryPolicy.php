<?php

namespace App\Policies;

class CategoryPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'categories';
    }
}
