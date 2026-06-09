<?php


# IMPORTANT: Do not edit below unless you know what you are doing!
if(!defined('IN_TRACKER'))
 die('Прямой вызов запрещён.');

require_once($rootpath . 'include/functions_global.php');
require_once($rootpath . 'include/functions_torrenttable.php');
require_once($rootpath . 'include/functions_commenttable.php');

function check_port($host, $port, $timeout, $force_fsock = false) {
    // Валидация входных параметров
    if (empty($host) || $port < 1 || $port > 65535 || $timeout <= 0) {
        return false;
    }

    // Принудительное использование fsockopen (резервный метод)
    if ($force_fsock || !function_exists('socket_create')) {
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($socket === false) {
            return false;
        }
        fclose($socket);
        return true;
    }

    // Расширенный метод через сокеты (поддержка дробного таймаута и точной диагностики)
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        return false;
    }

    // Неблокирующий режим для асинхронного соединения
    if (!socket_set_nonblock($socket)) {
        socket_close($socket);
        return false;
    }

    // Асинхронное подключение (ошибка EINPROGRESS — штатная ситуация)
    @socket_connect($socket, $host, $port);
    $lastError = socket_last_error($socket);
    if ($lastError !== SOCKET_EINPROGRESS && $lastError !== SOCKET_EALREADY && $lastError !== 0) {
        socket_close($socket);
        return false;
    }

    // Подготовка к socket_select (таймаут с поддержкой дробных секунд)
    $sec  = (int)$timeout;
    $usec = (int)(($timeout - $sec) * 1000000);
    $write = [$socket];
    $except = [$socket];
    $read = [];

    $result = false;
    $status = socket_select($read, $write, $except, $sec, $usec);

    if ($status === 1 && !empty($write)) {
        // Соединение установлено – проверим, нет ли скрытой ошибки
        $error_code = socket_get_option($socket, SOL_SOCKET, SO_ERROR);
        $result = ($error_code === 0);
    } else {
        // Таймаут ($status === 0) или ошибка ($status === false, 2 и т.д.)
        $result = false;
    }

    socket_close($socket);
    return $result;
}

function is_theme($theme = "") {
	global $rootpath;
	return file_exists($rootpath . "themes/$theme/stdhead.php") && file_exists($rootpath . "themes/$theme/stdfoot.php") && file_exists($rootpath . "themes/$theme/template.php");
}

function theme_resolve_name($theme = "") {
	$theme = trim((string)$theme);
	$lower = function_exists('mb_strtolower') ? mb_strtolower($theme, 'UTF-8') : strtolower($theme);

	if ($lower === 'tbdev' || $lower === 'основная') {
		return 'TBDev';
	}
	if ($lower === 'winter' || $lower === 'зимний') {
		return 'Winter';
	}
	if ($lower === 'tbdev2030' || $lower === 'tbdev 2030 (экспериментальная)') {
		return 'TBDev2030';
	}

	return $theme;
}

function theme_display_name($theme = "") {
	$resolved = theme_resolve_name($theme);
	if ($resolved === 'TBDev') {
		return 'Основная';
	}
	if ($resolved === 'Winter') {
		return 'Зимний';
	}
	if ($resolved === 'TBDev2030') {
		return 'TBDev 2030 (экспериментальная)';
	}
	return $theme;
}

function get_themes() {
	global $rootpath;
	$handle = opendir($rootpath . "themes");
	$themelist = array();
	while ($file = readdir($handle)) {
		if (is_theme($file) && $file != "." && $file != "..") {
			$themelist[] = $file;
		}
	}
	closedir($handle);
	sort($themelist);
	return $themelist;
}

function select_theme() {
	global $CURUSER, $default_theme;
	if ($CURUSER)
		$theme = $CURUSER["theme"];
	else
		$theme = $default_theme;
	$theme = theme_resolve_name($theme);
	$default = theme_resolve_name($default_theme);
	if (!is_theme($theme))
		$theme = $default;
	return $theme;
}

function sql_query($query)
{
    global $link, $queries, $query_stat, $querytime;

    if (!$link instanceof mysqli) {
        die('sql_query: нет подключения к базе данных');
    }

    $queries = isset($queries) ? (int)$queries + 1 : 1;

    $query_start_time = timer();

    $result = mysqli_query($link, $query);

    $query_end_time = timer();
    $query_time = $query_end_time - $query_start_time;

    $querytime = isset($querytime) ? (float)$querytime + $query_time : $query_time;

    /*
     * Файл и строку собираем только в debug-режиме,
     * чтобы не грузить сайт лишним backtrace на production.
     */
    $debug_enabled = (
        (defined('DEBUG_MODE') && DEBUG_MODE)
        || isset($_GET['yuna'])
    );

    $debug_file = '';
    $debug_line = '';

    if ($debug_enabled) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);

        foreach ($trace as $trace_item) {
            if (empty($trace_item['file'])) {
                continue;
            }

            /*
             * Пропускаем сам файл с sql_query(), чтобы найти реальный вызов:
             * details.php, browse.php, index.php и т.д.
             */
            if ($trace_item['file'] === __FILE__) {
                continue;
            }

            $debug_file = (string)$trace_item['file'];
            $debug_line = isset($trace_item['line']) ? (int)$trace_item['line'] : 0;
            break;
        }

        if ($debug_file !== '' && defined('ROOT_PATH')) {
            $debug_file = str_replace(ROOT_PATH, '', $debug_file);
        }
    }

    $query_stat[] = array(
        'seconds' => $query_time,
        'query'   => $query,
        'file'    => $debug_file,
        'line'    => $debug_line,
    );

    if ($result === false) {
        $error_file = $debug_file !== '' ? $debug_file : 'не определено';
        $error_line = $debug_line > 0 ? $debug_line : 'не определено';

        die(
            'sql_query: ошибка MySQL [' .
            mysqli_errno($link) .
            ']: ' .
            htmlspecialchars(mysqli_error($link), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            '<br><br>Файл: <b>' .
            htmlspecialchars((string)$error_file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            '</b>' .
            '<br>Строка: <b>' .
            htmlspecialchars((string)$error_line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            '</b>' .
            '<br><br>Запрос:<br><pre>' .
            htmlspecialchars($query, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            '</pre>'
        );
    }

    if (function_exists('tracker_cache_invalidate_for_query')) {
        tracker_cache_invalidate_for_query($query);
    }

    return $result;
}
function dbconn($autoclean = false, $lightmode = false)
{
    global $mysql_host, $mysql_user, $mysql_pass, $mysql_db, $mysql_charset, $link;

    $link = mysqli_connect($mysql_host, $mysql_user, $mysql_pass);

    if (!$link) {
        die("[" . mysqli_connect_errno() . "] dbconn: mysqli_connect: " . mysqli_connect_error());
    }

    if (!mysqli_select_db($link, $mysql_db)) {
        die("[" . mysqli_errno($link) . "] dbconn: mysqli_select_db: " . mysqli_error($link));
    }

    if (!mysqli_set_charset($link, $mysql_charset)) {
        die("[" . mysqli_errno($link) . "] dbconn: mysqli_set_charset: " . mysqli_error($link));
    }

    if (function_exists('site_settings_apply_runtime_overrides')) {
        site_settings_apply_runtime_overrides();
    }

    if (function_exists('tracker_upgrade_legacy_passkeys')) {
        tracker_upgrade_legacy_passkeys();
    }

    userlogin($lightmode);

    if (basename($_SERVER['SCRIPT_FILENAME']) == 'index.php') {
        register_shutdown_function("autoclean");
    }

    register_shutdown_function(function () {
        global $link;

        if ($link instanceof mysqli) {
            mysqli_close($link);
        }
    });
}
function userlogin($lightmode = false): void
{
    global $SITE_ONLINE, $default_language, $tracker_lang, $use_lang, $use_ipbans, $_COOKIE_SALT;

    unset($GLOBALS['CURUSER']);

    if (empty($_COOKIE_SALT)) {
        $_COOKIE_SALT = 'tracker-cookie-salt';
    }

    $ip = getip();
    $nip = ip2long($ip);

    /*
     * Проверка IP-бана.
     * Старый вариант падал на mysql_num_rows().
     * Новый вариант берёт только одну запись, а не SELECT *.
     */
    if ($use_ipbans && !$lightmode && $nip !== false) {
        $nip = sprintf('%u', $nip);

        $res = sql_query("SELECT comment FROM bans WHERE {$nip} >= first AND {$nip} <= last LIMIT 1") or sqlerr(__FILE__, __LINE__);
        $ban = mysqli_fetch_assoc($res);

        if ($ban) {
            header('HTTP/1.0 403 Forbidden');
            print("<html><body><h1>403 Forbidden</h1>Unauthorized IP address.</body></html>\n");
            die;
        }
    }

    $c_uid  = $_COOKIE[COOKIE_UID] ?? '';
    $c_pass = $_COOKIE[COOKIE_PASSHASH] ?? '';

    if (!$SITE_ONLINE || $c_uid === '' || $c_pass === '') {
        if ($use_lang) {
            include_once('languages/lang_' . $default_language . '/lang_main.php');
        }

        if (!$lightmode) {
            user_session();
        }
        return;
    }

    $id = (int) $c_uid;

    if ($id <= 0 || strlen((string) $c_pass) !== 32) {
        if ($use_lang) {
            include_once('languages/lang_' . $default_language . '/lang_main.php');
        }

        if (!$lightmode) {
            user_session();
        }
        return;
    }

    /*
     * Не SELECT *.
     * Берём только поля, которые реально нужны этой функции и обычно нужны дальше в CURUSER.
     * Если в проекте где-то ожидаются дополнительные поля из CURUSER, можно вернуть SELECT *.
     */
    $res = sql_query("
        SELECT *
        FROM users
        WHERE id = {$id}
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);

    $row = mysqli_fetch_assoc($res);

    if (!$row) {
        if ($use_lang) {
            include_once('languages/lang_' . $default_language . '/lang_main.php');
        }

        if (!$lightmode) {
            user_session();
        }
        return;
    }

    /*
     * Старый код делал subnet только для IPv4.
     * Для IPv6 оставляем полный IP, чтобы не ломать авторизацию.
     */
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $subnet = explode('.', $ip);
        $subnet[2] = 0;
        $subnet[3] = 0;
        $subnet = implode('.', $subnet);
    } else {
        $subnet = $ip;
    }

    $expectedPass = md5($row['passhash'] . COOKIE_SALT . $subnet);

    if (!hash_equals($expectedPass, (string) $c_pass)) {
        if ($use_lang) {
            include_once('languages/lang_' . $default_language . '/lang_main.php');
        }

        if (!$lightmode) {
            user_session();
        }
        return;
    }

    $updateset = array();

    if (!$lightmode && $ip !== ($row['ip'] ?? '')) {
        $updateset[] = 'ip = ' . sqlesc($ip);
        $row['ip'] = $ip;
    }

    $last_access_ts = !empty($row['last_access']) ? strtotime((string)$row['last_access']) : 0;
    if (!$lightmode && (!$last_access_ts || $last_access_ts < (TIMENOW - 60))) {
        $updateset[] = 'last_access = ' . sqlesc(get_date_time());
    }

    if (!empty($updateset)) {
        sql_query('UPDATE users SET ' . implode(', ', $updateset) . ' WHERE id = ' . (int) $row['id']) or sqlerr(__FILE__, __LINE__);
    }

    if ((int) $row['override_class'] < (int) $row['class']) {
        $row['class'] = $row['override_class'];
    }

    $GLOBALS['CURUSER'] = $row;

    if ($use_lang) {
        $language = !empty($row['language']) ? $row['language'] : $default_language;
        include_once('languages/lang_' . $language . '/lang_main.php');
    }

    if (($row['enabled'] ?? 'yes') === 'no') {
        $GLOBALS['use_blocks'] = 0;

        $banRes = sql_query('
            SELECT reason, disuntil
            FROM users_ban
            WHERE userid = ' . (int) $row['id'] . '
            LIMIT 1
        ') or sqlerr(__FILE__, __LINE__);

        $banRow = mysqli_fetch_assoc($banRes);

        $reason = $banRow['reason'] ?? '';
        $disuntil = $banRow['disuntil'] ?? null;

        $banUntilText = (!empty($disuntil) && $disuntil !== '0000-00-00 00:00:00')
            ? '<br />Дата снятия бана: ' . htmlspecialchars($disuntil, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : '<br />Дата снятия бана: никогда';

        stderr(
            $tracker_lang['error'] ?? 'Ошибка',
            'Вы забанены на трекере.' .
            $banUntilText .
            '<br />Причина: ' . htmlspecialchars((string) $reason, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    if (!$lightmode) {
        user_session();
    }
}

function user_session()
{
	global $CURUSER, $use_sessions;

	if (empty($use_sessions)) {
		return;
	}

	$ip = getip();

	// REQUEST_URI может отсутствовать, getenv() медленнее и менее предсказуем в PHP 8+
	$url = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';

	// Ограничиваем URL до 150 символов, чтобы не раздувать таблицу sessions
	if ($url !== '') {
		if (function_exists('mb_strlen') && function_exists('mb_substr')) {
			if (mb_strlen($url, 'UTF-8') > 150) {
				$url = mb_substr($url, 0, 150, 'UTF-8');
			}
		} elseif (strlen($url) > 150) {
			$url = substr($url, 0, 150);
		}
	}

	if (empty($CURUSER) || !is_array($CURUSER)) {
		$uid = -1;
		$username = '';
		$class = -1;
	} else {
		$uid = isset($CURUSER['id']) ? (int)$CURUSER['id'] : -1;
		$username = isset($CURUSER['username']) ? (string)$CURUSER['username'] : '';
		$class = isset($CURUSER['class']) ? (int)$CURUSER['class'] : -1;
	}

	$sid = session_id();
	$ctime = time();
	$agent = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';

	// Чтобы useragent не раздувал запрос и не бил по полю в БД
	if ($agent !== '') {
		if (function_exists('mb_strlen') && function_exists('mb_substr')) {
			if (mb_strlen($agent, 'UTF-8') > 255) {
				$agent = mb_substr($agent, 0, 255, 'UTF-8');
			}
		} elseif (strlen($agent) > 255) {
			$agent = substr($agent, 0, 255);
		}
	}

	if ($sid === '') {
		return;
	}

	session_write_close();

	$sql = "
		INSERT INTO sessions 
			(sid, uid, username, class, ip, time, url, useragent)
		VALUES 
			(" . sqlesc($sid) . ",
			 " . sqlesc($uid) . ",
			 " . sqlesc($username) . ",
			 " . sqlesc($class) . ",
			 " . sqlesc($ip) . ",
			 " . sqlesc($ctime) . ",
			 " . sqlesc($url) . ",
			 " . sqlesc($agent) . ")
		ON DUPLICATE KEY UPDATE
			uid = VALUES(uid),
			username = VALUES(username),
			class = VALUES(class),
			ip = VALUES(ip),
			time = VALUES(time),
			url = VALUES(url),
			useragent = VALUES(useragent)
	";

	sql_query($sql) or sqlerr(__FILE__, __LINE__);
}

function unesc($x)
{
    if (is_array($x)) {
        return array_map('unesc', $x);
    }

    if ($x === null) {
        return '';
    }

    return (string)$x;
}

function gzip(): void
{
    global $use_gzip;

    static $alreadyLoaded = false;

    // Предотвращаем повторный запуск
    if ($alreadyLoaded) {
        return;
    }
    $alreadyLoaded = true;

    // Если заголовки уже отправлены, включать буферизацию поздно
    if (headers_sent()) {
        return;
    }

    // Разрешено ли сжатие в настройках трекера?
    $useGzip = !empty($use_gzip);

    // Проверка наличия библиотеки zlib
    $zlibLoaded = extension_loaded('zlib');

    // Включено ли сжатие на уровне сервера (php.ini)
    $zlibIni = ini_get('zlib.output_compression');
    $zlibOutputCompression = !empty($zlibIni) && $zlibIni !== 'off';

    // Текущий обработчик вывода (например, ob_gzhandler)
    $outputHandler = strtolower((string) ini_get('output_handler'));

    // Активные обработчики в стеке буферов вывода
    $handlersList = ob_list_handlers();
    $alreadyCompressed = (
        $zlibOutputCompression ||
        $outputHandler === 'ob_gzhandler' ||
        in_array('ob_gzhandler', $handlersList, true)
    );

    // Включаем сжатие, только если:
    // - трекер разрешил,
    // - zlib доступна,
    // - сжатие ещё не активно,
    // - функция ob_gzhandler существует.
    if ($useGzip && $zlibLoaded && !$alreadyCompressed && function_exists('ob_gzhandler')) {
        // Запускаем буфер вывода с обработчиком gzip
        ob_start('ob_gzhandler');
        return;
    }

    // Иначе просто включаем буферизацию без сжатия (для совместимости)
    ob_start();
}

function validip($ip) {
    // Базовая проверка формата (корректный IPv4)
    if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }

    // Резервные диапазоны IANA (IPv4)
    $reserved_ranges = [
        ['0.0.0.0',    '2.255.255.255'],   // 0.0.0.0/8, 1.0.0.0/8? на самом деле 0/8 и 1/8? IANA: 0.0.0.0/8, 1.0.0.0/8? нет, 1.0.0.0/8 выделен. Но оставим как в оригинале.
        ['10.0.0.0',   '10.255.255.255'],  // 10.0.0.0/8
        ['127.0.0.0',  '127.255.255.255'], // 127.0.0.0/8
        ['169.254.0.0','169.254.255.255'], // 169.254.0.0/16
        ['172.16.0.0', '172.31.255.255'],  // 172.16.0.0/12
        ['192.0.2.0',  '192.0.2.255'],     // 192.0.2.0/24 (TEST-NET)
        ['192.168.0.0','192.168.255.255'], // 192.168.0.0/16
        ['255.255.255.0','255.255.255.255'] // 255.255.255.0/24? Это широковещательный? Оригинал так и оставим.
    ];

    $ipLong = ip2long($ip);
    foreach ($reserved_ranges as $range) {
        $min = ip2long($range[0]);
        $max = ip2long($range[1]);
        if ($ipLong >= $min && $ipLong <= $max) {
            return false;
        }
    }

    return true;
}

function getip($trust_proxy_headers = false) {
    $ip = null;

    // Опциональное доверие заголовкам прокси (только если явно включено)
    if ($trust_proxy_headers) {
        // Проверяем X-Forwarded-For (стандарт для прозрачных прокси)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && validip($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        // Альтернативный заголовок Client-Ip (используется некоторыми прокси)
        elseif (!empty($_SERVER['HTTP_CLIENT_IP']) && validip($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
    }

    // Если заголовки не дали результат или доверие отключено — берём реальный IP подключения
    if (empty($ip)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? getenv('REMOTE_ADDR');
        if (empty($ip) || !validip($ip)) {
            $ip = '0.0.0.0'; // безопасное значение по умолчанию
        }
    }

    return $ip;
}

function autoclean() {
	global $autoclean_interval, $rootpath, $link;

	$now = time();
	$docleanup = 0;

	$res = sql_query("SELECT value_u FROM avps WHERE arg = 'lastcleantime'");
	$row = mysqli_fetch_array($res);
	if (!$row) {
		sql_query("INSERT INTO avps (arg, value_u, value_s) VALUES ('lastcleantime',$now,'')");
		return;
	}
	$ts = $row[0];
	if ($ts + $autoclean_interval > $now)
		return;
	if ($ts > $now) { // Fuck, someone has set time in future!
		sql_query("UPDATE avps SET value_u=$now WHERE arg='lastcleantime' AND value_u = $ts");
		return;
	}
	sql_query("UPDATE avps SET value_u=$now WHERE arg='lastcleantime' AND value_u = $ts");
	if (!mysqli_affected_rows($link))
		return;

	require_once($rootpath . 'include/cleanup.php');

	docleanup();
}

function mksize($bytes) {
    // Обработка отрицательных и нулевых значений
    if ($bytes <= 0) {
        return '0 kB';
    }

    // Множители (1024 в разных степенях)
    $kb = 1024;
    $mb = $kb * 1024;       // 1 048 576
    $gb = $mb * 1024;       // 1 073 741 824
    $tb = $gb * 1024;       // 1 099 511 627 776

    if ($bytes < 1000 * $kb) {           // < 1 024 000 байт (оригинал: 1000 * 1024)
        return number_format($bytes / $kb, 2) . ' kB';
    } elseif ($bytes < 1000 * $mb) {     // < 1 048 576 000 байт
        return number_format($bytes / $mb, 2) . ' MB';
    } elseif ($bytes < 1000 * $gb) {     // < 1 073 741 824 000 байт
        return number_format($bytes / $gb, 2) . ' GB';
    } else {
        return number_format($bytes / $tb, 2) . ' TB';
    }
}

function deadtime() {
    global $announce_interval;

    // Если интервал не задан или некорректен — возвращаем текущее время
    if (!isset($announce_interval) || !is_numeric($announce_interval) || $announce_interval <= 0) {
        return time();
    }

    // Оригинальная формула: вычитаем 130% от announce_interval
    return time() - (int)floor($announce_interval * 1.3);
}

function validfilename($name) {
    return preg_match('/^[^\0-\x1f:\\\\\/?*\xff#<>|]+$/si', $name) === 1;
}

function validemail($email) {
	 $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function mail_possible($email) {
    // Извлекаем домен после @
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return false; // Неверный email
    }
    $domain = $parts[1];

    if (function_exists('checkdnsrr')) {
        return checkdnsrr($domain, 'MX') === true;
    }
    return true; // Если проверка недоступна, считаем, что почта возможна
}

function send_pm($sender, $receiver, $added, $subject, $msg) {
    // Экранируем все параметры через sqlesc (предполагается, что функция определена)
    $values = array_map('sqlesc', array($sender, $receiver, $added, $subject, $msg));
    $query = 'INSERT INTO messages (sender, receiver, added, subject, msg) VALUES (' . implode(', ', $values) . ')';
    sql_query($query) or sqlerr(__FILE__, __LINE__);
}

function sent_mail($to, $fromname, $fromemail, $subject, $body, $multiple = false, $multiplemail = '') {
    if (stripos(PHP_OS, 'WIN') === 0) {
        $eol = "\r\n";
    } elseif (stripos(PHP_OS, 'MAC') === 0) {
        $eol = "\r";
    } else {
        $eol = "\n";
    }

    $serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
    $mid = md5(getip() . (string)$fromname . microtime(true));

    $headers = "From: $fromname <$fromemail>" . $eol;
    $headers .= "Reply-To: $fromname <$fromemail>" . $eol;
    $headers .= "Return-Path: $fromname <$fromemail>" . $eol;
    $headers .= "Message-ID: <$mid.thesystem@$serverName>" . $eol;
    $headers .= "X-Mailer: PHP v" . phpversion() . $eol;
    $headers .= "MIME-Version: 1.0" . $eol;
    $headers .= "Content-type: text/plain; charset=utf-8" . $eol;
    $headers .= "X-Sender: PHP" . $eol;

    if ($multiple && trim((string)$multiplemail) !== '') {
        $headers .= "Bcc: $multiplemail" . $eol;
    }

    return @mail($to, $subject, $body, $headers);
}

function sqlesc($value, $force = false)
{
    global $link;

    if (!$link instanceof mysqli) {
        die('sqlesc: нет активного подключения к базе данных');
    }

    // NULL должен уходить в SQL как NULL, а не как пустая строка
    if ($value === null) {
        return 'NULL';
    }

    // Числа не оборачиваем в кавычки, если явно не заставили
    if (!$force && is_numeric($value)) {
        return $value;
    }

    return "'" . mysqli_real_escape_string($link, (string) $value) . "'";
}

function sqlwildcardesc($x) {
	global $link;

	if (!$link instanceof mysqli) {
		die('sqlwildcardesc: нет активного подключения к базе данных');
	}

	return str_replace(array("%","_"), array("\\%","\\_"), mysqli_real_escape_string($link, (string)$x));
}

function stdhead($title = "", $msgalert = true)
{
	global $CURUSER, $SITE_ONLINE, $SITENAME, $ss_uri, $tracker_lang, $hide_right_blocks;

	if (!$SITE_ONLINE) {
		die('Сайт временно закрыт на техническое обслуживание. Пожалуйста, зайдите позже.<br />');
	}

	$charset = isset($tracker_lang['language_charset'])
		? $tracker_lang['language_charset']
		: 'UTF-8';

	header('Content-Type: text/html; charset=' . $charset);
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');

	$title = trim((string)$title);

	if ($title === '') {
		$title = $SITENAME;
	} else {
		$title = $SITENAME . ' :: ' . htmlspecialchars_uni($title);
	}

	$ss_uri = select_theme();

	require_once 'themes/' . $ss_uri . '/template.php';
	require_once 'themes/' . $ss_uri . '/stdhead.php';
}

function stdfoot() {
	global $CURUSER, $ss_uri, $tracker_lang, $queries, $tstart, $query_stat, $querytime;

	if (!is_theme($ss_uri) || empty($ss_uri)) {
		$ss_uri = select_theme();
	}

	require_once('themes/' . $ss_uri . '/template.php');
	require_once('themes/' . $ss_uri . '/stdfoot.php');

	if ((DEBUG_MODE || isset($_GET['yuna'])) && !empty($query_stat) && is_array($query_stat)) {
		$total_time = 0.0;
		$slow_count = 0;

		foreach ($query_stat as $value) {
			$seconds = isset($value['seconds']) ? (float)$value['seconds'] : 0.0;
			$total_time += $seconds;

			if ($seconds > 0.01) {
				$slow_count++;
			}
		}

		print('<br />');
		print('<table class="tables1" width="100%" cellspacing="0" cellpadding="5">');

		print('<tr>');
		print('<td class="colhead" colspan="5" align="center">Debug SQL-запросов</td>');
		print('</tr>');

		print('<tr>');
		print('<td class="tables2" colspan="5" align="center">');
		print('<span class="small">');
		print('Всего запросов: <b>' . (int)count($query_stat) . '</b> &nbsp; | &nbsp; ');
		print('Общее время SQL: <b>' . htmlspecialchars_uni(number_format($total_time, 5, '.', '')) . ' сек.</b> &nbsp; | &nbsp; ');

		if ($slow_count > 0) {
			print('Медленных запросов: <b><font color="red">' . (int)$slow_count . '</font></b>');
		} else {
			print('Медленных запросов: <b><font color="green">0</font></b>');
		}

		print('</span>');
		print('</td>');
		print('</tr>');

		print('<tr>');
		print('<td class="tables2" align="center" width="40"><b>#</b></td>');
		print('<td class="tables2" align="center" width="90"><b>Время</b></td>');
		print('<td class="tables2" align="center" width="120"><b>Статус</b></td>');
		print('<td class="tables2" align="center" width="220"><b>Файл / строка</b></td>');
		print('<td class="tables2" align="center"><b>SQL-запрос</b></td>');
		print('</tr>');

		foreach ($query_stat as $key => $value) {
			$seconds = isset($value['seconds']) ? (float)$value['seconds'] : 0.0;
			$query = isset($value['query']) ? (string)$value['query'] : '';

			$file = '';
			$line = '';

			if (!empty($value['file'])) {
				$file = (string)$value['file'];
			} elseif (!empty($value['src'])) {
				$file = (string)$value['src'];
			} elseif (!empty($value['source'])) {
				$file = (string)$value['source'];
			}

			if (!empty($value['line'])) {
				$line = (string)$value['line'];
			}

			/*
			 * Если в query_stat пока нет file/line,
			 * попробуем аккуратно определить место вызова через backtrace,
			 * но только для вывода debug и без тяжёлой глубокой трассировки.
			 */
			if ($file === '' && isset($value['trace']) && is_array($value['trace'])) {
				foreach ($value['trace'] as $trace_item) {
					if (!empty($trace_item['file'])) {
						$file = (string)$trace_item['file'];
						$line = !empty($trace_item['line']) ? (string)$trace_item['line'] : '';
						break;
					}
				}
			}

			if ($file !== '') {
				$file = str_replace(ROOT_PATH, '', $file);
			}

			$is_slow = ($seconds > 0.01);
			$is_warning = ($seconds > 0.005 && $seconds <= 0.01);

			if ($is_slow) {
				$time_html = '<font color="red" title="Медленный SQL-запрос. Рекомендуется проверить индекс, WHERE, JOIN, ORDER BY или LIMIT.">' . htmlspecialchars_uni(number_format($seconds, 5, '.', '')) . '</font>';
				$status_html = '<font color="red"><b>Медленный</b></font>';
			} elseif ($is_warning) {
				$time_html = '<font color="#b8860b" title="Запрос не критичный, но стоит обратить внимание при высокой нагрузке.">' . htmlspecialchars_uni(number_format($seconds, 5, '.', '')) . '</font>';
				$status_html = '<font color="#b8860b"><b>Средний</b></font>';
			} else {
				$time_html = '<font color="green" title="Запрос выполняется быстро.">' . htmlspecialchars_uni(number_format($seconds, 5, '.', '')) . '</font>';
				$status_html = '<font color="green"><b>OK</b></font>';
			}

			$place = 'не указано';

			if ($file !== '') {
				$place = htmlspecialchars_uni($file);

				if ($line !== '') {
					$place .= ':' . htmlspecialchars_uni($line);
				}
			}

			print('<tr>');
			print('<td class="tables2" align="center" valign="top"><b>' . ((int)$key + 1) . '</b></td>');
			print('<td class="tables2" align="center" valign="top"><b>' . $time_html . '</b></td>');
			print('<td class="tables2" align="center" valign="top">' . $status_html . '</td>');
			print('<td class="tables2" valign="top"><span class="small">' . $place . '</span></td>');
			print('<td class="tables2" valign="top"><span class="small">' . htmlspecialchars_uni($query) . '</span></td>');
			print('</tr>');
		}

		print('</table>');
		print('<br />');
	}
}

function genbark($x,$y) {
	stdhead($y);
	print('<h2>' . htmlspecialchars_uni($y) . '</h2>');
	print('<p>' . htmlspecialchars_uni($x) . '</p>');
	stdfoot();
	exit();
}

function mksecret($length = 20) {
    // Набор символов: буквы (верхний и нижний регистр) + цифры
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charsLen = strlen($chars);
    $result = '';

    // Используем криптостойкий генератор, если доступен (PHP 7+)
    for ($i = 0; $i < $length; $i++) {
        if (function_exists('random_int')) {
            $result .= $chars[random_int(0, $charsLen - 1)];
        } else {
            // fallback для старых версий PHP
            $result .= $chars[mt_rand(0, $charsLen - 1)];
        }
    }

    return $result;
}

function tracker_random_base62($length = 10)
{
	$length = max(10, (int)$length);
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$max = strlen($chars) - 1;
	$value = '';

	for ($i = 0; $i < $length; $i++) {
		$value .= $chars[function_exists('random_int') ? random_int(0, $max) : mt_rand(0, $max)];
	}

	return $value;
}

function tracker_valid_passkey($passkey)
{
	return (bool)preg_match('/^[A-Za-z0-9]{10}$/', (string)$passkey);
}

function tracker_generate_passkey($user_id = 0, $length = 10)
{
	$user_id = (int)$user_id;

	for ($i = 0; $i < 20; $i++) {
		$passkey = tracker_random_base62($length);
		$where = 'passkey = ' . sqlesc($passkey);
		if ($user_id > 0) {
			$where .= ' AND id <> ' . $user_id;
		}

		$res = sql_query('SELECT id FROM users WHERE ' . $where . ' LIMIT 1') or sqlerr(__FILE__, __LINE__);
		if (!mysqli_fetch_assoc($res)) {
			return $passkey;
		}
	}

	return tracker_random_base62(max(16, (int)$length));
}

function tracker_ensure_user_passkey(&$user)
{
	if (!is_array($user) || empty($user['id'])) {
		return '';
	}

	if (tracker_valid_passkey($user['passkey'] ?? '')) {
		return (string)$user['passkey'];
	}

	$passkey = tracker_generate_passkey((int)$user['id']);
	sql_query('UPDATE users SET passkey = ' . sqlesc($passkey) . ' WHERE id = ' . (int)$user['id']) or sqlerr(__FILE__, __LINE__);
	$user['passkey'] = $passkey;

	return $passkey;
}

function tracker_upgrade_legacy_passkeys($limit = 200)
{
	$limit = max(1, min(1000, (int)$limit));

	$marker = sql_query("SELECT value_u FROM avps WHERE arg = 'passkeys_v2_done' LIMIT 1");
	$row = $marker ? mysqli_fetch_assoc($marker) : null;
	if ($row && (int)$row['value_u'] === 1) {
		return;
	}

	$res = sql_query("
		SELECT id
		FROM users
		WHERE passkey NOT REGEXP '^[A-Za-z0-9]{10}$'
		ORDER BY id ASC
		LIMIT $limit
	") or sqlerr(__FILE__, __LINE__);

	$count = 0;
	while ($user = mysqli_fetch_assoc($res)) {
		$userid = (int)$user['id'];
		if ($userid <= 0) {
			continue;
		}

		$passkey = tracker_generate_passkey($userid);
		sql_query('UPDATE users SET passkey = ' . sqlesc($passkey) . ' WHERE id = ' . $userid) or sqlerr(__FILE__, __LINE__);
		$count++;
	}

	if ($count === 0) {
		tracker_passkey_schema_upgrade();
		sql_query("
			INSERT INTO avps (arg, value_u, value_s)
			VALUES ('passkeys_v2_done', 1, '')
			ON DUPLICATE KEY UPDATE value_u = 1, value_s = ''
		") or sqlerr(__FILE__, __LINE__);
	}
}

function tracker_passkey_schema_upgrade()
{
	$marker = sql_query("SELECT value_u FROM avps WHERE arg = 'passkeys_v2_schema' LIMIT 1");
	$row = $marker ? mysqli_fetch_assoc($marker) : null;
	if ($row && (int)$row['value_u'] === 1) {
		return;
	}

	$tables = array('users', 'peers');
	foreach ($tables as $table) {
		$res = sql_query("SHOW COLUMNS FROM `$table` LIKE 'passkey'") or sqlerr(__FILE__, __LINE__);
		$column = mysqli_fetch_assoc($res);
		$type = isset($column['Type']) ? strtolower((string)$column['Type']) : '';
		if (preg_match('/varchar\((\d+)\)/', $type, $m) && (int)$m[1] !== 10) {
			if ($table === 'peers') {
				sql_query("DELETE FROM peers WHERE passkey NOT REGEXP '^[A-Za-z0-9]{10}$'") or sqlerr(__FILE__, __LINE__);
			}
			sql_query("ALTER TABLE `$table` MODIFY `passkey` varchar(10) NOT NULL DEFAULT ''") or sqlerr(__FILE__, __LINE__);
		}
	}

	sql_query("
		INSERT INTO avps (arg, value_u, value_s)
		VALUES ('passkeys_v2_schema', 1, '')
		ON DUPLICATE KEY UPDATE value_u = 1, value_s = ''
	") or sqlerr(__FILE__, __LINE__);
}

function httperr($code = 404) {
	$sapi_name = php_sapi_name();
	if ($sapi_name == 'cgi' OR $sapi_name == 'cgi-fcgi') {
		header('Status: 404 Not Found');
	} else {
		header('HTTP/1.1 404 Not Found');
	}
	exit;
}

function gmtime() {
	return strtotime(get_date_time());
}

function logincookie($id, $passhash, $updatedb = 1, $expires = 0x7fffffff) {

	$subnet = explode('.', getip());
	$subnet[2] = $subnet[3] = 0;
	$subnet = implode('.', $subnet); // 255.255.0.0

	setcookie(COOKIE_UID, $id, $expires, '/');
	setcookie(COOKIE_PASSHASH, md5($passhash.COOKIE_SALT.$subnet), $expires, '/');

	if ($updatedb)
		sql_query('UPDATE users SET last_login = NOW() WHERE id = '.$id);
}

function logoutcookie() {
//	setcookie(COOKIE_UID, '', 0x7fffffff, '/'); // Не стоит убирать комментирование т.к небудет работать система анти-двойной реги
	setcookie(COOKIE_PASSHASH, '', 0x7fffffff, '/');
}

function loggedinorreturn($nowarn = false) {
	global $CURUSER, $DEFAULTBASEURL;
	if (!$CURUSER) {
		header('Location: '.$DEFAULTBASEURL.'/login.php?returnto=' . urlencode(basename($_SERVER['REQUEST_URI'])).($nowarn ? '&nowarn=1' : ''));
		exit();
	}
}

function deletetorrent($id) {
	global $torrent_dir;
	$images = mysqli_fetch_array(sql_query('SELECT image1, image2, image3, image4, image5 FROM torrents WHERE id = '.$id));
	if ($images) { for ($x=1; $x <= 5; $x++) {
			if ($images['image' . $x] != '' && file_exists('torrents/images/' . $images['image' . $x]))
				unlink('torrents/images/' . $images['image' . $x]);
		}
	}
	sql_query('DELETE FROM torrents WHERE id = '.$id);
	sql_query('DELETE FROM snatched WHERE torrent = '.$id);
	sql_query('DELETE FROM bookmarks WHERE torrentid = '.$id);
	sql_query('DELETE FROM readtorrents WHERE torrentid = '.$id);
	foreach(explode('.','peers.files.comments.ratings') as $x)
		sql_query('DELETE FROM '.$x.' WHERE torrent = '.$id);
	sql_query('DELETE FROM torrents_scrape WHERE tid = '.$id);
	sql_query('DELETE FROM torrent_trackers WHERE torrentid = '.$id);
	sql_query('DELETE FROM torrents_descr WHERE tid = '.$id);
	unlink($torrent_dir.'/'.$id.'.torrent');
}

function pager($rpp, $count, $href, $opts = array())
{
    $rpp   = max(1, (int)$rpp);
    $count = max(0, (int)$count);
    $pages = (int)ceil($count / $rpp);

    if (!empty($opts['lastpagedefault'])) {
        $pagedefault = (int)floor(($count - 1) / $rpp);
        if ($pagedefault < 0) {
            $pagedefault = 0;
        }
    } else {
        $pagedefault = 0;
    }

    $page = isset($_GET['page']) ? (int)$_GET['page'] : $pagedefault;

    if ($page < 0) {
        $page = $pagedefault;
    }

    if ($pages > 0 && $page >= $pages) {
        $page = $pages - 1;
    }

    $start = $page * $rpp;

    if ($count < 1 || $pages < 1) {
        return array('', '', "LIMIT 0,$rpp");
    }

    $html = '<div class="paginator"><ul>';

    if ($page > 0) {
        $html .= '<li><a rel="prev" href="' . $href . 'page=' . ($page - 1) . '">Назад</a></li>';
    }

    $dotspace = 3;
    $dotted = false;

    for ($i = 0; $i < $pages; $i++) {
        $show_page =
            $i < 5 ||
            $i == ($pages - 1) ||
            ($i >= ($page - 2) && $i <= ($page + 2));

        if (!$show_page) {
            if (!$dotted) {
                $html .= '<li class="dots">...</li>';
                $dotted = true;
            }
            continue;
        }

        $dotted = false;

        $text = $i + 1;
        $title_start = ($i * $rpp) + 1;
        $title_end = min($title_start + $rpp - 1, $count);

        if ($i == $page) {
            $html .= '<li class="current"><a href="' . $href . 'page=' . $i . '">' . $text . '</a></li>';
        } else {
            $html .= '<li><a title="' . $title_start . ' - ' . $title_end . '" href="' . $href . 'page=' . $i . '">' . $text . '</a></li>';
        }
    }

    if ($page < ($pages - 1)) {
        $html .= '<li><a rel="next" href="' . $href . 'page=' . ($page + 1) . '">Вперед</a></li>';
    }

    $html .= '</ul></div>';

    $pagertop = '';

    $pagerbottom =
        '<div class="pager_info">Всего ' . $count .
        ' на ' . $pages .
        ' страницах по ' . $rpp .
        ' на каждой странице.</div>' . "\n" .
        $html . "\n";

    return array($pagertop, $pagerbottom, "LIMIT $start,$rpp");
}

function page_online_users_html($url_patterns, $empty_text = 'никого нет на странице')
{
	if (!is_array($url_patterns)) {
		$url_patterns = array($url_patterns);
	}

	$where = array();
	foreach ($url_patterns as $pattern) {
		$pattern = trim((string)$pattern);
		if ($pattern === '') {
			continue;
		}
		$where[] = 'url LIKE ' . sqlesc($pattern);
	}

	if (!$where) {
		$where[] = '1 = 0';
	}

	$dt = time() - 300;
	$res = sql_query("
		SELECT uid, username, class
		FROM sessions
		WHERE time >= $dt
		  AND uid > 0
		  AND (" . implode(' OR ', $where) . ")
		GROUP BY uid, username, class
		ORDER BY username ASC
	") or sqlerr(__FILE__, __LINE__);

	$users = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$username = get_user_class_color((int)$row['class'], htmlspecialchars_uni($row['username']));
		$users[] = '<a href="userdetails.php?id=' . (int)$row['uid'] . '">' . $username . '</a>';
	}

	$content = $users ? implode(', ', $users) : htmlspecialchars_uni($empty_text);

	return $content;
}

function page_online_box($url_patterns, $empty_text = 'никого нет на странице')
{
	$content = page_online_users_html($url_patterns, $empty_text);

	return page_online_block_html($content);
}

function page_online_block_html($content)
{
	return "<div class=\"bx2_0\">\n"
		. "<ul class=\"men\">\n"
		. "<li class=\"tp2 center\">&#1050;&#1090;&#1086; &#1054;&#1085;&#1051;&#1072;&#1081;&#1085; &#1079;&#1076;&#1077;&#1089;&#1100;, &#1085;&#1072; &#1101;&#1090;&#1086;&#1081; &#1089;&#1090;&#1088;&#1072;&#1085;&#1080;&#1094;&#1077; [ <a class=\"sba\" href=\"/pay.php\">&#1087;&#1086;&#1084;&#1086;&#1095;&#1100; &#1087;&#1088;&#1086;&#1077;&#1082;&#1090;&#1091;</a> ]</li>\n"
		. "<li><div class=\"pad5x5\">$content</div></li>\n"
		. "</ul>\n"
		. "</div>\n";
}

function genrelist() {
	if (function_exists('tracker_cache_remember')) {
		return tracker_cache_remember('categories:genrelist', 600, 'genrelist_query');
	}

	return genrelist_query();
}

function genrelist_query() {
	$ret = array();
	$res = sql_query('SELECT id, name FROM categories ORDER BY sort ASC');
	while ($row = mysqli_fetch_array($res))
		$ret[] = $row;
	return $ret;
}

function linkcolor($num) {
	if (!$num)
		return 'red';
//	if ($num == 1)
//		return 'yellow';
	return 'green';
}

function hash_pad($hash) {
	return str_pad($hash, 20);
}

function get_user_icons($arr, $big = false) {
	if (function_exists('statuses_user_icons_html')) {
		return statuses_user_icons_html($arr);
	}

	return '';
}

function parked() {
	   global $CURUSER, $tracker_lang;
	   if (($CURUSER['parked'] ?? 'no') === 'yes')
		  stderr($tracker_lang['error'] ?? 'Ошибка', 'Ваш аккаунт припаркован.');
}

function magnet($arg1, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = array())
{
    // Определяем стиль вызова по типу первого аргумента
    if (is_bool($arg1)) {
        // Старый порядок: magnet($html, $info_hash, $name, $size, $announces)
        $html       = (bool)$arg1;
        $info_hash  = (string)$arg2;
        $name       = (string)$arg3;
        $size       = (int)$arg4;
        $announces  = is_array($arg5) ? $arg5 : array();
    } else {
        // Новый порядок: magnet($info_hash, $name, $size, $announces, $html)
        $info_hash  = (string)$arg1;
        $name       = (string)$arg2;
        $size       = (int)$arg3;
        $announces  = is_array($arg4) ? $arg4 : array();
        $html       = is_bool($arg5) ? $arg5 : true; // по умолчанию true
    }

    // Разделитель параметров: & для обычной ссылки, &amp; для HTML-страницы
    $separator = $html ? '&amp;' : '&';

    // Собираем параметры трекеров: каждый получает вид &tr=URL
    $trackers_part = '';
    if (!empty($announces)) {
        $trackers_part = implode($separator . 'tr=', (array)$announces);
    }

    // Формируем magnet-ссылку
    return sprintf(
        'magnet:?xt=urn:btih:%2$s%1$sdn=%3$s%1$sxl=%4$d%1$str=%5$s',
        $separator,
        $info_hash,
        urlencode($name),
        $size,
        $trackers_part
    );
}

?>
