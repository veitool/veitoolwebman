<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

return [
    'default' => [
        'password' => genv('redis.password', ''),
        'host'     => genv('redis.hostname', '127.0.0.1'),
        'port'     => genv('redis.hostport', 6379),
        'database' => genv('redis.select', 0),
        // Connection pool, supports only Swoole or Swow drivers. 连接池配置
        'pool' => [
            'max_connections'    => 5,  // 连接池最大连接数
            'min_connections'    => 1,  // 连接池最小连接数
            'wait_timeout'       => 3,  // 从连接池获取连接最大等待时间
            'idle_timeout'       => 60, // 连接池中连接空闲超时时间，超过该时间会被关闭，直到连接数为min_connections
            'heartbeat_interval' => 50, // 心跳检测间隔，不要小于60秒
        ],
    ]
];
