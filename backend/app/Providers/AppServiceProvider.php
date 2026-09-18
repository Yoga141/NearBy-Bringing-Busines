<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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

        // Outside production, report lazy loading (the usual source of N+1
        // queries) to the log. Logged rather than thrown, so a missed
        // eager-load shows up in storage/logs without breaking a page.
        if (! $this->app->isProduction()) {
            Model::preventLazyLoading();
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
                Log::warning('Lazy loading terdeteksi (kemungkinan N+1): '.$model::class.'::'.$relation);
            });
        }

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $tooMany = fn (string $message) => fn (Request $request, array $headers) => response()->json(
            ['message' => $message],
            429,
            $headers,
        );

        // Password guessing: a few tries per account per IP, plus a looser
        // per-IP ceiling so one address can't sweep through many accounts.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)
                ->by('login|'.Str::lower((string) $request->input('email')).'|'.$request->ip())
                ->response($tooMany('Terlalu banyak percobaan masuk. Tunggu 1 menit lalu coba lagi.')),
            Limit::perMinute(30)
                ->by('login-ip|'.$request->ip())
                ->response($tooMany('Terlalu banyak percobaan masuk dari jaringan ini. Coba lagi nanti.')),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)
            ->by('register|'.$request->ip())
            ->response($tooMany('Terlalu banyak pendaftaran dari jaringan ini. Coba lagi nanti.')));

        // Public forms that write to the database (help widget).
        RateLimiter::for('public-forms', fn (Request $request) => Limit::perMinute(6)
            ->by('forms|'.($request->user('sanctum')?->id ?? $request->ip()))
            ->response($tooMany('Terlalu banyak kiriman. Tunggu sebentar lalu coba lagi.')));
    }
}
