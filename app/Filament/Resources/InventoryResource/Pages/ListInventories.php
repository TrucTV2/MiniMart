<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Models\Branch;
use App\Models\Product;
use App\Services\InventoryService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListInventories extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        return [
            Actions\Action::make('import')->label('Nhập kho')->icon('heroicon-o-arrow-down-tray')
                ->visible($user->can('inventory.adjust'))
                ->form([
                    Forms\Components\Select::make('branch_id')->label('Chi nhánh')
                        ->options(Branch::where('is_active', true)->pluck('name', 'id'))
                        ->default($user->branch_id)->required()
                        ->visible($user->isSuperAdmin()),
                    Forms\Components\Repeater::make('items')->label('Danh sách hàng nhập')->minItems(1)->required()
                        ->columns(3)->defaultItems(1)->addActionLabel('Thêm dòng')
                        ->schema([
                            Forms\Components\Select::make('product_id')->label('Sản phẩm')->required()->searchable()
                                ->getSearchResultsUsing(fn (string $search) => Product::where('name', 'like', "%{$search}%")
                                    ->orWhere('sku', 'like', "%{$search}%")->limit(20)->pluck('name', 'id'))
                                ->getOptionLabelUsing(fn ($value) => Product::find($value)?->name)
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')->label('Số lượng')->numeric()->minValue(1)->required(),
                        ]),
                    Forms\Components\TextInput::make('note')->label('Ghi chú / Số phiếu nhập')->maxLength(255),
                ])
                ->action(function (array $data, InventoryService $service) use ($user) {
                    $branchId = $user->isSuperAdmin() ? (int) $data['branch_id'] : $user->branch_id;

                    DB::transaction(function () use ($data, $service, $branchId) {
                        foreach ($data['items'] as $item) {
                            $service->move($branchId, (int) $item['product_id'], (int) $item['quantity'], 'import', note: $data['note'] ?? null);
                        }
                    });

                    Notification::make()->title('Nhập kho thành công')->success()->send();
                }),
        ];
    }
}
