<?php

namespace App\Filament\Widgets;

use App\Models\Inventory;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStock extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Sắp hết hàng';

    public static function canView(): bool
    {
        return auth()->user()->can('inventory.view');
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->query(Inventory::query()
                ->with(['product', 'branch'])
                ->join('products', 'products.id', '=', 'inventories.product_id')
                ->whereNull('products.deleted_at')
                ->where('products.is_active', true)
                ->whereColumn('inventories.quantity', '<=', 'products.min_stock')
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('inventories.branch_id', $user->branch_id))
                ->select('inventories.*')
                ->orderBy('inventories.quantity'))
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label('Sản phẩm')->weight('bold')->limit(30),
                Tables\Columns\TextColumn::make('branch.name')->label('Chi nhánh')->visible($user->isSuperAdmin()),
                Tables\Columns\TextColumn::make('quantity')->label('Còn')->badge()
                    ->color(fn (int $state) => $state <= 0 ? 'danger' : 'warning'),
            ])
            ->paginated([5])
            ->defaultPaginationPageOption(5);
    }
}
