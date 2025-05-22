<?php

namespace App\Modules\Larastore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Market\OrderCancelRequest;
use App\Http\Requests\Admin\Market\OrderToAssemblyRequest;
use App\Http\Requests\Admin\Market\OrderWaitingPaymentRequest;
use App\Models\Order;
use App\Services\Market\DTO\ReservationDTO;
use App\Services\Market\WarehouseService;
use App\Services\OrderService;
use Illuminate\Validation\ValidationException;

class OrderStatusController extends Controller
{
    public function cancel(OrderCancelRequest $request)
    {
        $cancel = WarehouseService::cancelReservation($request->order_id);
        if ($cancel===true) OrderService::setStatus($request->order_id, 82); //куда коммент?
    }
    
    public function waitingPayment(OrderWaitingPaymentRequest $request)
    {
       OrderService::setStatus($request->order_id, 77); 
    }

    public function orderToAssembly(OrderToAssemblyRequest $request)
    {
        $validated = $request->validated();
        $order = Order::whereId($validated['order_id'])->firstOrFail();        
        $orderBody = collect($order->body);
        $toReserve=[];

        if($orderBody->count()!=count($validated['toAssembly'])) throw ValidationException::withMessages([
            'order_id' => ["Ошибка при формировании списания."],
        ]);

        if($order->status!=83){
            $whwo=[];
            $whWriteOff = array_map(function($arr){
                return array_column($arr['writeOffWh'], 'id');
            }, $validated['toAssembly']);
    
            foreach($whWriteOff as $w) $whwo=[...$whwo, ...$w];
            
            if(count(array_unique($whwo))>1) throw ValidationException::withMessages([
                'order_id' => ["Заказ с нескольких складов нельзя поставить в сборку до оплаты."],
            ]);
        }

        foreach($validated['toAssembly'] as $position)
        {
            $inOrder = $orderBody->first(function($arr) use ($position) {return $arr['offer_id']==$position['offer_id'] && $arr['product_id']==$position['product_id'];});

            if (($inOrder['quantity'] != $position['quantity']) || ($position['quantity'] != array_sum(array_column($position['writeOffWh'], 'quantity'))))
            {
                throw ValidationException::withMessages([
                    'order_id' => ["Ошибка при формировании списания."],
                ]);
            }

            $reserve = array_map(function($p) use ($position, $order) {
                return new ReservationDTO(name:$position['name'], order_id:$order->id, product_id:$position['product_id'], offer_id:$position['offer_id'], warehouse_id:$p['id'], quantity:$p['quantity']);
            }, $position['writeOffWh']);

            array_push($toReserve, ...$reserve);
        }

        $reserved = WarehouseService::reservation($toReserve);
        if ($reserved===true) OrderService::setStatus($order, 78);     
    }
}
