<?php

namespace ApiCore;

use ApiCore\Console\Commands\MakeApiResourceCommand;
use ApiCore\Contracts\AuthRepositoryInterface;
use ApiCore\Contracts\RepositoryInterface;
use ApiCore\Providers\FirebaseServiceProvider;
use ApiCore\Repositories\AuthRepository;
use ApiCore\Traits\HasResponseMacro;
use ApiCore\Traits\HasRouteMacro;
use Illuminate\Support\ServiceProvider;
use Tymon\JWTAuth\Providers\LaravelServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    use HasResponseMacro;
    use HasRouteMacro;

    public function register()
    {
        $this->app->register(LaravelServiceProvider::class);
        $this->app->register(FirebaseServiceProvider::class);

        // Merge the package configuration so that `config('api-core.*')`
        // has sensible defaults even if the config file wasn't published.
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-core.php',
            'api-core'
        );
    }

    public function boot(): void
    {
        static::responseMacro();
        static::macroAuthRoute();

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'apicore');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load configuration
        $this->publishes([
            __DIR__ . '/../config/api-core.php' => config_path('api-core.php'),
        ], 'config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeApiResourceCommand::class,
            ]);
        }
    }
}


