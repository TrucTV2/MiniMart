<x-filament-panels::page>
    <style>
        .pos { display: grid; gap: 1rem; grid-template-columns: 1fr; }
        @media (min-width: 1024px) { .pos { grid-template-columns: minmax(0, 1fr) 400px; } }
        .pos-card { background: #fff; border-radius: .75rem; box-shadow: 0 1px 2px rgba(0,0,0,.06); border: 1px solid rgba(0,0,0,.06); padding: 1rem; }
        .dark .pos-card { background: rgb(24 24 27); border-color: rgba(255,255,255,.1); }
        .pos-toolbar { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .pos-input { width: 100%; border-radius: .5rem; border: 1px solid rgba(0,0,0,.15); padding: .55rem .75rem; background: transparent; color: inherit; }
        .dark .pos-input { border-color: rgba(255,255,255,.2); }
        .pos-input:focus { outline: 2px solid rgb(245 158 11); outline-offset: -1px; }
        .pos-chips { display: flex; gap: .4rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .pos-chip { padding: .3rem .8rem; border-radius: 999px; font-size: .8rem; border: 1px solid rgba(0,0,0,.12); cursor: pointer; }
        .dark .pos-chip { border-color: rgba(255,255,255,.2); }
        .pos-chip.active { background: rgb(245 158 11); color: #fff; border-color: transparent; }
        .pos-grid { display: grid; gap: .75rem; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
        .pos-product { text-align: left; border-radius: .6rem; border: 1px solid rgba(0,0,0,.08); padding: .75rem; transition: .15s; cursor: pointer; background: inherit; display: flex; flex-direction: column; gap: .25rem; }
        .dark .pos-product { border-color: rgba(255,255,255,.1); }
        .pos-product:hover:not(:disabled) { border-color: rgb(245 158 11); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(245,158,11,.15); }
        .pos-product:disabled { opacity: .45; cursor: not-allowed; }
        .pos-product .name { font-weight: 600; font-size: .9rem; line-height: 1.25; min-height: 2.5em; }
        .pos-product .price { color: rgb(217 119 6); font-weight: 700; }
        .pos-muted { font-size: .75rem; opacity: .6; }
        .pos-cart-row { display: grid; grid-template-columns: 1fr 64px auto 24px; gap: .5rem; align-items: center; padding: .55rem 0; border-bottom: 1px dashed rgba(127,127,127,.25); }
        .pos-qty { width: 64px; text-align: center; padding: .3rem; }
        .pos-line { display: flex; justify-content: space-between; padding: .25rem 0; }
        .pos-total { font-size: 1.5rem; font-weight: 800; color: rgb(217 119 6); }
        .pos-btn { width: 100%; padding: .85rem; border-radius: .6rem; background: rgb(245 158 11); color: #fff; font-weight: 700; font-size: 1.05rem; }
        .pos-btn:hover { background: rgb(217 119 6); }
        .pos-btn:disabled { opacity: .5; cursor: not-allowed; }
        .pos-pay { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; }
        .pos-pay label { text-align: center; border: 1px solid rgba(127,127,127,.3); padding: .45rem; border-radius: .5rem; cursor: pointer; font-size: .85rem; }
        .pos-pay input { display: none; }
        .pos-pay input:checked + span { color: rgb(217 119 6); font-weight: 700; }
        .pos-pay label:has(input:checked) { border-color: rgb(245 158 11); background: rgba(245,158,11,.08); }
        .pos-x { color: rgb(220 38 38); font-weight: 700; }
        .pos-empty { text-align: center; padding: 2.5rem 0; opacity: .5; }
        .pos-sticky { position: sticky; top: 5rem; }
    </style>

    <div class="pos">
        {{-- Danh sách sản phẩm --}}
        <div class="pos-card">
            <div class="pos-toolbar">
                <input type="search" class="pos-input" autofocus
                       placeholder="🔍 Tìm tên sản phẩm hoặc quét mã vạch rồi nhấn Enter..."
                       wire:model.live.debounce.300ms="search" wire:keydown.enter="scan">
            </div>

            <div class="pos-chips">
                <button type="button" class="pos-chip {{ $categoryId === null ? 'active' : '' }}" wire:click="$set('categoryId', null)">Tất cả</button>
                @foreach ($this->categories as $cat)
                    <button type="button" class="pos-chip {{ $categoryId === $cat->id ? 'active' : '' }}" wire:click="$set('categoryId', {{ $cat->id }})">{{ $cat->name }}</button>
                @endforeach
            </div>

            <div class="pos-grid">
                @forelse ($this->products as $product)
                    @php $stock = (int) $product->stock; @endphp
                    <button type="button" class="pos-product" wire:click="add({{ $product->id }})" wire:key="p-{{ $product->id }}" @disabled($stock <= 0)>
                        <span class="name">{{ $product->name }}</span>
                        <span class="pos-muted">{{ $product->sku }}</span>
                        <span class="price">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                        <span class="pos-muted" style="{{ $stock <= $product->min_stock ? 'color:rgb(220 38 38);opacity:1' : '' }}">
                            Tồn: {{ $stock }} {{ $product->unit }}
                        </span>
                    </button>
                @empty
                    <div class="pos-empty" style="grid-column:1/-1">Không tìm thấy sản phẩm</div>
                @endforelse
            </div>

            <div style="margin-top:1rem">{{ $this->products->links() }}</div>
        </div>

        {{-- Giỏ hàng --}}
        <div>
            <div class="pos-card pos-sticky">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                    <h2 style="font-weight:700;font-size:1.1rem">Giỏ hàng ({{ array_sum($cart) }})</h2>
                    @if ($cart)
                        <button type="button" class="pos-muted" wire:click="clearCart">Xóa hết</button>
                    @endif
                </div>

                <div style="max-height:40vh;overflow-y:auto">
                    @forelse ($this->cartItems as $item)
                        <div class="pos-cart-row" wire:key="c-{{ $item->id }}">
                            <div>
                                <div style="font-weight:600;font-size:.9rem">{{ $item->name }}</div>
                                <div class="pos-muted">{{ number_format($item->price, 0, ',', '.') }}đ</div>
                            </div>
                            <input type="number" min="0" class="pos-input pos-qty" value="{{ $item->qty }}"
                                   wire:change="setQty({{ $item->id }}, $event.target.value)">
                            <div style="font-weight:600;text-align:right">{{ number_format($item->total, 0, ',', '.') }}</div>
                            <button type="button" class="pos-x" wire:click="remove({{ $item->id }})" aria-label="Xóa">×</button>
                        </div>
                    @empty
                        <div class="pos-empty">Chưa có sản phẩm nào</div>
                    @endforelse
                </div>

                <div style="margin-top:1rem;display:grid;gap:.6rem">
                    <div style="display:flex;gap:.4rem">
                        <input type="tel" class="pos-input" placeholder="SĐT khách hàng" wire:model="customerPhone" wire:keydown.enter="findCustomer">
                        <x-filament::button color="gray" wire:click="findCustomer">Tìm</x-filament::button>
                    </div>
                    @if ($this->customer)
                        <div class="pos-muted" style="opacity:1">
                            👤 <b>{{ $this->customer->name }}</b> — {{ $this->customer->points }} điểm
                            <button type="button" class="pos-x" wire:click="$set('customerId', null)">×</button>
                        </div>
                    @endif

                    <div class="pos-line"><span>Tạm tính</span><b>{{ number_format($this->subtotal, 0, ',', '.') }}đ</b></div>
                    <div class="pos-line" style="align-items:center">
                        <span>Giảm giá</span>
                        <input type="number" min="0" step="1000" class="pos-input" style="width:140px;text-align:right" wire:model.live.debounce.400ms="discount">
                    </div>
                    <div class="pos-line" style="align-items:center"><span style="font-weight:700">Khách cần trả</span><span class="pos-total">{{ number_format($this->total, 0, ',', '.') }}đ</span></div>

                    <div class="pos-pay">
                        @foreach ($this->getPaymentMethods() as $key => $label)
                            <label><input type="radio" value="{{ $key }}" wire:model.live="paymentMethod"><span>{{ $label }}</span></label>
                        @endforeach
                    </div>

                    @if ($paymentMethod === 'cash')
                        <div class="pos-line" style="align-items:center">
                            <span>Khách đưa</span>
                            <input type="number" min="0" step="1000" class="pos-input" style="width:140px;text-align:right"
                                   placeholder="{{ $this->total }}" wire:model.live.debounce.400ms="paid">
                        </div>
                        @if ($paid && $paid >= $this->total)
                            <div class="pos-line"><span>Tiền thừa</span><b>{{ number_format($paid - $this->total, 0, ',', '.') }}đ</b></div>
                        @endif
                    @elseif ($paymentMethod === 'transfer' && $this->total > 0)
                        <div style="text-align:center">
                            <img alt="VietQR" style="margin:auto;max-width:180px"
                                 src="https://img.vietqr.io/image/{{ config('pos.bank_bin') }}-{{ config('pos.bank_account') }}-compact.png?amount={{ $this->total }}&addInfo=POS">
                        </div>
                    @endif

                    <button type="button" class="pos-btn" wire:click="checkout" wire:loading.attr="disabled" @disabled(! $cart)>
                        <span wire:loading.remove wire:target="checkout">THANH TOÁN (F9)</span>
                        <span wire:loading wire:target="checkout">Đang xử lý...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('print-receipt', ({ url }) => window.open(url, '_blank', 'width=420,height=640'));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F9') { e.preventDefault(); $wire.checkout(); }
        });
    </script>
    @endscript
</x-filament-panels::page>
