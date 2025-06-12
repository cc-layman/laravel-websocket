<?php

namespace Layman\LaravelWebsocket\Support;

class Utils
{
    /**
     * 设置统一消息格式
     * @param string $type
     * @param int|string $from
     * @param int|string $to
     * @param string $content
     * @param array|null $extra
     * @return array
     */
    public static function format(string $type, int|string $from, int|string $to, string $content, null|array $extra): array
    {
        return [
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'content' => $content,
            'extra' => $extra,
            'time' => time(),
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
