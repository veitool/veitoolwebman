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
 * 生成 RSA 密钥对
 * @param  int $bits 密钥长度，默认 2048（推荐 2048 或 4096）
 * @return array ['private' => string, 'public' => string] PEM 格式
 * @throws Exception 生成失败时抛出异常
 */
function getRSAKey(int $bits = 2048): array
{
    try {
        $flag = PHP_OS_FAMILY === 'Windows';
        $config = [
            "private_key_bits" => $bits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        if ($flag) $config['config'] = "nul";
        $res = openssl_pkey_new($config);
        $flag ? openssl_pkey_export($res, $privateKeyPem, null, $config) : openssl_pkey_export($res, $privateKeyPem);
        $publicKeyPem = openssl_pkey_get_details($res)['key'];
        return [
            'privateKeyPem' => $privateKeyPem,
            'publicKeyPem'  => $publicKeyPem
        ];
    } catch (Exception $e) {
        return [
            'privateKeyPem' => '',
            'publicKeyPem'  => ''
        ];
    }
}

/**
 * 获取config配置数据
 * @param  array  $keys  RSA密钥对、令牌密钥组、Domain
 * @return string
 */
function getVeitool(array $keys)
{
    return <<<EOT
<?php

return [
    //API接口地址 末尾不要加 /
    'api_url' => 'https://www.veitool.com',
    //插件强行卸载、覆盖 false 则会检查冲突文件
    'force'   => true,
    //插件是否备份有冲突的全局文件
    'back_up' => true,
    //是否删除插件原可动资源目录
    'clean'   => true,
    //是否允许未知来源的插件压缩包【当.env中APP_DEBUG = true 同时 unknown = true 时可安装未知来源插件。用于插件开发者调试】
    'unknown' => false,
    //插件卸载时是否删除相关数据表和配置
    'ddata'   => true,
    //服务端解密私钥，左边不要有空格，百度“rsa密钥在线生成”，需2048位PKCS1格式
    'rsa_pri_key' => <<<EOF
{$keys['privateKeyPem']}EOF,
    //加密公钥：用作前端密钥 和 jwt密钥
    'rsa_pub_key' => <<<EOF
{$keys['publicKeyPem']}EOF,
    'jwt' => [
        'algorithms'         => 'HS256', /* 算法类型 HS256、HS384、HS512、RS256、RS384、RS512、ES256、ES384、ES512、PS256、PS384、PS512 */
        'access_secret_key'  => '{$keys['access_secret_key']}', /* access令牌秘钥 */
        'refresh_secret_key' => '{$keys['refresh_secret_key']}', /* refresh令牌秘钥 */
        'access_exp'         => 7200, /* access令牌过期时间，单位：秒。默认 2 小时 */
        'refresh_exp'        => 604800, /* refresh令牌过期时间，单位：秒。默认 7 天 */
        'refresh_off'        => false, /* refresh令牌是否禁用，默认不禁用 false */
        'iss'                => '{$keys['domain']}', /* 令牌签发者 */
        'nbf'                => 0, /* 某个时间点后才能访问，单位秒。（如：30 表示当前时间30秒后才能使用） */
        'leeway'             => 60, /* 时钟偏差冗余时间，单位秒。建议小于120 */
        'single_device_on'   => false, /* 是否允许单设备登录，默认不允许 false，开启需要有 Redis 支持*/
        'cache_token_ttl'    => 604800, /* 缓存令牌时间，单位：秒。默认 7 天 */
        'cache_token_a_pre'  => 'JWT:TOKEN:', /* 缓存令牌前缀，默认 JWT:TOKEN: */
        'cache_token_r_pre'  => 'JWT:REFRESH_TOKEN:', /* 缓存刷新令牌前缀，默认 JWT:REFRESH_TOKEN: */
        'get_token_on'       => false, /* 是否支持 get 请求获取令牌 */
        'get_token_key'      => 'authorization', /* GET 请求获取令牌请求key */
        //'user_model'       => function(\$userid){return [];}, /* 用户信息模型 */
    ],
];
EOT;
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
