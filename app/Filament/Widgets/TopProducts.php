<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\BranchScoped;
use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopProducts extends BaseWidget
{
    use BranchScoped;

    protected static ?int $sort = 3;

    protected static ?string $heading = 'Top bán chạy 30 ngày';

    public function table(Table $table): Table
    {
        $orderIds = $this->completedOrders()->where('created_at', '>=', now()->subDays(30))->select('id');

        return $table
            ->query(Product::query()
                ->withSum(['orderItems as sold' => fn ($q) => $q->whereIn('order_id', $orderIds)], 'quantity')
                ->withSum(['orderItems as revenue' => fn ($q) => $q->whereIn('order_id', $orderIds)], 'total')
                ->whereHas('orderItems', fn ($q) => $q->whereIn('order_id', $orderIds))
                ->orderByDesc('sold'))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Sản phẩm')->weight('bold')->limit(30),
                Tables\Columns\TextColumn::make('sold')->label('Đã bán')->badge()->color('success'),
                Tables\Columns\TextColumn::make('revenue')->label('Doanh thu')->money('VND'),
            ])
            ->paginated([5])
            ->defaultPaginationPageOption(5);
    }
}
