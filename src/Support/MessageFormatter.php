<?php

namespace Layman\LaravelWebsocket\Support;

class MessageFormatter
{
    /**
     * 设置统一消息格式
     * @param string $type
     * @param int|string $from
     * @param int|string $to
     * @param string $content
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
}
