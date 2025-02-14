<?php

return [
    // 支持通配符 'user.*' => [] 形式处理多个事件
    'user.register' => [
        [app\common\event\User::class, 'register'],
        // ...其它事件处理函数...
    ],
    'user.logout' => [
        [app\common\event\User::class, 'logout'],
        // ...其它事件处理函数...
    ]
];