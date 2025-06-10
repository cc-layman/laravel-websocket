<?php

namespace Layman\LaravelWebsocket\Support;

use Illuminate\Support\Facades\Config;
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
     * 处理客户端消息
     * @param int $fd
     * @param array $data
     * @return void
     */
    public function handle(int $fd, array $data): void
    {
        $userid = $this->connections->getUserIdByFd($fd);
        if (!$userid) {
            $this->server->close($fd);
            return;
        }

        switch ($data['type'] ?? '') {
            case 'private':
                $this->privateMessage($userid, $data['to'], $data['content'] ?? '');
                break;

            case 'group':
                $this->groupMessage($userid, $data['groups'], $data['content'] ?? '');
                break;
            default:
                // Other types of messages, ignore or expand
                break;
        }
    }

    /**
     * 私聊消息
     * @param int|string $fromUserid
     * @param int|string $toUserid
     * @param string $content
     * @return void
     */
    protected function privateMessage(int|string $fromUserid, int|string $toUserid, string $content): void
    {
        $fd   = $this->connections->getFdByUserId($toUserid);
        $data = MessageFormatter::format('private', $fromUserid, $toUserid, $content);
        $this->sendMessage($fd, $data);
    }

    /**
     * 群聊消息
     * @param int|string $fromUserid
     * @param array $groups
     * @param string $content
     * @return void
     */
    protected function groupMessage(int|string $fromUserid, array $groups, string $content): void
    {
        foreach ($groups as $toUserid) {
            $fd   = $this->connections->getFdByUserId($toUserid);
            $data = MessageFormatter::format('group', $fromUserid, $toUserid, $content);
            $this->sendMessage($fd, $data);
        }
    }

    /**
     * 消息订阅
     * @param array $data
     * @return void
     */
    public function pushSystemMessage(array $data): void
    {
        if (isset($data['toUserid'])) {
            $fd   = $this->connections->getFdByUserId($data['toUserid']);
            $data = MessageFormatter::format($data['type'], $data['type'], $data['toUserid'], $data['content']);
            $this->sendMessage($fd, $data);
        } elseif (isset($data['toGroups'])) {
            foreach ($data['toGroups'] as $toUserid) {
                $fd   = $this->connections->getFdByUserId($toUserid);
                $data = MessageFormatter::format($data['type'], $data['type'], $data['toUserid'], $data['content']);
                $this->sendMessage($fd, $data);
            }
        } elseif (isset($data['toSystem'])) {
            foreach ($this->connections->getAllFds() as $fd) {
                $userid = $this->connections->getUserIdByFd($fd);
                $data   = MessageFormatter::format($data['type'], $data['type'], $userid, $data['content']);
                $this->sendMessage($fd, $data);
            }
        }
    }

    /**
     * 发送消息
     * @param int|null $fd
     * @param array $data
     * @return void
     */
    private function sendMessage(int|null $fd, array $data): void
    {
        $message = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($this->config['redis_persistence']) {
            $this->redisPersistence->add($data['to'], $message);
        }
        if ($this->config['database_persistence']) {
            $this->databasePersistence->add($data);
        }
        if ($fd) {
            $this->server->push($fd, $message);
            $this->redisPersistence->remove($data['to']);
            $this->databasePersistence->remove($data['to']);
        }
    }
}
