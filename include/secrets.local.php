<?php

// Автоопределение окружения:
// Docker обычно имеет переменную HOSTNAME и доступ к сервису "db"
// Локально используем localhost/root/без пароля

$is_docker = getenv('DOCKER_ENV') === '1' || file_exists('/.dockerenv');

if ($is_docker) {
    $mysql_host = "db";
    $mysql_user = "kinozal";
    $mysql_pass = "kinozal";
    $mysql_db = "kinozal";
} else {
    $mysql_host = "localhost";
    $mysql_user = "root";
    $mysql_pass = "";
    $mysql_db = "kinozal";
}

$mysql_charset = "utf8mb4";



/*$redis_host = "redis";
$redis_port = 6379;
$redis_timeout = 2.5;

$redis = new Redis();
$redis->connect($redis_host, $redis_port, $redis_timeout);*/
