<?php

namespace App\Modules\Larastore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Larastore\Http\Requests\Admin\CompleteDeliveryRequest;
use App\Modules\Larastore\Http\Requests\Admin\DeleteCatRequest;
use App\Modules\Larastore\Http\Requests\Admin\getWarehouseStocksRequest;
use App\Modules\Larastore\Http\Requests\Admin\OrderEditPositionRequest;
use App\Modules\Larastore\Http\Requests\Admin\OrderShippingStoreRequest;
use App\Modules\Larastore\Http\Requests\Admin\OrderStoreCommentRequest;
use App\Modules\Larastore\Http\Requests\Admin\OrderToSendFromWarehouseRequest;
use App\Modules\Larastore\Http\Requests\Admin\RemoveMediaRequest;
use App\Modules\Larastore\Http\Requests\Admin\SearchProductRequest;
use App\Modules\Larastore\Http\Requests\Admin\SetCatSortRequest;
use App\Modules\Larastore\Http\Requests\Admin\StoreCatRequest;
use App\Modules\Larastore\Http\Requests\Admin\StoreOfferRequest;
use App\Modules\Larastore\Http\Requests\Admin\StoreProductMediaRequest;
use App\Modules\Larastore\Http\Requests\Admin\StoreProductMediaSortingRequest;
use App\Modules\Larastore\Http\Requests\Admin\StoreProductRequest;
use App\Modules\Larastore\Http\Requests\Admin\StoreWarehouseMarkRequest;
use App\Modules\Larastore\Http\Requests\Admin\takeToDeliveryRequest;
use App\Modules\Larastore\Http\Resources\Admin\OrdersListResource;
use App\Modules\Larastore\Http\Resources\Admin\ProductResource;
use App\Modules\Larastore\Http\Resources\Admin\ProductOfferResource;
use App\Modules\Larastore\Http\Resources\Admin\StockBalanceResource;
use App\Modules\Larastore\Http\Resources\Admin\WarehouseActResource;
use App\Models\CatalogCat;
use App\Models\Order;
use App\Models\OrderComment;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOffer;
use App\Models\ReservedProduct;
use App\Models\Shipping;
use App\Models\WarehouseAct;
use App\Modules\Larastore\Services\WarehouseService;
use App\Services\OrderService;
use App\Services\Shipping\ShippingService;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Http\Request;
use Illuminate\Contracts\Database\Query\Builder;
use DB;
use Illuminate\Validation\ValidationException;
use Redirect;

class ManageActionsController extends Controller
{
    public function deleteCategory(DeleteCatRequest $request)
    {
        CatalogCat::whereId($request->id)->delete();
    }

    public function storeCategory(StoreCatRequest $request)
    {
        $validated = $request->validated();

        CatalogCat::firstOrNew(['id'=>$validated['id']])->fill($validated)->save();
    }

    public function sortingCategory(SetCatSortRequest $request)
    {
        CatalogCat::whereId($request->id)->update(['sort' => $request->sort]);
    }

    public function getProducts(Request $request)
    {
        $products = Product::with(['categories', 'media', 'offers']);

        if($request->category) $products->whereRaw("id IN (SELECT product_id FROM product_categories WHERE cat_id = ?)",$request->category);

        return ProductResource::collection($products->orderBy('id', 'desc')->paginate(50));
    }

    public function getWarehouseActs(Request $request)
    {
        return WarehouseActResource::collection(WarehouseAct::orderBy('created_at', 'desc')->paginate(25));
    }
    public function getWarehouseStocks(getWarehouseStocksRequest $request)
    {        
        $sb = DB::table('stocks_balances')
                ->select(['products.title as ptitle', 'products.id as pid', 'product_offers.title as otitle',
                          'product_offers.id as oid', 'stocks_balances.quantity', 'site_entities_values.value as measure'])
                ->leftJoin('product_offers', 'product_offers.id', '=', 'stocks_balances.offer_id')
                ->leftJoin('products', 'products.id', '=', 'product_offers.product_id')
                ->leftJoin('site_entities_values', 'site_entities_values.id', '=', 'products.measure')
                ->where('stocks_balances.warehouse_id', $request->warehouse)
                ->where('stocks_balances.quantity', '>', 0);
        
        if ($request->category) $sb->whereRaw("products.id IN (SELECT product_id FROM product_categories WHERE id = {$request->category})");
        if ($request->search) $sb->where(function(Builder $query) use ($request){
            $query->where('products.title', 'like', '%'.$request->search.'%')
                  ->orWhere('product_offers.title', 'like', '%'.$request->search.'%')
                  ->orWhere('products_offers.art', 'like', '%'.$request->search.'%');
        });

        return StockBalanceResource::collection($sb->paginate(25));
    }

    public function storeProduct(StoreProductRequest $request)
    {
        $validated = $request->validated();


        $product = Product::whereId($request->id)->firstOrNew();
        
        $product->fill($validated)->save();

        $product->categories()->sync(collect($request->categories)->pluck('id'));

        return Redirect::route('market.manage.product.link', [$product->link]);
    }

    public function storeOffer(StoreOfferRequest $request)
    {
        $product = Product::whereId($request->product_id)->first(['link']);
        $offer = ProductOffer::whereId($request->id)->firstOrNew();
        $offer->fill($request->validated())->save();
        return Redirect::route('market.manage.product.link', [$product->link]);
    }

    public function searchProduct(SearchProductRequest $request)
    {
        $search = DB::table('product_offers')
                    ->select([
                        'products.title as product_title', 
                        'products.id as product_id', 
                        'product_offers.id', 
                        'product_offers.art', 
                        'product_offers.title',
                        'product_offers.baseprice',
                        'product_offers.price',
                        'site_entities_values.value as measure_val'
                    ])
                    ->leftJoin('products', 'products.id', '=', 'product_offers.product_id')
                    ->leftJoin('site_entities_values', 'site_entities_values.id', '=', 'products.measure')
                    ->whereLike('product_offers.art', '%'.$request->search.'%')
                    ->orWhereLike('product_offers.barcode', '%'.$request->search.'%')
                    ->orWhereIn('product_offers.product_id', DB::table('products')->select('id')->whereLike('title', '%'.$request->search.'%'))
                    ->limit(15)->get();
        return ProductOfferResource::collection($search)->resolve();
    }

    public function storeProductMedia(StoreProductMediaRequest $request)
    {
        $dir = 'images/market/'.(empty($request->product_id)?'offers':'products').'/'.(empty($request->product_id)?$request->offer_id:$request->product_id).'/';
        
        $validated = $request->validated();
        
        foreach($validated['files'] as $file)
        {
            if (Storage::missing('public/'.$dir)) Storage::makeDirectory('public/'.$dir);

            $image = Image::read($file);
            $filename  = uniqid().'.'.$file->getClientOriginalExtension();
            $thumbname = 'thumb_'.$filename;
            $side = $image->width()<$image->height() ? $image->width() : $image->height();
            $image->crop($side, $side)->save(storage_path('app/public/'.$dir.$filename));
            $image->resize(350, 350)->save(storage_path('app/public/'.$dir.$thumbname));

            ProductMedia::create([
                'product_id'=>$validated['product_id'], 
                'offer_id'=>$validated['offer_id'], 
                'type'=>$file->getMimeType(),
                'path'=>$dir.$filename, 
                'preview'=>$dir.$thumbname
            ]);
        }
    }

    public function storeProductMediaSorting(StoreProductMediaSortingRequest $request)
    {   
        $validated = $request->validated();

        DB::transaction(function() use ($validated) {
            foreach ($validated['files'] as $file) 
            {
                ProductMedia::whereId($file['id'])->update(['sort'=>$file['sort']]);
            }
        });
    }

    public function removeProductMedia(RemoveMediaRequest $request)
    {
        $file = ProductMedia::whereId($request->id)->first();

        Storage::delete(['public/'.$file->path, 'public/'.$file->preview]);

        $file->delete();
    }

    public function editPosition(OrderEditPositionRequest $request)
    {
        $validated = $request->validated();

        $order = Order::whereId($validated['order_id'])->firstOrFail();

        $find = array_find_key($order->body, function($arr) use ($validated){
            return $arr['offer_id']==$validated['offer_id'];
        });

        $body = $order->body;

        if($find!==null) 
        {
            if($validated['quantity']>0) 
            {
                $offer = ProductOffer::whereId($validated['offer_id'])->whereProductId($validated['product_id'])->with('stocks')->firstOrFail();
                $sum = $offer->stocks->pluck('quantity')->sum();
        
                if ($sum<$validated['quantity']) throw ValidationException::withMessages([
                    'quantity' => ["Количество позиций на складах ($sum) меньше указанного."],
                ]);
                
                $body[$find]['quantity']=(int)$validated['quantity'];
                $body[$find]['amount']=round($body[$find]['quantity']*$body[$find]['price'], 2);
            }
            else unset($body[$find]);

            $order->body = $body;
            $order->amount = array_sum(array_column($order->body, 'amount'));
            
            $order->save();
        }
    }

    public function editShipping(OrderShippingStoreRequest $request)
    {      
        if (empty($request->key)) $shipping = null;
        else {
            $info = ShippingService::get($request->key);
            $shipping = ['title' => $info['title'], 'key'=>$request->key, 'amount' => (float) $request->amount];
        }

        Order::whereId($request->order_id)->update(['shipping'=>$shipping]);
    }

    public function saveComment(OrderStoreCommentRequest $request){
        OrderComment::create($request->validated());
    }

    public function getWarehouseOrders(Request $request){
        $orders = Order::whereIn('status', [78,79,87,88,89])->with('status_info')->whereHas('reserved', function($q) use ($request){
            $q->whereWarehouseId($request->warehouse);
        })->orderBy('id');

        return OrdersListResource::collection($orders->paginate(30));
    }

    public function whMarkPosition(StoreWarehouseMarkRequest $request)
    {
        ReservedProduct::whereId($request->position_id)->whereOrderId($request->order_id)->update(['mark_wh'=>$request->mark]);
        $result = WarehouseService::reservedPositionsReadyForShipping($request->order_id, $request->warehouse_id);
        if ($result) OrderService::readyToShip($request->order_id);
    }

    /**
     * Отправка части заказа со склада.
     */
    public function whOrderSent(OrderToSendFromWarehouseRequest $request)
    {
        $order = Order::whereId($request->order_id)->with(['reserved_with_offer'])->firstOrFail();

        $result = WarehouseService::createShipment($order, $request->warehouse_id, $request->track);

        if ($result) OrderService::orderSent($request->order_id, $request->warehouse_id);
    }

    public function takeToDelivery(takeToDeliveryRequest $request)
    {
        $shipping = Shipping::whereId($request->shipping)->with('warehouse_info')->firstOrFail();

        $res = ShippingService::client($shipping->carrier_key)->takeToDelivery($shipping);

        if (!empty($res)) OrderComment::create([
            'order_id' => $shipping->order_id,
            'auto' => true,
            'title' => $res
        ]);
    }

    public function completeDelivery(CompleteDeliveryRequest $request)
    {
        $shipping = Shipping::whereId($request->shipping)->with(['warehouse_info'])->firstOrFail();
        
        if($request->delivered==true) 
        {
            $result = ShippingService::client($shipping->carrier_key)->delivered($shipping);
            if ($result) 
            {
                $shippings = Shipping::whereOrderId($shipping->order_id)->where('status', '!=', 92)->count();
                OrderService::setStatus($shipping->order_id, $shippings>0?89:81, 'Статус заказа изменен: ');
            }
        } 
        else
        {            
            $message = 'Отменена доставка #'.$shipping->id;
            if ($shipping->warehouse_info) $message .= ' со склада <b>['.$shipping->warehouse_info->code.']</b> '.$shipping->warehouse_info->title.'.';

            if($request->returnedToWarehouse) 
            {
                ShippingService::client($shipping->carrier_key)->returned($shipping);

                $shippings = Shipping::whereOrderId($shipping->order_id)->where('status', '!=', 92)->count();

                //TODO куда меняем статус? возможны несколько вариантов, есть успешные отправки, нет успешных отправок
    
                OrderService::setStatus($shipping->order_id, $shippings>0?87:78, 'Статус заказа изменен: ');

                $message.=' Товары возвращены в резерв.';
            }
            else 
            {
                $result = ShippingService::client($shipping->carrier_key)->failed();
            }

            OrderComment::create([
                'order_id' => $shipping->order_id,
                'auto' => true,
                'title' => $message,
                'comment' => '<div class="border border-gray-300 rounded-md bg-white p-2 text-gray-700">'.$request->comment.'</div>'
            ]);
        }
    }
}