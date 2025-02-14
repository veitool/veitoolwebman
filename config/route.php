<?php
use Webman\Route;

/* 开启自定义登录入口 * /
Route::any('/admin123', function ($request) {
    $request->session()->set('IS_ADMIN','isok');
    return redirect('/admin');
});/**/
