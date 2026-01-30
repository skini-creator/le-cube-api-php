<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'shipping_address_id',
        'billing_address_id',
        'status',
        'payment_status',
        'payment_method',
        'subtotal',
        'tax',
        'shipping_cost',
        'discount',
        'total',
        'coupon_id',
        'notes',
        'tracking_number',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'pending');
    }

    // Static Methods
    public static function generateOrderNumber()
    {
        $prefix = 'ORD';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        return "{$prefix}-{$timestamp}-{$random}";
    }

    // Methods
    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function markAsPaid()
    {
        $this->update([
            'payment_status' => 'paid',
            'status' => 'processing'
        ]);
    }

    public function markAsShipped($trackingNumber = null)
    {
        $this->update([
            'status' => 'shipped',
            'tracking_number' => $trackingNumber,
            'shipped_at' => now()
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);
    }

    public function cancel()
    {
        if (!$this->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled');
        }

        $this->update(['status' => 'cancelled']);

        // Restore stock
        foreach ($this->items as $item) {
            $product = $item->product;
            $product->incrementStock($item->quantity);
        }
    }

    public function calculateTotals()
    {
        $subtotal = $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $tax = $subtotal * (config('app.tax_rate', 0.20));
        $discount = 0;

        if ($this->coupon) {
            $discount = $this->coupon->calculateDiscount($subtotal);
        }

        $total = $subtotal + $tax + $this->shipping_cost - $discount;

        $this->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total
        ]);
    }
}