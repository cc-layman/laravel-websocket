<?php

namespace Layman\LaravelWebsocket\Server;

use Layman\LaravelWebsocket\Support\ConnectionManager;
use Layman\LaravelWebsocket\Support\Heartbeat;
use Layman\LaravelWebsocket\Support\MessageDispatcher;
use Redis;
use Swoole\Http\Request;
use Swoole\WebSocket\Server;

class WebSocketServer
{

    protected Server $server;
    protected ConnectionManager $connections;
    protected Heartbeat $heartbeat;
    protected MessageDispatcher $dispatcher;
    protected array $config;

    public function __construct()
    {
        $this->config = config('websocket');
        $this->server = new Server($this->config['host'], $this->config['port'], $this->config['model']);
        if ($this->config['model'] == SWOOLE_PROCESS) {
            $this->server->set([
                'worker_num' => 4,
            ]);
        }
        $this->connections = new ConnectionManager();
        $this->heartbeat   = new Heartbeat($this->server, $this->connections, $this->config['heartbeat_interval'], $this->config['heartbeat_timeout']);
        $this->dispatcher  = new MessageDispatcher($this->server, $this->connections);
        $this->subscribeToRedis();
        $this->events();
    }

    public function start(): void
    {
        $this->server->start();
    }

    protected function events(): void
    {
        $this->open();
        $this->message();
        $this->close();
    }

    protected function open(): void
    {
        $this->server->on('open', function (Server $server, Request $request) {
            $userid = (int)($request->get['userid'] ?? 0);
            if ($userid > 0) {
                $this->connections->add($userid, $request->fd);
                $this->heartbeat->pong($request->fd);
            } else {
                $server->close($request->fd);
            }
        });
    }

    protected function message(): void
    {
        $this->server->on('message', function (Server $server, $frame) {
            $data = json_decode($frame->data, true);
            if (empty($data)) {
                return;
            }

            if ($data['type'] !== 'ping' && empty($data['content'])) {
                return;
            }

            if (($data['type'] ?? '') === 'ping') {
                $this->heartbeat->pong($frame->fd);
                $server->push($frame->fd, json_encode(['type' => 'pong']));
                return;
            }

            $this->dispatcher->handle($frame->fd, $data);
        });
    }

    protected function close(): void
    {
        $this->server->on('close', function (Server $server, $fd) {
            $this->connections->remove($fd);
            $this->heartbeat->remove($fd);
        });
    }

    protected function subscribeToRedis(): void
    {
        $config = config('database.redis.default');

        go(function () use ($config) {
            $redis = new Redis();
            $redis->connect($config['host'], $config['port']);
            if (!empty($config['password'])) {
                $redis->auth($config['password']);
            }

            // 订阅频道，消息由回调处理
            $redis->subscribe([$this->config['redis_channel']], function ($data) {
                $data = json_decode($data, true);
                if (empty($data) || empty($data['content'])) {
                    return;
                }
                $this->dispatcher->pushSystemMessage($data);
            });
        });
    }
}
