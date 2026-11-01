<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_visible'];

    protected $casts = ['is_visible' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            $category->slug = $category->slug ?: Str::slug($category->name);
        });

        static::saved(fn () => Cache::deleteMultiple(['home:v1', 'site:categories', 'sitemap.xml']));
        static::deleted(fn () => Cache::deleteMultiple(['home:v1', 'site:categories', 'sitemap.xml']));
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
