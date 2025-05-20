<?php

namespace App\Modules\Larastore\Http\Resources\Admin;

use App\Modules\Larastore\Services\Caschier\CaschierService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id??null,
            'title' => $this->title??null,
            'code' => $this->code??null,
            'phone' => $this->phone??null,
            'address' => $this->address??null,
            'school_id'=> $this->school_id??null,
            'description' => $this->description??null,
            'caschier' => $this->caschier??null,
            'caschier_settings'=>isset($this->id)?CaschierService::schoolSettings('warehouse'.$this->id):[],
        ];
    }
}