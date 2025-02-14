<?php

// 设置是否允许下一步
function setOk($val)
{
    global $isOK;
    $isOK = $val;
}

// 测试可写性
function isWrite($file)
{
    if(is_writable(base_path() .$file)){
        echo '<b class="green">可写</b>';
    }else{
        echo '<span>不可写</span>';
        setOk(false);
    }
}

// 测试函数是否存在
function isFunExists($func)
{
    $state = function_exists($func);
    if($state === false){
        setOk(false);
    }
    return $state;
}

// 测试函数是否存在
function isFunExistsTxt($func)
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
CHARSET  = utf8
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
