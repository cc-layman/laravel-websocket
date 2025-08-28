<?php

namespace Layman\LaravelWebsocket\Cores;

use Illuminate\Support\Facades\Redis;

class Connection
{
    private string $userToFdKey = 'laravel_websocket:user_to_fd';
    private string $fdToUserKey = 'laravel_websocket:fd_to_user';
    private string $roomToUserKey = 'laravel_websocket:room_to_user';

    /**
     * 设置用户与连接关系
     *
     * @param string $userid
     * @param int $fd
     * @return void
     */
    public function add(string $userid, int $fd): void
    {
        Redis::hset($this->userToFdKey, $userid, Utils::sid($fd));
        Redis::hset($this->fdToUserKey, Utils::sid($fd), $userid);
    }

    /**
     * 删除用户与连接关系
     *
     * @param int $fd
     * @return void
     */
    public function remove(int $fd): void
    {
        $userid = Redis::hget($this->fdToUserKey, Utils::sid($fd));
        if ($userid) {
            Redis::hdel($this->userToFdKey, $userid);
            Redis::hdel($this->fdToUserKey, Utils::sid($fd));
        }
    }

    /**
     * 使用用户标识获取连接fd
     *
     * @param int|string $userid
     * @return string|null
     */
    public function getFdByUserId(int|string $userid): ?string
    {
        $fd = Redis::hget($this->userToFdKey, $userid);
        return is_null($fd) ? null : $fd;
    }

    /**
     * 使用连接fd获取用户标识
     *
     * @param int $fd
     * @return string|null
     */
    public function getUserIdByFd(int $fd): ?string
    {
        $userid = Redis::hget($this->fdToUserKey, Utils::sid($fd));
        return $userid ?? null;
    }

    /**
     * 获取所有连接fd
     *
     * @return array
     */
    public function getAllFd(): array
    {
        return array_keys(Redis::hgetall($this->fdToUserKey));
    }

    /**
     * 获取所有连接fd
     *
     * @return array
     */
    public function getAllUserid(): array
    {
        return array_keys(Redis::hgetall($this->userToFdKey));
    }


    /**
     * 获取所有连接fd
     *
     * @param array $groupUserid
     * @return array
     */
    public function getGroupUserFd(array $groupUserid): array
    {
        $fds = Redis::hmget($this->userToFdKey, $groupUserid);

        return array_values(array_filter($fds, function ($fd) {
            return !empty($fd);
        }));
    }

    /**
     * 直播建立房间用户
     *
     * @param string $userid
     * @return void
     */
    public function roomAddUser(string $userid): void
    {
        Redis::sAdd($this->roomToUserKey, Utils::sid($userid));
    }

    /**
     * 判断直播房间成员是否存在
     *
     * @param string $userid
     * @return bool
     */
    public function isRoomUser(string $userid): bool
    {
        return Redis::sIsMember($this->roomToUserKey, $userid);
    }

    /**
     * 获取直播房间所有成员
     *
     * @param string $userid
     * @return array
     */
    public function getRoomUsers(string $userid): array
    {
        return Redis::sMembers($this->roomToUserKey);
    }
}
