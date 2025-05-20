<?php

namespace App\Modules\Larastore\Http\Resources\Market;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatalogCatResource extends JsonResource
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
            'code' => $this->code??null,
            'description'=>$this->description??'',
            'visibility'=>$this->visibility??true,
            'image'=>$this->image??'', //TODO url
            'small_image'=>$this->image??'', //TODO url
        ];
    }
}