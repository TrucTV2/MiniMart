<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Giới hạn dữ liệu resource theo chi nhánh của người dùng (trừ super admin).
 */
trait ScopesToBranch
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(static::$eagerLoad ?? []);
        $user = auth()->user();

        return $user->isSuperAdmin()
            ? $query
            : $query->where($query->getModel()->qualifyColumn('branch_id'), $user->branch_id);
    }
}
