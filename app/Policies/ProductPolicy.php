<?php

namespace App\Policies;

class ProductPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'products';
    }
}
