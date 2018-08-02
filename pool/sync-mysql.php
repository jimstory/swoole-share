<?php
$dbName='tickets';
$user='buyf';
$pwd='123456';
$host='127.0.0.1';
$dsn="mysql:host=$host;port=3306;dbname=$dbName";
$pdo=new PDO($dsn,$user,$pwd);
$query = "select * from sys_user";
$result = $pdo->prepare($query);
$result->execute();
if ($res=$result->fetchAll(PDO::FETCH_ASSOC))
{
   echo  json_encode($res);
}