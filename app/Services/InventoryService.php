<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Thay đổi tồn kho và ghi sổ kho. Phải gọi trong transaction (tự mở nếu chưa có).
     * Dùng lockForUpdate để 2 thu ngân bán cùng lúc không làm tồn kho âm.
     */
    public function move(
        int $branchId,
        int $productId,
        int $quantity,
        string $type,
        ?Model $reference = null,
        ?string $note = null,
        ?int $userId = null,
    ): Inventory {
        return DB::transaction(function () use ($branchId, $productId, $quantity, $type, $reference, $note, $userId) {
            $inventory = Inventory::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first()
                ?? Inventory::create(['branch_id' => $branchId, 'product_id' => $productId, 'quantity' => 0]);

            $newQty = $inventory->quantity + $quantity;

            if ($newQty < 0) {
                throw new InsufficientStockException($productId, $inventory->quantity, abs($quantity));
            }

            $inventory->update(['quantity' => $newQty]);

            StockMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => $userId ?? auth()->id(),
                'type' => $type,
                'quantity' => $quantity,
                'balance_after' => $newQty,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'note' => $note,
            ]);

            return $inventory;
        });
    }
}
