<?php
namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use app\common\DataEncryptor;

/**
 * Class StaticFile
 * @package app\middleware
 */
class StaticFile implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // Access to files beginning with. Is prohibited
        if (strpos($request->path(), '/.') !== false) {
            return response('<h1>403 forbidden</h1>', 403);
        }

        /* 全局引导安装 【安装后该段可以注释掉】 */ 
        if (!preg_match("/install|static/", $request->path()) && is_dir(base_path() . '/plugin/install') && !is_file(base_path() . '/plugin/install/install.lock')) {
            return redirect('/app/install');
        }/**/

        /* route.php 中开启自定义登录入口后 从登录入口进入的方可访问 admin 应用 * /
        if (strpos($request->path(), '/admin') !== false && session('IS_ADMIN') != 'isok') {
            return not_found();
        }/**/

        /* 前置解密处理 【采用比赋值符"="优先级低的 "or"】 */
        if(($data = $request->post('encrypt_data') or $data = $request->get('encrypt_data')) && ($key = $request->header('VeitoolAdminxKeySecret'))){
            try {
                $KeySecret = DataEncryptor::rsaDecrypt($key);
                $KeySecret = str_split($KeySecret, 32);
                $request->aes_key = $KeySecret[0];
                $request->aes_iv  = $KeySecret[1];
                // 其他地方 在拿到 Request 后可以进行加密 返回给终端 ['encrypt_data'=>DataEncryptor::aesEncrypt('你好，这是加密的原文', $this->request->aes_key, $this->request->aes_iv)]
                // 用 key & iv 解密数据 并 合并到对应数据集
                $data = DataEncryptor::aesDecrypt((string)$data, $request->aes_key, $request->aes_iv);
                if($request->method(true) === 'GET'){
                    $request->setGet(array_merge($request->get(), $data));
                }else{
                    $request->setPost(array_merge($request->post(), $data));
                }
            } catch (\Exception $e) {
                throw new \app\exception\ExitMsg("数据解密失败：{$e->getMessage()}", null, 500);
            }
        }/**/

        /** @var Response $response */
        $response = $next($request);
        // Add cross domain HTTP header
        /*$response->withHeaders([
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Credentials' => 'true',
        ]);*/

        return $response;
    }
}
