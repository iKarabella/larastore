<?php

namespace App\Modules\Larastore\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOfferResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'product_title' => $this->product_title??'',
            'product_id' => $this->product_id??null,
            'id' => $this->id??null,
            'art' => $this->art??'',
            'title' => $this->title??'',
            'baseprice' => $this->baseprice?number_format($this->baseprice, 2, '.', ''):0,
            'price' => $this->price?number_format($this->price, 2, '.', ''):'',
            'measure_val' => $this->measure_val
        ];
    }
}