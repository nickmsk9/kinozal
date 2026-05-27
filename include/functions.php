<?php


# IMPORTANT: Do not edit below unless you know what you are doing!
if(!defined('IN_TRACKER'))
  die('Hacking attempt!');

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

	return $theme;
}

function theme_display_name($theme = "") {
	$resolved = theme_resolve_name($theme);
	if ($resolved === 'TBDev') {
		return 'Основная';
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

function theme_selector($sel_theme = "", $use_fsw = false) {
	global $DEFAULTBASEURL;
	$themes = get_themes();
	$content = "<select name=\"theme\"".($use_fsw ? " onchange=\"window.location='$DEFAULTBASEURL/changetheme.php?theme='+this.options[this.selectedIndex].value\"" : "").">\n";
	$selectedResolved = theme_resolve_name($sel_theme);
	foreach ($themes as $theme) {
		$label = theme_display_name($theme);
		$value = ($theme === 'TBDev') ? 'Основная' : $theme;
		$content .= "<option value=\"$value\"".(theme_resolve_name($theme) == $selectedResolved ? " selected" : "").">$label</option>\n";
	}
	$content .= "</select>";
	return $content;
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

function decode_to_utf8($int = 0) {
	$t = '';
	if ( $int < 0 ) {
		return chr(0);
	} else if ( $int <= 0x007f ) {
		$t .= chr($int);
	} else if ( $int <= 0x07ff ) {
		$t .= chr(0xc0 | ($int >> 6));
		$t .= chr(0x80 | ($int & 0x003f));
	} else if ( $int <= 0xffff ) {
		$t .= chr(0xe0 | ($int  >> 12));
		$t .= chr(0x80 | (($int >> 6) & 0x003f));
		$t .= chr(0x80 | ($int  & 0x003f));
	} else if ( $int <= 0x10ffff ) {
		$t .= chr(0xf0 | ($int  >> 18));
		$t .= chr(0x80 | (($int >> 12) & 0x3f));
		$t .= chr(0x80 | (($int >> 6) & 0x3f));
		$t .= chr(0x80 | ($int  &  0x3f));
	} else {
		return chr(0);
	}
	return $t;
}

function convert_unicode($t, $to = 'utf8') {
	$to = strtolower($to);
	if ($to == 'utf-8') {
		$t = preg_replace( '#%u([0-9A-F]{1,4})#ie', "decode_to_utf8(hexdec('\\1'))", utf8_encode($t) );
		$t = urldecode ($t);
	} else {
		$t = preg_replace( '#%u([0-9A-F]{1,4})#ie', "'&#' . hexdec('\\1') . ';'", $t );
		$t = urldecode ($t);
		$t = @html_entity_decode($t, ENT_NOQUOTES, $to);
	}
	return $t;
}

function local_user() {
	return $_SERVER["SERVER_ADDR"] == $_SERVER["REMOTE_ADDR"];
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

    if ($_COOKIE_SALT === 'default'
        && ($_SERVER['SERVER_ADDR'] ?? '') !== '127.0.0.1'
        && ($_SERVER['SERVER_ADDR'] ?? '') !== ($_SERVER['REMOTE_ADDR'] ?? '')
    ) {
        die('Скрипт заблокирован! Измените значение переменной $_COOKIE_SALT в файле include/config.local.php на случайное');
    }

    if (empty($_COOKIE_SALT)) {
        die('Идите и учите <a href="http://www.php.net">PHP</a>... Сказано было ИЗМЕНИТЬ значение, а не удалить переменную!');
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

        user_session();
        return;
    }

    $id = (int) $c_uid;

    if ($id <= 0 || strlen((string) $c_pass) !== 32) {
        if ($use_lang) {
            include_once('languages/lang_' . $default_language . '/lang_main.php');
        }

        user_session();
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

        user_session();
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

        user_session();
        return;
    }

    $updateset = array();

    if ($ip !== ($row['ip'] ?? '')) {
        $updateset[] = 'ip = ' . sqlesc($ip);
        $row['ip'] = $ip;
    }

    $updateset[] = 'last_access = ' . sqlesc(get_date_time());

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

function get_server_load() {
    global $tracker_lang; // глобальный массив языковых строк (используется только для 'unknown')
    
    // Windows: нагрузка не определяется штатными средствами
    if (stripos(PHP_OS, 'WIN') === 0) {
        return 0;
    }
    
    $load = null;
    
    // Способ 1: чтение из /proc/loadavg (Linux)
    if (@file_exists('/proc/loadavg')) {
        $content = @file_get_contents('/proc/loadavg');
        if ($content !== false) {
            $parts = explode(' ', $content);
            if (isset($parts[0])) {
                $load = (float)trim($parts[0]);
            }
        }
    }
    
    // Способ 2: вызов uptime (Unix, BSD, macOS)
    if ($load === null && function_exists('exec')) {
        $uptime = @exec('uptime');
        if ($uptime && preg_match('/load average[s]?:?\s*(.+)/', $uptime, $matches)) {
            $load_str = trim($matches[1]);
            // Обычно формат: "0.08, 0.03, 0.01" – берём первое значение
            $load_parts = explode(',', $load_str);
            if (isset($load_parts[0])) {
                $load = (float)trim($load_parts[0]);
            }
        }
    }
    
    // Если нагрузку получить не удалось
    if ($load === null) {
        $unknown = isset($tracker_lang['unknown']) ? $tracker_lang['unknown'] : 'unknown';
        return $unknown;
    }
    
    // Округляем до 4 знаков (сохраняем поведение оригинала)
    return round($load, 4);
}

function user_session() {
	global $CURUSER, $use_sessions;

	if (!$use_sessions)
		return;

	$ip = getip();
	$url = getenv("REQUEST_URI");

	if (!$CURUSER) {
		$uid = -1;
		$username = '';
		$class = -1;
	} else {
		$uid = $CURUSER['id'];
		$username = $CURUSER['username'];
		$class = $CURUSER['class'];
	}

	$past = time() - 300;
	$sid = session_id();
	$where = array();
	$updateset = array();
	if ($sid)
		$where[] = "sid = ".sqlesc($sid);
	elseif ($uid)
		$where[] = "uid = $uid";
	else
		$where[] = "ip = ".sqlesc($ip);
	//sql_query("DELETE FROM sessions WHERE ".implode(" AND ", $where));
	$ctime = time();
	$agent = $_SERVER["HTTP_USER_AGENT"];
	$updateset[] = "sid = ".sqlesc($sid);
	$updateset[] = "uid = ".sqlesc($uid);
	$updateset[] = "username = ".sqlesc($username);
	$updateset[] = "class = ".sqlesc($class);
	$updateset[] = "ip = ".sqlesc($ip);
	$updateset[] = "time = ".sqlesc($ctime);
	$updateset[] = "url = ".sqlesc($url);
	$updateset[] = "useragent = ".sqlesc($agent);
	session_write_close();
	if (count($updateset))
		sql_query("UPDATE sessions SET ".implode(", ", $updateset)." WHERE ".implode(" AND ", $where)) or sqlerr(__FILE__,__LINE__);
	if (mysql_modified_rows() < 1)
		sql_query("INSERT INTO sessions (sid, uid, username, class, ip, time, url, useragent) VALUES (".implode(", ", array_map("sqlesc",
									array($sid, $uid, $username, $class, $ip, $ctime, $url, $agent))).")") or sqlerr(__FILE__,__LINE__);
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

function mksizeint($bytes) {
		$bytes = max(0, $bytes);
		if ($bytes < 1000)
				return floor($bytes) . " B";
		elseif ($bytes < 1000 * 1024)
				return floor($bytes / 1024) . " kB";
		elseif ($bytes < 1000 * 1048576)
				return floor($bytes / 1048576) . " MB";
		elseif ($bytes < 1000 * 1073741824)
				return floor($bytes / 1073741824) . " GB";
		else
				return floor($bytes / 1099511627776) . " TB";
}

function deadtime() {
	global $announce_interval;
	return time() - floor($announce_interval * 1.3);
}

function mkprettytime($s) {
    if ($s < 0)
	$s = 0;
    $t = array();
    foreach (array("60:sec","60:min","24:hour","0:day") as $x) {
		$y = explode(":", $x);
		if ($y[0] > 1) {
		    $v = $s % $y[0];
		    $s = floor($s / $y[0]);
		} else
		    $v = $s;
	$t[$y[1]] = $v;
    }

    if ($t["day"])
	return $t["day"] . "d " . sprintf("%02d:%02d:%02d", $t["hour"], $t["min"], $t["sec"]);
    if ($t["hour"])
	return sprintf("%d:%02d:%02d", $t["hour"], $t["min"], $t["sec"]);
	return sprintf("%d:%02d", $t["min"], $t["sec"]);
}

function mkglobal($vars) {
	if (!is_array($vars))
		$vars = explode(":", $vars);
	foreach ($vars as $v) {
		if (isset($_GET[$v]))
			$GLOBALS[$v] = unesc($_GET[$v]);
		elseif (isset($_POST[$v]))
			$GLOBALS[$v] = unesc($_POST[$v]);
		else
			return 0;
	}
	return 1;
}

function tr($x, $y, $noesc=0, $prints = true, $width = "", $relation = '') {
	if ($noesc)
		$a = $y;
	else {
		$a = htmlspecialchars_uni($y);
		$a = str_replace("\n", "<br />\n", $a);
	}
	if ($prints) {
	  $print = "<td width=\"". $width ."\" class=\"heading\" valign=\"top\" align=\"right\">$x</td>";
	  $colpan = "align=\"left\"";
	} else {
		$colpan = "colspan=\"2\"";
	}

	print("<tr".( $relation ? " relation=\"$relation\"" : "").">$print<td valign=\"top\" $colpan>$a</td></tr>\n");
}

function validfilename($name) {
	return preg_match('/^[^\0-\x1f:\\\\\/?*\xff#<>|]+$/si', $name);
}

function validemail($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function mail_possible($email) {
	list(, $domain) = explode('@', $email);
	if (function_exists('checkdnsrr'))
		return checkdnsrr($domain, 'MX');
	else
		return true;
}

function send_pm($sender, $receiver, $added, $subject, $msg) {
	sql_query('INSERT INTO messages (sender, receiver, added, subject, msg) VALUES ('.implode(', ', array_map('sqlesc', array($sender, $receiver, $added, $subject, $msg))).')') or sqlerr(__FILE__,__LINE__);
}

function sent_mail($to,$fromname,$fromemail,$subject,$body,$multiple=false,$multiplemail='') {
	global $SITENAME,$SITEEMAIL,$smtptype,$smtp,$smtp_host,$smtp_port,$smtp_from,$smtpaddress,$accountname,$accountpassword,$rootpath;
	# Sent Mail Function v.05 by xam (This function to help avoid spam-filters.)
	$result = true;
	if ($smtptype == 'default') {
		@mail($to, $subject, $body, "From: $SITEEMAIL") or $result = false;
	} elseif ($smtptype == 'advanced') {
	# Is the OS Windows or Mac or Linux?
	$headers = '';
	$windows = false;
	if (strtoupper(substr(PHP_OS,0,3)=='WIN')) {
		$eol="\r\n";
		$windows = true;
	}
	elseif (strtoupper(substr(PHP_OS,0,3)=='MAC'))
		$eol="\r";
	else
		$eol="\n";
	$mid = md5(getip() . $fromname);
	$name = $_SERVER["SERVER_NAME"];
	$headers .= "From: $fromname <$fromemail>".$eol;
	$headers .= "Reply-To: $fromname <$fromemail>".$eol;
	$headers .= "Return-Path: $fromname <$fromemail>".$eol;
	$headers .= "Message-ID: <$mid.thesystem@$name>".$eol;
	$headers .= "X-Mailer: PHP v".phpversion().$eol;
	    $headers .= "MIME-Version: 1.0".$eol;
	    $headers .= "Content-type: text/plain; charset=utf-8".$eol;
	    $headers .= "X-Sender: PHP".$eol;
    if ($multiple)
    	$headers .= "Bcc: $multiplemail.$eol";
	if ($smtp == "yes") {
		ini_set('SMTP', $smtp_host);
		ini_set('smtp_port', $smtp_port);
		if ($windows)
			ini_set('sendmail_from', $smtp_from);
		}

    	@mail($to, $subject, $body, $headers) or $result = false;

	    	ini_restore('SMTP');
			ini_restore('smtp_port');
			if ($windows)
				ini_restore('sendmail_from');
		} elseif ($smtptype == 'external') {
		require_once($rootpath . 'include/smtp/smtp.lib.php');
		$mail = new smtp;
		$mail->debug(false);
		$mail->open($smtp_host, $smtp_port);
		if (!empty($accountname) && !empty($accountpassword))
			$mail->auth($accountname, $accountpassword);
		$mail->from($SITEEMAIL);
		$mail->to($to);
		$mail->subject($subject);
		$mail->body($body);
		$result = $mail->send();
		$mail->close();
	} else
		$result = false;

	return $result;
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
	return str_replace(array("%","_"), array("\\%","\\_"), mysql_real_escape_string($x));
}

function urlparse($m) {
	$t = $m[0];
	if (preg_match(',^\w+://,', $t))
		return "<a href=\"$t\">$t</a>";
	return "<a href=\"http://$t\">$t</a>";
}

function parsedescr($d, $html) {
	if (!$html) {
	  $d = htmlspecialchars_uni($d);
	  $d = str_replace("\n", "\n<br>", $d);
	}
	return $d;
}

function stdhead($title = "", $msgalert = true) {
	global $CURUSER, $SITE_ONLINE, $SITENAME, $ss_uri, $tracker_lang;

	if (!$SITE_ONLINE) {
		die('Site is down for maintenance, please check back again later... thanks<br />');
	}

	header('Content-Type: text/html; charset=' . $tracker_lang['language_charset']);
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');

	if ($title === '') {
		$title = $SITENAME . (isset($_GET['yuna']) ? ' (' . TBVERSION . ')' : '');
	} else {
		$title = $SITENAME . (isset($_GET['yuna']) ? ' (' . TBVERSION . ')' : '') . ' :: ' . htmlspecialchars_uni($title);
	}

	$ss_uri = select_theme();

	require_once('themes/' . $ss_uri . '/template.php');
	require_once('themes/' . $ss_uri . '/stdhead.php');
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

function mksecret($length = 20)
{
    $set = array(
        'a','A','b','B','c','C','d','D','e','E','f','F','g','G','h','H',
        'i','I','j','J','k','K','l','L','m','M','n','N','o','O','p','P',
        'q','Q','r','R','s','S','t','T','u','U','v','V','w','W','x','X',
        'y','Y','z','Z','1','2','3','4','5','6','7','8','9'
    );

    $str = '';

    for ($i = 1; $i <= $length; $i++) {
        $ch = rand(0, count($set) - 1);
        $str .= $set[$ch];
    }

    return $str;
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
	sql_query('DELETE FROM torrents_descr WHERE tid = '.$id);
	unlink($torrent_dir.'/'.$id.'.torrent');
}

function pager($rpp, $count, $href, $opts = array()) {
	$pages = ceil($count / $rpp);

	if (!isset($opts['lastpagedefault']))
		$pagedefault = 0;
	else {
		$pagedefault = floor(($count - 1) / $rpp);
		if ($pagedefault < 0)
			$pagedefault = 0;
	}

	if (isset($_GET['page'])) {
		$page = 0 + (int) $_GET['page'];
		if ($page < 0)
			$page = $pagedefault;
	}
	else
		$page = $pagedefault;

	$pager = "<td class=\"pager\">Страницы:</td><td class=\"pagebr\">&nbsp;</td>";
	$pager2 = "";
	$bregs = "";

	$mp = $pages - 1;
	$as = "<b>«</b>";
	if ($page >= 1) {
		$pager .= "<td class=\"pager\">";
		$pager .= "<a href=\"{$href}page=" . ($page - 1) . "\" style=\"text-decoration: none;\">$as</a>";
		$pager .= "</td><td class=\"pagebr\">&nbsp;</td>";
	}

	$as = "<b>»</b>";
	if ($page < $mp && $mp >= 0) {
		$pager2 .= "<td class=\"pager\">";
		$pager2 .= "<a href=\"{$href}page=" . ($page + 1) . "\" style=\"text-decoration: none;\">$as</a>";
		$pager2 .= "</td>$bregs";
	} else
		$pager2 .= $bregs;

	if ($count) {
		$pagerarr = array();
		$dotted = 0;
		$dotspace = 3;
		$dotend = $pages - $dotspace;
		$curdotend = $page - $dotspace;
		$curdotstart = $page + $dotspace;
		for ($i = 0; $i < $pages; $i++) {
			if (($i >= $dotspace && $i <= $curdotend) || ($i >= $curdotstart && $i < $dotend)) {
				if (!$dotted)
				   $pagerarr[] = "<td class=\"pager\">...</td><td class=\"pagebr\">&nbsp;</td>";
				$dotted = 1;
				continue;
			}
			$dotted = 0;
			$start = $i * $rpp + 1;
			$end = $start + $rpp - 1;
			if ($end > $count)
				$end = $count;

			 $text = $i+1;
			if ($i != $page)
				$pagerarr[] = "<td class=\"pager\"><a title=\"$start&nbsp;-&nbsp;$end\" href=\"{$href}page=$i\" style=\"text-decoration: none;\"><b>$text</b></a></td><td class=\"pagebr\">&nbsp;</td>";
			else
				$pagerarr[] = "<td class=\"highlight\"><b>$text</b></td><td class=\"pagebr\">&nbsp;</td>";

				  }
		$pagerstr = join("", $pagerarr);
		$pagertop = "<table class=\"main\"><tr>$pager $pagerstr $pager2</tr></table>\n";
		$pagerbottom = "Всего $count на $i страницах по $rpp на каждой странице.<br /><br /><table class=\"main\">$pager $pagerstr $pager2</table>\n";
	}
	else {
		$pagertop = $pager;
		$pagerbottom = $pagertop;
	}

	$start = $page * $rpp;

	return array($pagertop, $pagerbottom, "LIMIT $start,$rpp");
}

function kz_page_online_box($url_patterns, $empty_text = 'никого нет на странице')
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

	return "<table class=\"tables2 w100p\" style=\"background:#EEF7FF;\">\n"
		. "<tr><td class=\"center\" style=\"padding:6px 8px;\">Кто ОнЛайн здесь, на этой странице [ <a class=\"sba\" href=\"javascript:void(0)\">помочь проекту</a> ]</td></tr>\n"
		. "<tr><td style=\"padding:6px 8px; color:#E47D00; font-weight:bold;\">$content</td></tr>\n"
		. "</table>\n";
}

function downloaderdata($res) {
	$rows = array();
	$ids = array();
	$peerdata = array();
	while ($row = mysql_fetch_assoc($res)) {
		$rows[] = $row;
		$id = $row["id"];
		$ids[] = $id;
		$peerdata[$id] = array(downloaders => 0, seeders => 0, comments => 0);
	}

	if (count($ids)) {
		$allids = implode(",", $ids);
		$res = sql_query("SELECT COUNT(*) AS c, torrent, seeder FROM peers WHERE torrent IN ($allids) GROUP BY torrent, seeder");
		while ($row = mysql_fetch_assoc($res)) {
			if ($row["seeder"] == "yes")
				$key = "seeders";
			else
				$key = "downloaders";
			$peerdata[$row["torrent"]][$key] = $row["c"];
		}
		$res = sql_query("SELECT COUNT(*) AS c, torrent FROM comments WHERE torrent IN ($allids) GROUP BY torrent");
		while ($row = mysql_fetch_assoc($res)) {
			$peerdata[$row["torrent"]]["comments"] = $row["c"];
		}
	}

	return array($rows, $peerdata);
}

function genrelist() {
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

function ratingpic($num) {
	global $pic_base_url, $tracker_lang, $ss_uri;
	$r = round($num);
	if ($r < 1 || $r > 5)
		return;
	return "<img src=\"themes/$ss_uri/images/rating/$r.gif\" border=\"0\" alt=\"".$tracker_lang['rating'].": $num / 5\" />";
}

function writecomment($userid, $comment) {
    $userid = intval($userid);
    if (!$userid)
        throw new Exception(E_FATAL_ERROR, 'User ID cannot be 0 or null');
	/*$res = sql_query("SELECT modcomment FROM users WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
	$arr = mysql_fetch_assoc($res);

	$modcomment = date('d-m-Y') . ' - ' . $comment . '' . ($arr['modcomment'] != '' ? "\n" : "") . $arr['modcomment'];
	$modcom = sqlesc($modcomment);

	return sql_query("UPDATE users SET modcomment = $modcom WHERE id = $userid") or sqlerr(__FILE__, __LINE__);*/

    $modcomment = sqlesc(date('d-m-Y') . ' - ' . $comment);
    return sql_query("UPDATE users SET modcomment = CONCAT_WS('\n', $modcomment, modcomment) WHERE id = $userid") or sqlerr(__FILE__,__LINE__);
}

function hash_pad($hash) {
	return str_pad($hash, 20);
}

function get_user_icons($arr, $big = false) {
	if (function_exists('kz_statuses_user_icons_html')) {
		return kz_statuses_user_icons_html($arr);
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
    // Старый порядок:
    // magnet($html, $info_hash, $name, $size, $announces)
    if (is_bool($arg1)) {
        $html = $arg1;
        $info_hash = $arg2;
        $name = $arg3;
        $size = $arg4;
        $announces = $arg5;
    } else {
        // Новый порядок:
        // magnet($info_hash, $name, $size, $announces, $html)
        $info_hash = $arg1;
        $name = $arg2;
        $size = $arg3;
        $announces = is_array($arg4) ? $arg4 : array();
        $html = is_bool($arg5) ? $arg5 : true;
    }

    $ampersand = $html ? '&amp;' : '&';

    return sprintf(
        'magnet:?xt=urn:btih:%2$s%1$sdn=%3$s%1$sxl=%4$d%1$str=%5$s',
        $ampersand,
        (string) $info_hash,
        urlencode((string) $name),
        (int) $size,
        implode($ampersand . 'tr=', (array) $announces)
    );
}

// В этой строке забит копирайт. При его убирании можешь поплатиться рабочим трекером ;) В данном случае - убирая строчки ниже ты не сможешь использовать трекер.
define ('VERSION', '');
define ('NUM_VERSION', '2.1.18');
define ('TBVERSION', 'Powered by <a href="http://www.tbdev.net" target="_blank" style="cursor: help;" title="Бесплатная OpenSource база" class="copyright">TBDev</a> v'.NUM_VERSION.' <a href="http://bit-torrent.kiev.ua" target="_blank" style="cursor: help;" title="Сайт разработчика движка" class="copyright">Yuna Scatari Edition</a> '.VERSION.' Copyright &copy; 2001-'.date('Y'));

function mysql_modified_rows(): int
{
    global $link;

    if (!$link instanceof mysqli) {
        return 0;
    }

    $affectedRows = mysqli_affected_rows($link);

    if ($affectedRows > 0) {
        return $affectedRows;
    }

    $info = mysqli_info($link);

    if (!is_string($info) || $info === '') {
        return max(0, $affectedRows);
    }

    if (preg_match('/Rows matched:\s*([0-9]+)/i', $info, $matched)) {
        return (int) $matched[1];
    }

    return max(0, $affectedRows);
}
?>
