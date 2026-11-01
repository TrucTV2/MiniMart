<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Bán hàng';

    protected static ?string $modelLabel = 'đơn hàng';

    protected static ?string $pluralModelLabel = 'Đơn hàng';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->visibleTo(auth()->user())
            ->with(['customer', 'cashier', 'branch'])
            ->withCount('items');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->whereDate('created_at', today())->count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Mã đơn')->searchable()->sortable()->weight('bold')->copyable(),
                Tables\Columns\TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Khách hàng')->default('Khách lẻ')
                    ->description(fn (Order $r) => $r->customer?->phone)->searchable(),
                Tables\Columns\TextColumn::make('items_count')->label('SL mặt hàng')->alignCenter(),
                Tables\Columns\TextColumn::make('total')->label('Tổng tiền')->money('VND')->sortable()->alignEnd()->weight('bold')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('VND')->label('Tổng')),
                Tables\Columns\TextColumn::make('payment_method')->label('Thanh toán')->badge()
                    ->formatStateUsing(fn (string $state) => Order::PAYMENT_METHODS[$state] ?? $state)->color('gray'),
                Tables\Columns\TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success', 'refunded' => 'warning', default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('cashier.name')->label('Thu ngân')->toggleable(),
                Tables\Columns\TextColumn::make('branch.name')->label('Chi nhánh')->toggleable()
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Trạng thái')->options(Order::STATUSES),
                Tables\Filters\SelectFilter::make('payment_method')->label('Thanh toán')->options(Order::PAYMENT_METHODS),
                Tables\Filters\SelectFilter::make('user_id')->label('Thu ngân')
                    ->options(fn () => User::query()
                        ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->where('branch_id', auth()->user()->branch_id))
                        ->pluck('name', 'id'))
                    ->visible(fn () => auth()->user()->can('orders.view_all')),
                Tables\Filters\SelectFilter::make('branch_id')->label('Chi nhánh')->relationship('branch', 'name')
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\Filter::make('created_at')->form([
                    Forms\Components\DatePicker::make('from')->label('Từ ngày'),
                    Forms\Components\DatePicker::make('until')->label('Đến ngày'),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d)))
                    ->indicateUsing(fn (array $data) => array_filter([
                        ! empty($data['from']) ? 'Từ '.Carbon::parse($data['from'])->format('d/m/Y') : null,
                        ! empty($data['until']) ? 'Đến '.Carbon::parse($data['until'])->format('d/m/Y') : null,
                    ])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('print')->label('In')->icon('heroicon-o-printer')->color('gray')
                    ->url(fn (Order $r) => route('orders.receipt', $r), shouldOpenInNewTab: true),
                static::refundAction(Tables\Actions\Action::make('refund')),
            ])
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Thông tin đơn hàng')->columns(4)->schema([
                Infolists\Components\TextEntry::make('code')->label('Mã đơn')->weight('bold')->copyable(),
                Infolists\Components\TextEntry::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i'),
                Infolists\Components\TextEntry::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'completed' ? 'success' : 'warning'),
                Infolists\Components\TextEntry::make('payment_method')->label('Thanh toán')
                    ->formatStateUsing(fn (string $state) => Order::PAYMENT_METHODS[$state] ?? $state),
                Infolists\Components\TextEntry::make('customer.name')->label('Khách hàng')->default('Khách lẻ'),
                Infolists\Components\TextEntry::make('cashier.name')->label('Thu ngân'),
                Infolists\Components\TextEntry::make('branch.name')->label('Chi nhánh'),
                Infolists\Components\TextEntry::make('note')->label('Ghi chú')->default('—'),
            ]),
            Infolists\Components\Section::make('Sản phẩm')->schema([
                Infolists\Components\RepeatableEntry::make('items')->hiddenLabel()->columns(4)->schema([
                    Infolists\Components\TextEntry::make('product_name')->label('Sản phẩm')->weight('bold'),
                    Infolists\Components\TextEntry::make('quantity')->label('SL'),
                    Infolists\Components\TextEntry::make('price')->label('Đơn giá')->money('VND'),
                    Infolists\Components\TextEntry::make('total')->label('Thành tiền')->money('VND'),
                ]),
            ]),
            Infolists\Components\Section::make()->columns(4)->schema([
                Infolists\Components\TextEntry::make('subtotal')->label('Tạm tính')->money('VND'),
                Infolists\Components\TextEntry::make('discount')->label('Giảm giá')->money('VND'),
                Infolists\Components\TextEntry::make('total')->label('Tổng cộng')->money('VND')->weight('bold')->size('lg')->color('primary'),
                Infolists\Components\TextEntry::make('paid')->label('Khách trả')->money('VND'),
            ]),
        ]);
    }

    /** Dùng chung cho Table action và Page action. */
    public static function refundAction($action)
    {
        return $action->label('Hoàn trả')->icon('heroicon-o-arrow-uturn-left')->color('danger')
            ->authorize(fn (Order $record) => auth()->user()->can('refund', $record))
            ->requiresConfirmation()
            ->modalDescription('Hàng sẽ được nhập lại kho và trừ điểm khách hàng. Không thể hoàn tác.')
            ->form([Forms\Components\TextInput::make('reason')->label('Lý do hoàn trả')->required()->maxLength(200)])
            ->action(function (Order $record, array $data, OrderService $service) {
                $service->refund($record, auth()->user(), $data['reason']);
                Notification::make()->title("Đã hoàn trả đơn {$record->code}")->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
