# larastore

-- Install into your project
1. add submodule: git submodule add https://github.com/iKarabella/larastore app/Modules/Larastore
2. add provider in providers: App\Modules\Larastore\Providers\LarastoreServiceProvider::class
3. add middleware in middlewares: 'market'=> \App\Modules\Larastore\Http\Middleware\MarketMiddleware::class,
4. add in config/app.php: 'market_rights'=>[    // права в магазине
        1=>['statistic', 'orders', 'catalog', 'products', 'warehouse', 'delivery']
    ]

-- Updates

git submodule update --remote app/Modules/Larastore