<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view')
            && $order->branch_id === $user->branch_id
            && ($user->can('orders.view_all') || $order->user_id === $user->id);
    }

    // Đơn hàng chỉ tạo qua màn hình bán hàng, không sửa/xóa để đảm bảo toàn vẹn sổ sách.
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Order $order): bool
    {
        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->can('orders.refund')
            && $order->status === 'completed'
            && $order->branch_id === $user->branch_id;
    }
}
