<?php
return [
    'host' => '127.0.0.1',
    'port' => 9502,
    'model' => SWOOLE_BASE, // SWOOLE_PROCESS
    'worker_num' => 1,
    'heartbeat_interval' => 3,  // 每 3 秒发送一次 ping
    'heartbeat_timeout' => 30,  // 超过 30 秒未响应则断开连接
    'redis_channel' => 'ws:publish',
];
