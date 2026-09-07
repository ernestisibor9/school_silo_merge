<?php

namespace App\Providers;
use Illuminate\Support\Facades\Response;

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
    Response::macro('apiJson', function ($data = [], $status = 200, array $headers = []) {
        return response()->json(
            $data,
            $status,
            $headers,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    });
}
}
