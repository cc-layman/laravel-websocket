<?php

namespace Layman\LaravelWebsocket\Support;

use Illuminate\Support\Facades\Redis;

class RedisPersistence
{
    /**
     * 设置缓存键
     * @param int|string $userid
     * @return string
     */
    public function getUserOfflineMessagesKey(int|string $userid): string
    {
        return sprintf("websocket:%d:offline_messages", $userid);
    }

    /**
     * 写入消息
     * @param int|string $toUserid
     * @param string $message
     * @return void
     */
    public function add(int|string $toUserid, string $message): void
    {
        Redis::rpush($this->getUserOfflineMessagesKey($toUserid), $message);
    }

    /**
     * 删除消息
     * @param int|string $toUserid
     * @return void
     */
    public function remove(int|string $toUserid): void
    {
        Redis::del($this->getUserOfflineMessagesKey($toUserid));
    }
}
