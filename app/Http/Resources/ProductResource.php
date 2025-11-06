<?php
// app/Http/Resources/ProductResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'unique_key' => $this->unique_key,
            'title' => $this->product_title,
            'description' => $this->product_description,
            'style_no' => $this->style_no,
            'sanmar_mainframe_color' => $this->sanmar_mainframe_color,
            'size' => $this->size,
            'color_name' => $this->color_name,
            'piece_price' => (float) $this->piece_price,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}