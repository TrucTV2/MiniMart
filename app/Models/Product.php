<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'category_id', 'sku', 'barcode', 'name', 'slug', 'unit',
        'cost_price', 'price', 'min_stock', 'image', 'description',
        'meta_title', 'meta_description', 'is_active', 'is_published',
    ];

    protected $casts = [
        'cost_price' => 'integer',
        'price' => 'integer',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $product->slug = $product->slug ?: Str::slug($product->name).'-'.Str::lower(Str::random(4));
        });

        // Xóa cache trang công khai khi dữ liệu thay đổi.
        static::saved(fn () => Cache::deleteMultiple(['home:v1', 'sitemap.xml']));
        static::deleted(fn () => Cache::deleteMultiple(['home:v1', 'sitemap.xml']));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_published', true);
    }

    public function stockAt(int $branchId): int
    {
        return (int) $this->inventories->firstWhere('branch_id', $branchId)?->quantity;
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price', 'cost_price', 'is_active'])
            ->logOnlyDirty();
    }
}
