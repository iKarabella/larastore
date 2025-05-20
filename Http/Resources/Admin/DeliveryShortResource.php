<?php

namespace App\Modules\Larastore\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryShortResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id??null,            
            'user_id'=>$this->user_id??null,
            'warehouse_id' => $this->warehouse_id??null,
            'order_id' => $this->order_id??null,
            'positions' => $this->positions??null,
            'customer' => $this->customer??[],
            'address' => $this->address??null,
            'track' => $this->track??null,
            'carrier_key' => $this->carrier_key??null,
            'carrier' => $this->carrier??null,
            'courier' => $this->courier??null,
            'status' => $this->status??null,
            'weight' => $this->weight??null,
            'width' => $this->width??null,
            'height' => $this->height??null,
            'length' => $this->length??null,
            'status_info' => $this->status_info?[
                'id' => $this->status_info->id,
                'value' => $this->status_info->value,
                'description' => $this->status_info->descr,
            ]:null,
            'warehouse_info' => $this->warehouse_info??[],
            'created_at' => new Carbon($this->created_at??null)->format('d.m.Y H:i:s'),
            'updated_at' => new Carbon($this->updated_at??null)->format('d.m.Y H:i:s'),
        ];
    }
}