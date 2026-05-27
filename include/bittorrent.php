<?php

/*
 * Защита от двойного инклуда ядра
 * Protection from double including the core
 */

if (!defined('IN_TRACKER')) {
    define('IN_TRACKER', true);

    /*
     * Базовая защита от старых register_globals-style атак.
     */
    if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
        http_response_code(400);
        exit('Request tainting attempted.');
    }

    /*
     * PHP environment.
     * На production лучше display_errors = 0,
     * но оставлено 1, если проект сейчас в режиме отладки.
     */
    error_reporting(E_ALL);
    ini_set('error_reporting', (string) E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '0');
    ini_set('ignore_repeated_errors', '1');

    ignore_user_abort(true);
    set_time_limit(0);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

    /*
     * Дополнительные разрешённые referrer-домены.
     * Каждый домен с новой строки.
     */
    $allowed_referrers = <<<REF

REF;

    /*
     * Referrer check for POST requests.
     * Это не полноценная CSRF-защита, но оставлено для совместимости.
     * Правильнее постепенно заменить на CSRF-токены.
     */
    if (
        ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && !defined('SKIP_REFERRER_CHECK')
    ) {
        $http_host = $_SERVER['HTTP_HOST']
            ?? $_ENV['HTTP_HOST']
            ?? $_SERVER['SERVER_NAME']
            ?? $_ENV['SERVER_NAME']
            ?? '';

        $http_referer = $_SERVER['HTTP_REFERER'] ?? '';

        if ($http_host !== '' && $http_referer !== '') {
            $http_host = preg_replace('#:80$#', '', trim($http_host));

            $referrer_parts = parse_url($http_referer);

            if (
                !is_array($referrer_parts)
                || empty($referrer_parts['host'])
            ) {
                http_response_code(403);
                exit('Invalid referrer.');
            }

            $ref_port = isset($referrer_parts['port'])
                ? (int) $referrer_parts['port']
                : 80;

            $ref_host = $referrer_parts['host'];

            if ($ref_port !== 80) {
                $ref_host .= ':' . $ref_port;
            }

            $allowed = preg_split(
                '#\s+#',
                $allowed_referrers,
                -1,
                PREG_SPLIT_NO_EMPTY
            );

            if (!is_array($allowed)) {
                $allowed = [];
            }

            $allowed[] = preg_replace('#^www\.#i', '', $http_host);
            $allowed[] = '.paypal.com';

            $pass_ref_check = false;

            foreach ($allowed as $host) {
                $host = trim((string) $host);

                if ($host === '') {
                    continue;
                }

                if (preg_match('#' . preg_quote($host, '#') . '$#i', $ref_host)) {
                    $pass_ref_check = true;
                    break;
                }
            }

            unset($allowed);

            if (!$pass_ref_check) {
                http_response_code(403);
                exit('In order to accept POST request originating from this domain, the admin must add this domain to the whitelist.');
            }
        }
    }

    function timer(): float
    {
        return microtime(true);
    }

    /*
     * Basic engine checks.
     */
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        exit('Извините, трекер работает на PHP 8.0 и выше. Обновите версию PHP.');
    }

    if (!interface_exists('ArrayAccess')) {
        exit('У вас не установлено расширение PHP SPL (Standard PHP Library). Без установки этого расширения дальнейшая работа невозможна.');
    }

    /*
     * register_globals удалён из современных PHP,
     * но проверку оставляем безопасной для старых окружений.
     */
    $register_globals = ini_get('register_globals');

    if ($register_globals === '1' || strtolower((string) $register_globals) === 'on') {
        exit('Отключите register_globals в php.ini/.htaccess (угроза безопасности)');
    }

    /*
     * В проекте используется legacy-синтаксис <?,
     * поэтому short_open_tag пока оставляем техническим требованием.
     */
    if ((int) ini_get('short_open_tag') === 0) {
        exit('Включите short_open_tag в php.ini/.htaccess (техническое требование)');
    }

    if (!is_file(ROOT_PATH . 'include/secrets.local.php')) {
        exit('Создайте файл include/secrets.local.php и переместите в него свои локальные настройки из include/secrets.php (техническое требование)');
    }

    if (!is_file(ROOT_PATH . 'include/config.local.php')) {
        exit('Создайте файл include/config.local.php и переместите в него свои локальные настройки из include/config.php (техническое требование)');
    }

    /*
     * Start time.
     */
    $tstart = timer();

    /*
     * Include back-end.
     */
    if (empty($rootpath)) {
        $rootpath = ROOT_PATH;
    }

    require_once $rootpath . 'include/core.php';
}