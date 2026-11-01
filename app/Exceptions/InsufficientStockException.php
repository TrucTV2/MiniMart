<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(public int $productId, public int $available, public int $requested)
    {
        $name = Product::withTrashed()->find($productId)?->name ?? "#{$productId}";

        parent::__construct("Sản phẩm \"{$name}\" không đủ tồn kho (còn {$available}, cần {$requested}).");
    }
}
