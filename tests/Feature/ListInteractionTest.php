<?php

namespace Tests\Feature;

use App\Filament\Resources;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Bấm thử mọi tab và bộ lọc trên các trang danh sách.
 * Filament truyền tham số closure theo TÊN ($query), đặt sai tên sẽ lỗi khi re-render.
 */
class ListInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public static function interactions(): array
    {
        $today = now()->toDateString();

        return [
            'orders tab today' => [Resources\OrderResource\Pages\ListOrders::class, 'tab', 'today'],
            'orders tab week' => [Resources\OrderResource\Pages\ListOrders::class, 'tab', 'week'],
            'orders date filter' => [Resources\OrderResource\Pages\ListOrders::class, 'filter', ['created_at', ['from' => $today, 'until' => $today]]],
            'products tab active' => [Resources\ProductResource\Pages\ListProducts::class, 'tab', 'active'],
            'products tab inactive' => [Resources\ProductResource\Pages\ListProducts::class, 'tab', 'inactive'],
            'products low stock' => [Resources\ProductResource\Pages\ListProducts::class, 'filter', ['low_stock', true]],
            'inventory low' => [Resources\InventoryResource\Pages\ListInventories::class, 'filter', ['low', true]],
            'movements date' => [Resources\StockMovementResource\Pages\ListStockMovements::class, 'filter', ['created_at', ['from' => $today]]],
            'customers vip' => [Resources\CustomerResource\Pages\ManageCustomers::class, 'filter', ['vip', true]],
        ];
    }

    #[DataProvider('interactions')]
    public function test_list_interaction(string $page, string $type, mixed $value): void
    {
        $this->actingAs(User::where('email', 'admin@pos.test')->first());

        $lw = Livewire::test($page);
        $type === 'tab' ? $lw->set('activeTab', $value) : $lw->filterTable(...$value);
        $lw->assertSuccessful();
    }
}
