<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\BranchScoped;
use App\Models\OrderItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    use BranchScoped;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = (clone $this->completedOrders())->whereDate('created_at', today());
        $yesterday = (clone $this->completedOrders())->whereDate('created_at', today()->subDay());

        $revenueToday = (int) $today->sum('total');
        $revenueYesterday = (int) $yesterday->sum('total');
        $ordersToday = $today->count();

        $profitToday = (int) OrderItem::query()
            ->whereIn('order_id', (clone $this->completedOrders())->whereDate('created_at', today())->select('id'))
            ->sum(DB::raw('total - cost_price * quantity'));

        // Doanh thu 7 ngày cho sparkline
        $trend = collect(range(6, 0))->map(fn ($d) => (int) (clone $this->completedOrders())
            ->whereDate('created_at', today()->subDays($d))->sum('total'))->all();

        $change = $revenueYesterday > 0 ? round(($revenueToday - $revenueYesterday) / $revenueYesterday * 100, 1) : null;

        $monthRevenue = (int) (clone $this->completedOrders())
            ->whereBetween('created_at', [now()->startOfMonth(), now()])->sum('total');

        return [
            Stat::make('Doanh thu hôm nay', $this->vnd($revenueToday))
                ->description($change === null ? 'Chưa có dữ liệu hôm qua' : ($change >= 0 ? "+{$change}%" : "{$change}%").' so với hôm qua')
                ->descriptionIcon($change !== null && $change < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($change !== null && $change < 0 ? 'danger' : 'success')
                ->chart($trend),
            Stat::make('Đơn hàng hôm nay', number_format($ordersToday))
                ->description('Giá trị TB: '.$this->vnd($ordersToday ? intdiv($revenueToday, $ordersToday) : 0))
                ->descriptionIcon('heroicon-m-shopping-bag'),
            Stat::make('Lợi nhuận gộp hôm nay', $this->vnd($profitToday))
                ->description($revenueToday ? 'Biên LN: '.round($profitToday / $revenueToday * 100, 1).'%' : '—')
                ->color('warning'),
            Stat::make('Doanh thu tháng này', $this->vnd($monthRevenue))
                ->descriptionIcon('heroicon-m-calendar'),
        ];
    }

    private function vnd(int $value): string
    {
        return number_format($value, 0, ',', '.').'đ';
    }
}
