<?php

namespace App\Modules\Larastore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Larastore\Http\Requests\Market\CreateOrderRequest;
use App\Modules\Larastore\Http\Requests\Market\StoreOrderRequest;
use App\Modules\Larastore\Models\Order;
use App\Modules\Larastore\Models\ProductOffer;
use App\Modules\Larastore\Services\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Redirect;
use App\Helpers\RightMenu;

class OrderController extends Controller
{

    public function startPagePost(CreateOrderRequest $request)
    {
        $order = [
            'positions'=>$request->positions,
            'total_sum'=>$request->total,
            'customer'=>[
                'name'=>$request->user()->name,
                'patronymic'=>$request->user()->patronymic??'',
                'surname'=>$request->user()->surname??'',
                'phone'=>'+'.$request->user()->phone,
            ],
            'delivery'=>[
                'region'=>'Ленинградская область', //TODO удалить
                'city'=>'Санкт-Петербург',
                'street'=>'Кораблестроителей',
                'house'=>'32к1',
                'apartment'=>'176',
            ],
            'code'=>''
        ];

        $request->session()->put('order_create_form', $order);

        return $this->startPage($request);
    }

    public function startPage(Request $request)
    {
        $order = $request->session()->get('order_create_form', false);

        //TODO проверка доступного количество на складах и указанного в заказе

        if(!$order) return Redirect::route('market.cart');

        return Inertia::render('Market/OrderCreate', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'order' => $order,
        ]);
    }

    public function store(StoreOrderRequest $request)
    {
        $offers = ProductOffer::with(['stocks', 'product'])->whereIn('id', array_column($request->positions, 'offer'))->get();
        $userCart = $request->session()->get('user_cart', []);
        $total_sum = 0;
        $total_count = 0;
        $orderBody=[];

        foreach($request->positions as $position)
        {
            $offer = $offers->firstOrFail(function($a) use ($position){return $a->id==$position['offer'] && $a->product_id==$position['product'];});
            
            if($position['quantity']>$offer->stocks->pluck('quantity')->sum()) throw ValidationException::withMessages([
                'order' => ['Наличие на складе меньше указанного в заказе.'],
            ]); 
 
            $total_sum += $position['quantity']*$offer->price;
            $total_count++;
            $orderBody[]=[
                'product_id'=>$offer->product->id,
                'offer_id'=>$offer->id,
                'name'=>"{$offer->product->title}, {$offer->title}",
                'price'=>$offer->price,
                'quantity'=>$position['quantity'],
                'amount'=>round($position['quantity']*$offer->price, 2)
            ];
        }

        if((int)$total_sum != (int)$request->total_sum) throw ValidationException::withMessages([
            'order' => ['Некорректная сумма заказа'],
        ]); 

        $userCart = array_filter($userCart, function($a) use ($offers){
            return !in_array($a['offer'], $offers->pluck('id')->toArray());
        });

        $order = Order::create([
            'user_id'=>$request->user()->id,
            'status'=>76,
            'amount'=>$total_sum,
            'discount'=>null,
            'body'=>$orderBody,
            'customer'=>$request->customer,
            'delivery'=>$request->delivery,
            'recurrent'=>false,
            'school_id'=>null
        ]);
        
        if (!$order) throw ValidationException::withMessages([
            'order' => ['Не удалось создать заказ'],
        ]);
        
        if (count($userCart)) $request->session()->put('user_cart', $userCart);
        else $request->session()->forget('user_cart'); //TODO fix: он ресается из localStorage цука такая

        $request->session()->forget('order_create_form');
        $request->session()->flash('updateCartInLocalStore', count($userCart)?$userCart:'removeAll');

        Redirect::route('profile.order', [$order->id]);
    }
}
