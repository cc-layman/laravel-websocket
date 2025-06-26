<?php

namespace Layman\LaravelWebsocket\Support;


use Illuminate\Support\Facades\Redis;

class ConnectionManager
{
    protected string $userToFdKey = 'websocket:user_to_fd';
    protected string $fdToUserKey = 'websocket:fd_to_user';

    /**
     * 设置用户与连接关系
     * @param int|string $userid
     * @param int $fd
     * @return void
     */
    public function add(int|string $userid, int $fd): void
    {
        Redis::hset($this->userToFdKey, $userid, $fd);
        Redis::hset($this->fdToUserKey, $fd, $userid);
    }

    /**
     * 删除用户与连接关系
     * @param int $fd
     * @return void
     */
    public function remove(int $fd): void
    {
        $userid = Redis::hget($this->fdToUserKey, $fd);
        if ($userid) {
            Redis::hdel($this->fdToUserKey, $fd);
            Redis::hdel($this->userToFdKey, $userid);
        }
    }

    /**
     * 使用用户标识获取连接fd
     * @param int|string $userid
     * @return int|null
     */
    public function getFdByUserId(int|string $userid): ?int
    {
        $fd = Redis::hget($this->userToFdKey, $userid);
        return $fd !== null ? (int)$fd : null;
    }

    /**
     * 使用连接fd获取用户标识
     * @param int $fd
     * @return int|string|null
     */
    public function getUserIdByFd(int $fd): int|string|null
    {
        $userid = Redis::hget($this->fdToUserKey, $fd);
        return $userid !== null ? $userid : null;
    }

    /**
     * 获取所有连接fd
     * @return int[]
     */
    public function getAllFds(): array
    {
        return array_map('intval', array_keys(Redis::hgetall($this->fdToUserKey)));
    }
}
