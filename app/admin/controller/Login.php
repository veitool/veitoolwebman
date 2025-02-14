<?php
/**
 * ===========================================================================
 * Veitool 快捷开发框架系统
 * Author: Niaho 26843818@qq.com
 * Copyright (c)2019-2025 www.veitool.com All rights reserved.
 * Licensed: 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
 * ---------------------------------------------------------------------------
 */
namespace app\admin\controller;

use app\BaseController;
use app\model\system\SystemManager as Manager;
use app\model\system\SystemLoginLog as LoginLog;
use app\model\system\SystemOnline as Online;
use app\common\Lock;

/**
 * 后台登录
 */
class Login extends BaseController
{
    /**
     * 覆盖无需业务 & 初始映射路径
     */
    protected function __home(){}

    /**
     * 登录首页
     */
    public function index()
    {
        if(!empty(session(VT_MANAGER))) return redirect(VT_MAP);
        return $this->fetch();
    }

    /**
     * 退出系统
     */
    public function logout()
    {
        if($User = session(VT_MANAGER)) Online::del(['uid'=>$User['uid'],'userid'=>$User['userid']]); //删除在线数据
        $this->request->session()->delete(VT_MANAGER);
        return redirect(VT_MAP);
    }

    /**
     * 解锁屏处理
     * @return  json
     */
    public function unlock()
    {
        if(is_null($us = session(VT_MANAGER))) return $this->returnMsg('还未登录');
        $password = $this->request->post('password','');
        if($us['password'] === set_password($password,$us['passsalt'])){
            return $this->returnMsg('success',1);
        }else{
            return $this->returnMsg('解锁密码错误');
        }
    }

    /**
     * 登录验证
     * @return  json
     */
    public function check()
    {
        //多次尝试验证
        $ip = $this->request->getRealIp();
        if(Lock::check(['key'=>'LOGIN_'.$ip])) return $this->returnMsg(Lock::msg());
        $d = $this->only(['username/*/u/管理帐号','password/*/p/登录密码','captcha']);
        if(vconfig('admin_captcha',1) && !captcha_check($d['captcha'])) return $this->returnMsg('验证码错误！');
        $username = $d['username'];
        $password = $d['password'];
        //查询用户数据
        $rs = Manager::get(compact('username'));
        if(empty($rs)){
            LoginLog::add($username, $password, '', '账号错误');
            Lock::add();
            return $this->returnMsg('帐号或密码错误！');
        }
        if($rs->state == 0) return $this->returnMsg('帐号已被停用！');
        if($rs['password'] === set_password($password,$rs['passsalt'])){
            $rs->logintime = time();
            $rs->loginip   = $ip;
            $rs->logins ++;
            $rs->save();
            $rs = $rs->toArray();
            $rs['uid'] = 'AM-'.uniqid(); //设置编号
            LoginLog::add($username, $password, $rs['passsalt']);
            $this->request->session()->set(VT_MANAGER,$rs);
            Lock::del();
            return $this->returnMsg('登录成功！',1,['url'=>(VT_MAP ?: '/')]);
        }
        LoginLog::add($username, $password, $rs['passsalt'], '密码错误');
        Lock::add();
        return $this->returnMsg('帐号或密码错误！');
    }

}