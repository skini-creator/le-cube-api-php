<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'sale_price',
        'stock_quantity',
        'sku',
        'category_id',
        'user_id',
        'is_active',
        'is_featured',
        'weight',
        'dimensions',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'views_count',
        'average_rating',
        'reviews_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'meta_keywords' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'average_rating' => 'decimal:2',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopePriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('price', [$minPrice, $maxPrice]);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    // Accessors & Mutators
    public function getCurrentPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function getDiscountPercentageAttribute()
    {
        if (!$this->sale_price) {
            return 0;
        }
        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    public function getIsOnSaleAttribute()
    {
        return !is_null($this->sale_price) && $this->sale_price < $this->price;
    }

    public function getIsInStockAttribute()
    {
        return $this->stock_quantity > 0;
    }

    // Methods
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function updateRating()
    {
        $this->average_rating = $this->reviews()->where('is_approved', true)->avg('rating');
        $this->reviews_count = $this->reviews()->where('is_approved', true)->count();
        $this->save();
    }

    public function decrementStock($quantity)
    {
        $this->decrement('stock_quantity', $quantity);
    }

    public function incrementStock($quantity)
    {
        $this->increment('stock_quantity', $quantity);
    }
}