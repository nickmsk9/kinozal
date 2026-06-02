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

if (!isset($cache_enabled)) {
    $cache_enabled = true;
}
if (!isset($cache_backend)) {
    $cache_backend = 'redis';
}
if (!isset($cache_prefix)) {
    $cache_prefix = 'kinozal';
}
if (!isset($redis_host)) {
    $redis_host = $is_docker ? 'redis' : '127.0.0.1';
}
if (!isset($redis_port)) {
    $redis_port = 6379;
}
if (!isset($redis_timeout)) {
    $redis_timeout = 0.25;
}
if (!isset($redis_database)) {
    $redis_database = 0;
}
if (!isset($redis_password)) {
    $redis_password = '';
}
