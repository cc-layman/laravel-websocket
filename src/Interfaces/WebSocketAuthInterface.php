<?php

namespace Layman\LaravelWebsocket\Interfaces;
interface WebSocketAuthInterface
{
    /**
     * 通过请求数据进行认证
     * @param array $query WebSocket 握手请求参数
     * @return int|null|string  用户唯一标识
     */
    public function authenticate(array $query): int|null|string;
}
