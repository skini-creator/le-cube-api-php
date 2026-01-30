<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'image_path' => $this->image_path,
            'url' => $this->url,
            'is_primary' => $this->is_primary,
            'order' => $this->order,
        ];
    }
}
