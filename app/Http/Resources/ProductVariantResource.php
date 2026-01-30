<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'attributes' => $this->attributes,
            'price' => $this->price,
            'stock_quantity' => $this->stock_quantity,
            'image' => $this->image,
            'is_active' => $this->is_active,
        ];
    }
}