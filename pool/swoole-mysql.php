<?php

$dbms='mysql';
$dbName='tickets';
$user='buyf';
$pwd='123456';
$host='127.0.0.1';
$dsn="mysql:host=$host;port=3306;dbname=$dbName";


$server = new swoole_http_server("0.0.0.0",9502);
$server->set([
                 'worker_num' => 4 ,
             ]);
$server->on("request",function($request,$response) use ($dsn,$user,$pwd) {
    try
    {
        $pdo=new PDO($dsn,$user,$pwd);
        $query = "select * from sys_user";
        $result = $pdo->prepare($query);
        $result->execute();
        if ($res=$result->fetchAll(PDO::FETCH_ASSOC))
        {
            $response->end(json_encode($res));
        }
    }
    catch(Exception $e)
    {
        error_log($e->getMessage(),3,__DIR__.'/logs/swoole-myql.log');
    }

});
$server->start();