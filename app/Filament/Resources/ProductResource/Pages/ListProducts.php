<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Thêm sản phẩm')];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả'),
            'active' => Tab::make('Đang bán')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'inactive' => Tab::make('Ngừng bán')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
        ];
    }
}
