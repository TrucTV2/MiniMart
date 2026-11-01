<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Hàng hóa';

    protected static ?string $modelLabel = 'sản phẩm';

    protected static ?string $pluralModelLabel = 'Sản phẩm';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'barcode'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->columnSpan(['lg' => 2])->schema([
                Forms\Components\Section::make('Thông tin sản phẩm')->columns(2)->schema([
                    Forms\Components\TextInput::make('name')->label('Tên sản phẩm')->required()->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation) {
                            if ($operation === 'create') {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('sku')->label('Mã SKU')->required()->maxLength(50)->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('barcode')->label('Mã vạch')->maxLength(50)->unique(ignoreRecord: true),
                    Forms\Components\Select::make('category_id')->label('Danh mục')
                        ->relationship('category', 'name')->searchable()->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('Tên danh mục')->required(),
                        ]),
                    Forms\Components\TextInput::make('unit')->label('Đơn vị tính')->default('cái')->required()->maxLength(20),
                    Forms\Components\RichEditor::make('description')->label('Mô tả')->columnSpanFull(),
                ]),
                Forms\Components\Section::make('Giá & tồn kho')->columns(3)->schema([
                    Forms\Components\TextInput::make('cost_price')->label('Giá vốn')->numeric()->minValue(0)->default(0)->suffix('đ'),
                    Forms\Components\TextInput::make('price')->label('Giá bán')->numeric()->required()->minValue(0)->suffix('đ')
                        ->gte('cost_price')->validationMessages(['gte' => 'Giá bán phải lớn hơn hoặc bằng giá vốn.']),
                    Forms\Components\TextInput::make('min_stock')->label('Cảnh báo khi tồn dưới')->numeric()->minValue(0)->default(5),
                ]),
                Forms\Components\Section::make('SEO')->description('Hiển thị trên trang catalog công khai')->collapsible()->schema([
                    Forms\Components\TextInput::make('slug')->label('Đường dẫn (slug)')->required()->unique(ignoreRecord: true)
                        ->prefix(url('/san-pham').'/'),
                    Forms\Components\TextInput::make('meta_title')->label('Meta title')->maxLength(70)
                        ->helperText('Tối ưu 50–60 ký tự. Để trống sẽ dùng tên sản phẩm.'),
                    Forms\Components\Textarea::make('meta_description')->label('Meta description')->maxLength(160)->rows(2)
                        ->helperText('Tối ưu 120–160 ký tự.'),
                ]),
            ]),
            Forms\Components\Group::make()->columnSpan(['lg' => 1])->schema([
                Forms\Components\Section::make('Trạng thái')->schema([
                    Forms\Components\Toggle::make('is_active')->label('Đang kinh doanh')->default(true),
                    Forms\Components\Toggle::make('is_published')->label('Hiển thị trên website'),
                ]),
                Forms\Components\Section::make('Hình ảnh')->schema([
                    Forms\Components\FileUpload::make('image')->hiddenLabel()->image()->imageEditor()
                        ->disk('public')->directory('products')->maxSize(2048),
                ]),
            ]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('category')
                ->withSum(['inventories as stock' => fn (Builder $q) => $user->isSuperAdmin() ? $q : $q->where('branch_id', $user->branch_id)], 'quantity'))
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')->disk('public')->square()->size(44)
                    ->defaultImageUrl(fn (Product $record) => 'https://ui-avatars.com/api/?background=f59e0b&color=fff&name='.urlencode($record->name)),
                Tables\Columns\TextColumn::make('name')->label('Sản phẩm')->searchable(['name', 'sku', 'barcode'])->sortable()
                    ->weight('bold')->description(fn (Product $record) => $record->sku)->wrap(),
                Tables\Columns\TextColumn::make('category.name')->label('Danh mục')->badge()->color('gray')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('cost_price')->label('Giá vốn')->money('VND')->sortable()->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => auth()->user()->can('reports.view')),
                Tables\Columns\TextColumn::make('price')->label('Giá bán')->money('VND')->sortable()->alignEnd(),
                Tables\Columns\TextColumn::make('stock')->label('Tồn kho')->numeric()->sortable()->alignCenter()->badge()
                    ->formatStateUsing(fn ($state) => (int) $state)
                    ->color(fn (Product $record) => match (true) {
                        (int) $record->stock <= 0 => 'danger',
                        (int) $record->stock <= $record->min_stock => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\ToggleColumn::make('is_active')->label('Kinh doanh')
                    ->disabled(fn () => ! auth()->user()->can('products.update')),
                Tables\Columns\IconColumn::make('is_published')->label('Web')->boolean()->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Cập nhật')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')->label('Danh mục')->relationship('category', 'name')->multiple()->preload(),
                Tables\Filters\TernaryFilter::make('is_active')->label('Đang kinh doanh'),
                Tables\Filters\TernaryFilter::make('is_published')->label('Hiển thị web'),
                Tables\Filters\Filter::make('low_stock')->label('Sắp hết hàng')->toggle()
                    ->query(fn (Builder $query) => $query->whereRaw(
                        '(SELECT COALESCE(SUM(quantity), 0) FROM inventories WHERE inventories.product_id = products.id'
                        .($user->isSuperAdmin() ? '' : ' AND inventories.branch_id = '.(int) $user->branch_id)
                        .') <= products.min_stock'
                    )),
                Tables\Filters\TrashedFilter::make()->label('Đã xóa'),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('view_web')->label('Xem trên web')->icon('heroicon-o-globe-alt')
                        ->url(fn (Product $record) => route('catalog.show', $record), shouldOpenInNewTab: true)
                        ->visible(fn (Product $record) => $record->is_published),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')->label('Hiển thị lên web')->icon('heroicon-o-eye')
                        ->action(fn ($records) => $records->each->update(['is_published' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()->can('products.update')),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
