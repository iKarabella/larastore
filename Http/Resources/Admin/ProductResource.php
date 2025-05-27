<?php

namespace App\Modules\Larastore\Http\Resources\Admin;

use App\Modules\Larastore\Http\Resources\ProductMediaResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,               // id продукта
            'title' => $this->title,         // Название
            'link'  => $this->link, //линк
            'short_description'=>$this->short_description,
            'description' => $this->description,     // Описание
            'visibility' => $this->visibility, // Видимость
            'offersign' => $this->offersign, 
            'categories' => $this->categories, //категории
            'offers'    => $this->offers??[],
            'measure'   => $this->measure??null,
            'media' => $this->media?ProductMediaResource::collection($this->media):[],
            'created' => new Carbon($this->created_at)->format('d.m.Y H:i:s'), //создан
            'updated' => new Carbon($this->updated_at)->format('d.m.Y H:i:s'), //обновлен
        ];
    }
}