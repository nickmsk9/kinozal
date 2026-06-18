<?php


# IMPORTANT: Do not edit below unless you know what you are doing!
if (!defined('IN_ANNOUNCE'))
    die('Прямой вызов запрещён.');

require_once($rootpath . 'include/config.php');
require_once($rootpath . 'include/secrets.php');

if (!function_exists('get_magic_quotes_gpc')) {
    function get_magic_quotes_gpc() {
        return false;
    }
}

function err($msg) {
    benc_resp(array("failure reason" => array('type' => 'string', 'value' => $msg)));
    exit();
}

function benc_resp($d) {
    benc_resp_raw(benc(array('type' => 'dictionary', 'value' => $d)));
}

function benc_resp_raw($x) {
    header('Content-Type: text/plain');
    header('Pragma: no-cache');
    print($x);
}

function get_date_time($timestamp = 0) {
    if ($timestamp)
        return date('Y-m-d H:i:s', $timestamp); else
        return date('Y-m-d H:i:s');
}

function gmtime() {
    return strtotime(get_date_time());
}

function strip_magic_quotes($arr) {
    foreach ($arr as $k => $v) {
        if (is_array($v)) {
            $arr[$k] = strip_magic_quotes($v);
        } else {
            $arr[$k] = stripslashes($v);
        }
    }

    return $arr;
}

function mksize($bytes) {
    if ($bytes < 1000 * 1024)
        return number_format($bytes / 1024, 2) . ' kB'; elseif ($bytes < 1000 * 1048576)
        return number_format($bytes / 1048576, 2) . ' MB';
    elseif ($bytes < 1000 * 1073741824)
        return number_format($bytes / 1073741824, 2) . ' GB';
    else
        return number_format($bytes / 1099511627776, 2) . ' TB';
}

function emu_getallheaders() {
    foreach ($_SERVER as $name => $value)
        if (substr($name, 0, 5) == 'HTTP_')
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
    return $headers;
}

function portblacklisted($port) {
    if ($port >= 411 && $port <= 413)
        return true;
    if ($port >= 6881 && $port <= 6889)
        return true;
    if ($port == 1214)
        return true;
    if ($port >= 6346 && $port <= 6347)
        return true;
    if ($port == 4662)
        return true;
    if ($port == 6699)
        return true;
    return false;
}

function validip($ip) {
    if (!empty($ip) && $ip == long2ip(ip2long($ip))) {
        $reserved_ips = array(array('0.0.0.0', '2.255.255.255'), array('10.0.0.0', '10.255.255.255'), array('127.0.0.0', '127.255.255.255'), array('169.254.0.0', '169.254.255.255'), array('172.16.0.0', '172.31.255.255'), array('192.0.2.0', '192.0.2.255'), array('192.168.0.0', '192.168.255.255'), array('255.255.255.0', '255.255.255.255'));

        foreach ($reserved_ips as $r) {
            $min = ip2long($r[0]);
            $max = ip2long($r[1]);
            if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max))
                return false;
        }
        return true;
    } else return false;
}

function getip() {
    return $_SERVER['REMOTE_ADDR'];
}

function tracker_ip_version($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return 6;
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return 4;
    }
    return 0;
}

function dbconn($autoclean = false, $lightmode = false) {
    global $mysql_host, $mysql_user, $mysql_pass, $mysql_db, $mysql_charset, $announce_link;

    if ($announce_link instanceof mysqli) {
        return;
    }

    $announce_link = @mysqli_connect($mysql_host, $mysql_user, $mysql_pass, $mysql_db);
    if (!$announce_link) {
        err('dbconn: mysqli_connect: ' . mysqli_connect_error());
    }

    if (!mysqli_set_charset($announce_link, $mysql_charset)) {
        err('dbconn: mysqli_set_charset: ' . mysqli_error($announce_link));
    }

    register_shutdown_function(function () {
        global $announce_link;

        if ($announce_link instanceof mysqli) {
            mysqli_close($announce_link);
        }
    });
}

function sqlesc($value) {
    // Stripslashes
    /*if (get_magic_quotes_gpc()) {
        $value = stripslashes($value);
    }*/
    // Quote if not a number or a numeric string
    if (!is_numeric($value)) {
        $value = "'" . mysql_real_escape_string($value) . "'";
    }
    return $value;
}

if (!function_exists('mysql_query')) {
    function mysql_query($query) {
        global $announce_link;

        return mysqli_query($announce_link, $query);
    }
}

if (!function_exists('mysql_error')) {
    function mysql_error() {
        global $announce_link;

        return $announce_link instanceof mysqli ? mysqli_error($announce_link) : mysqli_connect_error();
    }
}

if (!function_exists('mysql_affected_rows')) {
    function mysql_affected_rows() {
        global $announce_link;

        return $announce_link instanceof mysqli ? mysqli_affected_rows($announce_link) : 0;
    }
}

if (!function_exists('mysql_fetch_assoc')) {
    function mysql_fetch_assoc($result) {
        return mysqli_fetch_assoc($result);
    }
}

if (!function_exists('mysql_fetch_array')) {
    function mysql_fetch_array($result) {
        return mysqli_fetch_array($result);
    }
}

if (!function_exists('mysql_fetch_row')) {
    function mysql_fetch_row($result) {
        return mysqli_fetch_row($result);
    }
}

if (!function_exists('mysql_real_escape_string')) {
    function mysql_real_escape_string($value) {
        global $announce_link;

        return mysqli_real_escape_string($announce_link, (string)$value);
    }
}

function hash_pad($hash) {
    return str_pad($hash, 20);
}

// Was used long long ago, now not needed.
// Disabled, but not deleted for quick restore in case of regression

/*function hash_where($name, $hash) {
    $shhash = preg_replace('/ *$/s', "", $hash);
    return "($name = " . sqlesc($hash) . " OR $name = " . sqlesc($shhash) . ")";
}*/

function unesc($x) {
    if (get_magic_quotes_gpc())
        return stripslashes($x);
    return $x;
}

function gzip() {
    if (@extension_loaded('zlib') && @ini_get('zlib.output_compression') != '1' && @ini_get('output_handler') != 'ob_gzhandler') {
        @ob_start('ob_gzhandler');
    }
}

function tracker_valid_passkey($passkey) {
    return (bool)preg_match('/^[A-Za-z0-9]{10}$/', (string)$passkey);
}

// Check open port, requires --enable-sockets
function check_port($host, $port, $timeout) {
    if (!tracker_ip_version($host)) {
        return false;
    }

    if (function_exists('socket_create')) {
        $family = tracker_ip_version($host) === 6 ? AF_INET6 : AF_INET;
        $socket = socket_create($family, SOCK_STREAM, SOL_TCP);
        if ($socket == false) {
            return false;
        }
        if (socket_set_nonblock($socket) == false) {
            socket_close($socket);
            return false;
        }

        @socket_connect($socket, $host, $port);

        switch (socket_select($r = array($socket), $w = array($socket), $f = array($socket), $timeout)) {
            case 2:
                $result = false;
                break;
            case 1:
                $error_code = socket_get_option($socket, SOL_SOCKET, SO_ERROR);
                $result = ($error_code === 0);
                break;
            case 0:
            default:
                $result = false;
                break;
        }

        socket_close($socket);
    } else {
        $target = tracker_ip_version($host) === 6 ? '[' . $host . ']' : $host;
        $socket = @fsockopen($target, $port, $errno, $errstr, $timeout);
        if (!$socket)
            $result = false; else {
            $result = true;
            @fclose($socket);
        }
    }

    return $result;
}

?>
