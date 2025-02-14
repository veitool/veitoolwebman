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
            'iswrite_array' => ['/.env'], // 检测是否可写的路径
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
        $dbhost = $this->request->get('dbhost','');
        $dbname = $this->request->get('dbname','');
        $dbpre  = $this->request->get('dbpre','vt_');
        $dbuser = $this->request->get('dbuser','');
        $dbpwd  = $this->request->get('dbpwd','');
        $dbport = $this->request->get('dbport','3306');
        $adminmap  = $this->request->get('adminmap','admin');
        $adminuser = $this->request->get('adminuser','admin');
        $adminpass = $this->request->get('adminpass','123456');
        // 连接证数据库
        try{
            $dsn = "mysql:host={$dbhost};port={$dbport};charset=utf8";
            $pdo = new \PDO($dsn, $dbuser, $dbpwd);
            $pdo->query("SET NAMES utf8"); // 设置数据库编码
        }catch(\Exception $e){
            return $this->returnMsg("数据库连接错误，请检查！");
        }
        // 查询数据库
        $res = $pdo->query('show Databases');
        // 遍历所有数据库，存入数组
        $dbnameArr = [];
        foreach($res->fetchAll(\PDO::FETCH_ASSOC) as $row){
            $dbnameArr[] = $row['Database'];
        }
        // 检查数据库是否存在，没有则创建数据库
        if(!in_array(trim($dbname), $dbnameArr)){
            if(!$pdo->exec("CREATE DATABASE `$dbname`")){
                return $this->returnMsg("创建数据库失败，请检查权限或联系管理员！");
            }
        }
        // 数据库创建完成，开始连接
        $pdo->query("USE `$dbname`");

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
        $dbhost = $this->request->get('dbhost','');
        $dbport = $this->request->get('dbport','');
        $dbuser = $this->request->get('dbuser','');
        $dbpwd  = $this->request->get('dbpwd','');
        try{
            $dsn = "mysql:host={$dbhost};port={$dbport};charset=utf8";
            new \PDO($dsn, $dbuser, $dbpwd);
            return 'true';
        }catch(\Exception $e){
            return 'false';
        }
    }

}
