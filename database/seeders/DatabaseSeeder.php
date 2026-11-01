<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(InventoryService $inventory, OrderService $orders): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleEnum::allPermissions() as $name) {
            Permission::findOrCreate($name, 'web');
        }
        foreach (RoleEnum::cases() as $roleEnum) {
            Role::findOrCreate($roleEnum->value, 'web')->syncPermissions($roleEnum->permissions());
        }

        $hcm = Branch::create(['code' => 'HCM01', 'name' => 'Chi nhánh Quận 1', 'address' => '123 Lê Lợi, Q.1, TP.HCM', 'phone' => '0281234567']);
        $hn = Branch::create(['code' => 'HN01', 'name' => 'Chi nhánh Hoàn Kiếm', 'address' => '45 Hàng Bài, Hoàn Kiếm, Hà Nội', 'phone' => '0241234567']);

        // Tài khoản demo: mật khẩu chung "password"
        $accounts = [
            ['admin@pos.test', 'Quản trị viên', $hcm, RoleEnum::SuperAdmin],
            ['manager@pos.test', 'Nguyễn Văn Quản Lý', $hcm, RoleEnum::Manager],
            ['cashier@pos.test', 'Trần Thị Thu Ngân', $hcm, RoleEnum::Cashier],
            ['warehouse@pos.test', 'Lê Văn Kho', $hcm, RoleEnum::Warehouse],
            ['manager.hn@pos.test', 'Phạm Quản Lý HN', $hn, RoleEnum::Manager],
            ['cashier.hn@pos.test', 'Hoàng Thu Ngân HN', $hn, RoleEnum::Cashier],
        ];
        $users = [];
        foreach ($accounts as [$email, $name, $branch, $role]) {
            $users[$email] = User::create([
                'name' => $name, 'email' => $email, 'password' => 'password',
                'branch_id' => $branch->id, 'email_verified_at' => now(),
            ])->assignRole($role->value);
        }

        $catalog = [
            'Đồ uống' => [['Nước suối Aquafina 500ml', 4000, 6000], ['Coca-Cola lon 330ml', 7000, 10000], ['Trà xanh 0 độ 455ml', 7500, 11000], ['Sữa tươi Vinamilk 1L', 28000, 35000], ['Cà phê lon Highlands', 12000, 17000]],
            'Bánh kẹo' => [['Bánh Chocopie hộp 12', 42000, 55000], ['Snack Oishi tôm cay', 4500, 7000], ['Kẹo Alpenliebe gói', 15000, 22000], ['Bánh quy Cosy 400g', 38000, 52000]],
            'Mì & đồ khô' => [['Mì Hảo Hảo tôm chua cay', 3500, 5000], ['Mì Omachi xốt bò', 6500, 9000], ['Gạo ST25 túi 5kg', 140000, 185000], ['Nước mắm Nam Ngư 500ml', 28000, 38000]],
            'Hóa mỹ phẩm' => [['Kem đánh răng P/S 200g', 25000, 34000], ['Dầu gội Clear 340g', 98000, 135000], ['Nước rửa chén Sunlight 750g', 22000, 31000], ['Khăn giấy Pulppy hộp', 15000, 22000]],
        ];

        $productIds = [];
        foreach ($catalog as $catName => $items) {
            $category = Category::create(['name' => $catName, 'slug' => Str::slug($catName), 'description' => "Các sản phẩm {$catName} chính hãng, giá tốt."]);
            foreach ($items as $i => [$name, $cost, $price]) {
                $p = Product::create([
                    'category_id' => $category->id,
                    'sku' => strtoupper(Str::substr(Str::slug($catName, ''), 0, 3)).str_pad((string) (count($productIds) + 1), 4, '0', STR_PAD_LEFT),
                    'barcode' => '893'.str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT),
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'cost_price' => $cost,
                    'price' => $price,
                    'min_stock' => 10,
                    'description' => "<p><strong>{$name}</strong> – sản phẩm chính hãng, được phân phối tại hệ thống ".config('pos.store_name').'.</p>',
                    'is_published' => true,
                ]);
                $productIds[] = $p->id;

                foreach ([$hcm, $hn] as $branch) {
                    $inventory->move($branch->id, $p->id, random_int(30, 150), 'import', note: 'Tồn đầu kỳ', userId: $users['warehouse@pos.test']->id);
                }
            }
        }

        $customers = collect([
            ['Nguyễn Minh Anh', '0901234567'], ['Trần Quốc Bảo', '0912345678'], ['Lê Thu Hà', '0987654321'],
            ['Phạm Gia Huy', '0934567890'], ['Võ Ngọc Lan', '0976543210'],
        ])->map(fn ($c) => Customer::create(['name' => $c[0], 'phone' => $c[1]]));

        // Sinh đơn hàng 30 ngày gần nhất để dashboard có dữ liệu.
        $cashiers = [$users['cashier@pos.test'], $users['cashier.hn@pos.test']];
        for ($day = 29; $day >= 0; $day--) {
            foreach (range(1, random_int(3, 8)) as $_) {
                $cart = collect($productIds)->random(random_int(1, 4))->mapWithKeys(fn ($id) => [$id => random_int(1, 3)])->all();
                try {
                    $order = $orders->checkout(
                        cashier: $cashiers[array_rand($cashiers)],
                        cart: $cart,
                        customerId: random_int(0, 2) ? null : $customers->random()->id,
                        paymentMethod: array_rand(Order::PAYMENT_METHODS),
                    );
                } catch (InsufficientStockException) {
                    continue; // hết hàng thì bỏ qua đơn này
                }
                $at = now()->subDays($day)->setTime(random_int(7, 21), random_int(0, 59));
                $order->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
            }
        }
    }
}
