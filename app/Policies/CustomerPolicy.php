<?php

namespace App\Policies;

class CustomerPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'customers';
    }
}
