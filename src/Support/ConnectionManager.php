<?php

namespace Layman\LaravelWebsocket\Support;


use Illuminate\Support\Facades\Redis;

class ConnectionManager
{
    protected string $userToFdKey = 'ws:user_to_fd';
    protected string $fdToUserKey = 'ws:fd_to_user';

    public function add(int $userid, int $fd): void
    {
        Redis::hset($this->userToFdKey, $userid, $fd);
        Redis::hset($this->fdToUserKey, $fd, $userid);
    }

    public function remove(int $fd): void
    {
        $userid = Redis::hget($this->fdToUserKey, $fd);
        if ($userid) {
            Redis::hdel($this->fdToUserKey, $fd);
            Redis::hdel($this->userToFdKey, $userid);
        }
    }

    public function getFdByUserId(int $userid): ?int
    {
        $fd = Redis::hget($this->userToFdKey, $userid);
        return $fd !== null ? (int)$fd : null;
    }

    public function getUserIdByFd(int $fd): ?int
    {
        $userid = Redis::hget($this->fdToUserKey, $fd);
        return $userid !== null ? (int)$userid : null;
    }

    /**
     * 获取所有连接 fd
     * @return int[]
     */
    public function getAllFds(): array
    {
        return array_map('intval', array_keys(Redis::hgetall($this->fdToUserKey)));
    }
}
