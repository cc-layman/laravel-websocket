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
        $this->config = config('chat');
        $this->server = new Server($this->config['host'], $this->config['port'], $this->config['model']);
        $this->server->set([
            'websocket_max_frame_size' => $this->config['websocket_max_frame_size'],
        ]);
        if ($this->config['model'] == SWOOLE_PROCESS) {
            $this->server->set([
                'worker_num' => $this->config['worker_num'],
            ]);
        }
        $this->connection = new Connection();
        $this->heartbeat  = new Heartbeat($this->server, $this->connection, $this->config);
        $this->dispatcher = new Dispatcher($this->server, $this->connection, $this->config);
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
            if ($workerId === 0) {
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
                $this->heartbeat->pong($request->fd);
            }
        });
    }

    protected function message(): void
    {
        $this->server->on('message', function (Server $server, $frame) {
            switch ($frame->opcode) {
                case WEBSOCKET_OPCODE_PONG:
                    $this->heartbeat->pong($frame->fd);
                    break;
                case WEBSOCKET_OPCODE_TEXT:
                    $data = $frame->data;
                    if (empty($data)) {
                        return;
                    }
                    $this->dispatcher->handle($data, $frame->fd, false);
                    break;
                case WEBSOCKET_OPCODE_BINARY:
                    $data = $frame->data;
                    if (empty($data)) {
                        return;
                    }
                    $this->dispatcher->handle($data, $frame->fd, true);
                    break;
                default:
                    break;
            }
        });
    }

    protected function close(): void
    {
        $this->server->on('close', function (Server $server, $fd) {
            $this->connection->remove($fd);
            $this->heartbeat->remove($fd);
        });
    }

    protected function subscribe(): void
    {
        $dispatcher = $this->dispatcher;
        $config     = config('database.redis.default');
        $channels   = $this->config['subscribe_channels'];
        go(function () use ($dispatcher, $config, $channels) {
            while (true) {
                try {
                    $redis = new Redis();
                    $redis->setOptions([
                        'timeout' => -1,
                    ]);
                    $connect = $redis->connect($config['host'], (int)$config['port']);
                    if (!$connect) {
                        Log::error('Redis connect failed. errCode: ', [$redis->errCode, $redis->errMsg]);
                        Coroutine::sleep(3);
                        continue;
                    }

                    if (!empty($config['password'])) {
                        $redis->auth($config['password']);
                    }
                    if (!empty($config['database'])) {
                        $redis->select($config['database']);
                    }

                    $redis->subscribe(array_merge($channels, (array)Utils::sid()));

                    while (true) {
                        $message = $redis->recv();
                        if ($message === false) {
                            break;
                        }
                        if (is_array($message) && $message[0] === 'message') {
                            $origin = Str::before('-', $message[1]) === 'sid';
                            $data   = json_decode($message[2], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                                $dispatcher->handle($message[2], 0, false, $origin);
                            } else {
                                $dispatcher->handle($message[2], 0, true, $origin);
                            }
                        }
                    }
                    $redis->close();
                } catch (\Throwable $throwable) {
                    Log::error('Redis subscription error: ', [$throwable->getMessage()]);
                }
                Coroutine::sleep(3);
            }
        });
    }
}
