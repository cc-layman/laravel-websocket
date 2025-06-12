<?php
return [
    /**
     * 地址
     */
    'host' => '0.0.0.0',

    /**
     * 端口
     */
    'port' => 9502,

    /**
     * 启动类型 SWOOLE_BASE|SWOOLE_PROCESS
     */
    'model' => SWOOLE_BASE,

    /**
     * 启动进程数
     */
    'worker_num' => 1,

    /**
     * 每3秒发送一次 ping
     */
    'heartbeat_interval' => 5,

    /**
     *  超过30秒未响应则断开连接
     */
    'heartbeat_timeout' => 30,

    /*
     * redis消息订阅频道
     */
    'redis_subscribe_channel' => 'redis_subscribe_channel',

    /**
     * 启用redis离线消息 启用后消息一发出，会立即销毁当前消息缓存
     */
    'redis_persistence' => false,

    /**
     * 启动数据库离线消息(mysql) 启用后不会销毁当前消息数据，如需销毁，建议自行实现用户已读销毁
     */
    'database_persistence' => false,

    /**
     * auth认证类
     */
    'auth_class' => null,
];
