<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Bán hàng';

    protected static ?string $modelLabel = 'khách hàng';

    protected static ?string $pluralModelLabel = 'Khách hàng';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Họ tên')->required()->maxLength(255),
            Forms\Components\TextInput::make('phone')->label('Số điện thoại')->tel()->required()->maxLength(20)
                ->regex('/^(0|\+84)[0-9]{9,10}$/')->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('email')->email()->maxLength(255),
            Forms\Components\TextInput::make('address')->label('Địa chỉ')->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Khách hàng')->searchable()->sortable()->weight('bold')
                    ->description(fn (Customer $r) => $r->email),
                Tables\Columns\TextColumn::make('phone')->label('Điện thoại')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('orders_count')->counts('orders')->label('Số đơn')->sortable()->alignCenter(),
                Tables\Columns\TextColumn::make('total_spent')->label('Tổng chi tiêu')->money('VND')->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('points')->label('Điểm')->badge()->color('warning')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Ngày tạo')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('total_spent', 'desc')
            ->filters([
                Tables\Filters\Filter::make('vip')->label('Khách VIP (≥ 5 triệu)')->toggle()
                    ->query(fn (Builder $query) => $query->where('total_spent', '>=', 5_000_000)),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCustomers::route('/'),
        ];
    }
}
