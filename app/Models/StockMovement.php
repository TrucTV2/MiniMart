<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    public const TYPES = [
        'import' => 'Nhập kho',
        'sale' => 'Bán hàng',
        'refund' => 'Hoàn trả',
        'adjust' => 'Điều chỉnh',
    ];

    protected $fillable = [
        'branch_id', 'product_id', 'user_id', 'type', 'quantity',
        'balance_after', 'reference_type', 'reference_id', 'note',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
