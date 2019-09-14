<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $GLOBALS['permission'] = null;//主要防止过多查询数据库
        $GLOBALS['navigation'] = null;//主要防止过多查询数据库
        \Schema::defaultStringLength(191);
        Paginator::defaultView('pagination::du-bootstrap');
        Horizon::auth(
            function ($request) {
                // 通过认证可以访问
                if (\auth('admin')->check()) {
                    return true;
                }
            }
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
        //
    }
}
