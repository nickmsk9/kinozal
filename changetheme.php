<?php


require_once("include/bittorrent.php");
dbconn();
loggedinorreturn();
tracker_require_form_token('GET');

$theme = (string)($_GET["theme"] ?? '');
$resolved = theme_resolve_name($theme);
$stored = $resolved;

if (is_theme($resolved))
	sql_query("UPDATE users SET theme = ".sqlesc($stored)." WHERE id = {$CURUSER["id"]}") or sqlerr(__FILE__,__LINE__);

$returnto = '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';

if ($referer !== '' && $host !== '') {
	$parts = parse_url($referer);
	if (!is_array($parts) || empty($parts['host']) || strcasecmp($parts['host'], $host) !== 0) {
		$returnto = '';
	} else {
		$path = (string)($parts['path'] ?? '/my.php');
		$query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
		$returnto = tracker_safe_local_redirect($path . $query, '/my.php');
	}
}

if ($returnto === '') {
	$returnto = '/my.php';
}

header('Location: ' . $DEFAULTBASEURL . $returnto);

?>
