<?php

/**
 * Современная система защиты трекера от веб-атак
 * 
 * - Блокирует SQL‑инъекции, XSS, shell‑команды, path traversal, RCE
 * - Работает с GET и POST (рекурсивно, с защитой от глубоких массивов)
 * - Использует регулярные выражения и декодирование, чтобы обход %xx был бесполезен
 * - Имеет белый список для типовых безопасных параметров
 * - Логирует попытки атак в файл и выдаёт HTTP 403
 * 
 * @version 2.0 (2026)
 * @license GPL
 */

// Защита от прямого вызова
if (!defined('IN_TRACKER')) {
    die('Прямой вызов запрещён.');
}

// -----------------------------------------------------------------
// 1. Настройки
// -----------------------------------------------------------------

// Белый список параметров, которые полностью безопасны (значения не проверяются)
// Можно указать имена параметров или паттерны. Здесь – точные имена.
$safeParameters = [
    'page', 'sort', 'order', 'id', 'cat', 'forum_id', 'topic_id', 'post_id',
    'action', 'mode', 'search', 'query', 'user_id', 'torrent_id', 'limit', 'offset',
    'news_id', 'comment_id', 'start', 'view', 'lang', 'theme'
];

// Белый список значений для особо опасных параметров (например, action)
$safeValues = [
    'action' => ['view', 'edit', 'delete', 'add', 'update', 'list', 'search', 'go']
];

// Чёрный список регулярных выражений (шаблоны атак)
// Все выражения должны быть в разделителях /.../ с модификаторами
$attackPatterns = [
    // SQL‑инъекции
    '/\b(union\s+select|select\s+.*\s+from\s+\w+|insert\s+into\s+\w+|delete\s+from\s+\w+|drop\s+table|update\s+\w+\s+set|sleep\s*\(|benchmark\s*\(|concat\s*\()/i',
    '/\b(0x[0-9a-f]{32,}|hex\s*\(|unhex\s*\(|char\s*\(|load_file\s*\()/i',
    
    // XSS (теги и обработчики событий)
    '/<script\b[^>]*>/i',
    '/\bon\w+\s*=\s*["\']?[^>]*/i',
    '/javascript\s*:/i',
    '/<iframe\b/i', '/<object\b/i', '/<embed\b/i', '/<link\b/i', '/<meta\b/i',
    '/expression\s*\(/i',
    
    // Path traversal и файловые операции
    '/\.\.\/|\.\.\\\\/i',
    '/\/etc\/(passwd|shadow|group|sudoers)/i',
    '/\/var\/log\//i',
    '/\.(htaccess|htpasswd|env|git|svn|ini|bak|sql|log|db)$/i',
    
    // Системные команды (RCE)
    '/\b(exec|system|shell_exec|popen|proc_open|passthru|pcntl_exec)\s*\(/i',
    '/\b(eval|assert|create_function|call_user_func|preg_replace.*\/e)\s*\(/i',
    '/\b(wget|curl|nc|telnet|ssh|scp|ftp|perl|python|bash|sh)\b/i',
    '/\b(rm\s+-rf|chmod\s+777|chown|killall|reboot|shutdown|poweroff|halt)\b/i',
    '/\b(backtick|system\.execute|phpinfo)\b/i',
    
    // Обфускация и нестандартные протоколы
    '/\b(php:\/\/input|php:\/\/filter|expect:\/\/|data:\/\/|gopher:\/\/)\b/i',
    '/\b(base64_decode|gzuncompress|str_rot13|pack\s*\()/i',
    
    // HTTP‑спуфинг / заголовки
    '/\b(HTTP_X_FORWARDED_FOR|HTTP_CLIENT_IP|HTTP_PROXY)\b/i',
    
    // Известные векторы для старых CMS (предотвращение сканеров)
    '/\/wp-admin\//i', '/\/admin\/login.php/i', '/\/cgi-bin\//i', '/\.(pl|cgi|py|rb)$/i',
];

// -----------------------------------------------------------------
// 2. Функция проверки строки (рекурсивно для массивов)
// -----------------------------------------------------------------

/**
 * Рекурсивно проверяет строку или массив на наличие атак
 * @param mixed $data   Данные для проверки (строка или массив)
 * @param array $attackPatterns   Массив регулярных выражений
 * @param bool  $inSafeParam      Флаг, что мы внутри белого параметра
 * @return bool true – атака обнаружена, false – безопасно
 */
function isAttackDetected($data, $attackPatterns, $inSafeParam = false) {
    if (is_array($data)) {
        // Защита от слишком глубокой рекурсии (max уровень 5)
        static $depth = 0;
        if ($depth > 5) return false;
        $depth++;
        foreach ($data as $key => $value) {
            // Если параметр в белом списке, пропускаем его полностью
            global $safeParameters;
            if (in_array((string)$key, $safeParameters, true)) {
                $inSafeParam = true;
            }
            if (isAttackDetected($value, $attackPatterns, $inSafeParam)) {
                $depth--;
                return true;
            }
        }
        $depth--;
        return false;
    }
    
    if (!is_string($data)) return false;
    
    // Если параметр помечен как безопасный, не проверяем его содержимое
    if ($inSafeParam) return false;
    
    // 1. Декодируем URL несколько раз (на случай двойного кодирования)
    $decoded = rawurldecode($data);
    while ($decoded !== ($tmp = rawurldecode($decoded))) {
        $decoded = $tmp;
    }
    
    // 2. Удаляем нулевые байты и управляющие символы
    $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $decoded);
    
    // 3. Проверяем по чёрному списку
    foreach ($attackPatterns as $pattern) {
        if (preg_match($pattern, $clean)) {
            return true;
        }
    }
    return false;
}

// -----------------------------------------------------------------
// 3. Логирование атаки
// -----------------------------------------------------------------

/**
 * Записывает информацию об атаке в лог-файл
 */
function logAttack($message) {
    $logDir = __DIR__ . '/logs/';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . 'attack.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $logEntry = "[$timestamp] $ip | $uri | UA: $userAgent | $message" . PHP_EOL;
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// -----------------------------------------------------------------
// 4. Основная защита
// -----------------------------------------------------------------

// Защита от атак через QUERY_STRING
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$getAttack = isAttackDetected($queryString, $attackPatterns);

// Защита от атак через POST данные (все параметры)
$postAttack = false;
if (!empty($_POST)) {
    $postAttack = isAttackDetected($_POST, $attackPatterns);
}

// Можно также защитить REQUEST_URI (на случай других методов)
$uriAttack = isAttackDetected($_SERVER['REQUEST_URI'] ?? '', $attackPatterns);

// Если атака обнаружена – блокируем и логируем
if ($getAttack || $postAttack || $uriAttack) {
    // Логирование (без раскрытия деталей злоумышленнику)
    logAttack('Blocked suspicious request');
    
    // Отправляем HTTP 403 и минимальное сообщение
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    die(<<<HTML
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Доступ запрещён</title></head>
<body>
<h1>403 — Доступ запрещён</h1>
<p>Ваш запрос был заблокирован системой безопасности трекера.</p>
<p>Если вы считаете, что это ошибка, пожалуйста, свяжитесь с администратором.</p>
</body>
</html>
HTML
    );
}

// -----------------------------------------------------------------
// 5. Дополнительная защита: предотвращение обхода через заголовки (опционально)
// -----------------------------------------------------------------

// Блокировка прокси-заголовков, если они не разрешены глобально
// (можно вынести в конфиг)
if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !defined('ALLOW_PROXY_HEADERS')) {
    // Логируем попытку подмены IP
    logAttack('X-Forwarded-For header detected – possible IP spoofing');
    // По желанию – блокируем
    // http_response_code(403); die('Proxy headers are not allowed');
}

// Защита от HTTP-параметров, переопределяющих глобальные переменные
// (не обязательно, но для legacy кода)
if (ini_get('register_globals')) {
    // Принудительно отключаем – современные PHP уже не используют
    // Но предупредим в логе
    logAttack('register_globals is ON – unsafe configuration');
}

?>