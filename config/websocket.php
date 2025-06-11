<?php
return [
    /**
     * 地址
     */
    'host' => '127.0.0.1',

    /**
     * 端口
     */
    'port' => 9502,

    /**
     * 启动类型 SWOOLE_BASE = 1|SWOOLE_PROCESS = 2
     */
    'model' => 1,

    /**
     * 启动进程数
     */
    'worker_num' => 1,

    /**
     * 每3秒发送一次 ping
     */
    'heartbeat_interval' => 3,

    /**
     *  超过30秒未响应则断开连接
     */
    'heartbeat_timeout' => 30,

    /*
     * redis消息订阅频道
     */
    'redis_subscribe_channel' => 'redis_subscribe_channel',

    /**
     * 启用redis离线消息
     */
    'redis_persistence' => false,

    /**
     * 启动数据库离线消息(mysql)
     */
    'database_persistence' => false,
];
