<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopesToBranch;
use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    use ScopesToBranch;

    protected static ?string $model = StockMovement::class;

    protected static array $eagerLoad = ['product', 'user', 'branch'];

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Kho';

    protected static ?string $modelLabel = 'phiếu kho';

    protected static ?string $pluralModelLabel = 'Lịch sử xuất nhập';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()->can('inventory.view');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Loại')->badge()
                    ->formatStateUsing(fn (string $state) => StockMovement::TYPES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'import' => 'success', 'sale' => 'info', 'refund' => 'warning', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('product.name')->label('Sản phẩm')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('quantity')->label('SL')->alignCenter()
                    ->formatStateUsing(fn (int $state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('balance_after')->label('Tồn sau')->alignCenter(),
                Tables\Columns\TextColumn::make('branch.name')->label('Chi nhánh')->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('user.name')->label('Người thực hiện')->toggleable(),
                Tables\Columns\TextColumn::make('note')->label('Ghi chú')->limit(40)->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Loại')->options(StockMovement::TYPES)->multiple(),
                Tables\Filters\Filter::make('created_at')->form([
                    DatePicker::make('from')->label('Từ ngày'),
                    DatePicker::make('until')->label('Đến ngày'),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
        ];
    }
}
