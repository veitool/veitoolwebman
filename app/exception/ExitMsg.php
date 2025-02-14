<?php
/**
 * ===========================================================================
 * Veitool 快捷开发框架系统
 * Author: Niaho 26843818@qq.com
 * Copyright (c)2019-2025 www.veitool.com All rights reserved.
 * Licensed: 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
 * ---------------------------------------------------------------------------
 */
namespace app\exception;

use support\exception\BusinessException;
use Webman\Http\Request;
use Webman\Http\Response;
use function json_encode;

/**
 * Class BusinessException
 * @package support\exception
 */
class ExitMsg extends BusinessException
{
    /**
     * 反馈信息
     * @var string
     */
    protected $msg = '';

    /**
     * 视图地址
     * @var string
     */
    protected $tpl = '';

    /**
     * 状态码
     * @var int
     */
    protected $code = 200;

    /**
     * 数据集
     * @var array
     */
    protected $data = [];

    /**
     * 头部信息组
     * @var array
     */
    protected $headers = [];

    /**
     * 构造函数
     * @param string   $msg       反馈信息
     * @param string   $tpl       视图地址
     * @param integer  $code      状态码
     * @param array    $data      数据集
     * @param array    $headers   头部信息组
     * 
     */
    public function __construct(string $msg, string $tpl = null, int $code = 302, array $data = [], array $headers = [])
    {
        $this->msg     = $msg;
        $this->tpl     = $tpl;
        $this->code    = $code;
        $this->data    = $data;
        $this->headers = $headers;
    }

    /**
     * 渲染实现跳转
     * @param  Request $request
     * @return Response|null
     */
    public function render(Request $request): ?Response
    {
        $headers = [];
        $body    = $this->msg;
        if ($this->tpl) {
            $handler = \config('view.handler');
            $body = $handler::render($this->tpl, $this->data);
        }else if ($request->expectsJson()) {
            $headers = ['Content-Type' => 'application/json'];
            $body    = json_encode(['msg' => $this->msg, 'code' => $this->code, 'data' => $this->data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        return (new Response(200, $headers, $body))->withHeaders($this->headers);
    }

}