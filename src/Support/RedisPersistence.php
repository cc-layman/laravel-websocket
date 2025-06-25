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
        return sprintf("websocket:%s:offline_messages", $userid);
    }

    /**
     * 写入消息
     * @param int|string $to
     * @param string $message
     * @return void
     */
    public function add(int|string $to, string $message): void
    {
        Redis::rpush($this->getUserOfflineMessagesKey($to), $message);
    }

    /**
     * 删除消息
     * @param int|string $to
     * @return void
     */
    public function remove(int|string $to): void
    {
        Redis::del($this->getUserOfflineMessagesKey($to));
    }
}
