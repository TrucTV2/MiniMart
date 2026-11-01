<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, Inventory $inventory): bool
    {
        return $user->can('inventory.view') && $inventory->branch_id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Inventory $inventory): bool
    {
        return false;
    }

    public function delete(User $user, Inventory $inventory): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function adjust(User $user, Inventory $inventory): bool
    {
        return $user->can('inventory.adjust') && $inventory->branch_id === $user->branch_id;
    }
}
