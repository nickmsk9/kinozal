<?php


require_once("include/bittorrent.php");
dbconn();
loggedinorreturn();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
	http_response_code(405);
	header('Allow: POST');
	exit('Method Not Allowed');
}

tracker_require_form_token('POST');

$theme = (string)($_POST["theme"] ?? '');
$resolved = theme_resolve_name($theme);
$stored = $resolved;

if (is_theme($resolved))
	sql_query("UPDATE users SET theme = ".sqlesc($stored)." WHERE id = {$CURUSER["id"]}") or sqlerr(__FILE__,__LINE__);

$returnto = tracker_safe_local_redirect($_POST['returnto'] ?? '', '/my.php');

header('Location: ' . $DEFAULTBASEURL . $returnto);

?>
