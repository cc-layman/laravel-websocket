<?php

namespace Layman\LaravelWebsocket\Servers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Layman\LaravelWebsocket\Cores\Dispatcher;
use Layman\LaravelWebsocket\Cores\Connection;
use Layman\LaravelWebsocket\Cores\Heartbeat;
use Layman\LaravelWebsocket\Cores\Utils;
use Layman\LaravelWebsocket\Interfaces\AuthenticateInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Redis;
use Swoole\Http\Request;
use Swoole\WebSocket\Server;

class WebsocketServer
{
    protected array $config;
    protected Server $server;
    protected Connection $connection;
    protected Heartbeat $heartbeat;
    protected Dispatcher $dispatcher;

    public function __construct()
    {
        $this->config = config('websocket');
        $this->server = new Server($this->config['host'], $this->config['port'], $this->config['model']);
        $this->server->set([
            'package_max_length' => $this->config['package_max_length'],
        ]);
        if ($this->config['model'] == SWOOLE_PROCESS) {
            $this->server->set([
                'worker_num' => $this->config['worker_num'],
            ]);
        }
        $this->connection = new Connection();
        $this->heartbeat  = new Heartbeat($this->server, $this->connection, $this->config);
        $this->dispatcher = new Dispatcher($this->server, $this->connection, $this->heartbeat, $this->config);
        $this->events();
    }

    public function start(): void
    {
        $this->server->start();
    }

    private function events(): void
    {
        $this->open();
        $this->message();
        $this->close();
        $this->heartbeat->start();
        $this->server->on('workerStart', function ($server, $workerId) {
            if ($workerId === 0 && !$server->taskworker) {
                $this->subscribe();
            }
        });
    }

    private function open(): void
    {
        $this->server->on('open', function (Server $server, Request $request) {
            $auth = $this->config['authenticate'];
            if ($auth === false) {
                $userid = $request->get['userid'] ?? null;
            } else {
                if ($auth instanceof AuthenticateInterface) {
                    $userid = $auth->authenticate($request->get ?? []);
                } else {
                    $userid = null;
                }
            }

            if (is_null($userid)) {
                $server->close($request->fd);
            } else {
                $this->connection->add($userid, $request->fd);
                $this->dispatcher->offline($userid, $request->fd);
                $this->heartbeat->pong($request->fd);
            }
        });
    }

    private function message(): void
    {
        $this->server->on('message', function (Server $server, $frame) {
            if ($frame->opcode === WEBSOCKET_OPCODE_BINARY) {
                $data = $frame->data;
                if (empty($data)) {
                    return;
                }
                $this->dispatcher->handle($data, $frame->fd);
            }
        });
    }

    private function close(): void
    {
        $this->server->on('close', function (Server $server, $fd) {
            $this->connection->remove($fd);
            $this->heartbeat->remove($fd);
        });
    }

    private function subscribe(): void
    {
        $dispatcher = $this->dispatcher;
        $config     = config('database.redis.default');
        $channels   = $this->config['subscribe_channels'];
        go(function () use ($dispatcher, $config, $channels) {
            $retryCount = 0;
            while (true) {
                try {
                    $redis = new Redis();
                    $redis->setOptions([
                        'timeout' => -1,
                    ]);
                    $connect = $redis->connect($config['host'], (int)$config['port']);
                    if (!$connect) {
                        Log::error('Redis connect failed. errCode: ', [$redis->errCode, $redis->errMsg]);
                        $retryCount++;
                        $wait = min(60, 2 ** $retryCount);
                        Log::warning("Redis reconnect attempt #{$retryCount}, waiting {$wait}s...");
                        Coroutine::sleep($wait);
                        continue;
                    }

                    if (!empty($config['password'])) {
                        $redis->auth($config['password']);
                    }
                    if (!empty($config['database'])) {
                        $redis->select($config['database']);
                    }

                    $retryCount = 0;

                    $redis->subscribe(array_merge($channels, [Utils::sid()]));

                    while (true) {
                        $message = $redis->recv();
                        if ($message === false) {
                            break;
                        }
                        if (is_array($message) && $message[0] === 'message') {
                            $dispatcher->handle($message[2], 0, !str_starts_with($message[1], 'sid'));
                        }
                    }
                    $redis->close();
                } catch (\Throwable $throwable) {
                    Log::error('Redis subscription error: ', [$throwable->getMessage()]);
                    $retryCount++;
                    $wait = min(60, 2 ** $retryCount);
                    Log::warning("Redis reconnect attempt #{$retryCount}, waiting {$wait}s...");
                    Coroutine::sleep($wait);
                }
            }
        });
    }
}
