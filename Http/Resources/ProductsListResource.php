<?php

namespace App\Modules\Larastore\Http\Resources\Market;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductsListResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request)
    {
        $canEdit = (
            Auth::check() && 
            isset(config('app.market_rights')[Auth::user()->id]) && 
            in_array('catalog', config('app.market_rights')[Auth::user()->id])
        );

        return [
            'id' => $this->id,
            'title' => $this->title??null,
            'link' => $this->link??null,
            'short_description' => $this->short_description??null,
            'media' => ProductMediaResource::collection($this->media)->resolve()??[],
            'offers' => $this->publicOffersWithRel??[],
            'instock' => $this->instock,//??0
            'canEdit' => $canEdit
        ];
    }
}