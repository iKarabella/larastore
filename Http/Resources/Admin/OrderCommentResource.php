<?php

namespace App\Modules\Larastore\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderCommentResource extends JsonResource
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
            'order_id'=>$this->order_id,
            'user_id'=>$this->user_id,
            'auto'=>$this->auto?true:false,
            'title'=>$this->title??null,
            'comment'=>$this->comment,
            'created_at'=> new Carbon($this->created_at)->format('d.m.Y H:i:s'),
            'updated_at'=> $this->created_at!=$this->updated_at ? new Carbon($this->updated_at)->format('d.m.Y H:i:s') : null,
        ];
    }
}