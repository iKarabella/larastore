<?php

namespace App\Modules\Larastore\Http\Controllers\Admin;

use App\Helpers\RightMenu;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Market\StoreWarehouseReceiptRequest;
use App\Http\Requests\Admin\Market\StoreWarehouseRequest;
use App\Http\Resources\Admin\Market\OrderResource;
use App\Http\Resources\Admin\Market\WarehouseResource;
use App\Models\Order;
use App\Models\ProductOffer;
use App\Models\StocksBalance;
use App\Models\Warehouse;
use App\Models\WarehouseAct;
use App\Services\Caschier\CaschierService;
use App\Services\Shipping\ShippingService;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Redirect;

class WarehouseController extends Controller
{
    public function edit(Request $request, $wh=null)
    {
        if($wh) $warehouse = Warehouse::whereCode($wh)->firstOrFail();
        else $warehouse = [];
        return Inertia::render('Admin/Market/EditWarehouse', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'warehouse' => WarehouseResource::make($warehouse)->resolve(),
            'rights'=>config('app.market_rights')[$request->user()->id]
        ]);
    }

    public function store(StoreWarehouseRequest $request)
    {
        Warehouse::whereId($request->id)->firstOrNew()->fill($request->validated())->save();

        if ($request->caschier && $request->caschier_settings) 
        {
            CaschierService::setSettings('warehouse'.$request->id, $request->caschier_settings);
        }

        return Redirect::route('market.manage.warehouse');
    }

    public function storeReceipt(StoreWarehouseReceiptRequest $request)
    {
        DB::transaction(function() use ($request) {
            
            foreach ($request->items as $item) 
            {
                StocksBalance::updateOrCreate(
                    [
                        'warehouse_id'=>$request->warehouse, 
                        'offer_id'=>$item['offer_id']
                    ], 
                    [
                        'quantity'=>DB::raw('quantity + '.$item['quantity'])
                    ]
                );

                ProductOffer::whereId($item['offer_id'])->update(['baseprice'=>floatval($item['price'])]);
            }

            WarehouseAct::create(['user_id'=>$request->user()->id, 'warehouse_id'=>$request->warehouse, 'act'=>$request->items]);
        });
    }

    public function orderManage(Request $request, $orderId)
    {
        $order = Order::whereId($orderId)->with(['status_info', 'comments', 'reserved'])->firstOrFail();
        $wh    = Warehouse::whereId($request->warehouse)->firstOrFail(['id', 'title', 'code', 'address']);

        if($order->shipping && !empty($order->shipping['key'])) $shipping = ShippingService::get($order->shipping['key']);
        else $shipping = null;

        return Inertia::render('Admin/Market/OrderPicking', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'section' => 'warehouse',
            'warehouse' => $wh,
            'shipping' => $shipping,
            'order' => OrderResource::make($order)->resolve(),
            'rights' => config('app.market_rights')[$request->user()->id],
        ]);
    }
}
