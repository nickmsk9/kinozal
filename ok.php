<?php

require_once __DIR__ . '/include/bittorrent.php';

dbconn();

$type  = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
$email = isset($_GET['email']) ? trim((string)$_GET['email']) : '';

if ($type === '') {
    die();
}

if (!function_exists('confirm_h')) {
    function confirm_h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('confirm_base_url')) {
    function confirm_base_url()
    {
        global $DEFAULTBASEURL;

        $url = trim((string)($DEFAULTBASEURL ?? '/'));

        if ($url === '') {
            return '/';
        }

        return rtrim($url, '/') . '/';
    }
}

if (!function_exists('confirm_url')) {
    function confirm_url($url)
    {
        $url = trim((string)$url);

        if ($url === '') {
            return '/';
        }

        return confirm_h($url);
    }
}

if (!function_exists('confirm_link')) {
    function confirm_link($url, $text, $class = 'altlink')
    {
        return '<a class="' . confirm_h($class) . '" href="' . confirm_url($url) . '"><b>' . confirm_h($text) . '</b></a>';
    }
}

if (!function_exists('confirm_box')) {
    function confirm_box($title, $message, $status = 'ok')
    {
        $icon = $status === 'error' ? 'Ошибка' : 'Готово';

        echo '<div class="bx1">';
        echo '<div class="bx2">';
        echo '<div class="bx2_0">';

        echo '<table class="tables1" width="100%" cellspacing="0" cellpadding="5">';
        echo '<tr>';
        echo '<td class="colhead" colspan="2">' . confirm_h($title) . '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td width="90" align="center" valign="top">';
        echo '<b>' . confirm_h($icon) . '</b>';
        echo '</td>';

        echo '<td valign="top">';
        echo '<div style="padding:6px 4px; line-height:18px;">' . $message . '</div>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';

        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}

if ($type === 'signup') {
    if ($email === '' || !validemail($email)) {
        stderr($tracker_lang['error'] ?? 'Ошибка', 'Это не похоже на реальный email адрес.');
    }

    stdhead($tracker_lang['signup_successful'] ?? 'Регистрация завершена');

    $title = $tracker_lang['signup_successful'] ?? 'Регистрация завершена';

    if (!empty($use_email_act)) {
        $message = sprintf(
            $tracker_lang['confirmation_mail_sent'] ?? 'Письмо с подтверждением отправлено на адрес %s.',
            confirm_h($email)
        );
    } else {
        $message = sprintf(
            $tracker_lang['thanks_for_registering'] ?? 'Спасибо за регистрацию на %s.',
            confirm_h($SITENAME ?? '')
        );
    }

    confirm_box($title, $message);

    stdfoot();
    exit;
}

if ($type === 'sysop') {
    stdhead($tracker_lang['sysop_activated'] ?? 'Аккаунт администратора активирован');

    if (isset($CURUSER)) {
        $message = 'Аккаунт активирован. Перейти на сайт: ' . confirm_link(confirm_base_url(), confirm_base_url());

        confirm_box($tracker_lang['sysop_activated'] ?? 'Аккаунт активирован', $message);
    } else {
        $message = '
            Ваш аккаунт активирован, но автоматический вход не выполнен.
            Возможно, в браузере отключены cookies.
            Включите cookies и попробуйте
            ' . confirm_link('login.php', 'войти') . '
            снова.
        ';

        confirm_box($tracker_lang['sysop_activated'] ?? 'Аккаунт активирован', $message);
    }

    stdfoot();
    exit;
}

if ($type === 'confirmed') {
    stdhead($tracker_lang['account_activated'] ?? 'Аккаунт активирован');

    confirm_box(
        $tracker_lang['account_activated'] ?? 'Аккаунт активирован',
        confirm_h($tracker_lang['this_account_activated'] ?? 'Этот аккаунт уже активирован.')
    );

    stdfoot();
    exit;
}

if ($type === 'confirm') {
    stdhead('Подтверждение регистрации');

    if (isset($CURUSER)) {
        $message = '
            Ваш аккаунт успешно подтвержден и теперь активирован.
            Вы автоматически вошли на сайт.
            <br><br>
            Теперь вы можете
            ' . confirm_link(confirm_base_url(), 'перейти на главную') . '
            и начать использовать ваш аккаунт.
            <br><br>
            Перед началом использования ' . confirm_h($SITENAME ?? 'сайта') . ' рекомендуем прочитать
            ' . confirm_link('rules.php', 'правила') . '.
        ';

        confirm_box('Ваш аккаунт успешно подтвержден!', $message);
    } else {
        $message = '
            Ваш аккаунт активирован, но автоматический вход не выполнен.
            Возможно, в браузере отключены cookies.
            Включите cookies и попробуйте
            ' . confirm_link('login.php', 'войти') . '
            снова.
        ';

        confirm_box('Аккаунт успешно подтвержден!', $message);
    }

    stdfoot();
    exit;
}

die();
