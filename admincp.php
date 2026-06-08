<?php

require "include/bittorrent.php";
dbconn(false);
ob_start();
stdhead("Панель администратора");

define("ADMIN_FILE", 1);
$admin_file = "admincp";
include_once("admin/admin.php");

stdfoot();

?>