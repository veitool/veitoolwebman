<?php
namespace app\common\queue\redis;

use Webman\RedisQueue\Consumer;

/**
 * 消费演示类
 */
class EmailSend implements Consumer
{
    // 要消费的队列名，名称须和投递时的名称一致
    public $queue = 'send-mail';

    // 连接名，对应 config/plugin/webman/redis-queue/redis.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        // 无需反序列化
        var_export($data); // 输出 ['to' => 'tom@gmail.com', 'content' => 'hello']
    }

    // 消费失败时
    public function onConsumeFailure(\Throwable $exception, $package)
    {
        var_export($package);
        // 直接更改消息队列数据结构，将最大重试次数max_attempts字段设置为0，即不再重试。
        $package['max_attempts'] = 0;
        // 除此之外还可更改data字段(也就是consume方法中的$data)

        // 返回更改后的数据结构
        return $package;
    }

}