<?php

namespace Layman\LaravelWebsocket\Support;

use Layman\LaravelWebsocket\Models\WebSocketMessage;

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
            'from' => $data['from'],
            'to' => $data['to'],
            'content' => $data['content'],
            'extra' => is_null($data['extra']) ? null : json_encode($data['extra']),
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
            ->where('to', $toUserid)
            ->delete();
    }
}
