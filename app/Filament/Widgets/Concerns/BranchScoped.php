<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

trait BranchScoped
{
    public static function canView(): bool
    {
        return auth()->user()->can('reports.view');
    }

    protected function completedOrders(): Builder
    {
        $user = auth()->user();

        return Order::query()->completed()
            ->when(! $user->isSuperAdmin(), fn (Builder $q) => $q->where('orders.branch_id', $user->branch_id));
    }
}
