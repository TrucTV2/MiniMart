<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    /** 10.000đ = 1 điểm tích lũy */
    public const POINT_RATE = 10_000;

    public function __construct(private InventoryService $inventory) {}

    /**
     * @param  array<int, int>  $cart  product_id => quantity
     */
    public function checkout(
        User $cashier,
        array $cart,
        ?int $customerId = null,
        int $discount = 0,
        string $paymentMethod = 'cash',
        ?int $paid = null,
        ?string $note = null,
    ): Order {
        $cart = array_filter($cart, fn ($qty) => $qty > 0);

        if ($cart === []) {
            throw new InvalidArgumentException('Giỏ hàng trống.');
        }
        if (! $cashier->branch_id) {
            throw new InvalidArgumentException('Tài khoản chưa được gán chi nhánh.');
        }
        if (! array_key_exists($paymentMethod, Order::PAYMENT_METHODS)) {
            throw new InvalidArgumentException('Phương thức thanh toán không hợp lệ.');
        }

        return DB::transaction(function () use ($cashier, $cart, $customerId, $discount, $paymentMethod, $paid, $note) {
            // Giá luôn lấy từ DB, không tin giá từ client.
            $products = Product::query()->where('is_active', true)->findMany(array_keys($cart))->keyBy('id');

            if ($products->count() !== count($cart)) {
                throw new InvalidArgumentException('Có sản phẩm không tồn tại hoặc đã ngừng bán.');
            }

            $subtotal = $products->sum(fn (Product $p) => $p->price * $cart[$p->id]);
            $discount = max(0, min($discount, $subtotal));
            $total = $subtotal - $discount;
            $paid ??= $total;

            if ($paymentMethod === 'cash' && $paid < $total) {
                throw new InvalidArgumentException('Số tiền khách đưa không đủ.');
            }

            $order = Order::create([
                'code' => $this->generateCode($cashier->branch_id),
                'branch_id' => $cashier->branch_id,
                'user_id' => $cashier->id,
                'customer_id' => $customerId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid' => $paid,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'note' => $note,
            ]);

            // Sắp xếp theo id để các transaction luôn khóa theo cùng thứ tự => tránh deadlock.
            foreach ($products->sortKeys() as $product) {
                $qty = $cart[$product->id];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'price' => $product->price,
                    'cost_price' => $product->cost_price,
                    'total' => $product->price * $qty,
                ]);

                $this->inventory->move($order->branch_id, $product->id, -$qty, 'sale', $order, userId: $cashier->id);
            }

            if ($customerId) {
                Customer::whereKey($customerId)->increment('total_spent', $total, [
                    'points' => DB::raw('points + '.intdiv($total, self::POINT_RATE)),
                ]);
            }

            return $order->load('items');
        });
    }

    public function refund(Order $order, User $user, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            // Khóa và kiểm tra lại trạng thái để chống hoàn trả 2 lần do bấm đồng thời.
            $order = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== 'completed') {
                throw new InvalidArgumentException('Chỉ hoàn trả được đơn đã hoàn thành.');
            }

            foreach ($order->items as $item) {
                $this->inventory->move($order->branch_id, $item->product_id, $item->quantity, 'refund', $order, $reason, $user->id);
            }

            if ($order->customer_id) {
                $customer = Customer::query()->lockForUpdate()->find($order->customer_id);
                $customer?->update([
                    'total_spent' => max(0, $customer->total_spent - $order->total),
                    'points' => max(0, $customer->points - intdiv($order->total, self::POINT_RATE)),
                ]);
            }

            $order->update(['status' => 'refunded', 'note' => trim($order->note.' | Hoàn trả: '.$reason, ' |')]);

            return $order;
        });
    }

    private function generateCode(int $branchId): string
    {
        return sprintf('HD%02d%s%04d', $branchId, now()->format('ymd'), random_int(0, 9999))
            .strtoupper(substr(bin2hex(random_bytes(1)), 0, 2));
    }
}
