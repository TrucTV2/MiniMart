<?php

namespace Tests\Feature;

use App\Filament\Pages\PointOfSale;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_cashier_can_sell_through_pos_screen(): void
    {
        $cashier = User::where('email', 'cashier@pos.test')->first();
        $product = Product::first();
        $this->actingAs($cashier);

        $before = Order::count();

        Livewire::test(PointOfSale::class)
            ->call('add', $product->id)
            ->call('add', $product->id)
            ->assertSet('cart', [$product->id => 2])
            ->set('paid', 1_000_000)
            ->call('checkout')
            ->assertSet('cart', [])
            ->assertDispatched('print-receipt');

        $this->assertSame($before + 1, Order::count());
        $this->assertSame($cashier->id, Order::latest('id')->first()->user_id);
    }

    public function test_barcode_scan_adds_product(): void
    {
        $this->actingAs(User::where('email', 'cashier@pos.test')->first());
        $product = Product::first();

        Livewire::test(PointOfSale::class)
            ->set('search', $product->barcode)
            ->call('scan')
            ->assertSet('cart', [$product->id => 1])
            ->assertSet('search', '');
    }

    public function test_manager_cannot_create_super_admin(): void
    {
        $this->actingAs(User::where('email', 'manager@pos.test')->first());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Hacker', 'email' => 'x@pos.test', 'password' => 'Secret123!', 'role' => 'super_admin',
            ])
            ->call('create')
            ->assertHasFormErrors(['role']);

        $this->assertDatabaseMissing('users', ['email' => 'x@pos.test']);
    }

    public function test_manager_creates_cashier_in_own_branch(): void
    {
        $manager = User::where('email', 'manager@pos.test')->first();
        $this->actingAs($manager);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'NV mới', 'email' => 'new@pos.test', 'password' => 'Secret123!', 'role' => 'cashier'])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'new@pos.test')->first();
        $this->assertSame($manager->branch_id, $user->branch_id);
        $this->assertTrue($user->hasRole('cashier'));
    }

    public function test_order_list_filters_work(): void
    {
        $admin = User::where('email', 'admin@pos.test')->first();
        $this->actingAs($admin);

        Livewire::test(ListOrders::class)
            ->filterTable('status', 'completed')
            ->filterTable('payment_method', 'cash')
            ->filterTable('user_id', User::where('email', 'cashier@pos.test')->value('id'))
            ->filterTable('branch_id', $admin->branch_id)
            ->assertSuccessful()
            ->set('activeTab', 'today')
            ->sortTable('total')
            ->set('activeTab', 'week')
            ->call('gotoPage', 2)
            ->assertSuccessful();
    }

    public function test_manager_refunds_from_order_page(): void
    {
        $manager = User::where('email', 'manager@pos.test')->first();
        $this->actingAs($manager);
        $order = Order::where('branch_id', $manager->branch_id)->completed()->first();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('refund', ['reason' => 'Hàng lỗi'])
            ->assertHasNoActionErrors();

        $this->assertSame('refunded', $order->fresh()->status);
    }
}
