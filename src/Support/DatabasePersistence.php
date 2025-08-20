<?php

namespace Layman\LaravelWebsocket\Support;

use Layman\LaravelWebsocket\Models\WebSocketMessage;
use Layman\LaravelWebsocket\Models\WebSocketMessageReceipt;

class DatabasePersistence
{
    /**
     * 写入消息
     * @param array $data
     * @return void
     */
    public function createMessage(array $data): void
    {
        WebSocketMessage::query()->create([
            'msg_id' => $data['msg_id'],
            'type' => $data['type'],
            'group_id' => $data['group_id'],
            'from' => $data['from'],
            'classify' => $data['classify'],
            'content' => $data['content'],
            'extra' => is_null($data['extra']) ? null : json_encode($data['extra']),
        ]);
    }

    /**
     * 写入消息接收信息
     * @param string $msgId
     * @param int|string $to
     * @return void
     */
    public function createMessageReceipt(string $msgId, int|string $to): void
    {
        WebSocketMessageReceipt::query()->create([
            'msg_id' => $msgId,
            'to' => $to,
            'pushed' => 'PENDING',
        ]);
    }

    /**
     * 推送状态修改
     * @param string $msgId
     * @param int|string $to
     * @return void
     */
    public function pushed(string $msgId, int|string $to): void
    {
        WebSocketMessageReceipt::query()
            ->where('msg_id', $msgId)
            ->where('to', $to)
            ->update([
                'pushed' => 'SUCCESS',
            ]);
    }

}
