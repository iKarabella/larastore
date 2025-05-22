<?php

namespace App\Modules\Larastore\Services\DTO;


class ReservationDTO  
{
    public function __construct(
        public readonly string $name = '',
        public readonly int $order_id,
        public readonly int $product_id,
        public readonly int $offer_id,
        public readonly int $warehouse_id,
        public readonly int $quantity
    ) {}

    public function toArray(){
        return [
            'name' => $this->name,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'offer_id' => $this->offer_id,
            'warehouse_id' => $this->warehouse_id,
            'quantity' => $this->quantity,
        ];
    }
}