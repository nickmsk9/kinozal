<?php

require_once("include/bittorrent.php");
require_once("include/captcha.php");

dbconn(false);

@ini_set('display_errors', '0');

$id = isset($_GET['id']) ? (string)$_GET['id'] : '';
$builder = tracker_captcha_build_image($id);

header('Content-type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Disposition: inline; filename=captcha.png');
$builder->output(90);
exit;

?>
