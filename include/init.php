<?php


$debug_errors = in_array(strtolower((string)(getenv('KZ_DEBUG') ?: getenv('TRACKER_DEBUG') ?: '0')), array('1', 'true', 'yes', 'on'), true);
error_reporting(E_ALL);
ini_set('display_errors', $debug_errors ? '1' : '0');
ini_set('display_startup_errors', $debug_errors ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php_errors.log');

# IMPORTANT: Do not edit below unless you know what you are doing!
if(!defined('IN_TRACKER') && !defined('IN_ANNOUNCE'))
  die("Hacking attempt!");

$tracker_timezone = trim((string)(getenv('KZ_TIMEZONE') ?: getenv('TZ') ?: 'Europe/Moscow'));
if ($tracker_timezone === '' || !@date_default_timezone_set($tracker_timezone)) {
	date_default_timezone_set('Europe/Moscow');
}

if (!defined('TRACKER_TIMEZONE')) {
	define('TRACKER_TIMEZONE', date_default_timezone_get());
}

if (!function_exists('tracker_mysql_time_zone_offset')) {
	function tracker_mysql_time_zone_offset()
	{
		$offset = date('P');

		return preg_match('/^[+-]\d{2}:\d{2}$/', $offset) ? $offset : '+00:00';
	}

	function tracker_apply_mysql_timezone($connection)
	{
		if ($connection instanceof mysqli) {
			@mysqli_query($connection, "SET time_zone = '" . tracker_mysql_time_zone_offset() . "'");
		}
	}
}

if (!function_exists("htmlspecialchars_uni")) {
	function htmlspecialchars_uni($message) {
		$message = preg_replace("#&(?!\#[0-9]+;)#si", "&amp;", $message); // Fix & but allow unicode
		$message = str_replace("<","&lt;",$message);
		$message = str_replace(">","&gt;",$message);
		$message = str_replace("\"","&quot;",$message);
		$message = str_replace("  ", "&nbsp;&nbsp;", $message);
		return $message;
	}

    function html_uni($str) {
        return htmlspecialchars_uni($str);
    }
}

// DEFINE IMPORTANT CONSTANTS
define ('TIMENOW', time());
$configured_base_url = trim((string)(getenv('KZ_BASE_URL') ?: getenv('TRACKER_BASE_URL') ?: ''));
if ($configured_base_url !== '') {
	$DEFAULTBASEURL = rtrim($configured_base_url, '/');
} else {
	$php_self = (string)($_SERVER['PHP_SELF'] ?? '');
	$url = explode('/', htmlspecialchars_uni($php_self));
	array_pop($url);
	$server_port = (int)($_SERVER['SERVER_PORT'] ?? 80);
	$http_host = (string)($_SERVER['HTTP_HOST'] ?? getenv('HTTP_HOST') ?: 'localhost');
	$https = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
	$DEFAULTBASEURL = ($https || $server_port === 443 ? 'https://' : 'http://') . htmlspecialchars_uni($http_host) . implode('/', $url);
}
$BASEURL = $DEFAULTBASEURL;
$announce_urls = array();
$announce_url = trim((string)(getenv('KZ_ANNOUNCE_URL') ?: getenv('TRACKER_ANNOUNCE_URL') ?: ''));
$announce_urls[] = $announce_url !== '' ? $announce_url : "$DEFAULTBASEURL/announce.php";

// После смены этих двух параметров всем пользователям надо будет ввести логин пароль
define ('COOKIE_UID', 'uid'); // Имя куки для userid
define ('COOKIE_PASSHASH', 'pass'); // Имя куки для пароля

// DEFINE TRACKER GROUPS
define ("UC_USER", 0);
define ("UC_POWER_USER", 1);
define ("UC_HONOR_USER", 2);
define ("UC_VIP", 3);
define ("UC_UPLOADER", 4);
define ("UC_SENIOR_UPLOADER", 5);
define ("UC_MANAGER", 6);
define ("UC_MODERATOR", 7);
define ("UC_ADMINISTRATOR", 8);
define ("UC_SYSOP", 9);

?>
