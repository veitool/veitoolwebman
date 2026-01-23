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
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDQOPfCHJ/Rc172
Mzz3cVElgONac7H24fp1fflNE5j8BVXr7446a+Q1XUTPduqFW61yaQ/YC4MHPUJv
dGV4ozUTW5quHgEgRxrR4JM0L3MhfFQ/EL8g6uDV6nDdM83rycLlPNclzZxVBj8Z
q5MIdm/jmWrj/kxD992AZBpOOKYBhWeIbBXbXrN75OF8m0SMfEOIJhSXr04KDG17
7zsf6u0+n4VoIABsikJv5hA3FXVjOABAsSit0gjiu9yfxZUBV0YFj0p8EZD7mFzc
fpmx22kAYt84tQvANl0TpI+lCApCqdV72YLvqJLzlxvJH8/lR1EPedYg0D3QUC3I
y8ek9uUbAgMBAAECggEAEAaSawow8rnicSh4ZML/79b/qJKG++1gjXJooZoEiEEQ
39vMIorzQXgwN5d7bVAlBU04vvQD3abFWjanKKXHC/pc6VG93HIcilKyga21OrqK
8V+kli/9pfkpPp0iGCerLGGGXY55ncGhUaR26IvVCLtiErIbxI9rKNqLe8G4ZR1v
1GoobR1mt69/kDJdF9wcQ0t3u6vH/MHkO8eguzdrdzKzxyWmRJz9IUJmQIfYZq1d
4H+qPIO3tIw/iJ7feuHxPFt+MWomNbkQPc1Cz//5KOueIznbDSs+RZknQ6eA6H0l
pMZG1FltLErKFfbZD+ChMaLnz5L/aLnQCBY1l9rOAQKBgQDnKdJ9TUV57siacJ51
HrdnWi+CV8FwOwnHg8GdMcw8zrdyahqlcZRTHXq1ZjGNMkCQiF2TXoBwewho1UKC
4Ymd254htJaUeAgM3gMyLrIdCTP1IqIGTPzi1nnZuZKKabBwmCEYA06XsqxudOET
+8yXgsa3bZeUkvYGj6Odln4f2wKBgQDmmCeppSY55u7XM64tt5rUfsa1nibuObM4
QeEODWBUVtNrNZL8qs7eJ5t+Zcuze5X2OSlrrtbC0kkRsQNexjPERBgCKRzOo60+
Y7WjVlth2H0fVZ4sRnHxkXJUoqPCB8QYqXWldftMxxxLcDaptJwq60yGUGoLgyB1
fXVtWizzwQKBgQCrUvfKHkbrw+mxbN5D92v+kXy8ocWgJGFvGVuZ4Zp5Rsv3ZGQz
UiaYIzUa1I7NEv1/IVIZMuUGeGkXKVeOIRIZiPd+C5W+m+d7gd/khW9EzdlQSUbE
XLLgfAz7LOpK42H9UQLWBT1ueOILS1y1rt1HPYmB0RuS5gipfDPLTApWGQKBgCfv
df8FYQdqHTcb9jBoueHPSu6tQyTCzW0Sy705R9OTbe4FSz2C/2yuA1nym0KsRp5r
6+aAUyVOceUkeObzAIgfGuFU6W5IHnrNnel5zT221oSUuV4FnTou7FQIDsBNxAJZ
ZsyPEESNvzK5bl4Zr2oncgtY5eS0guyWG74ifeKBAoGBAIQ+JQ2ovn3gJGhUfj7P
CWKY8HsINV3/Za0yQ4i1vCQomYlEm23cMx5CvfvvPsdOlrvi+qUa/MMvJF5pCk2H
16oEr26G0Rv+tko8kX3b0okdhWRhAWjgeAQinXIM92+3Y407qba+QAAep2wTiPj2
iOddHNl+KrahVeJf1JdU2H12
-----END PRIVATE KEY-----
EOF,
    //加密公钥：用作前端密钥 和 jwt密钥
    'rsa_pub_key' => <<<EOF
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0Dj3whyf0XNe9jM893FR
JYDjWnOx9uH6dX35TROY/AVV6++OOmvkNV1Ez3bqhVutcmkP2AuDBz1Cb3RleKM1
E1uarh4BIEca0eCTNC9zIXxUPxC/IOrg1epw3TPN68nC5TzXJc2cVQY/GauTCHZv
45lq4/5MQ/fdgGQaTjimAYVniGwV216ze+ThfJtEjHxDiCYUl69OCgxte+87H+rt
Pp+FaCAAbIpCb+YQNxV1YzgAQLEordII4rvcn8WVAVdGBY9KfBGQ+5hc3H6Zsdtp
AGLfOLULwDZdE6SPpQgKQqnVe9mC76iS85cbyR/P5UdRD3nWINA90FAtyMvHpPbl
GwIDAQAB
-----END PUBLIC KEY-----
EOF,
    'jwt' => [
        'algorithms'         => 'HS256', /* 算法类型 HS256、HS384、HS512、RS256、RS384、RS512、ES256、ES384、ES512、PS256、PS384、PS512 */
        'access_secret_key'  => '3f24c2ddc78926a8c0a0847faba1b7a6', /* access令牌秘钥 */
        'refresh_secret_key' => 'e51078acbe62457f27a40be2bdbe1688', /* refresh令牌秘钥 */
        'access_exp'         => 7200, /* access令牌过期时间，单位：秒。默认 2 小时 */
        'refresh_exp'        => 604800, /* refresh令牌过期时间，单位：秒。默认 7 天 */
        'refresh_off'        => false, /* refresh令牌是否禁用，默认不禁用 false */
        'iss'                => '127.0.0.1:8787', /* 令牌签发者 */
        'nbf'                => 0, /* 某个时间点后才能访问，单位秒。（如：30 表示当前时间30秒后才能使用） */
        'leeway'             => 60, /* 时钟偏差冗余时间，单位秒。建议小于120 */
        'single_device_on'   => false, /* 是否允许单设备登录，默认不允许 false，开启需要有 Redis 支持*/
        'cache_token_ttl'    => 604800, /* 缓存令牌时间，单位：秒。默认 7 天 */
        'cache_token_a_pre'  => 'JWT:TOKEN:', /* 缓存令牌前缀，默认 JWT:TOKEN: */
        'cache_token_r_pre'  => 'JWT:REFRESH_TOKEN:', /* 缓存刷新令牌前缀，默认 JWT:REFRESH_TOKEN: */
        'get_token_on'       => false, /* 是否支持 get 请求获取令牌 */
        'get_token_key'      => 'authorization', /* GET 请求获取令牌请求key */
        //'user_model'       => function($userid){return [];}, /* 用户信息模型 */
    ],
];