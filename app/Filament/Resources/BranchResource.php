<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Hệ thống';

    protected static ?string $modelLabel = 'chi nhánh';

    protected static ?string $pluralModelLabel = 'Chi nhánh';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('code')->label('Mã')->required()->maxLength(20)->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')->label('Tên chi nhánh')->required()->maxLength(255),
                Forms\Components\TextInput::make('phone')->label('Điện thoại')->tel()->maxLength(20),
                Forms\Components\TextInput::make('address')->label('Địa chỉ')->maxLength(255),
                Forms\Components\Toggle::make('is_active')->label('Đang hoạt động')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Mã')->badge()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Tên')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('address')->label('Địa chỉ')->toggleable(),
                Tables\Columns\TextColumn::make('phone')->label('Điện thoại'),
                Tables\Columns\TextColumn::make('users_count')->counts('users')->label('Nhân viên')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Hoạt động')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Trạng thái'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBranches::route('/'),
        ];
    }
}
