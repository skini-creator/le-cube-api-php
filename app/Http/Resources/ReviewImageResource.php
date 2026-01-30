<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewImageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'image_path' => $this->image_path,
            'url' => asset('storage/' . $this->image_path),
        ];
    }
}