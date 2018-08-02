<?php
$http = new swoole_http_server("0.0.0.0", 9505, SWOOLE_BASE);

$http->set([
               'worker_num' => 4,
           ]);
$http->on('request', function ($request, swoole_http_response $response) {
    $response->end("<h1>\nHello Swoole.\n</h1>");
});

$http->start();