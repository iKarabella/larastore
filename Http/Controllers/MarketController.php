<?php

namespace App\Modules\Larastore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Larastore\Http\Requests\Market\SaveCartRequest;
use App\Modules\Larastore\Http\Resources\Market\BasketListResorce;
use App\Modules\Larastore\Http\Resources\Market\ProductResource;
use App\Modules\Larastore\Http\Resources\Market\ProductsListResource;
use App\Models\CatalogCat;
use App\Models\Product;
use App\Models\ProductOffer;
use DB;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Inertia;
use Inertia\Response;
use App\Helpers\RightMenu;

class MarketController extends Controller
{

    private function getBreadcrumb(?Product $product=null, ?string $cat = null):array
    {
        $breadcrumb = [
            ['title'=>'Catalog', 'link'=>route('market.catalog')],
        ];

        if($product!=null) 
        {
            $cat = $product->categories->firstOrFail()->code;
        }

        if($cat==null){
            $getCats = CatalogCat::whereNull('parent')->whereVisibility(true)->orderBy('sort')->get();
            $cats=[];
            foreach ($getCats as $c) $cats[]=['title'=>$c->title, 'link'=>route('market.catalog.cat', $c->code)];
            $breadcrumb[]=['title'=>'Категория', 'link'=>$cats];
            $currentCat=(object)['id'=>null];
        }
        else
        {
            $rawSql="WITH RECURSIVE Tree AS (
                    SELECT id, parent, title, code, sort FROM catalog_cats WHERE code = '$cat'
                    UNION ALL
                    SELECT cc.id, cc.parent, cc.title, cc.code, cc.sort FROM catalog_cats cc
                    JOIN Tree ON cc.id = Tree.parent
                )
                SELECT * FROM Tree WHERE id != '$cat';";

            $catsTree = DB::select($rawSql);
            
            $getCats = CatalogCat::where(function(Builder $query) use ($catsTree){
                $query->whereNull('parent');
                if (count($catsTree)) $query->orWhereIn('parent', array_column($catsTree, 'id'));
            })->whereVisibility(true)->get();

            $currentCat = $getCats->first(function($f) use ($cat) {return $f->code==$cat;});

            $func = function($arr) use ($getCats)
            {
                $links = $getCats->filter(function($f) use($arr){
                    return $f->parent == $arr->parent;
                });

                if (count($links)>1) {
                    $link = $links->map(function($l){
                        return [
                            'title'=>$l->title,
                            'link'=>route('market.catalog.cat', $l->code)
                        ];
                    })->values();
                }
                else {
                    $link = route('market.catalog.cat', $arr->code);
                }

                return [
                    'title'=>$arr->title,
                    'link'=>$link
                ];
            };

            $tree = array_map($func, array_reverse($catsTree));
            $childs = [];

            if($product==null)
            {
                $childCats = $getCats->filter(function($arr) use ($currentCat){
                    return $arr->parent == $currentCat->id;
                })->map(function($arr){
                    return [
                        'title'=>$arr->title,
                        'link'=>route('market.catalog.cat', $arr->code)
                    ];
                })->values();
    
                if ($childCats->count()) $childs = [[
                    'title'=>'Выбрать',
                    'link'=>$childCats
                ]];
            }

            array_push($breadcrumb, ...$tree, ...$childs);
        }

        if ($product!=null) $breadcrumb[]=['title'=>$product->title, 'link'=>''];

        return $breadcrumb;
    }

    public function index(Request $request, ?string $cat=null):Response
    {
        return Inertia::render('Market/Catalog', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'breadcrumb'=>$this->getBreadcrumb(null, $cat),
            'currentCat'=>$cat,
            'userCart' => $request->session()->get('user_cart', [])
        ]);
    }

    public function getProducts(Request $request):JsonResource
    {
        $products = Product::select(['id', 'title', 'link', 'short_description', 'measure', 'visibility', 'offersign'])
                           ->with(['media', 'publicOffersWithRel'])
                           ->whereVisibility(true);
        
        //if ($request->instock) $products->whereHas('publicOffersWithRel'); //->where(function(Builder $query){
        //     $query->whereRaw('0 < (SELECT count(*) FROM stocks_balances WHERE stocks_balances.quantity>0 AND stocks_balances.offer_id IN (SELECT product_offers.id FROM product_offers WHERE product_offers.product_id = products.id))');
        // });

        if($request->category) 
        {
            $products->whereHas('categories', function(Builder $query) use ($request){
                $query->whereRaw('catalog_cats.id IN (
                    with recursive hierarchy(id) as (
                    select id from catalog_cats where catalog_cats.code=\''.$request->category.'\'
                    union all
                    select catalog_cats.id from catalog_cats,hierarchy where catalog_cats.parent = hierarchy.id 
                    )
                    select id from hierarchy
                )');
            });
        }

        //dd(ProductsListResource::collection($products->take(2)->get()));
        return ProductsListResource::collection($products->paginate(27));
    }

    public function product(Request $request, string $code):Response
    {
        $product = Product::whereLink($code)
                          ->with(['categories', 'media', 'publicOffersWithRel', 'measure_value'])
                          ->firstOrFail();

        return Inertia::render('Market/ProductCard', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'product'   => ProductResource::make($product)->resolve(),
            'breadcrumb' => $this->getBreadcrumb($product)
        ]);
    }

    public function cart(Request $request)
    {
        $userCard = $request->session()->get('user_cart', []);
        $basket = [];

        if($userCard){
            $offers = ProductOffer::with(['productWithFirstMedia', 'mediaFirst', 'stocks'])->whereIn('id', array_column($userCard, 'offer'))->get();
            $basket = BasketListResorce::collection($offers)->resolve();
        }

        return Inertia::render('Market/Basket', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'basket' => $basket
        ]);
    }

    public function cartUpdate(SaveCartRequest $request)
    {
        $request->session()->put('user_cart', $request->cart);
        $offers = ProductOffer::with(['productWithFirstMedia', 'mediaFirst', 'stocks'])->whereIn('id', array_column($request->cart, 'offer'))->get();

        return Inertia::render('Market/Basket', [
            'status' => session('status'),
            'rightMenu' => RightMenu::get(),
            'basket' => BasketListResorce::collection($offers)->resolve()
        ]);
    }
}
