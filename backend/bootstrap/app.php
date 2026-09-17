<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\PostTooLargeException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // This app is an SPA + token API and has no server-rendered "login"
        // route. Without this, an unauthenticated API call that doesn't send
        // `Accept: application/json` makes Authenticate::redirectTo() resolve
        // route('login') eagerly and blow up with a 500 ("Route [login] not
        // defined") before the JSON exception renderer below ever runs.
        // Returning null leaves the 401 to be rendered as JSON.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') ? null : '/login',
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // A body larger than php.ini's `post_max_size` is discarded by PHP
        // before Laravel sees it, so the guide-video upload would otherwise
        // fail with a bare "Pilih berkas video terlebih dahulu" — technically
        // true, useless in practice. Name the real cause instead.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Berkas terlalu besar untuk diterima server. '
                    .'Batas PHP saat ini: upload_max_filesize = '.ini_get('upload_max_filesize')
                    .', post_max_size = '.ini_get('post_max_size')
                    .'. Naikkan kedua nilai itu di php.ini lalu mulai ulang server.',
            ], 413);
        });
    })->create();
