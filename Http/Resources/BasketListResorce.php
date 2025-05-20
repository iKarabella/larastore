<?php

namespace App\Modules\Larastore\Http\Resources\Market;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BasketListResorce extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userCard = $request->session()->get('user_cart', []);
        
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_link'=>$this->productWithFirstMedia->link??null,
            'description' => $this->productWithFirstMedia->short_description??null,
            'product_title'=> $this->productWithFirstMedia->title,
            'offersign'=>$this->productWithFirstMedia->offersign,
            'offer_title'=>$this->title,
            'price'=>$this->price??null,
            'measure'=>$this->productWithFirstMedia->measure_value->value??null,
            'art'=>$this->art??null,
            'media'=>ProductMediaResource::make($this->mediaFirst?$this->mediaFirst:$this->productWithFirstMedia->mediaFirst)->resolve(),
            'stocks'=>array_sum($this->stocks->pluck('quantity')->toArray()),
            'quantity'=>array_find($userCard, function($a){return $a['offer']==$this->id;})['quantity'],
            'toOrder'=>true,
        ];
    }
}
