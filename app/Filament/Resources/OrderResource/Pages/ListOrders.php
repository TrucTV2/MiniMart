<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Pages\PointOfSale;
use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pos')->label('Mở màn hình bán hàng')->icon('heroicon-o-shopping-cart')
                ->url(PointOfSale::getUrl())
                ->visible(PointOfSale::canAccess()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'today' => Tab::make('Hôm nay')->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today())),
            'week' => Tab::make('7 ngày')->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7))),
            'all' => Tab::make('Tất cả'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
    }
}
