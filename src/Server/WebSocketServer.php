<?php

namespace Layman\LaravelWebsocket\Server;

use Illuminate\Support\Facades\Log;
use Layman\LaravelWebsocket\Models\WebSocketMessage;
use Layman\LaravelWebsocket\Support\ConnectionManager;
use Layman\LaravelWebsocket\Support\DatabasePersistence;
use Layman\LaravelWebsocket\Support\Heartbeat;
use Layman\LaravelWebsocket\Support\MessageDispatcher;
use Layman\LaravelWebsocket\Support\Utils;
use Layman\LaravelWebsocket\Support\RedisPersistence;
use Swoole\Coroutine;
use Swoole\Coroutine\Redis;
use Swoole\Http\Request;
use Swoole\WebSocket\Server;

class WebSocketServer
{
    protected Server $server;
    protected ConnectionManager $connections;
    protected Heartbeat $heartbeat;
    protected MessageDispatcher $dispatcher;
    protected DatabasePersistence $databasePersistence;
    protected RedisPersistence $redisPersistence;
    protected array $config;

    public function __construct()
    {
        $this->config = config('websocket');
        $this->server = new Server($this->config['host'], $this->config['port'], $this->config['model']);
        if ($this->config['model'] == SWOOLE_PROCESS) {
            $this->server->set([
                'worker_num' => $this->config['worker_num'],
            ]);
        }
        $this->connections         = new ConnectionManager();
        $this->heartbeat           = new Heartbeat($this->server, $this->connections, $this->config['heartbeat_interval'], $this->config['heartbeat_timeout']);
        $this->dispatcher          = new MessageDispatcher($this->server, $this->connections, $this->config);
        $this->databasePersistence = new DatabasePersistence();
        $this->redisPersistence    = new RedisPersistence();
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
        $this->server->on('workerStart', function ($server, $workerId) {
            if ($workerId === 0) {
                $this->subscribeToRedis();
            }
        });
    }

    protected function open(): void
    {
        $this->server->on('open', function (Server $server, Request $request) {
            $userid = (int)($request->get['userid'] ?? 0);
            if ($userid > 0) {
                if ($this->config['database_persistence']) {
                    $offlineMessages = WebSocketMessage::query()
                        ->where('to', $userid)
                        ->where('pushed', 'PENDING')
                        ->get();
                    foreach ($offlineMessages as $message) {
                        $data = Utils::format($message->type, $message->from, $message->to, $message->content, json_decode($message->extra, true) ?? $message->extra);
                        $data = array_merge($data, ['msg_id' => $message->msg_id]);
                        $server->push($request->fd, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        $this->databasePersistence->pushed($message->msg_id);
                    }
                }
                if ($this->config['redis_persistence']) {
                    $key = $this->redisPersistence->getUserOfflineMessagesKey($userid);
                    while ($message = \Illuminate\Support\Facades\Redis::lpop($key)) {
                        $server->push($request->fd, $message);
                    }
                }

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

            if ($data['type'] !== 'ping' && empty($data['content']) || (!empty($data['extra']) && is_string($data['extra']))) {
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
        $dispatcher = $this->dispatcher;
        $config     = config('database.redis.default');
        $channel    = $this->config['redis_subscribe_channel'];
        go(function () use ($dispatcher, $config, $channel) {
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

                    $redis->subscribe([$channel]);

                    while (true) {
                        $message = $redis->recv();
                        if ($message === false) {
                            break;
                        }
                        if (is_array($message) && $message[0] === 'message') {
                            $data = json_decode($message[2], true);
                            if (!empty($data['content'])) {
                                $dispatcher->pushSystemMessage($data);
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
