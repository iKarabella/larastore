<?php

namespace App\Modules\Larastore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title??null,
            'link' => $this->link??null,
            'short_description' => $this->short_description??null,
            'description'=>empty($this->description)?'':$this->description,
            'offersign'=>$this->offersign??'',
            'measure'=>$this->measure_value?[
                'value' => $this->measure_value->value,
                'description' => $this->measure_value->descr
            ]:[],
            'categories'=>CatalogCatResource::collection($this->categories??(object)[])->resolve(),
            'media' => ProductMediaResource::collection($this->media??(object)[])->resolve(),
            'offers' => OfferResource::collection($this->publicOffersWithRel)->resolve()
        ];
    }
}