<?php
namespace app\common\event;

/**
 * 事件处理类示例
 */
class User
{
    /**
     * 用户注册事件处理
     * @param array $user 用户信息
     */
    function register($user)
    {
        var_export($user);
    }

    /**
     * 用户登出事件处理
     * @param array $user 用户信息
     */
    function logout($user)
    {
        var_export($user);
    }

}