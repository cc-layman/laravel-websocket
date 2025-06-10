<?php

namespace Layman\LaravelWebsocket\Commands;

use Illuminate\Console\Command;
use Layman\LaravelWebsocket\Server\WebSocketServer;

class WebSocketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:start';

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
