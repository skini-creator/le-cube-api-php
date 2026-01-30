<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'current_price' => $this->current_price,
            'discount_percentage' => $this->discount_percentage,
            'is_on_sale' => $this->is_on_sale,
            'stock_quantity' => $this->stock_quantity,
            'is_in_stock' => $this->is_in_stock,
            'sku' => $this->sku,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count,
            'views_count' => $this->views_count,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'vendor' => new UserResource($this->whenLoaded('user')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'primary_image' => new ProductImageResource($this->whenLoaded('primaryImage')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
