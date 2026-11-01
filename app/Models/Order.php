<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use LogsActivity;

    public const STATUSES = [
        'completed' => 'Hoàn thành',
        'refunded' => 'Đã hoàn trả',
        'cancelled' => 'Đã hủy',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'Tiền mặt',
        'transfer' => 'Chuyển khoản',
        'card' => 'Thẻ',
    ];

    protected $fillable = [
        'code', 'branch_id', 'user_id', 'customer_id', 'subtotal', 'discount',
        'total', 'paid', 'payment_method', 'status', 'note',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'discount' => 'integer',
        'total' => 'integer',
        'paid' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /** Giới hạn dữ liệu theo phân quyền của người dùng hiện tại. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $query->where('branch_id', $user->branch_id);

        return $user->can('orders.view_all') ? $query : $query->where('user_id', $user->id);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'total'])->logOnlyDirty();
    }
}
