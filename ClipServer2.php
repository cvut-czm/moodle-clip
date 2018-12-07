<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use clip\Handler;
define('CLI_SCRIPT', true);

require dirname(__DIR__) . '/clip/vendor/autoload.php';
require dirname(__FILE__) . '/vendor/autoload.php';
require dirname(__DIR__).'/../config.php';

$loop   = new \clip\StreamSelectLoopPlus();
$socket = new \React\Socket\Server( '0.0.0.0:88', $loop);
$server = new IoServer(
        new HttpServer(
                new WsServer(
                        new Handler(new \clip\auth\BasicAuth(__DIR__.'/authorized_users.json'),$loop,[
                                \moodle\clip\context\CoursesContext::class,
                                \moodle\clip\context\SemesterContext::class
                        ])
                )
        ),
        $socket,$loop
);


$server->run();