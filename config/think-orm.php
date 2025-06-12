<?php

return [
    'default' => genv('database.driver', 'mysql'),

    // 自动写入时间戳字段
    // true为自动识别类型 false关闭
    // 字符串则明确指定时间字段类型 支持 int timestamp datetime date
    'auto_timestamp'  => 'int',

    // 时间字段取出后的默认时间格式
    'datetime_format' => false, //'Y-m-d H:i:s',
 
    'connections' => [
        'mysql' => [
            // 数据库类型
            'type'     => genv('database.type', 'mysql'),
            // 服务器地址
            'hostname' => genv('database.hostname', '127.0.0.1'),
            // 数据库名
            'database' => genv('database.database', ''),
            // 数据库用户名
            'username' => genv('database.username', 'root'),
            // 数据库密码
            'password' => genv('database.password', ''),
            // 数据库连接端口
            'hostport' => genv('database.hostport', '3306'),
            // 数据库连接参数
            'params' => [
                // 连接超时3秒
                \PDO::ATTR_TIMEOUT => 3,
            ],
            // 数据库编码默认采用utf8
            'charset' => genv('database.charset', 'utf8'),
            // 数据库表前缀
            'prefix' => genv('database.prefix', ''),
            // 断线重连
            'break_reconnect' => true,
            // 自定义分页类
            'bootstrap' =>  '',
            // 连接池配置(仅在swow/swoole驱动下有效)
            'pool' => [
                'max_connections' => 5,
                'min_connections' => 1,
                'wait_timeout' => 3,
                'idle_timeout' => 60,
                'heartbeat_interval' => 50,
            ],
        ],
    ],
];
