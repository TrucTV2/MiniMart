<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopesToBranch;
use App\Filament\Resources\InventoryResource\Pages;
use App\Models\Inventory;
use App\Services\InventoryService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryResource extends Resource
{
    use ScopesToBranch;

    protected static ?string $model = Inventory::class;

    protected static array $eagerLoad = ['product', 'branch'];

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Kho';

    protected static ?string $modelLabel = 'tồn kho';

    protected static ?string $pluralModelLabel = 'Tồn kho';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label('Sản phẩm')->searchable()->sortable()->weight('bold')
                    ->description(fn (Inventory $r) => $r->product?->sku),
                Tables\Columns\TextColumn::make('branch.name')->label('Chi nhánh')->badge()->color('gray')
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Columns\TextColumn::make('quantity')->label('Số lượng')->numeric()->sortable()->alignCenter()->badge()
                    ->color(fn (Inventory $r) => match (true) {
                        $r->quantity <= 0 => 'danger',
                        $r->quantity <= ($r->product?->min_stock ?? 0) => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('product.unit')->label('ĐVT'),
                Tables\Columns\TextColumn::make('updated_at')->label('Cập nhật')->since()->sortable(),
            ])
            ->defaultSort('quantity')
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')->label('Chi nhánh')->relationship('branch', 'name')
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\Filter::make('low')->label('Sắp hết / hết hàng')->toggle()
                    ->query(fn (Builder $query) => $query->whereHas('product', fn (Builder $p) => $p->whereColumn('inventories.quantity', '<=', 'products.min_stock'))),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust')->label('Điều chỉnh')->icon('heroicon-o-adjustments-horizontal')
                    ->authorize(fn (Inventory $record) => auth()->user()->can('adjust', $record))
                    ->fillForm(fn (Inventory $record) => ['quantity' => $record->quantity])
                    ->form([
                        Forms\Components\TextInput::make('quantity')->label('Số lượng thực tế')->numeric()->minValue(0)->required(),
                        Forms\Components\TextInput::make('note')->label('Lý do')->required()->maxLength(255),
                    ])
                    ->action(function (Inventory $record, array $data, InventoryService $service) {
                        $diff = (int) $data['quantity'] - $record->quantity;
                        if ($diff !== 0) {
                            $service->move($record->branch_id, $record->product_id, $diff, 'adjust', note: $data['note']);
                        }
                        Notification::make()->title('Đã cập nhật tồn kho')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
        ];
    }
}
