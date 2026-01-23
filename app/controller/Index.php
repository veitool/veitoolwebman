<?php
/**
 * ===========================================================================
 * Veitool 快捷开发框架系统
 * Author: Niaho 26843818@qq.com
 * Copyright (c)2019-2026 www.veitool.com All rights reserved.
 * Licensed: 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
 * ---------------------------------------------------------------------------
 */
namespace app\controller;

use app\BaseController;

/**
 * 前台主控制器
 */
class Index extends BaseController
{
    /**
     * 后台首页
     * @return mixed
     */
    public function index()
    {
        $this->msgTpl = 'app/v_msg.tpl';
        return $this->returnMsg('欢迎使用Veitool快捷开发框架系统（Webman版）');
    }

    /**
     * 发布事件
     * 测试时请开启 config/plugin/webman/event/app.php 中的 enable 为 true
     * 同时设置 config/event.php 中的事件处理函数
     * @return mixed
     */
    public function event()
    {
        return '发布事件测试';

        $user = [
            'name' => 'tom',
            'age'  => 18
        ];
        // 触发事件
        \Webman\Event\Event::dispatch('user.register', $user);

        return response('触发事件成功');
    }

    /**
     * 投递消息请求页(同步) 消费目录在 app/common/queue/redis 下，
     * 由配置文件 config/plugin/webman/redis-queue/process.php 设定消费目录，
     * 不同类型的消费组可以设置多个消费进程处理。
     * 测试时请开启 config/plugin/webman/redis-queue/app.php 中的 enable 为 true
     * @return mixed
     */
    public function send()
    {
        return '投递消息请求页测试(同步)';

        // 队列名
        $queue = 'send-mail';
        // 数据，可以直接传数组，无需序列化
        $data = ['to' => 'tom@gmail.com', 'content' => 'hello'];

        // 投递消息 投递成功返回true，否则返回false或者抛出异常。
        // 也可以选定 redis 来投递 Client::connection('default')->send($queue, $data); 
        // 其中 default 是配置文件：config/plugin/webman/redis-queue/redis.php 中的 redis 配置
        \Webman\RedisQueue\Redis::send($queue, $data);
        // 投递延迟消息，消息会在60秒后处理
        \Webman\RedisQueue\Redis::send($queue, $data, 10);

        return response('同步投递消息成功 redis queue test');
    }

    /**
     * 投递消息请求页(异步) 消费目录在 app/common/queue/redis 下，
     * 由配置文件 config/plugin/webman/redis-queue/process.php 设定消费目录，
     * 不同类型的消费组可以设置多个消费进程处理
     * @return mixed
     */
    public function send1()
    {
        return '投递消息请求页测试(异步)';

        // 队列名
        $queue = 'send-mail';
        // 数据，可以直接传数组，无需序列化
        $data = ['to2' => 'tom2@gmail.com', 'content2' => 'hello2'];

        // 投递消息 没有返回值，它属于异步推送，它不保证消息%100送达redis。
        \Webman\RedisQueue\Client::send($queue, $data);
        // 投递延迟消息，消息会在60秒后处理
        \Webman\RedisQueue\Client::send($queue, $data, 60);

        return response('异步投递消息成功 redis queue test');
    }

    /**
     * 协程测试
     * 需要安装扩展 composer require revolt/event-loop ^1.0.0 后才可使用
     * @return void
     */
    public function coroutine()
    {
        return '协程测试';

        /* 协程延迟5秒执行 * /
        \Revolt\EventLoop::delay(5, function () {
            echo "veitool";
        });/**/

        //访问会立即返回信息
        return '这里是协程测试';
    }

}