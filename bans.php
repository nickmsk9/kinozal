<?php

require_once __DIR__ . '/include/bittorrent.php';

dbconn(false);
loggedinorreturn();

if (get_user_class() < UC_MODERATOR) {
    stderr('Ошибка', 'Доступ запрещён.');
}

if (!function_exists('bans_h')) {
    function bans_h($value): string
    {
        if (function_exists('htmlspecialchars_uni')) {
            return htmlspecialchars_uni((string)$value);
        }

        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('bans_is_ipv4')) {
    function bans_is_ipv4(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }
}

if (!function_exists('bans_ip_to_db')) {
    function bans_ip_to_db(string $ip): string
    {
        return sprintf('%u', ip2long($ip));
    }
}

if (!function_exists('bans_db_to_ip')) {
    function bans_db_to_ip($ip): string
    {
        $ip = (string)$ip;

        if ($ip === '') {
            return '';
        }

        return long2ip((int)$ip);
    }
}

if (!function_exists('bans_user_link')) {
    function bans_user_link($id, $username): string
    {
        $id = (int)$id;
        $username = trim((string)$username);

        if ($id <= 0 || $username === '') {
            return 'Пользователь удалён';
        }

        return '<a href="userdetails.php?id=' . $id . '">' . bans_h($username) . '</a>';
    }
}

if (!function_exists('bans_redirect')) {
    function bans_redirect(): void
    {
        header('Location: bans.php');
        exit;
    }
}

/*
 * Снятие IP-бана.
 */
$remove = isset($_GET['remove']) ? (int)$_GET['remove'] : 0;

if (is_valid_id($remove)) {
    $res = sql_query("
        SELECT `id`, `first`, `last`
        FROM `bans`
        WHERE `id` = " . $remove . "
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);

    $ban = mysqli_fetch_assoc($res);

    if ($ban) {
        $first = bans_db_to_ip($ban['first']);
        $last = bans_db_to_ip($ban['last']);

        sql_query("
            DELETE FROM `bans`
            WHERE `id` = " . $remove . "
            LIMIT 1
        ") or sqlerr(__FILE__, __LINE__);

        $rangeText = ($first === $last)
            ? 'адрес ' . $first
            : 'адреса с ' . $first . ' по ' . $last;

        write_log(
            'Бан IP-адреса №' . $remove . ' (' . $rangeText . ') был снят пользователем ' . $CURUSER['username'] . '.'
        );
    }

    bans_redirect();
}

/*
 * Добавление IP-бана.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && get_user_class() >= UC_ADMINISTRATOR) {
    $first = trim((string)($_POST['first'] ?? ''));
    $last = trim((string)($_POST['last'] ?? ''));
    $comment = trim((string)($_POST['comment'] ?? ''));

    if ($first === '' || $last === '' || $comment === '') {
        stderr('Ошибка', 'Заполнены не все поля.');
    }

    if (!bans_is_ipv4($first) || !bans_is_ipv4($last)) {
        stderr('Ошибка', 'Укажите корректные IPv4-адреса.');
    }

    $firstLong = bans_ip_to_db($first);
    $lastLong = bans_ip_to_db($last);

    if ((float)$firstLong > (float)$lastLong) {
        stderr('Ошибка', 'Первый IP-адрес не может быть больше последнего IP-адреса.');
    }

    sql_query("
        INSERT INTO `bans`
            (`added`, `addedby`, `first`, `last`, `comment`)
        VALUES
            (
                " . sqlesc(get_date_time()) . ",
                " . (int)$CURUSER['id'] . ",
                " . sqlesc($firstLong) . ",
                " . sqlesc($lastLong) . ",
                " . sqlesc($comment) . "
            )
    ") or sqlerr(__FILE__, __LINE__);

    write_log(
        'IP-адреса с ' . $first . ' по ' . $last . ' были забанены пользователем ' . $CURUSER['username'] . '.'
    );

    bans_redirect();
}

/*
 * Получаем список банов.
 */
$res = sql_query("
    SELECT
        b.`id`,
        b.`added`,
        b.`addedby`,
        b.`first`,
        b.`last`,
        b.`comment`,
        u.`username`
    FROM `bans` AS b
    LEFT JOIN `users` AS u ON u.`id` = b.`addedby`
    ORDER BY b.`added` DESC
") or sqlerr(__FILE__, __LINE__);

stdhead($tracker_lang['bans'] ?? 'Баны IP-адресов');

/*
 * Таблица списка банов.
 */
print '<table class="tables1" width="100%" border="1" cellspacing="0" cellpadding="5">';

print '<tr>';
print '<td class="colhead" colspan="6" align="center">Забаненные IP-адреса</td>';
print '</tr>';

print '<tr>';
print '<td class="colhead" align="center" width="135">Добавлен</td>';
print '<td class="colhead" align="center" width="120">Первый IP</td>';
print '<td class="colhead" align="center" width="120">Последний IP</td>';
print '<td class="colhead" align="center" width="150">Кем добавлен</td>';
print '<td class="colhead" align="center">Комментарий</td>';
print '<td class="colhead" align="center" width="95">Действие</td>';
print '</tr>';

if (mysqli_num_rows($res) === 0) {
    print '<tr>';
    print '<td class="text" colspan="6" align="center"><b>Ничего не найдено</b></td>';
    print '</tr>';
} else {
    while ($arr = mysqli_fetch_assoc($res)) {
        $id = (int)$arr['id'];

        $firstIp = bans_db_to_ip($arr['first']);
        $lastIp = bans_db_to_ip($arr['last']);

        print '<tr>';

        print '<td class="text" align="center" valign="middle" nowrap="nowrap">';
        print bans_h($arr['added']);
        print '</td>';

        print '<td class="text" align="center" valign="middle" nowrap="nowrap">';
        print bans_h($firstIp);
        print '</td>';

        print '<td class="text" align="center" valign="middle" nowrap="nowrap">';
        print bans_h($lastIp);
        print '</td>';

        print '<td class="text" align="center" valign="middle" nowrap="nowrap">';
        print bans_user_link($arr['addedby'], $arr['username']);
        print '</td>';

        print '<td class="text" align="left" valign="middle">';
        print bans_h($arr['comment']);
        print '</td>';

        print '<td class="text" align="center" valign="middle" nowrap="nowrap">';
        print '<a href="bans.php?remove=' . $id . '">Снять бан</a>';
        print '</td>';

        print '</tr>';
    }
}

print '</table>';

/*
 * Форма добавления IP-бана.
 */
if (get_user_class() >= UC_ADMINISTRATOR) {
    print '<br>';

    print '<form method="post" action="bans.php">';

    print '<table class="tables1" width="100%" border="1" cellspacing="0" cellpadding="5">';

    print '<tr>';
    print '<td class="colhead" colspan="2" align="center">Забанить IP-адрес</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="rowhead" width="170" align="right" valign="middle">Первый IP</td>';
    print '<td class="text" align="left" valign="middle">';
    print '<input type="text" name="first" size="45" maxlength="15">';
    print '</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="rowhead" width="170" align="right" valign="middle">Последний IP</td>';
    print '<td class="text" align="left" valign="middle">';
    print '<input type="text" name="last" size="45" maxlength="15">';
    print '</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="rowhead" width="170" align="right" valign="middle">Комментарий</td>';
    print '<td class="text" align="left" valign="middle">';
    print '<input type="text" name="comment" size="45" maxlength="255">';
    print '</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="text" colspan="2" align="center">';
    print '<input type="submit" value="Забанить" class="buttonS">';
    print '</td>';
    print '</tr>';

    print '</table>';

    print '</form>';
}

stdfoot();

?>