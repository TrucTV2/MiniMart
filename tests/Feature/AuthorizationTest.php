<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public static function pageAccessMatrix(): array
    {
        // [email, url, expected status]
        return [
            'admin dashboard' => ['admin@pos.test', '/admin', 200],
            'admin branches' => ['admin@pos.test', '/admin/branches', 200],
            'admin users' => ['admin@pos.test', '/admin/users', 200],
            'admin products' => ['admin@pos.test', '/admin/products', 200],
            'admin create product' => ['admin@pos.test', '/admin/products/create', 200],
            'admin orders' => ['admin@pos.test', '/admin/orders', 200],
            'admin inventories' => ['admin@pos.test', '/admin/inventories', 200],
            'admin stock movements' => ['admin@pos.test', '/admin/stock-movements', 200],
            'admin customers' => ['admin@pos.test', '/admin/customers', 200],
            'admin categories' => ['admin@pos.test', '/admin/categories', 200],
            'admin pos' => ['admin@pos.test', '/admin/pos', 200],

            'manager dashboard' => ['manager@pos.test', '/admin', 200],
            'manager users' => ['manager@pos.test', '/admin/users', 200],
            'manager no branches' => ['manager@pos.test', '/admin/branches', 403],
            'manager pos' => ['manager@pos.test', '/admin/pos', 200],

            'cashier pos' => ['cashier@pos.test', '/admin/pos', 200],
            'cashier orders' => ['cashier@pos.test', '/admin/orders', 200],
            'cashier no users' => ['cashier@pos.test', '/admin/users', 403],
            'cashier no inventory' => ['cashier@pos.test', '/admin/inventories', 403],
            'cashier no create product' => ['cashier@pos.test', '/admin/products/create', 403],

            'warehouse inventories' => ['warehouse@pos.test', '/admin/inventories', 200],
            'warehouse products' => ['warehouse@pos.test', '/admin/products', 200],
            'warehouse no pos' => ['warehouse@pos.test', '/admin/pos', 403],
            'warehouse no orders' => ['warehouse@pos.test', '/admin/orders', 403],
        ];
    }

    #[DataProvider('pageAccessMatrix')]
    public function test_role_page_access(string $email, string $url, int $status): void
    {
        $this->actingAs($this->user($email))->get($url)->assertStatus($status);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/products')->assertRedirect('/admin/login');
        $this->get(route('orders.receipt', Order::first()))->assertRedirect('/admin/login');
    }

    public function test_inactive_user_cannot_access_panel(): void
    {
        $user = $this->user('cashier@pos.test');
        $user->update(['is_active' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_cashier_only_sees_own_orders_and_manager_sees_branch(): void
    {
        $cashier = $this->user('cashier@pos.test');
        $manager = $this->user('manager@pos.test');

        $this->assertTrue(Order::visibleTo($cashier)->get()->every(fn ($o) => $o->user_id === $cashier->id));
        $this->assertTrue(Order::visibleTo($manager)->get()->every(fn ($o) => $o->branch_id === $manager->branch_id));
        $this->assertGreaterThan(Order::visibleTo($manager)->count(), Order::visibleTo($this->user('admin@pos.test'))->count());
    }

    public function test_receipt_access_respects_branch(): void
    {
        $hnOrder = Order::where('branch_id', '!=', $this->user('manager@pos.test')->branch_id)->firstOrFail();

        $this->actingAs($this->user('manager@pos.test'))->get(route('orders.receipt', $hnOrder))->assertForbidden();
        $this->actingAs($this->user('manager.hn@pos.test'))->get(route('orders.receipt', $hnOrder))->assertOk()->assertSee($hnOrder->code);
    }

    public function test_only_manager_can_refund(): void
    {
        $cashier = $this->user('cashier@pos.test');
        $order = Order::where('user_id', $cashier->id)->completed()->firstOrFail();

        $this->assertFalse($cashier->can('refund', $order));
        $this->assertTrue($this->user('manager@pos.test')->can('refund', $order));
        $this->assertFalse($this->user('manager.hn@pos.test')->can('refund', $order));
    }
}
