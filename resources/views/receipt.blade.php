<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Hóa đơn {{ $order->code }}</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        body { font-family: 'Courier New', monospace; font-size: 12px; width: 72mm; margin: 0 auto; color: #000; }
        h1 { font-size: 16px; text-align: center; margin: 0; }
        .c { text-align: center; } .r { text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 2px 0; vertical-align: top; }
        hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        .big { font-size: 15px; font-weight: bold; }
        .no-print { text-align: center; margin: 12px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <h1>{{ config('pos.store_name') }}</h1>
    <div class="c">{{ $order->branch->name }}<br>{{ $order->branch->address }}<br>ĐT: {{ $order->branch->phone }}</div>
    <hr>
    <div class="c big">HÓA ĐƠN BÁN HÀNG</div>
    <div>Số: {{ $order->code }}<br>
        Ngày: {{ $order->created_at->format('d/m/Y H:i') }}<br>
        Thu ngân: {{ $order->cashier->name }}<br>
        Khách: {{ $order->customer?->name ?? 'Khách lẻ' }}</div>
    @if ($order->status !== 'completed')
        <div class="c big">*** {{ mb_strtoupper(\App\Models\Order::STATUSES[$order->status]) }} ***</div>
    @endif
    <hr>
    <table>
        @foreach ($order->items as $item)
            <tr><td colspan="3">{{ $item->product_name }}</td></tr>
            <tr>
                <td>{{ $item->quantity }} x</td>
                <td class="r">{{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="r">{{ number_format($item->total, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td>Tạm tính</td><td class="r">{{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
        @if ($order->discount)<tr><td>Giảm giá</td><td class="r">-{{ number_format($order->discount, 0, ',', '.') }}</td></tr>@endif
        <tr class="big"><td>TỔNG CỘNG</td><td class="r">{{ number_format($order->total, 0, ',', '.') }}đ</td></tr>
        <tr><td>{{ \App\Models\Order::PAYMENT_METHODS[$order->payment_method] }}</td><td class="r">{{ number_format($order->paid, 0, ',', '.') }}</td></tr>
        @if ($order->paid > $order->total)<tr><td>Tiền thừa</td><td class="r">{{ number_format($order->paid - $order->total, 0, ',', '.') }}</td></tr>@endif
    </table>
    <hr>
    <div class="c">Cảm ơn quý khách & hẹn gặp lại!</div>
    <div class="no-print"><button onclick="window.print()">In lại</button></div>
</body>
</html>
