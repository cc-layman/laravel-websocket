<?php

namespace Layman\LaravelWebsocket\Interfaces;

interface AuthenticateInterface
{
    /**
     * @param array $query 请求参数
     * @return int|null|string  用户唯一标识
     */
    public function authenticate(array $query): int|string|null;
}
