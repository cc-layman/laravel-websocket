<?php

namespace Layman\LaravelWebsocket\Server;

use co;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Layman\LaravelWebsocket\Models\WebSocketMessage;
use Layman\LaravelWebsocket\Support\ConnectionManager;
use Layman\LaravelWebsocket\Support\DatabasePersistence;
use Layman\LaravelWebsocket\Support\Heartbeat;
use Layman\LaravelWebsocket\Support\MessageDispatcher;
use Layman\LaravelWebsocket\Support\MessageFormatter;
use Layman\LaravelWebsocket\Support\RedisPersistence;
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
        $this->subscribeToRedis();
    }

    protected function open(): void
    {
        $this->server->on('open', function (Server $server, Request $request) {
            $userid = (int)($request->get['userid'] ?? 0);
            if ($userid > 0) {
                if ($this->config['database_persistence']) {
                    $offlineMessages = WebSocketMessage::where('to_userid', $userid)
                        ->where('status', 'UNREAD')
                        ->get();
                    foreach ($offlineMessages as $message) {
                        $message = MessageFormatter::format($message->type, $message->from_userid, $message->to_userid, $message->content);
                        $server->push($request->fd, json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        $this->databasePersistence->remove($message->to_userid);
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
        Co::set(['hook_flags' => SWOOLE_HOOK_TCP]);
        Co\run(function () {
            go(function () {
                $dispatcher = $this->dispatcher;
                while (true) {
                    try {
                        Redis::subscribe([$this->config['redis_subscribe_channel']], function (Redis $redis, string $channel, string $message) use ($dispatcher) {
                            $data = json_decode($message, true);
                            if (empty($data) || empty($data['content'])) {
                                return;
                            }
                            $dispatcher->pushSystemMessage($data);
                        });
                    } catch (\Throwable $e) {
                        Log::error('Redis subscribe error: ' . $e->getMessage());
                        Co::sleep(3);
                    }
                }
            });
        });
    }
}
