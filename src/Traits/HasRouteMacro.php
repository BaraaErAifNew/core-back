<?php

namespace ApiCore\Traits;

use Illuminate\Support\Facades\Route;

trait HasRouteMacro
{
    public function macroAuthRoute()
    {
        Route::macro('auth', function ($middleware, $controller = 'AuthController') {
            Route::controller($controller)
                ->prefix('auth')
                ->name('auth.')
                ->group(function () use ($controller, $middleware) {
                    Route::post('login', "$controller@login");
                    Route::post('send-verification-code', "$controller@sendVerificationCode")->middleware('throttle:3,1');
                    Route::patch('verify', "$controller@verify");

                    Route::middleware($middleware)->group(function () use ($controller) {
                        Route::middleware('customer')->group(function () use ($controller) {
                            Route::get('profile', "$controller@profile");
                            Route::put('update', "$controller@update");
                        });
                        Route::post('logout', "$controller@logout");
                        Route::delete('delete', "$controller@delete");
                    });
                });
        });
    }
}
