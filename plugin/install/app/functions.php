<?php

/**
 * 获取pdo连接
 * @param string $host     数据库地址
 * @param string $username 数据库账号
 * @param string $password 数据库密码
 * @param string $port     数据库端口
 * @param string $database 数据库名称
 * @return \PDO
 */
function getPDO($host, $username, $password, $port, $database = null)
{
    $dsn = "mysql:host={$host};port={$port};".($database ? "dbname={$database}" : "");
    $params = [
        \PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8mb4",
        \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        \PDO::ATTR_EMULATE_PREPARES => false,
        \PDO::ATTR_TIMEOUT => 5,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    ];
    return new \PDO($dsn, $username, $password, $params);
}

/**
 * 设置步骤全局状态 $isOK 的值
 * @global bool $isOK
 * @param  bool $val
 */
function setOk(bool $val)
{
    global $isOK;
    $isOK = $val;
}

/**
 * 测试可写性
 * @param  string $path 路径
 * @param  int    $p    权限值
 * @return string
 */
function isWrite(string $path, int $p = 0)
{
    if (!@file_exists(base_path() . $path)) {
        $perms = 0;
    } else {
        $perms = (int)substr(sprintf('%o', @fileperms(base_path() . $path)), -3);
    }
    if ($perms >= $p) {
        echo '<b class="green">符合('.$perms.')</b>';
    } else {
        echo '<span>不符合('.$perms.')</span>';
        setOk(false);
    }
}

/**
 * 测试函数是否存在
 * @param  string  $func  函数名
 * @return bool
 */
function isFunExists(string $func)
{
    $state = function_exists($func);
    if($state === false){
        setOk(false);
    }
    return $state;
}

/**
 * 测试函数是否存在
 * @param  string  $func  函数名
 * @return string
 */
function isFunExistsTxt(string $func)
{
    if(isFunExists($func)){
        echo '<b class="layui-icon green">&#xe697;</b>';
    }else{
        echo '<span>需安装</span>';
        setOk(false);
    }
}

/**
 * 获取扩展要求数据
 * @return array
 */
function getExtendArray()
{
    $data = [
        [
            'name' => 'CURL',
            'status' => extension_loaded('curl'),
        ],
        [
            'name' => 'OpenSSL',
            'status' => extension_loaded('openssl'),
        ],
        [
            'name' => 'PDO Mysql',
            'status' => extension_loaded('PDO') && extension_loaded('pdo_mysql'),
        ],
        [
            'name' => 'Mysqlnd',
            'status' => extension_loaded('mysqlnd'),
        ],
        [
            'name' => 'JSON',
            'status' => extension_loaded('json')
        ],
        [
            'name' => 'Fileinfo',
            'status' => extension_loaded('fileinfo')
        ],
        [
            'name' => 'GD',
            'status' => extension_loaded('gd'),
        ],
        [
            'name' => 'BCMath',
            'status' => extension_loaded('bcmath'),
        ],
        [
            'name' => 'Mbstring',
            'status' => extension_loaded('mbstring'),
        ],
        [
            'name' => 'SimpleXML',
            'status' => extension_loaded('SimpleXML'),
        ]
    ];
    foreach($data as $item){
        !$item['status'] && setOk(false);
    }
    return $data;
}

/**
 * 获取Env配置数据
 * @return string
 */
function getEnvs()
{
    return <<<EOT
APP_DEBUG = true
APP_TRACE = false

[APP]
DEFAULT_TIMEZONE = Asia/Shanghai

[DATABASE]
TYPE     = mysql
HOSTNAME = ~db_host~
DATABASE = ~db_name~
USERNAME = ~db_user~
PASSWORD = ~db_pwd~
HOSTPORT = ~db_port~
PREFIX   = ~db_pre~
CHARSET  = utf8mb4
DEBUG    = true

[CACHE]
DRIVER = file

[REDIS]
HOSTNAME = 127.0.0.1
HOSTPORT = 6379
PASSWORD =
SELECT = 0

[LANG]
default_lang = zh-cn
EOT;
}
