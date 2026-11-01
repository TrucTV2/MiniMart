<?php

namespace App\Filament\Resources\UserResource\Pages\Concerns;

use App\Enums\Role;
use Illuminate\Validation\ValidationException;

trait SyncsRole
{
    /** Kiểm tra phía server trước khi lưu: quản lý không thể nâng quyền hoặc chuyển nhân viên sang chi nhánh khác. */
    protected function prepareData(array $data): array
    {
        $actor = auth()->user();

        $allowed = $actor->isSuperAdmin()
            ? array_column(Role::cases(), 'value')
            : [Role::Cashier->value, Role::Warehouse->value];

        if (! in_array($this->data['role'] ?? null, $allowed, true)) {
            throw ValidationException::withMessages(['data.role' => 'Bạn không được gán vai trò này.']);
        }

        if (! $actor->isSuperAdmin()) {
            $data['branch_id'] = $actor->branch_id;
        }

        return $data;
    }

    protected function syncRole(): void
    {
        $this->record->syncRoles([$this->data['role']]);
    }
}
