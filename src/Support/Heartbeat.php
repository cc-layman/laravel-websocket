<?php

namespace Layman\LaravelWebsocket\Support;

use Swoole\Timer;
use Swoole\WebSocket\Server;

class Heartbeat
{
    protected Server $server;
    protected ConnectionManager $connections;
    protected int $interval;
    protected int $timeout;

    protected array $lastPongTime = [];

    public function __construct(Server $server, ConnectionManager $connections, int $interval, int $timeout)
    {
        $this->server      = $server;
        $this->connections = $connections;
        $this->interval    = $interval;
        $this->timeout     = $timeout;
    }

    public function start(): void
    {
        Timer::tick($this->interval * 1000, function () {
            $now = time();
            foreach ($this->connections->getAllFds() as $fd) {
                if (!isset($this->lastPongTime[$fd])) {
                    $this->lastPongTime[$fd] = $now;
                } elseif ($now - $this->lastPongTime[$fd] > $this->timeout) {
                    $this->server->push($fd, json_encode(['type' => 'close', 'reason' => 'heartbeat timeout']));
                    $this->server->close($fd);
                    $this->connections->remove($fd);
                    unset($this->lastPongTime[$fd]);
                }
            }
        });
    }

    public function pong(int $fd): void
    {
        $this->lastPongTime[$fd] = time();
    }

    public function remove(int $fd): void
    {
        unset($this->lastPongTime[$fd]);
    }
}
