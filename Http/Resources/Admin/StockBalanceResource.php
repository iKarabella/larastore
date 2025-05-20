<?php

namespace App\Modules\Larastore\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockBalanceResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'ptitle' => $this->ptitle??'',
            'pid' => $this->pid??null,
            'otitle' => $this->otitle??'',
            'oid' => $this->oid??null,
            'quantity' => $this->quantity??0,
            'measure' => $this->measure??'',
        ];
    }
}