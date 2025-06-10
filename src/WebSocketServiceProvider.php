<?php

namespace Layman\LaravelWebsocket;

use Illuminate\Support\ServiceProvider;
use Layman\LaravelWebsocket\Commands\WebSocketCommand;

class WebSocketServiceProvider extends ServiceProvider
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
        ], 'websocket');

        if ($this->app->runningInConsole()) {
            $this->commands([
                WebSocketCommand::class,
            ]);
            // 自动加载迁移文件
            $this->loadMigrationsFrom(__DIR__ . '/../src/Database');
        }

        $this->loadViewsFrom(__DIR__ . '/../src/Views', 'websocket');

        $this->publishes([
            __DIR__ . '/../src/Views/websocket.blade.php' => resource_path('views/websocket.blade.php'),
        ], 'websocket-view');
    }
}
