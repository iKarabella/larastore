<?php

namespace App\Modules\Larastore\Http\Controllers\Admin;

use App\Helpers\RightMenu;
use App\Modules\Larastore\Http\Controllers\Controller;
use App\Modules\Larastore\Http\Requests\Admin\OrdersListRequest;
use App\Modules\Larastore\Http\Resources\Admin\DeliveryShortResource;
use App\Modules\Larastore\Http\Resources\Admin\OfferResource;
use App\Modules\Larastore\Http\Resources\Admin\OrderResource;
use App\Modules\Larastore\Http\Resources\Admin\ProductResource;
use App\Modules\Larastore\Http\Resources\SiteEntitiesValuesResource;
use App\Models\CatalogCat;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\Shipping;
use App\Models\SiteEntitiesValues;
use App\Models\Warehouse;
use App\Modules\Larastore\Services\WarehouseService;
use App\Services\Shipping\ShippingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Управление заказами
 */
class ManageController extends Controller
{
    public function index(Request $request)
    {
        if(!array_key_exists($request->user()->id, config('app.market_rights'))) abort(403);

        $rights = config('app.market_rights')[$request->user()->id];

        if(!method_exists($this, $rights[array_key_first($rights)])) abort(403);

        return $this->{$rights[array_key_first($rights)]}($request);
    }
    
    public function statistic(Request $request)
    {
        return Inertia::render('Admin/Market/Statistic', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'section'=>'statistic',
            'rights'=>config('app.market_rights')[$request->user()->id]
        ]);
    }
    
    public function orders(OrdersListRequest $request)
    {
        $validated = $request->validated();

        $orders = Order::with(['status_info']);

        $filters = $request->session()->get('manage.orders.filters', collect([
            'statuses' => SiteEntitiesValues::whereEntity(14)->get()->map(function($arr){return ['status'=>$arr->id, 'name'=>$arr->value, 'on'=>true];}),
            'dates' => [new Carbon()->startOfWeek(), new Carbon()->endOfDay()],
            'sortDesc' => false
        ]));
        
        if(isset($validated['filters']['statuses'])) 
        {
            if(array_any($validated['filters']['statuses'], function($a){return $a['on']==true;}))
            {
                $filters['statuses'] = $filters['statuses']->map(function($s) use ($validated){
                    $s['on'] = array_find($validated['filters']['statuses'], function($f) use ($s){return $f['status']==$s['status'];})['on'];
                    return $s;
                });
            }
        }
        if(isset($validated['filters']['dates'])) $filters['dates'] = $validated['filters']['dates'];
        if(isset($validated['filters']['sortDesc'])) $filters['sortDesc'] = $validated['filters']['sortDesc'];

        $request->session()->put('manage.orders.filters', $filters);

        if($filters['statuses']) $orders->whereIn('status', $filters['statuses']->filter(function($f){return $f['on'];})->pluck('status'));
        if(isset($filters['dates']) && count($filters['dates'])) 
        {
            if ($filters['dates'][0]) $orders->where('created_at', '>', new Carbon($filters['dates'][0])->startOfDay());
            if ($filters['dates'][1]) $orders->where('created_at', '<', new Carbon($filters['dates'][1])->endOfDay());
        }
        if(isset($filters['sortDesc'])) 
        {
            if($filters['sortDesc']) $orders->orderByDesc('created_at');
            else $orders->orderBy('created_at');
        }
        else $orders->orderBy('created_at');

        return Inertia::render('Admin/Market/Orders', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'section'=>'orders',
            'filters'=>$filters,
            'orders'=>OrderResource::collection($orders->paginate(30)),
            'rights'=>config('app.market_rights')[$request->user()->id]
        ]);
    }
    
    public function orderManage(Request $request, int $orderId)
    {         
        $order = Order::whereId($orderId)->with(['status_info', 'comments'])->firstOrFail();
        $whs = Warehouse::all(['id', 'title', 'code', 'address', 'phone']);
        $order->body = WarehouseService::writeOffCreate($order, $whs);
        return Inertia::render('Admin/Market/OrderManage', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'section'=>'orders',
            'warehouses'=>Warehouse::all(['id', 'title', 'code', 'address', 'phone']),
            'shipping'=> ShippingService::list(),
            'order'=>OrderResource::make($order)->resolve(),
            'rights'=>config('app.market_rights')[$request->user()->id],
        ]);
    }

    public function catalog(Request $request)
    {
        return Inertia::render('Admin/Market/Catalog', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'section'=>'catalog',
            'rights'=>config('app.market_rights')[$request->user()->id],
            'categories'=>CatalogCat::all()
        ]);
    }

    public function products(Request $request, $link=null)
    {
        if ($link!=null) $product=Product::whereLink($link)->with(['categories', 'offers', 'media'])->firstOrFail();
        else if ($request->id) $product=Product::whereId($request->id)->with(['categories', 'offers', 'media'])->firstOrFail();
        else{
            $product=collect([
                'id'=>null,
                'title'=>'',
                'description'=>'',
                'visibility'=>false,
                'categories'=>[],
                'media'=>[],
                'created_at'=>null,
                'updated_at'=>null,
            ]);
            if ($request->category) {
                $product->categories[] = CatalogCat::whereId($request->category)->first();
            }
        }
        
        return Inertia::render('Admin/Market/EditProduct', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'product'=>ProductResource::make($product)->resolve(),
            'entities'=>SiteEntitiesValuesResource::collection(SiteEntitiesValues::whereEntity(15)->get())->resolve(),
            'categories'=>CatalogCat::all(),
            'rights'=>config('app.market_rights')[$request->user()->id]
        ]);
    }

    public function offer(Request $request, $link, $offer_id=null)
    {

        $product=Product::whereLink($link)->with(['categories'])->firstOrFail();

        if ($offer_id) $offer = ProductOffer::whereId($offer_id)->with(['media'])->firstOrFail();
        else $offer= (object) [
            'id'=>null,
            'product_id'=>$product->id,
            'title'=>'',
            'baseprice'=>'0.00',
            'price'=>'0.00',
            'barcode'=>'',
            'art'=>'',
            'media'=>[],
            'visibility'=>true
        ];

        return Inertia::render('Admin/Market/EditOffer', [
            'status' => session('status'),
            'product'=>ProductResource::make($product)->resolve(),
            'offer'=>OfferResource::make($offer)->resolve(),
            'rightMenu' => RightMenu::get(),
            'rights'=>config('app.market_rights')[$request->user()->id]
        ]);
    }

    public function warehouse(Request $request)
    {
        return Inertia::render('Admin/Market/Warehouse', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'section'=>'warehouse',
            'warehouses'=>Warehouse::all(),
            'rights'=>config('app.market_rights')[$request->user()->id]
        ]);
    }

    public function delivery(Request $request)
    {
        $shipping = Shipping::whereCarrierKey('own')->with(['status_info', 'warehouse_info']);

        return Inertia::render('Admin/Market/Delivery', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'section'=>'delivery',
            'deliveries'=>DeliveryShortResource::collection($shipping->paginate(35)),
            'rights'=>config('app.market_rights')[$request->user()->id]
        ]);
    }
}
