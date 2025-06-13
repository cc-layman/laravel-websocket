<?php

namespace Layman\LaravelWebsocket\Support;

use Swoole\WebSocket\Server;

class MessageDispatcher
{
    protected Server $server;
    protected ConnectionManager $connections;
    protected array $config;
    protected RedisPersistence $redisPersistence;
    protected DatabasePersistence $databasePersistence;

    public function __construct(Server $server, ConnectionManager $connections, array $config)
    {
        $this->server              = $server;
        $this->connections         = $connections;
        $this->config              = $config;
        $this->redisPersistence    = new RedisPersistence();
        $this->databasePersistence = new DatabasePersistence();
    }

    /**
     * 处理消息
     * @param string $type
     * @param int $fd
     * @param array $data
     * @return void
     */
    public function handle(string $type, int $fd, array $data): void
    {
        if ($type == 'message') {
            $userid = $this->connections->getUserIdByFd($fd);
            if (!$userid) {
                $this->server->close($fd);
                return;
            }
        }

        $data = Utils::format($data);
        if ($this->config['database_persistence']) {
            $this->databasePersistence->createMessage($data);
        }

        $this->dispatch($data);
    }

    /**
     * 消息分发处理
     * @param array $data
     * @return void
     */
    private function dispatch(array $data): void
    {
        switch ($data['type']) {
            case 'notice':
            case 'private':
                $fd = $this->connections->getFdByUserId($data['to']);
                $this->push($fd, $data['to'], $data);
                break;
            case 'broadcast':
            case 'group':
                foreach ($data['to'] as $to) {
                    $fd = $this->connections->getFdByUserId($to);
                    $this->push($fd, $to, $data);
                }
                break;
            case 'online':
                foreach ($this->connections->getAllFds() as $fd) {
                    $to = $this->connections->getUserIdByFd($fd);
                    $this->push($fd, $to, $data);
                }
                break;
            default:
                break;
        }
    }

    /**
     * 消息推送
     * @param int|null $fd
     * @param int|string $to
     * @param array $data
     * @return void
     */
    private function push(int|null $fd, int|string $to, array $data): void
    {
        if ($this->config['redis_persistence']) {
            $this->redisPersistence->add($data['to'], json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        if ($this->config['database_persistence']) {
            $this->databasePersistence->createMessageReceipt($data['msg_id'], $data['to']);
        }
        if ($fd) {
            $this->server->push($fd, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if ($this->config['redis_persistence']) {
                $this->redisPersistence->remove($data['to']);
            }
            if ($this->config['database_persistence']) {
                $this->databasePersistence->pushed($data['msg_id'], $data['to']);
            }
        }
    }
}
