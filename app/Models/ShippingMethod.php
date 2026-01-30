<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cost',
        'estimated_days_min',
        'estimated_days_max',
        'is_active',
        'free_shipping_threshold',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isFreeForAmount($amount)
    {
        if (!$this->free_shipping_threshold) {
            return false;
        }
        return $amount >= $this->free_shipping_threshold;
    }

    public function getCostForAmount($amount)
    {
        if ($this->isFreeForAmount($amount)) {
            return 0;
        }
        return $this->cost;
    }
}