<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
        // Tamu yang membuka halaman wajib login diarahkan ke /login
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Terlalu banyak percobaan login: kembali ke form dengan pesan yang jelas
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            if ($request->is('login')) {
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam 1 menit.']);
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('ajax/*') || $request->is('admin/ajax/*') || $request->expectsJson(),
        );
    })->create();
