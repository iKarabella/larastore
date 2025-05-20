<?php

namespace App\Modules\Larastore\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseActResource extends JsonResource
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
            'user_id' => $this->user_id??null,
            'warehouse_id' => $this->warehouse_id??null,
            'warehouse'=> $this->warehouse?[
                'title'=>$this->warehouse->title??'',
                'code'=>$this->warehouse->code??'',
                'address'=>$this->warehouse->address??'',
                'phone'=>$this->warehouse->phone??''
            ]:[],
            'act'=>$this->act,
            'user'=>$this->user?[
                'name'=>implode(' ', [$this->user->surname, $this->user->name, $this->user->patronymic]),
                'login'=>$this->user->login??'',
                'phone'=>$this->user->phone??''
            ]:[],
            'created_at'=>new Carbon($this->created_at)->format('d.m.Y H:i')
        ];
    }
}