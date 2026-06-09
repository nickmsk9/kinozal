<?php


require_once("include/bittorrent.php");
dbconn();
loggedinorreturn();

$theme = (string) $_GET["theme"];
$resolved = theme_resolve_name($theme);
$stored = $resolved;

if (is_theme($resolved))
	sql_query("UPDATE users SET theme = ".sqlesc($stored)." WHERE id = {$CURUSER["id"]}") or sqlerr(__FILE__,__LINE__);

$returnto = $_SERVER['HTTP_REFERER'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';

if ($returnto !== '' && $host !== '') {
	$parts = parse_url($returnto);
	if (!is_array($parts) || empty($parts['host']) || strcasecmp($parts['host'], $host) !== 0) {
		$returnto = '';
	}
}

if ($returnto === '') {
	$returnto = $DEFAULTBASEURL . '/my.php';
}

header('Location: ' . $returnto);

?>
