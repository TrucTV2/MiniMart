<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Hàng hóa';

    protected static ?string $modelLabel = 'danh mục';

    protected static ?string $pluralModelLabel = 'Danh mục';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Tên danh mục')->required()->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
            Forms\Components\TextInput::make('slug')->label('Đường dẫn (slug)')->required()->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('description')->label('Mô tả')->columnSpanFull(),
            Forms\Components\Toggle::make('is_visible')->label('Hiển thị trên website')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Tên')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('slug')->color('gray')->toggleable(),
                Tables\Columns\TextColumn::make('products_count')->counts('products')->label('Số sản phẩm')->badge()->sortable(),
                Tables\Columns\IconColumn::make('is_visible')->label('Hiển thị')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('Cập nhật')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_visible')->label('Hiển thị'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategories::route('/'),
        ];
    }
}
