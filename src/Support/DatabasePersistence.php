<?php

namespace Layman\LaravelWebsocket\Support;

use App\Models\WebSocketMessage;

class DatabasePersistence
{
    /**
     * 写入消息
     * @param array $data
     * @return void
     */
    public function add(array $data): void
    {
        WebSocketMessage::query()->create([
            'type' => $data['type'],
            'from_userid' => $data['from'],
            'to_userid' => $data['to'],
            'content' => $data['content'],
        ]);
    }

    /**
     * 删除消息
     * @param int|string $toUserid
     * @return void
     */
    public function remove(int|string $toUserid): void
    {
        WebSocketMessage::query()
            ->where('to_userid', $toUserid)
            ->delete();
    }
}
