<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Product extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $attributes = [
            'sku' => $this->sku,
            'type' => $this->type,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->priceLabel(),
            'featured_image' => $this->getFeaturedImage(),
            'short_description' => $this->short_description,
            'description' => $this->description,
        ];

        if ($this->type == 'configurable' && $this->variants->count() > 0) {
            $attributes['variants'] = new ProductCollection($this->variants);
        }

        return $attributes;
    }

    private function getFeaturedImage()
    {
        return ($this->productImages->first()) ? asset('storage/'.$this->productImages->first()->medium) : null;
    }
}
