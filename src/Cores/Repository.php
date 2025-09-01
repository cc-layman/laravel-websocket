<?php

namespace Layman\LaravelWebsocket\Cores;

use Layman\LaravelWebsocket\Models\WebsocketGroup;
use Layman\LaravelWebsocket\Models\WebsocketMessage;
use Layman\LaravelWebsocket\Models\WebsocketMessageReceipt;

class Repository
{
    /**
     * 创建消息
     *
     * @param array $data
     * @return string
     */
    public static function createMessage(array $data): string
    {
        unset($data['receiver']);
        $message = WebsocketMessage::query()->create($data);
        return $message->uuid;
    }

    /**
     * 创建消息接收者
     *
     * @param string $uuid
     * @param string|int $receiver
     * @param array $message
     * @param int $pushed
     * @return void
     */
    public static function createMessageReceipt(string $uuid, string|int $receiver, array $message, int $pushed = 1): void
    {
        WebsocketMessageReceipt::query()->create([
            'message_uuid' => $uuid,
            'receiver' => $receiver,
            'sn' => $message['sn'],
            'index' => $message['index'],
            'count' => $message['count'],
            'pushed' => $pushed,
        ]);
    }

    public static function getGroup(string $groupCode)
    {
        return WebsocketGroup::query()
            ->with('websocketGroupUser')
            ->find($groupCode);
    }
}
