<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function __invoke(Order $order): View
    {
        $order->load(['items', 'customer', 'cashier', 'branch']);

        return view('receipt', compact('order'));
    }
}
