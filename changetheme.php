<?php


require_once("include/bittorrent.php");
dbconn();
loggedinorreturn();

$theme = (string) $_GET["theme"];
$resolved = theme_resolve_name($theme);
$stored = ($resolved === 'TBDev') ? 'Основная' : $theme;

if (is_theme($resolved))
	sql_query("UPDATE users SET theme = ".sqlesc($stored)." WHERE id = {$CURUSER["id"]}") or sqlerr(__FILE__,__LINE__);

header('Location: '.$DEFAULTBASEURL);

?>
