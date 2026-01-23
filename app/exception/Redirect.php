<?php
/**
 * ===========================================================================
 * Veitool 快捷开发框架系统
 * Author: Niaho 26843818@qq.com
 * Copyright (c)2019-2026 www.veitool.com All rights reserved.
 * Licensed: 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
 * ---------------------------------------------------------------------------
 */
namespace app\exception;

use support\exception\BusinessException;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 *【自定义跳转类】
 */
class Redirect extends BusinessException
{
    /**
     * 跳转 URL
     * @var string
     */
    protected $location = '';

    /**
     * 状态码
     * @var int
     */
    protected $status = 302;

    /**
     * 头部信息组
     * @var array
     */
    protected $headers = [];

    /**
     * 构造函数
     * @param string  $location   跳转 URL
     * @param integer $status     状态码
     * @param array   $headers    头部信息组
     */
    public function __construct(string $location, int $status = 302, array $headers = [])
    {
        $this->location = $location;
        $this->status  = $status;
        $this->headers = $headers;
    }

    /**
     * 渲染实现跳转
     * @param  Request $request
     * @return Response|null
     */
    public function render(Request $request): ?Response
    {
        return redirect($this->location, $this->status, $this->headers);
    }

}