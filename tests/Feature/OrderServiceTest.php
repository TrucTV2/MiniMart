<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $cashier;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashier = User::where('email', 'cashier@pos.test')->firstOrFail();
        $this->service = app(OrderService::class);
    }

    private function stock(Product $product): int
    {
        return Inventory::where('branch_id', $this->cashier->branch_id)->where('product_id', $product->id)->value('quantity');
    }

    public function test_checkout_creates_order_decrements_stock_and_adds_points(): void
    {
        $product = Product::first();
        $customer = Customer::first();
        $before = $this->stock($product);
        $pointsBefore = $customer->points;

        $order = $this->service->checkout($this->cashier, [$product->id => 2], $customer->id, discount: 1000, paid: 1_000_000);

        $this->assertSame($product->price * 2 - 1000, $order->total);
        $this->assertSame($before - 2, $this->stock($product));
        $this->assertDatabaseHas('stock_movements', ['reference_id' => $order->id, 'type' => 'sale', 'quantity' => -2]);
        $this->assertSame($pointsBefore + intdiv($order->total, OrderService::POINT_RATE), $customer->fresh()->points);
    }

    public function test_cannot_sell_more_than_stock(): void
    {
        $product = Product::first();
        $before = $this->stock($product);

        $this->expectException(InsufficientStockException::class);

        try {
            $this->service->checkout($this->cashier, [$product->id => $before + 1]);
        } finally {
            // Transaction rollback: tồn kho không đổi, không có đơn rác.
            $this->assertSame($before, $this->stock($product));
        }
    }

    public function test_price_comes_from_database_and_discount_is_capped(): void
    {
        $product = Product::first();

        $order = $this->service->checkout($this->cashier, [$product->id => 1], discount: 999_999_999);

        $this->assertSame($product->price, $order->subtotal);
        $this->assertSame(0, $order->total);
    }

    public function test_rejects_insufficient_cash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->checkout($this->cashier, [Product::first()->id => 1], paid: 1);
    }

    public function test_refund_restores_stock_and_cannot_refund_twice(): void
    {
        $product = Product::first();
        $order = $this->service->checkout($this->cashier, [$product->id => 3]);
        $afterSale = $this->stock($product);

        $manager = User::where('email', 'manager@pos.test')->first();
        $this->service->refund($order, $manager, 'Khách đổi ý');

        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame($afterSale + 3, $this->stock($product));

        $this->expectException(InvalidArgumentException::class);
        $this->service->refund($order, $manager, 'lần 2');
    }
}
