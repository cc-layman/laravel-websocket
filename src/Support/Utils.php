<?php

namespace Layman\LaravelWebsocket\Support;

use Layman\LaravelWebsocket\Models\WebSocketMessageReceipt;

class Utils
{

    /**
     * 设置全局统一消息格式
     * @param array $data
     * @return array
     */
    public static function format(array $data): array
    {
        return [
            'msg_id' => Utils::generate_uuid(),
            'type' => strtoupper($data['type']),
            'group_id' => $data['group_id'] ?? null,
            'from' => $data['from'],
            'to' => $data['to'],
            'classify' => $data['classify'],
            'content' => $data['content'],
            'extra' => $data['extra'] ?? null,
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * 设置数据库统一消息格式
     * @param WebSocketMessageReceipt $data
     * @return array
     */
    public static function schema(WebSocketMessageReceipt $data): array
    {
        return [
            'msg_id' => $data->getAttribute('msg_id'),
            'type' => strtoupper($data->getRelation('websocketMessage')->getAttribute('type')),
            'group_id' => $data->getRelation('websocketMessage')->getAttribute('group_id'),
            'from' => $data->getRelation('websocketMessage')->getAttribute('from'),
            'to' => $data->getAttribute('to'),
            'classify' => $data->getRelation('websocketMessage')->getAttribute('classify'),
            'content' => $data->getRelation('websocketMessage')->getAttribute('content'),
            'extra' => $data->getRelation('websocketMessage')->getAttribute('extra'),
            'created_at' => $data->getAttribute('created_at'),
        ];
    }


    public static function generate_uuid(string $format = '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', callable|string $salt = 'strtoupper'): string
    {
        $uuid = sprintf($format,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        return $salt($uuid);
    }
}
