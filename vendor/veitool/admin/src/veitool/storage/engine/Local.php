<?php
/**
 * ===========================================================================
 * Veitool 快捷开发框架系统
 * Author: Niaho 26843818@qq.com
 * Copyright (c)2019-2025 www.veitool.com All rights reserved.
 * Licensed: 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
 * ---------------------------------------------------------------------------
 */
namespace veitool\storage\engine;

/**
 * 本地文件驱动
 * @package app\common\library\storage\drivers
 */
class Local extends Server
{
    private $config;

    private $fileDir = '';

    /**
     * 构造方法
     * @param $config
     */
    public function __construct($config)
    {
        parent::__construct();
        $this->config = $config;
    }

    /**
     * 上传图片文件
     * @return array|bool
     */
    public function upload()
    {
        $type = $this->config['type'];
        $type = isset($this->config[$type]) && is_array($this->config[$type]) ? $type : 'image';
        $ext  = $this->config[$type]['ext'];
        $size = $this->config[$type]['size'];
        $thum = $this->config['thum'];
        $pre  = $this->config['pre'] ?? '';

        //文件大小限制
        if ($this->fileInfo['size'] > $size * 1024 * 1024){
            $this->error = '上传的文件大小不能超过'.$size.'M';
            return false;
        }

        //文件类型限制
        if (!in_array($this->fileInfo['ext'], explode(',', $ext))){
            $this->error = '请上传后缀为:'.$ext.'的文件';
            return false;
        }

        //设置路径及存储
        $this->fileName = str_replace('\\', '/', $type . VT_DS . $pre . date('Ymd') . VT_DS . uniqid().($thum ? '_b' : '') .'.'. $this->fileInfo['ext']);
        $this->fileDir  = trim(public_path(),'\\'). $this->config['domain']. VT_DS . $this->fileName;
        $this->file->move($this->fileDir);

        //安全检测
        if($this->checkHex()){
            if($thum && $type=='image'){ // 生成缩略图
                $arr = explode('|', $thum);
                $w = intval($arr[0]);
                $h = isset($arr[1]) ? intval($arr[1]) : 0;
                $w = $w>0 ? $w : 150;
                $h = $h>0 ? $h : 150;
                $timg  = $this->fileDir;
                $image = \think\Image::open($timg);
                $timg  = str_replace('_b.', '_x.', $timg);
                $image->thumb($w, $h)->save($timg);
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * 16进制文件安全检测
     */
    public function checkHex(){
        if(file_exists($this->fileDir)){
            $resource = fopen($this->fileDir, 'rb');
            $fileSize = filesize($this->fileDir);
            fseek($resource, 0);
            if($fileSize > 512){
                $hexCode = bin2hex(fread($resource, 512));
                fseek($resource, $fileSize - 512);
                $hexCode .= bin2hex(fread($resource, 512));
            }else{
                $hexCode = bin2hex(fread($resource, $fileSize));
            }
            fclose($resource);
            /* 整个类检测木马脚本的核心  通过匹配十六进制代码检测是否存在木马脚本 匹配16进制中的 <% ( ) %> 、<? ( ) ?> 、<script | /script> 大小写亦可 */
            if(preg_match("/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is", $hexCode)){
                @unlink($this->fileDir);
                return false;
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除文件
     * @param  $fileName
     * @return bool|mixed
     */
    public function delete($fileName)
    {
        // 文件所在目录
        $filePath = VT_PUBLIC . "uploads/{$fileName}";
        return !file_exists($filePath) ?: unlink($filePath);
    }

    /**
     * 返回文件路径
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

}