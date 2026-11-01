<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use Concerns\SyncsRole;

    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->prepareData($data);
    }

    protected function afterSave(): void
    {
        $this->syncRole();
    }
}
