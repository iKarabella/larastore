<?php

use App\Modules\Larastore\Http\Controllers\Admin\ManageActionsController;
use App\Modules\Larastore\Http\Controllers\Admin\ManageController;
use App\Modules\Larastore\Http\Controllers\Admin\OrderStatusController;
use App\Modules\Larastore\Http\Controllers\Admin\WarehouseController;

use App\Modules\Larastore\Http\Controllers\MarketActionsController;
use App\Modules\Larastore\Http\Controllers\MarketController;
use App\Modules\Larastore\Http\Controllers\OrderController;

Route::get('/market', [MarketController::class, 'index'])->name('market.catalog');
Route::post('/market', [MarketController::class, 'getProducts']);
Route::get('/market/cart', [MarketController::class, 'cart'])->name('market.cart');
Route::post('/market/cart', [MarketController::class, 'cartUpdate']);
Route::post('/market/actions/saveCart', [MarketActionsController::class, 'saveCart'])->name('market.saveCart');
Route::post('/market/actions/notifyAboutAdmission', [MarketActionsController::class, 'notifyAboutAdmission'])->name('market.notifyAboutAdmission');
Route::get('/market/catalog/{cat}', [MarketController::class, 'index'])->name('market.catalog.cat');
Route::get('/market/products/{product}', [MarketController::class, 'product'])->name('market.product');

Route::post('/market/order/store', [OrderController::class, 'store'])->name('market.order.store');
Route::get('/market/order', [OrderController::class, 'startPage'])->name('market.create_order');
Route::post('/market/order', [OrderController::class, 'startPagePost']);

Route::get('/market/manage', [ManageController::class, 'index'])->name('market.manage');
Route::get('/market/manage/statistic', [ManageController::class, 'statistic'])->name('market.manage.statistic');
Route::get('/market/manage/catalog', [ManageController::class, 'catalog'])->name('market.manage.catalog');
Route::get('/market/manage/products', [ManageController::class, 'products'])->name('market.manage.products');
Route::get('/market/manage/products/{link}', [ManageController::class, 'products'])->name('market.manage.product.link');
Route::get('/market/manage/products/{link}/offers/', [ManageController::class, 'offer'])->name('market.manage.product.newOffer');
Route::get('/market/manage/products/{link}/offers/{offer_id}', [ManageController::class, 'offer'])->name('market.manage.product.offer');
Route::get('/market/manage/warehouse', [ManageController::class, 'warehouse'])->name('market.manage.warehouse');
Route::get('/market/manage/warehouse/new', [WarehouseController::class, 'edit'])->name('market.manage.createwarehouse');
Route::get('/market/manage/warehouse/@{code}', [WarehouseController::class, 'edit'])->name('market.manage.editwarehouse');
Route::post('/market/manage/warehouse/store', [WarehouseController::class, 'store'])->name('market.manage.storewarehouse');
Route::put('/market/manage/warehouse/receipt', [WarehouseController::class, 'storeReceipt'])->name('market.manage.storeReceipt');
Route::get('/market/manage/warehouse/picking/{orderId}', [WarehouseController::class, 'orderManage'])->name('market.manage.warehouse.order');
Route::get('/market/manage/delivery', [ManageController::class, 'delivery'])->name('market.manage.delivery');
Route::get('/market/manage/orders', [ManageController::class, 'orders'])->name('market.manage.orders');
Route::post('/market/manage/orders', [ManageController::class, 'orders']);
Route::get('/market/manage/orders/{orderId}', [ManageController::class, 'orderManage'])->name('market.manage.orderManage');

Route::post('/market/manage/set_status/cancel', [OrderStatusController::class, 'cancel'])->name('market.manage.order.cancel');   
Route::post('/market/manage/set_status/waitingPayment', [OrderStatusController::class, 'waitingPayment'])->name('market.manage.waitingPayment');
Route::post('/market/manage/set_status/orderToAssembly', [OrderStatusController::class, 'orderToAssembly'])->name('market.manage.orderToAssembly');

Route::post('/market/manage/actions/editPosition', [ManageActionsController::class, 'editPosition'])->name('market.manage.editPosition');
Route::post('/market/manage/actions/editShipping', [ManageActionsController::class, 'editShipping'])->name('market.manage.editShipping');
Route::post('/market/manage/actions/saveComment', [ManageActionsController::class, 'saveComment'])->name('market.manage.saveComment');

Route::post('/market/manage/actions/get_products', [ManageActionsController::class, 'getProducts'])->name('market.manage.getProducts');
Route::post('/market/manage/actions/store_product', [ManageActionsController::class, 'storeProduct'])->name('market.manage.storeProduct');
Route::post('/market/manage/actions/store_offer', [ManageActionsController::class, 'storeOffer'])->name('market.manage.storeOffer');
Route::post('/market/manage/actions/search_product', [ManageActionsController::class, 'searchProduct'])->name('market.manage.searchProduct');
Route::post('/market/manage/actions/get_wh_acts', [ManageActionsController::class, 'getWarehouseActs'])->name('market.manage.getWarehouseActs');
Route::post('/market/manage/actions/get_wh_stocks', [ManageActionsController::class, 'getWarehouseStocks'])->name('market.manage.getWarehouseStocks');
Route::post('/market/manage/actions/get_wh_orders', [ManageActionsController::class, 'getWarehouseOrders'])->name('market.manage.getWarehouseOrders');
Route::post('/market/manage/actions/wh_order_sent', [ManageActionsController::class, 'whOrderSent'])->name('market.manage.order.whOrderSent');
Route::post('/market/manage/actions/wh_mark_position', [ManageActionsController::class, 'whMarkPosition'])->name('market.manage.whMarkPosition');
Route::post('/market/manage/actions/store_product_media', [ManageActionsController::class, 'storeProductMedia'])->name('market.manage.productMedia');
Route::post('/market/manage/actions/store_media_sort', [ManageActionsController::class, 'storeProductMediaSorting'])->name('market.manage.productMedia.sorting');
Route::post('/market/manage/actions/remove_media', [ManageActionsController::class, 'removeProductMedia'])->name('market.manage.productMedia.remove');
Route::post('/market/manage/actions/take_to_delivery', [ManageActionsController::class, 'takeToDelivery'])->name('market.manage.order.takeToDelivery');
Route::post('/market/manage/actions/complete_delivery', [ManageActionsController::class, 'completeDelivery'])->name('market.manage.order.completeDelivery');

Route::delete('/market/manage/catalog/category', [ManageActionsController::class, 'deleteCategory'])->name('market.catsManage');
Route::post('/market/manage/catalog/category', [ManageActionsController::class, 'storeCategory']);
Route::post('/market/manage/catalog/sorting_category', [ManageActionsController::class, 'sortingCategory'])->name('market.catsSortManage');