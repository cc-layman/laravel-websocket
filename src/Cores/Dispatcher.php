<?php

namespace Layman\LaravelWebsocket\Cores;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Predis\Client;
use Swoole\WebSocket\Server;

class Dispatcher
{
    protected Server $server;
    protected Connection $connection;
    protected Heartbeat $heartbeat;
    protected array $config;

    public function __construct(Server $server, Connection $connections, Heartbeat $heartbeat, array $config)
    {
        $this->server     = $server;
        $this->connection = $connections;
        $this->heartbeat  = $heartbeat;
        $this->config     = $config;
    }

    /**
     * 消息处理
     *
     * @param string $data
     * @param int $fd
     * @param bool $origin
     * @return void
     */
    public function handle(string $data, int $fd = 0, bool $origin = true): void
    {
        if ($fd > 0) {
            // 检测用户是否掉线
            $userid = $this->connection->getUserIdByFd($fd);
            if (is_null($userid)) {
                $this->server->close($fd);
                return;
            }
        }
        $message = Utils::unpack($data);
        Log::info('unpack-message', [$message]);
        if ($message['type'] === 102) {
            $this->heartbeat->pong($fd);
            return;
        }
        // 处理流消息处理，流消息暂不处理
        if ($message['count'] === 0) {
            $this->stream($data, $message);
            return;
        }
        $uuid = null;
        if (!$origin) {
            // 存消息数据
            $uuid = Repository::createMessage($message);
        }
        switch ($message['notice_type']) {
            case 1:
                // 个人消息
                $this->personal($data, $message, $uuid);
                break;
            case 2:
                $this->group($data, $message, $uuid);
                // 群聊消息
                break;
            case 3:
                $this->system($data, $message, $uuid);
                // 系统消息
                break;
            case 4:
                // 广播消息
                $fds = $this->connection->getAllFd();
                foreach ($fds as $fd) {
                    $this->broadcast($data, $fd);
                }
                break;
            default:
                break;
        }
    }

    /**
     * 流式消息
     *
     * @param string $data
     * @param array $message
     * @return void
     */
    private function stream(string $data, array $message): void
    {
        return;
    }


    /**
     * 个人消息
     *
     * @param string $data
     * @param array $message
     * @param string|null $uuid
     * @return void
     */
    private function personal(string $data, array $message, string|null $uuid): void
    {
        Log::info('personal-message', [$message]);
        if (!is_null($uuid)) {
            Repository::createMessageReceipt($uuid, $message['receiver']);
        }
        $fd = $this->connection->getFdByUserId($message['receiver']);
        if (empty($fd)) {
            return;
        }
        if ($this->isHost($fd)) {
            $this->server->push((int)Str::after($fd, '#'), $data, WEBSOCKET_OPCODE_BINARY);
        } else {
            $this->publish($fd, $data);
        }
    }

    /**
     * 群组消息
     *
     * @param string $data
     * @param array $message
     * @param string|null $uuid
     * @return void
     */
    private function group(string $data, array $message, string|null $uuid): void
    {
        $group = Repository::getGroup($message['group_code']);
        if (is_null($group)) {
            return;
        }
        $groupUserid = $group->websocketGroupUser->where('status', 1)->pluck('userid');
        foreach ($groupUserid as $value) {
            if (!is_null($uuid)) {
                Repository::createMessageReceipt($uuid, $value);
            }
        }
        $fds = $this->connection->getGroupUserFd($groupUserid);
        foreach ($fds as $fd) {
            if ($this->isHost($fd) === false) {
                $this->publish($fd, $data);
            }
        }
    }

    /**
     * 系统消息
     * @param string $data
     * @param array $message
     * @param string|null $uuid
     * @return void
     */
    private function system(string $data, array $message, string|null $uuid): void
    {
        foreach ($message['receiver'] as $value) {
            if (!is_null($uuid)) {
                Repository::createMessageReceipt($uuid, $value);
            }
        }
        $fds = $this->connection->getGroupUserFd($message['receiver']);
        foreach ($fds as $fd) {
            if ($this->isHost($fd)) {
                $this->server->push((int)Str::after($fd, '#'), $data, WEBSOCKET_OPCODE_BINARY);
            } else {
                $this->publish($fd, $data);
            }
        }
    }

    /**
     * 广播消息
     *
     * @param string $data
     * @param string $fd
     * @return void
     */
    private function broadcast(string $data, string $fd): void
    {
        if ($this->isHost($fd)) {
            $this->server->push((int)Str::after($fd, '#'), $data, WEBSOCKET_OPCODE_BINARY);
        } else {
            $this->publish($fd, $data);
        }
    }


    /**
     * 判断是否是本机
     *
     * @param string $fd
     * @return bool
     */
    private function isHost(string $fd): bool
    {
        return Str::before($fd, '#') === Utils::sid();
    }

    /**
     * 消息转发
     *
     * @param string $fd
     * @param string $data
     * @return void
     */
    private function publish(string $fd, string $data): void
    {
        $client = new Client();
        $client->publish(Str::before($fd,'#'), $data);
    }
}
