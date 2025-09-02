<?php

namespace Layman\LaravelWebsocket\Cores;

use Illuminate\Database\Eloquent\Collection;
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

    /**
     * @param string $groupCode
     * @return Collection|null
     */
    public static function getGroup(string $groupCode): ?Collection
    {
        return WebsocketGroup::query()
            ->with('websocketGroupUser')
            ->find($groupCode);
    }

    /**
     * @param string|int $receiver
     * @return Collection
     */
    public static function getOfflineMessage(string|int $receiver): Collection
    {
        return WebsocketMessageReceipt::query()
            ->where('pushed', 1)
            ->where('receiver', $receiver)
            ->with('websocketMessage')
            ->oldest('index')
            ->get();
    }

    /**
     * @param $message
     * @return void
     */
    public static function pushedUpdate($message): void
    {
        $message->update([
            'pushed' => 2,
        ]);
    }
}
