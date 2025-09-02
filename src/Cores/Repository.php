<?php

namespace Layman\LaravelWebsocket\Cores;

use Illuminate\Database\Eloquent\Collection;
use Layman\LaravelWebsocket\Models\WebsocketGroupUser;
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
    public static function createMessageReceipt(string $uuid, string|int $receiver, array $message, int $pushed = 1, bool $read = false): void
    {
        WebsocketMessageReceipt::query()->create([
            'message_uuid' => $uuid,
            'receiver' => $receiver,
            'sn' => $message['sn'],
            'index' => $message['index'],
            'count' => $message['count'],
            'pushed' => $pushed,
            'read' => $read ? now() : null,
        ]);
    }

    /**
     * @param string $groupCode
     * @return Collection
     */
    public static function getGroupUsers(string $groupCode): Collection
    {
        return WebsocketGroupUser::query()
            ->where('group_code', $groupCode)
            ->where('status', 1)
            ->get();
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
            ->oldest()
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
