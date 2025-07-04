<?php
/**
 * ===========================================================================
 * Veitool 快捷开发框架系统
 * Author: Niaho 26843818@qq.com
 * Copyright (c)2019-2025 www.veitool.com All rights reserved.
 * Licensed: 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
 * ---------------------------------------------------------------------------
 */
namespace app\model\system;

use app\model\Base;
use support\Cache;

/**
 *【角色模型】
 * 菜单缓存标识说明：VMENUS_1：后台全部菜单  VMENUS_2：会员全部菜单
 * 后台角色权限缓存：VMENUS_1_角色ID  在角色模型中处理 即当前页
 * 会员分组权限缓存：VMENUS_2_分组ID  在分组模型中处理
 */
class SystemRoles extends Base
{
    /**
     * 启用软删除操作
     */
    use \think\model\concern\SoftDelete; /**/

    /**
     *定义主键
     * @var string 
     */
    protected $pk = 'roleid';

    /**
     * 分页列表
     * @return obj
     */
    public function listQuery()
    {
        return $this->field('roleid,role_name,role_menuid,role_ext,listorder,state,add_time')->order('listorder', 'asc')->paginate(input('limit/d'));
    }

    /**
     * 下拉列表
     * @return array
     */
    public function listSelect()
    {
        return $this->field('roleid,role_name,state')->order('listorder', 'asc')->select()->toArray();
    }

    /**
     * 缓存角色权限
     * @param    int|array   $role     角色ID 或者 array('roleid'=>角色ID,'role_name'=>角色名,'role_menuid'=>拥有的菜单ID串)
     * @param    int         $reset    是否重置 默认 否
     * @return   array ['roleid'=>角色ID,'role_name'=>角色名,'role_menuid'=>拥有的菜单ID串,'role_ext'=>控制器内扩展权限控制,'actions'=>权限记录集]
     */
    public static function cache(int|array $role, int $reset = 0)
    {
        $roleid = is_array($role) ? $role['roleid'] : $role;
        $key = 'VMENUS_1_'.$roleid;
        $rs = Cache::get($key);
        if(!$rs || $reset){
            //清空记录
            $rs  = [];
            $str = '';
            //获取角色
            $ro = is_array($role) ? $role : self::where(['state'=>1,'roleid'=>$roleid])->field('roleid,role_name,role_menuid,role_ext')->findOrEmpty()->toArray();
            if(!empty($ro) && $ro['role_menuid']){
                //获取后台菜单缓存
                $ms = SystemMenus::cache();
                foreach ($ms as $k=>$v){
                    if(strpos(",$ro[role_menuid],",",$k,") !== false){
                        $str .= $v['role_url'] ? ','.$v['role_url'] : '';
                    }
                }
            }elseif(empty($ro)){
                Cache::delete($key);
                return [];
            }
            $rs['actions'] = explode(',', trim($str,','));
            if($ro['role_ext']){
                $arr = explode("\n", $ro['role_ext']);
                $ro['role_ext'] = [];
                foreach($arr as $v){
                    $ks = explode('=', $v);
                    $ro['role_ext'][$ks[0]] = $ks[1] ?? 0;
                }
            }else{
                $ro['role_ext'] = [];
            }
            $rs = array_merge($ro, $rs);
            Cache::set($key,$rs,31536000);
        }
        return $rs;
    }

}