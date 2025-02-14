<?php
namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

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
