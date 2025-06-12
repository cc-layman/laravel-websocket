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
            'msg_id' => $data['msg_id'],
            'type' => $data['type'],
            'from' => $data['from'],
            'to' => $data['to'],
            'content' => $data['content'],
            'extra' => is_null($data['extra']) ? null : json_encode($data['extra']),
            'pushed' => 'PENDING',
            'status' => 'UNREAD',
        ]);
    }

    /**
     * 推送状态修改
     * @param int|string $msgId
     * @return void
     */
    public function pushed(int|string $msgId): void
    {
        WebSocketMessage::query()
            ->where('msg_id', $msgId)
            ->update([
                'pushed' => 'SUCCESS',
            ]);
    }

}
