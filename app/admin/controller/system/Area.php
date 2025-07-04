<?php
/**
 * ===========================================================================
 * Veitool 快捷开发框架系统
 * Author: Niaho 26843818@qq.com
 * Copyright (c)2019-2025 www.veitool.com All rights reserved.
 * Licensed: 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
 * ---------------------------------------------------------------------------
 */
namespace app\admin\controller\system;

use app\admin\controller\AdminBase;
use app\model\system\SystemArea as A;

/**
 * 后台地区控制器
 */
class Area extends AdminBase
{
    /**
     * 地区列表
     * @param  string  $pid  上级ID
     * @return mixed
     */
    public function index(?string $pid = null)
    {
        $rs = A::all("parentid=".(int)$pid, 'areaid,areaname,parentid,arrparentid,childs,listorder', ['listorder'=>'asc']);
        if(is_null($pid)){
            $this->assign('list', json_encode($rs));
            return $this->fetch();
        }else{
            if(isset($rs[0])){
                $r = A::where('areaid','in',$rs[0]['arrparentid'])->column('areaid,areaname');
                $rs[0]['arrparentid'] = '0|顶级';
                foreach ($r as $v){
                    $rs[0]['arrparentid'] .= ','.$v['areaid'].'|'.$v['areaname'];
                }
            }
            A::where('areaid',$pid)->update(['childs'=>count($rs->toArray())]);
            return $this->returnMsg($rs);
        }
    }

    /**
     * 地区添加
     * @return json
     */
    public function add()
    {
        $d = $this->only(['@token'=>'','parentid/d','listorder/d','areaname/*/{2,100}/地区名称']);
        $parentid  = $d['parentid'];
        $listorder = $d['listorder'];
        $arrparentid = 0;
        if($parentid > 0){
            if(is_null($rs = A::one(['areaid'=>$parentid]))) return $this->returnMsg("上级地区ID不存在");
            $arrparentid = $rs['arrparentid'] ? $rs['arrparentid'].','.$rs['areaid'] : $rs['areaid'];
        }
        $data = [];
        $area = explode("\n",$d['areaname']);
        foreach($area as $v){
            $v = strip_html($v);
            if(!$v) continue;
            $data[] = ['areaname'=>$v,'parentid'=>$parentid,'listorder'=>$listorder,'arrparentid'=>$arrparentid,'creator'=>$this->manUser['username']];
            $listorder ++;
        }
        A::saveAll($data);
        A::cache(1);
        return $this->returnMsg("添加地区成功", 1);
    }

    /**
     * 地区编辑
     * @return json
     */
    public function edit()
    {
        $d = $this->only(['@token'=>'','areaid/d/参数错误','av','af']);
        $Myobj = A::one(['areaid'=>$d['areaid']]);
        if(!$Myobj) return $this->returnMsg("数据不存在");
        $value = $d['av'];
        $field = $d['af'];
        if(!in_array($field,['areaname','listorder'])) return $this->returnMsg("参数错误2");
        if($field=='areaname'){
            $this->only(['av/*/{2,30}/地区名称']);
        }else{
            $value = intval($value);
        }
        if($Myobj->save([$field=>$value,'editor'=>$this->manUser['username']])){
            A::cache(1);
            return $this->returnMsg("更新成功", 1);
        }else{
            return $this->returnMsg("无数据更新");
        }
    }

    /**
     * 数据导入
     * @return json
     */
    public function import()
    {
        if(A::count() > 0){
            return $this->returnMsg("地区表非空，不可导入");
        }else{
            @set_time_limit(0);
            $file = ROOT_PATH . 'plugin/install/data/area_data.sql';
            $prefix = genv('database.prefix', 'vt_');
            if(is_file($file)){
                $sql = trim(file_get_contents($file));
                $sql = explode("\n", $prefix == 'vt_' ? $sql : str_replace(["\r\n", "\r", "vt_"], ["\n", "\n", $prefix], $sql));
                foreach($sql as $v){
                    \think\facade\Db::execute($v);
                }
                A::cache(1);
                return $this->returnMsg("导入成功",1);
            }else{
                return $this->returnMsg("安装目录下无 data/area_data.sql 内置数据文件");
            }
        }
    }

    /**
     * 地区删除
     * @return json
     */
    public function del()
    {
        $areaid = $this->only(['@token'=>'','areaid'])['areaid'];
        $areaid = is_array($areaid) ? $areaid : [$areaid];
        if($areaid && A::whereIn('parentid', $areaid)->find()) return $this->returnMsg("该地区不存在或有子地区不能删除！");
        if(A::destroy($areaid)){
            A::cache(1);
            return $this->returnMsg("删除成功", 1);
        }else{
            return $this->returnMsg("删除失败");
        }
    }

}