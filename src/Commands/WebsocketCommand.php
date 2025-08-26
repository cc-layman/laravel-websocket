<?php

namespace Layman\LaravelWebsocket\Commands;

use Illuminate\Console\Command;
use Layman\LaravelWebsocket\Servers\WebSocketServer;

class WebsocketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'websocket server start';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $server = new WebSocketServer();
        $server->start();
    }
}
