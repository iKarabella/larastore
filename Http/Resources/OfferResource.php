<?php

namespace App\Modules\Larastore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id'=>$this->id,
            'product_id'=>$this->product_id??null,
            'title'=>$this->title??null,
            'price'=>$this->price??null,
            'barcode'=>$this->barcode??null,
            'art'=>$this->art??null,
            'weight'=>$this->weight??null,
            'length'=>$this->length??null,
            'width'=>$this->width??null,
            'height'=>$this->height??null,
            'media'=>ProductMediaResource::collection($this->media)->resolve(),
            'instock'=>$this->stocks && count($this->stocks) ? array_sum($this->stocks->pluck('quantity')->toArray()) : 0
        ];
    }
}