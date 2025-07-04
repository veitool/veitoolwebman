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
 *【地区模型】
 */
class SystemArea extends Base
{
    /**
     * 启用软删除操作
     */
    use \think\model\concern\SoftDelete; /**/

    /* *
     * 全局已开启自动时间戳，取消注释则会关闭该模型
     * @var bool
     * /
    protected $autoWriteTimestamp = false; /**/

    /**
     * 定义主键
     * @var string 
     */
    protected $pk = 'areaid';

    /**
     * 获取地区名称串
     * @param  string   $aid   地区ID串
     * @param  string   $fg    分隔符
     * @return string   返回地区名，如：广东-广州-番禺
     */
    public static function getAreaStr(string $aid = '', string $fg = '-')
    {
        $str = '';
        if(!$rs = Cache::get('VAREAS_N')) $rs = self::cache(1, 1);
        $arr = explode(',', $aid);
        foreach ($arr as $v){
            if(isset($rs[$v])){
                $str .= $str ? $fg.$rs[$v] : $rs[$v];
            }
        }
        return $str;
    }

    /**
     * 获取层级地区JSON数据（递归 用于手机版 调用 该方法已废用）
     * @param   array    $data  地区数据
     * @param   int      $pid   上级ID
     * @return  array
     */
    public static function getAreaJson(array $data = [], int $pid = 0)
    {
        if(!$data){
            $data = self::cache(0);
        }
        $arr = [];
        foreach($data as $v){
            if($v['parentid']==$pid){
                $a = [
                    'text'     => $v['areaname'],
                    'value'    => (string)$v['areaid'],
                    'children' => self::getAreaJson($data,$v['areaid'])
                ];
                if(!$a['children']) unset($a['children']);
                $arr[] = $a;
            }
        }
        return $arr;
    }

    /**
     * 缓存地区数据
     * @param   int   $reset   是否重置缓存
     * @param   int   $flag    是否返回键值对数据
     * @return  array
     */
    public static function cache(int $reset = 0, int $flag = 0)
    {
        $key = 'VAREAS';
        $rs = Cache::get($key);
        if(!$rs || $reset){
            $rs = self::order('listorder','asc')->column('areaid,areaname,parentid,arrparentid,childs,listorder','areaid');
            if(!$rs) return $rs;
            Cache::set($key,$rs,31536000);
            $data = [];
            foreach ($rs as $v){
                $data[$v['areaid']] = $v['areaname'];
            }
            Cache::set($key.'_N',$data,31536000);
            //生成JS文件
            $myfile = @fopen(VT_PUBLIC."static/script/cityData.js", "w");
            if($myfile){
                $txt = json_encode(self::getAreaJson(),JSON_UNESCAPED_UNICODE);
                fwrite($myfile, 'var cityData = '. str_replace(['"text"','"value"','"children"'], ["text","value","children"], $txt).';');
                fclose($myfile);
            }
        }
        return $flag ? $data : $rs;
    }

}