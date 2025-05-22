<?php

namespace App\Modules\Larastore\Services;

use App\Models\Order;
use App\Models\ProductOffer;
use App\Models\ReservedProduct;
use App\Models\Shipping;
use App\Models\StocksBalance;
use App\Services\Shipping\ShippingService;
use Auth;
use Illuminate\Database\Eloquent\Collection;
use DB;
use Exception;
use Illuminate\Contracts\Database\Query\Builder;

class WarehouseService
{
    /**
     * Списание товаров со склада
     */
    public static function writeOff(array $positions, int $warehouse):bool
    {
        try {
            DB::transaction(function() use ($positions, $warehouse) {
                foreach($positions as $item)
                {
                    StocksBalance::whereWarehouseId($warehouse)->whereOfferId($item['offer_id'])->decrement('quantity', $item['quantity']);
                }
            });
        } catch (Exception $e) {
            return false; //throw $e;
        }

        return true;
    }

    /**
     * Создание списка товаров из заказа к списанию со склада
     * 
     * @param Order $body тело заказа model Order->body
     * @param Collection $warehouses список складов, для расчета списания
     * @return array $body обновленный список товаров в заказе, с указанием списания со складов
     */
    public static function writeOffCreate(Order $order, Collection $warehouses)
    {
        $balance = []; $writeOffList=[];

        $body = $order->body;

        $offers = ProductOffer::whereIn('id', array_column($body, 'offer_id'))->with('stocks')->get();
        
        if($order->reserved && $order->reserved->count()) 
        {
            foreach($body as &$position)
            {
                $inReserved = $order->reserved->filter(function($arr) use ($position){
                    return $arr->offer_id==$position['offer_id'];
                });

                $map = function($arr) use ($warehouses){
                    $wh = $warehouses->first(function($w) use ($arr) {return $w->id==$arr->warehouse_id;});
                    return [
                        'id' => $arr->warehouse_id,
                        'code' => $wh->code??null,
                        'title' => $wh->title??null,
                        'address' => $wh->address??null,
                        'quantity' => $arr->quantity
                    ];
                };
                
                $position['writeOffWh'] = $inReserved->map($map)->values()->toArray();
                $position['stocks'] = $offers->first(function($o) use ($position){return $o->id==$position['offer_id'];})->stocks;
            }
        }
        else {
            foreach($offers->pluck('stocks') as $block){
                foreach($block as $stock){
                    $balance[$stock->warehouse_id]['whid'] = $stock->warehouse_id;
                    $balance[$stock->warehouse_id]['items'][]=[
                        'warehouse_id'=>$stock->warehouse_id,
                        'offer_id'=>$stock->offer_id,
                        'quantity'=>$stock->quantity
                    ];
                }
            }
    
            foreach ($body as $position) 
            {
                $writeOffList[$position['offer_id']]=[
                    'offer_id'=>$position['offer_id'],
                    'writeoff'=>0, 
                    'total'=>$position['quantity']
                ];
            }
    
            usort($balance, function($a, $b){ return count($b['items'])-count($a['items']);});
    
            foreach($balance as $stock)
            {
                if (!count($stock['items'])) continue;
    
                foreach($stock['items'] as $i)
                {
                    $position = array_find_key($body, function($arr) use($i) {
                        return $arr['offer_id']
                        ==
                        $i['offer_id'];
                    });
    
                    if($position!==null)
                    {
                        $whTick = $warehouses->first(function($wh) use ($stock){return $wh->id==$stock['whid'];});
    
                        $toWriteOff = $body[$position]['quantity']-$writeOffList[$body[$position]['offer_id']]['writeoff'];
                        
                        if($i['quantity']<$toWriteOff){
                            $whq = $i['quantity'];
                        }
                        else{
                            $whq = $toWriteOff;
                        }
    
                        $writeOffList[$body[$position]['offer_id']]['writeoff']+=$whq;
    
                        if($whq>0)
                        {
                            $body[$position]['writeOffWh'][] = 
                            [
                                'id' => $i['warehouse_id'], 
                                'code' => $whTick->code,
                                'title' => $whTick->title,
                                'address' => $whTick->address,
                                'quantity' => $whq,
                            ];
                        }
    
                        $body[$position]['stocks'] = $offers->first(function($o) use ($body, $position){return $o->id==$body[$position]['offer_id'];})->stocks;
                    }
                }
    
                $balance = array_map(
                    function($arr) use ($writeOffList) 
                    {
                        $arr['items'] = array_filter($arr['items'], function($a) use ($writeOffList){
                            return $writeOffList[$a['offer_id']]['writeoff']<$writeOffList[$a['offer_id']]['total'];
                        });
                        return $arr;
                    }, 
                    $balance
                );
            }
        }

        return $body;
    }

    /**
     * Резервирование товаров
     * 
     * @param array $toReserve массив позиций для резервирования
     * @return bool true при успешном резервировании
     * @throws \Exception
     */
    public static function reservation(array $toReserve):bool
    {
        try {
            DB::transaction(function() use ($toReserve) {

                if(!is_array($toReserve)) $toReserve = [$toReserve];

                foreach ($toReserve as $position) 
                {
                    ReservedProduct::create($position->toArray());
                    StocksBalance::whereWarehouseId($position->warehouse_id)->whereOfferId($position->offer_id)->decrement('quantity', $position->quantity);
                }
            });
        } catch (Exception $e) {
            throw $e;
        }

        return true;
    }

    /**
     * Отмена резервирования товаров заказа
     * 
     * @param int $order_id Order::id
     * @return bool true при успешном резервировании
     * @throws \Exception
     */
    public static function cancelReservation(int $order_id):bool
    {
        $reservation = ReservedProduct::whereOrderId($order_id)->get();

        try {
            DB::transaction(function() use ($reservation) 
            {
                foreach ($reservation as $position) 
                {
                    StocksBalance::whereWarehouseId($position->warehouse_id)->whereOfferId($position->offer_id)->increment('quantity', $position->quantity);
                    $position->delete();
                }
            });
        } catch (Exception $e) {
            throw $e;
        }

        return true;
    }

    /**
     * Отправление товаров со склада
     * 
     * @param int $order_id Order::id
     * @param int $warehouse Warehouse::id,
     */
    public static function createShipment(Order $order, int $warehouse_id, ?string $track=null):bool
    {
        $positions = $order->reserved->filter(function($arr) use ($warehouse_id){
            return $arr->warehouse_id==$warehouse_id;
        })->map(function($arr){
            return [
                'id'=>$arr->id,
                'name' => $arr->name,
                'product_id' => $arr->product_id,
                'offer_id' => $arr->offer_id,
                'quantity' => 1,
                'measure' => $arr->product->measure_value->value,
                'weight' => $arr->offer->weight,
                'width' => $arr->offer->width,
                'height' => $arr->offer->height,
                'length' => $arr->offer->length,
            ];
        });

        if ($order->shipping) $shipping = ShippingService::get($order->shipping['key']);
        else $shipping = ['key'=>null, 'title'=>'Самовывоз'];

        if($order->delivery){
            $address="{$order->delivery['region']}; {$order->delivery['city']}; ул. {$order->delivery['street']} д.{$order->delivery['house']}, кв.{$order->delivery['apartment']}";
        }
        else $address='';

        $ship = Shipping::create([
            'order_id'=>$order->id,
            'warehouse_id'=>$warehouse_id,
            'user_id' => Auth::user()->id,
            'positions' => $positions,
            'status'=>90,
            'track' => $track,
            'address'=>$address,
            'carrier_key' => $shipping['key'],
            'carrier' => $shipping['title'],
        ]);

        if ($ship) $order->reserved()->whereWarehouseId($warehouse_id)->delete();
        else return false;

        return true;
    }

    public static function reservedPositionsReadyForShipping($order_id, $warehouse_id):bool
    {
        $check = ReservedProduct::whereOrderId($order_id)->whereWarehouseId($warehouse_id)->where(function(Builder $query){
            $query->whereNull('mark_wh')->orWhereNotIn('mark_wh', [78, 79, 88]);
        })->count();

        $order = Order::whereId($order_id)->firstOrFail(['status', 'shipping']);

        if ($check==0) {
            if($order->shipping==null) $status=88;
            else $status=79;

            ReservedProduct::whereOrderId($order_id)->whereWarehouseId($warehouse_id)->update(['mark_wh'=>$status]);
        }

        return $check==0;
    }

    /**
     * Возврат позиций на склад из возвращенной доставки
     * 
     * @param Shipping $shipping Shipping model
     * @return Boolean true при успешном возврате позиций на склад
     * @throws \Exception Не удалось вернуть товары на склад
     */
    public static function returnPositionsFromShipping(Shipping &$shipping):bool
    {
        $positions = array_map(function($arr) use ($shipping){
            return [
                'name' => $arr['name'],
                'order_id' => $shipping->order_id,
                'product_id'=>$arr['product_id'],
                'offer_id'=>$arr['offer_id'],
                'warehouse_id'=>$shipping->warehouse_id,
                'shipping_id'=>$shipping->id,
                'quantity'=>$arr['quantity']
            ];
        }, $shipping->positions);

        try {
            DB::transaction(function() use ($positions) {
                foreach ($positions as $position) ReservedProduct::create($position);
            });
        } catch (Exception $e) {
            throw $e;
        }

        return true;
    }
}
