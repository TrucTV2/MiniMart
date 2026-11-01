<?php

namespace App\Filament\Pages;

use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class PointOfSale extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Bán hàng';

    protected static ?string $navigationLabel = 'Bán hàng (POS)';

    protected static ?string $title = 'Bán hàng';

    protected static ?string $slug = 'pos';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.point-of-sale';

    protected ?string $maxContentWidth = 'full';

    public string $search = '';

    public ?int $categoryId = null;

    /** @var array<int, int> product_id => qty */
    public array $cart = [];

    public string $customerPhone = '';

    public ?int $customerId = null;

    public int $discount = 0;

    public string $paymentMethod = 'cash';

    public ?int $paid = null;

    public ?int $lastOrderId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('pos.access') ?? false;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $branchId = auth()->user()->branch_id;

        return Product::query()
            ->where('is_active', true)
            ->when($this->categoryId, fn (Builder $q) => $q->where('category_id', $this->categoryId))
            ->when($this->search, fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', $this->search)
                ->orWhere('barcode', $this->search)))
            ->withSum(['inventories as stock' => fn (Builder $q) => $q->where('branch_id', $branchId)], 'quantity')
            ->orderBy('name')
            ->paginate(18);
    }

    #[Computed]
    public function cartItems(): Collection
    {
        $products = Product::findMany(array_keys($this->cart))->keyBy('id');

        return collect($this->cart)
            ->filter(fn ($qty, $id) => $products->has($id))
            ->map(fn ($qty, $id) => (object) [
                'id' => $id,
                'name' => $products[$id]->name,
                'price' => $products[$id]->price,
                'qty' => $qty,
                'total' => $products[$id]->price * $qty,
            ]);
    }

    #[Computed]
    public function subtotal(): int
    {
        return (int) $this->cartItems->sum('total');
    }

    #[Computed]
    public function total(): int
    {
        return max(0, $this->subtotal - max(0, $this->discount));
    }

    #[Computed]
    public function customer(): ?Customer
    {
        return $this->customerId ? Customer::find($this->customerId) : null;
    }

    /** Quét mã vạch: Enter trong ô tìm kiếm sẽ thêm ngay nếu khớp chính xác SKU/barcode. */
    public function scan(): void
    {
        $product = Product::where('is_active', true)
            ->where(fn ($q) => $q->where('barcode', $this->search)->orWhere('sku', $this->search))
            ->first();

        if ($product) {
            $this->add($product->id);
            $this->search = '';
        }
    }

    public function add(int $productId): void
    {
        $this->cart[$productId] = ($this->cart[$productId] ?? 0) + 1;
    }

    public function setQty(int $productId, $qty): void
    {
        $qty = (int) $qty;
        if ($qty <= 0) {
            unset($this->cart[$productId]);
        } else {
            $this->cart[$productId] = min($qty, 9999);
        }
    }

    public function remove(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function clearCart(): void
    {
        $this->reset(['cart', 'discount', 'paid', 'customerId', 'customerPhone']);
    }

    public function findCustomer(): void
    {
        $customer = Customer::where('phone', trim($this->customerPhone))->first();
        $this->customerId = $customer?->id;

        if (! $customer) {
            Notification::make()->title('Không tìm thấy khách hàng')->warning()->send();
        }
    }

    public function checkout(OrderService $service): void
    {
        try {
            $order = $service->checkout(
                cashier: auth()->user(),
                cart: $this->cart,
                customerId: $this->customerId,
                discount: max(0, $this->discount),
                paymentMethod: $this->paymentMethod,
                paid: $this->paymentMethod === 'cash' ? ($this->paid ?? $this->total) : null,
            );
        } catch (InvalidArgumentException|InsufficientStockException $e) {
            Notification::make()->title('Không thể thanh toán')->body($e->getMessage())->danger()->send();

            return;
        }

        $this->lastOrderId = $order->id;
        $this->clearCart();

        Notification::make()
            ->title("Thanh toán thành công {$order->code}")
            ->body('Tổng: '.number_format($order->total, 0, ',', '.').'đ — Tiền thừa: '.number_format($order->paid - $order->total, 0, ',', '.').'đ')
            ->success()
            ->send();

        $this->dispatch('print-receipt', url: route('orders.receipt', $order));
    }

    public function getPaymentMethods(): array
    {
        return Order::PAYMENT_METHODS;
    }
}
