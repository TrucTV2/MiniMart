<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')->label('In hóa đơn')->icon('heroicon-o-printer')->color('gray')
                ->url(fn () => route('orders.receipt', $this->record), shouldOpenInNewTab: true),
            OrderResource::refundAction(Actions\Action::make('refund'))
                ->after(fn () => $this->refreshFormData(['status'])),
        ];
    }
}
