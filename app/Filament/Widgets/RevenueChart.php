<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\BranchScoped;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    use BranchScoped;

    protected static ?string $heading = 'Doanh thu';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return ['7' => '7 ngày', '30' => '30 ngày', '90' => '90 ngày'];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $from = today()->subDays($days - 1);

        // Gom nhóm trong PHP để chạy được trên cả SQLite và MySQL.
        $rows = $this->completedOrders()
            ->where('created_at', '>=', $from)
            ->get(['total', 'created_at'])
            ->groupBy(fn ($o) => $o->created_at->format('Y-m-d'))
            ->map->sum('total');

        $labels = [];
        $data = [];
        for ($d = $from->copy(); $d->lte(today()); $d->addDay()) {
            $labels[] = $d->format('d/m');
            $data[] = (int) ($rows[$d->format('Y-m-d')] ?? 0);
        }

        return [
            'datasets' => [[
                'label' => 'Doanh thu (đ)',
                'data' => $data,
                'fill' => true,
                'borderColor' => '#f59e0b',
                'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                'tension' => 0.35,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
