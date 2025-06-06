<?php

namespace Layman\LaravelWebsocket\Support;

use Swoole\WebSocket\Server;

class MessageDispatcher
{
    protected Server $server;
    protected ConnectionManager $connections;

    public function __construct(Server $server, ConnectionManager $connections)
    {
        $this->server      = $server;
        $this->connections = $connections;
    }

    /**
     * 处理收到客户端消息
     * 例子消息格式：
     *  点对点: {"type":"private","to":123,"content":"hello"}
     *  群聊: {"type":"group","groups":1,"content":"hi all"}
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
                $this->sendPrivateMessage($userid, $data['to'], $data['content'] ?? '');
                break;

            case 'group':
                $this->sendGroupMessage($userid, $data['groups'], $data['content'] ?? '');
                break;
            default:
                // 其他类型消息，忽略或扩展
                break;
        }
    }

    protected function sendPrivateMessage(int $fromUserid, int $toUserid, string $content): void
    {
        $fd = $this->connections->getFdByUserId($toUserid);
        if ($fd) {
            $message = json_encode([
                'type' => 'group',
                'from' => $fromUserid,
                'to' => $toUserid,
                'content' => $content,
            ]);
            $this->server->push($fd, $message);
        }
    }

    protected function sendGroupMessage(int $fromUserid, array $groups, string $content): void
    {
        foreach ($groups as $toUserid) {
            $fd      = $this->connections->getFdByUserId($toUserid);
            $message = json_encode([
                'type' => 'group',
                'from' => $fromUserid,
                'groups' => $toUserid,
                'content' => $content,
            ]);
            $this->server->push($fd, $message);
        }
    }

    /**
     * 通过 Redis 订阅收到系统消息推送到用户或群
     * 格式示例：
     * 个人消息: ['type' => 'system', 'toUserid' => 123, 'content' => '系统通知']
     * 群消息: ['type' => 'system', 'toGroups' => 1, 'content' => '群通知']
     * 广播所有连接: ['type' => 'system', 'toSystem' => true, 'content' => '广播所有连接']
     */
    public function pushSystemMessage(array $data): void
    {
        if (isset($data['toUserid'])) {
            // 广播给指定连接
            $fd = $this->connections->getFdByUserId((int)$data['toUserid']);
            if ($fd) {
                $message = json_encode([
                    'type' => 'system',
                    'from' => 'system',
                    'to' => $data['toUserid'],
                    'content' => $data['content'],
                ]);
                $this->server->push($fd, $message);
            }
        } elseif (isset($data['toGroups'])) {
            // 广播给指定群组
            foreach ($data['toGroups'] as $toUserid) {
                $fd  = $this->connections->getFdByUserId($toUserid);
                $msg = json_encode([
                    'type' => 'system',
                    'from' => 'system',
                    'to' => $toUserid,
                    'content' => $data['content'],
                ]);
                $this->server->push($fd, $msg);
            }
        } elseif (isset($data['toSystem'])) {
            // 广播给所有连接的客户端
            foreach ($this->connections->getAllFds() as $fd) {
                $message = json_encode([
                    'type' => 'system',
                    'from' => 'system',
                    'content' => $data['content'],
                ]);
                $this->server->push($fd, $message);
            }
        }
    }
}
