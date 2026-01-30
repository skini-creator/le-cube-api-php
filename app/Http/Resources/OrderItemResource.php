<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'variant_details' => $this->variant_details,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total' => $this->total,
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
