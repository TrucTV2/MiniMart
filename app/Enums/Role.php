<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Manager = 'manager';
    case Cashier = 'cashier';
    case Warehouse = 'warehouse';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Quản trị hệ thống',
            self::Manager => 'Quản lý cửa hàng',
            self::Cashier => 'Thu ngân',
            self::Warehouse => 'Thủ kho',
        };
    }

    /**
     * Ma trận quyền: vai trò => danh sách permission.
     * Super admin được cấp toàn quyền qua Gate::before nên không cần liệt kê.
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [],
            self::Manager => [
                'pos.access',
                'products.view', 'products.create', 'products.update', 'products.delete',
                'categories.view', 'categories.create', 'categories.update', 'categories.delete',
                'inventory.view', 'inventory.adjust',
                'customers.view', 'customers.create', 'customers.update',
                'orders.view', 'orders.view_all', 'orders.refund',
                'users.view', 'users.create', 'users.update',
                'reports.view',
            ],
            self::Cashier => [
                'pos.access',
                'products.view',
                'customers.view', 'customers.create',
                'orders.view',
            ],
            self::Warehouse => [
                'products.view', 'products.create', 'products.update',
                'categories.view',
                'inventory.view', 'inventory.adjust',
            ],
        };
    }

    public static function allPermissions(): array
    {
        return collect(self::cases())
            ->flatMap(fn (self $role) => $role->permissions())
            ->unique()
            ->values()
            ->all();
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $r) => [$r->value => $r->label()])->all();
    }
}
