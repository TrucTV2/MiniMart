<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'address', 'points', 'total_spent'];

    protected $casts = ['total_spent' => 'integer'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
