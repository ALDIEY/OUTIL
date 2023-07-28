<?php

namespace App\Providers;

use GuzzleHttp\Psr7\Header;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // $debug = env('APP_DEBUG'); // true

    //     Header::set('Access-Control-Allow-Origin', '*'); 
    //     Header::set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    //     Header::set('Content-Type', 'application/json');
    }
}
