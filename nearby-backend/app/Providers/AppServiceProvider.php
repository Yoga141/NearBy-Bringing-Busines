<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
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
        // This API has no HATEOAS/pagination convention that needs the
        // default {"data": ...} envelope, and controllers already build
        // their own top-level shape (e.g. AuthController returns
        // {"user": ..., "token": ...}). Without this, a resource returned
        // directly from a route (e.g. GET /api/me) gets wrapped while one
        // nested in a plain array does not, which is an inconsistency
        // frontend clients would otherwise have to special-case per route.
        JsonResource::withoutWrapping();
    }
}
