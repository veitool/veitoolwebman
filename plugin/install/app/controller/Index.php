<?php
/**
 * ===========================================================================
 * Veitool 快捷开发框架系统
 * Author: Niaho 26843818@qq.com
 * Copyright (c)2019-2025 www.veitool.com All rights reserved.
 * Licensed: 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
 * ---------------------------------------------------------------------------
 */
namespace plugin\install\app\controller;

use app\BaseController;

/**
 * 安装
 */
class Index extends BaseController
{
    /**
     * 覆盖无需业务 【阻止去调用数据库而报错】
     */
    protected function __home(){}

    /**
     * 覆盖无需业务 【阻止去调用数据库而报错】
     */
    protected function logon(string $tip = ''){}

    /**
     * 初始化
     */ 
    protected function __init(){
        if (is_file(base_path() . '/plugin/install/install.lock')) {
            return $this->exitMsg('管理后台已安装！如需重新安装，请删除文件 plugin/install/install.lock 并重启', 303, ['url' => '/admin']);
        }
        $this->assign([
            'copyright' => '© 2025 veitool.com 版权所有'
        ]);
    }

    /**
     * 安装首页
     */
    public function index()
    {
        clearstatcache();
        return $this->fetch('index/index', '', false);
    }

    /**
     * 环境检测
     */
    public function step2()
    {
        $this->assign([
            'isOK' => true, // 初始通过
            'iswrite_array' => [['/.env',644],['/runtime/',755],['/plugin/install/',755],['/public/static/file/',755]], // 检测是否可写的路径
            'exists_array'  => ['curl_init', 'bcadd', 'mb_substr', 'simplexml_load_string'], // 获取检测的函数数据
            'extendArray'   => getExtendArray(), // 获取扩展要求数据
        ]);
        return $this->fetch('index/step2', '', false);
    }

    /**
     * 设定配置
     */
    public function step3()
    {
        $isOK = $this->request->post('isOK', false);
        if(!$isOK) redirect("/app/install/index/step2");
        $this->assign([
            'currentHost' => ($this->request->getRemotePort() == 443 ? 'https://' : 'http://') . $this->request->header('host') . '/'
        ]);
        return $this->fetch('index/step3', '', false);
    }

    /**
     * 执行安装
     */
    public function step4()
    {
        clearstatcache();
        // 初始化信息
        $dbhost = $this->request->get('dbhost','','trim');
        $dbname = $this->request->get('dbname','','trim');
        $dbpre  = $this->request->get('dbpre','vt_','trim');
        $dbuser = $this->request->get('dbuser','','trim');
        $dbpwd  = $this->request->get('dbpwd','','trim');
        $dbport = $this->request->get('dbport','3306','trim');
        $overwrite = $this->request->get('overwrite/d',0);
        //$adminmap  = $this->request->get('adminmap','admin','trim');
        $adminuser = $this->request->get('adminuser','admin','trim');
        $adminpass = $this->request->get('adminpass','123456','trim');
        // 连接证数据库
        try{
            $pdo = getPDO($dbhost, $dbuser, $dbpwd, $dbport);
        }catch(\Exception $e){
            return $this->returnMsg("数据库连接错误，请检查！");
        }
        // 查询数据库是否存在
        $res = $pdo->query("show databases like '$dbname'");
        if (empty($res->fetchAll())) {
            if(!$pdo->exec("CREATE DATABASE `$dbname`")){
                return $this->returnMsg("创建数据库失败，请检查权限或联系管理员！");
            }
        }
        // 指定操作目标库
        $pdo->query("USE `$dbname`");
        // 清空全部表 或 表重名检查
        if($overwrite){
            $tables_install = [
                $dbpre.'system_area',
                $dbpre.'system_category',
                $dbpre.'system_dict',
                $dbpre.'system_dict_group',
                $dbpre.'system_login_log',
                $dbpre.'system_manager',
                $dbpre.'system_manager_log',
                $dbpre.'system_menus',
                $dbpre.'system_online',
                $dbpre.'system_organ',
                $dbpre.'system_roles',
                $dbpre.'system_sequence',
                $dbpre.'system_setting',
                $dbpre.'system_sms',
                $dbpre.'system_upload_file',
                $dbpre.'system_upload_group',
                $dbpre.'system_web_log',
            ];
            $tables_tips = '';
            $tables = $pdo->query("show tables")->fetchAll();
            foreach ($tables as $table) {
                $table = current($table);
                if ($overwrite == 1) {
                    $pdo->exec("DROP TABLE `$table`");
                } elseif ($overwrite == 2 && in_array($table, $tables_install)) {
                    $tables_tips .= '<p>数据表【'.$table.'】</p>';
                }
            }
            if ($tables_tips) {
                return $this->returnMsg("<p>数据库【{$dbname}】中以下表已经存在</p>". $tables_tips. "<p>如需覆盖请选择 覆盖重名表 或 清空全部表！</p>");
            }
        }
        /*--安装数据解析导入处理--*/
        $sql  = '';
        $flag = $comment = false;
        $data = file_get_contents(base_path() . '/plugin/install/data/install_data.sql');
        $data = explode("\n", trim(str_replace(["\r\n", "\r", '`vt_'], ["\n", "\n", '`'.$dbpre], $data)));
        foreach ($data as $line) {
            if ($line == '') {
                continue;
            }
            if (preg_match("/^(#|--)/", $line)) {
                continue;
            }
            if (preg_match("/^\/\*(.*?)\*\//", $line)) {
                continue;
            }
            if (substr($line, 0, 2) == '/*') {
                $comment = true;
                continue;
            }
            if (substr($line, -2) == '*/') {
                $comment = false;
                continue;
            }
            if ($comment) {
                continue;
            }
            if ($line == 'BEGIN;' || $line == 'COMMIT;') {
                continue;
            }
            $sql .= $line."\n";
            $tmp  = trim($sql);
            $exec = '';
            if($flag || preg_match('/DELIMITER;;$/', $tmp)){
                if(preg_match('/;;DELIMITER;$/', $tmp)){
                    $flag = false;
                    $sql = str_replace(['DELIMITER;;','DELIMITER;',';;'],['','',''], $sql);
                    //$pdo->exec("set global log_bin_trust_function_creators=1;");
                    $exec = $sql;
                    $sql = '';
                }else{
                    $flag = true;
                }
            }elseif(preg_match('/.*;$/', $tmp)){
                $exec = $sql;
                $sql = '';
            }
            if ($exec) {
                $pdo->exec(trim($exec));
                if($txt = strstr($exec,'COMMENT=')){
                    $txt = str_replace(['COMMENT=','\'',';',"\n"],'',$txt);
                }
            }
        }/*--END--*/

        // 更新管理员信息
        $passsalt  = random(8);
        $adminpass = set_password($adminpass,$passsalt);
        $pdo->exec("UPDATE {$dbpre}system_manager SET `username` ='{$adminuser}',`password`='{$adminpass}',`passsalt`='{$passsalt}' WHERE userid = 1");

        // 更新veitool配置
        $keys = getRSAKey();
        $keys['access_secret_key']  = md5(uniqid());
        $keys['refresh_secret_key'] = md5(uniqid());
        $keys['domain'] = request()->host();
        $veitool_str = getVeitool($keys);
        $fp = fopen(base_path() . '/config/veitool.php', 'w');
        fwrite($fp, $veitool_str);
        fclose($fp);

        // 获取.env模板内容
        $env_str = getEnvs();
        $env_str = str_replace('~db_host~', $dbhost, $env_str);
        $env_str = str_replace('~db_name~', $dbname, $env_str);
        $env_str = str_replace('~db_user~', $dbuser, $env_str);
        $env_str = str_replace('~db_pwd~',  $dbpwd, $env_str);
        $env_str = str_replace('~db_port~', $dbport, $env_str);
        $env_str = str_replace('~db_pre~', $dbpre, $env_str);
        // 写入.env配置文件
        $fp = fopen(base_path() . '/.env', 'w');
        fwrite($fp, $env_str);
        fclose($fp);

        // 写入锁定
        $fp = fopen(base_path() . '/plugin/install/install.lock', 'w');
        fwrite($fp, '程序已正确安装，重新安装请删除本文件');
        fclose($fp);

        vreload();
        /*尝试reload* / 
        if (function_exists('posix_kill')) {
            set_error_handler(function () {});
            posix_kill(posix_getppid(), SIGUSR1);
            restore_error_handler();
        }/**/

        // 返回信息
        return $this->returnMsg("安装成功！", 1);
    }

    /**
     * 数据库连接检测
     */
    public function check()
    {
        $dbhost = $this->request->get('dbhost','','trim');
        $dbport = $this->request->get('dbport','','trim');
        $dbuser = $this->request->get('dbuser','','trim');
        $dbpwd  = $this->request->get('dbpwd','','trim');
        try{
            getPDO($dbhost, $dbuser, $dbpwd, $dbport);
            return 'true';
        }catch(\Exception $e){
            return 'false';
        }
    }

}
