<?php
/**
 * ===========================================================================
 * Veitool 快捷开发框架系统
 * Author: Niaho 26843818@qq.com
 * Copyright (c)2019-2026 www.veitool.com All rights reserved.
 * Licensed: 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
 * ---------------------------------------------------------------------------
 */
namespace app\api\controller;

use app\BaseController;
use Webman\Captcha\CaptchaBuilder;
use Webman\Captcha\PhraseBuilder;

/**
 * 验证码
 */
class Captcha extends BaseController
{
    /**
     * 后台验证码
     */
    public function admin()
    {
        // 验证码长度
        $length = 5;
        // 包含哪些字符
        $chars = '123588689856823982698952818900358565866688228';
        $builder = new PhraseBuilder($length, $chars);
        $captcha = new CaptchaBuilder(null, $builder);
        // 生成验证码
        $captcha->build();
        // 将验证码的值存储到 session 中
        $this->request->session()->set('captcha', [
            'key' => password_hash(mb_strtolower($captcha->getPhrase(), 'UTF-8'), PASSWORD_BCRYPT, ['cost' => 10])
        ]);
        // 输出验证码二进制数据
        return response($captcha->get(), 200, ['Content-Type' => 'image/jpeg']);
    }

    /**
     * 前台验证码
     */
    public function index()
    {
        // 验证码长度
        $length = 5;
        // 包含哪些字符
        $chars = '123588689856823982698952818900358565866688228';
        $builder = new PhraseBuilder($length, $chars);
        $captcha = new CaptchaBuilder(null, $builder);
        // 生成验证码
        $captcha->build();
        // 将验证码的值存储到 session 中
        $this->request->session()->set('captcha', [
            'key' => password_hash(mb_strtolower($captcha->getPhrase(), 'UTF-8'), PASSWORD_BCRYPT, ['cost' => 10])
        ]);
        // 输出验证码二进制数据
        return response($captcha->get(), 200, ['Content-Type' => 'image/jpeg']);
    }

}