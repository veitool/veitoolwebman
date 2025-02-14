<?php

namespace app\common\gateway;

use GatewayWorker\Lib\Gateway;

class Events
{
    public static function onWorkerStart($worker)
    {

    }

    public static function onConnect($client_id)
    {

    }

    public static function onWebSocketConnect($client_id, $data)
    {

    }

    public static function onMessage($client_id, $message)
    {
        Gateway::sendToClient($client_id, "receive message $message". $client_id);
    }

    public static function onClose($client_id)
    {

    }

}
