<?php

namespace Layman\LaravelWebsocket\Cores;

use Swoole\Timer;
use Swoole\WebSocket\Server;

class Heartbeat
{
    protected Server $server;
    protected Connection $connection;
    protected array $config;
    protected array $lastPongTime = [];

    public function __construct(Server $server, Connection $connection, array $config)
    {
        $this->server     = $server;
        $this->connection = $connection;
        $this->config     = $config;
    }

    /**
     * 心跳检测
     *
     * @return void
     */
    public function start(): void
    {
        Timer::tick($this->config['heartbeat_interval'] * 1000, function () {
            $now = time();
            foreach ($this->connection->getAllFd() as $fds) {
                [$fingerprint, $fd] = explode('#', $fds);
                if ($fingerprint !== Utils::sid()) {
                    continue;
                }
                $fd = (int)$fd;
                if (!isset($this->lastPongTime[$fd])) {
                    $this->lastPongTime[$fd] = $now;
                } elseif ($now - $this->lastPongTime[$fd] > $this->config['heartbeat_timeout']) {
                    @$this->server->push($fd, json_encode(['type' => 'close', 'reason' => 'heartbeat timeout']));
                    $this->server->close($fd);
                    $this->connection->remove($fd);
                    unset($this->lastPongTime[$fd]);
                    continue;
                }
                @$this->server->push($fd, json_encode(['type' => 'ping', 'time' => time()]), WEBSOCKET_OPCODE_PING);
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
