<?php

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Hệ thống';

    protected static ?string $modelLabel = 'nhân viên';

    protected static ?string $pluralModelLabel = 'Nhân viên';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->with(['roles', 'branch'])
            ->when(! $user->isSuperAdmin(), fn (Builder $q) => $q->where('branch_id', $user->branch_id));
    }

    /** Vai trò người dùng hiện tại được phép gán. */
    protected static function assignableRoles(): array
    {
        $options = Role::options();

        return auth()->user()->isSuperAdmin()
            ? $options
            : array_intersect_key($options, array_flip([Role::Cashier->value, Role::Warehouse->value]));
    }

    public static function form(Form $form): Form
    {
        $isAdmin = auth()->user()->isSuperAdmin();

        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Họ tên')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->label('Điện thoại')->tel()->maxLength(20),
                Forms\Components\TextInput::make('password')->label('Mật khẩu')->password()->revealable()
                    ->rule(Password::defaults())
                    ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Để trống nếu không đổi.' : null),
                Forms\Components\Select::make('branch_id')->label('Chi nhánh')->relationship('branch', 'name')
                    ->required(! $isAdmin)->visible($isAdmin),
                Forms\Components\Select::make('role')->label('Vai trò')->options(static::assignableRoles())->required()
                    ->afterStateHydrated(fn ($component, ?User $record) => $component->state($record?->roles->first()?->name))
                    ->dehydrated(false),
                Forms\Components\Toggle::make('is_active')->label('Cho phép đăng nhập')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nhân viên')->searchable()->sortable()->weight('bold')
                    ->description(fn (User $r) => $r->email),
                Tables\Columns\TextColumn::make('roles.name')->label('Vai trò')->badge()
                    ->formatStateUsing(fn (string $state) => Role::tryFrom($state)?->label() ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'super_admin' => 'danger', 'manager' => 'warning', 'cashier' => 'success', default => 'info',
                    }),
                Tables\Columns\TextColumn::make('branch.name')->label('Chi nhánh')->default('—'),
                Tables\Columns\TextColumn::make('phone')->label('Điện thoại')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label('Hoạt động')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Ngày tạo')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')->label('Vai trò')->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => Role::tryFrom($record->name)?->label() ?? $record->name),
                Tables\Filters\SelectFilter::make('branch_id')->label('Chi nhánh')->relationship('branch', 'name')
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                Tables\Filters\TernaryFilter::make('is_active')->label('Hoạt động'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
