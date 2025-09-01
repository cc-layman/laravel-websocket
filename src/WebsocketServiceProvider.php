<?php

namespace Layman\LaravelWebsocket;

use Illuminate\Support\ServiceProvider;
use Layman\LaravelWebsocket\Commands\WebsocketCommand;

class WebsocketServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/websocket.php', 'websocket');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        $this->publishes([
            __DIR__ . '/../config/websocket.php' => config_path('websocket.php'),
        ], 'websocket-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                WebsocketCommand::class,
            ]);
            $this->loadMigrationsFrom(__DIR__ . '/../src/Migrations');
        }

        $this->loadViewsFrom(__DIR__ . '/../src/Resources/views', 'websocket');

        $this->publishes([
            __DIR__ . '/../src/Resources/views/websocket.blade.php' => resource_path('views/websocket.blade.php'),
            __DIR__ . '/../src/Resources/js/ws-client.js' => public_path('js/ws-client.js'),
        ], 'websocket-views');
    }
}
