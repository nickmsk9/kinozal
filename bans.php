<?

/*
// +--------------------------------------------------------------------------+
// | Project:    TBDevYSE - TBDev Yuna Scatari Edition                        |
// +--------------------------------------------------------------------------+
// | This file is part of TBDevYSE. TBDevYSE is based on TBDev,               |
// | originally by RedBeard of TorrentBits, extensively modified by           |
// | Gartenzwerg.                                                             |
// |                                                                          |
// | TBDevYSE is free software; you can redistribute it and/or modify         |
// | it under the terms of the GNU General Public License as published by     |
// | the Free Software Foundation; either version 2 of the License, or        |
// | (at your option) any later version.                                      |
// |                                                                          |
// | TBDevYSE is distributed in the hope that it will be useful,              |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with TBDevYSE; if not, write to the Free Software Foundation,      |
// | Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            |
// +--------------------------------------------------------------------------+
// |                                               Do not remove above lines! |
// +--------------------------------------------------------------------------+
*/



require_once __DIR__ . '/include/bittorrent.php';

dbconn(false);

loggedinorreturn();

if (get_user_class() < UC_MODERATOR) {
    die;
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

if (!function_exists('bans_ip_to_db')) {
    function bans_ip_to_db(string $ip): string
    {
        return sprintf('%u', ip2long($ip));
    }
}

if (!function_exists('bans_db_to_ip')) {
    function bans_db_to_ip($ip): string
    {
        return long2ip((int)$ip);
    }
}

if (!function_exists('is_good_ip')) {
    function is_good_ip(string $ip_addr): bool
    {
        return filter_var($ip_addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }
}

/*
 * Снятие IP-бана.
 */
$remove = isset($_GET['remove']) ? (int)$_GET['remove'] : 0;

if (is_valid_id($remove)) {
    $res = sql_query("
        SELECT `first`, `last`
        FROM `bans`
        WHERE `id` = " . $remove . "
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);

    if ($ip = mysqli_fetch_assoc($res)) {
        $first = bans_db_to_ip($ip['first']);
        $last = bans_db_to_ip($ip['last']);

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

    header('Location: bans.php');
    exit;
}

/*
 * Добавление нового IP-бана.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && get_user_class() >= UC_ADMINISTRATOR) {
    $first = trim((string)($_POST['first'] ?? ''));
    $last = trim((string)($_POST['last'] ?? ''));
    $comment = trim((string)($_POST['comment'] ?? ''));

    if ($first === '' || $last === '' || $comment === '') {
        stderr($tracker_lang['error'] ?? 'Ошибка', $tracker_lang['missing_form_data'] ?? 'Заполнены не все поля.');
    }

    if (!is_good_ip($first) || !is_good_ip($last)) {
        stderr('Ошибка', 'Укажите корректные IPv4-адреса.');
    }

    $firstLong = bans_ip_to_db($first);
    $lastLong = bans_ip_to_db($last);

    if ((float)$firstLong > (float)$lastLong) {
        stderr('Ошибка', 'Первый IP-адрес не может быть больше последнего IP-адреса.');
    }

    $added = sqlesc(get_date_time());
    $addedBy = (int)$CURUSER['id'];

    sql_query("
        INSERT INTO `bans`
            (`added`, `addedby`, `first`, `last`, `comment`)
        VALUES
            (
                " . $added . ",
                " . $addedBy . ",
                " . sqlesc($firstLong) . ",
                " . sqlesc($lastLong) . ",
                " . sqlesc($comment) . "
            )
    ") or sqlerr(__FILE__, __LINE__);

    write_log(
        'IP-адреса с ' . $first . ' по ' . $last . ' были забанены пользователем ' . $CURUSER['username'] . '.'
    );

    header('Location: bans.php');
    exit;
}

/*
 * Получаем список банов.
 */
$res = sql_query("
    SELECT
        `bans`.*,
        `users`.`username`
    FROM `bans`
    LEFT JOIN `users` ON `bans`.`addedby` = `users`.`id`
    ORDER BY `bans`.`added` DESC
") or sqlerr(__FILE__, __LINE__);

stdhead($tracker_lang['bans'] ?? 'Баны IP-адресов');

if (mysqli_num_rows($res) === 0) {
    print '<p align="center"><b>' . bans_h($tracker_lang['nothing_found'] ?? 'Ничего не найдено') . '</b></p>';
} else {
    /*
     * Внешняя таблица нужна только для центрирования.
     * Новые CSS-классы не добавляем.
     */
    print '<table border="0" cellspacing="0" cellpadding="0" align="center" width="95%">';
    print '<tr><td>';

    begin_table();

    print '<tr>';
    print '<td class="colhead" colspan="6">Забаненные IP-адреса</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="colhead">Добавлен</td>';
    print '<td class="colhead" align="left">Первый IP</td>';
    print '<td class="colhead" align="left">Последний IP</td>';
    print '<td class="colhead" align="left">Кем добавлен</td>';
    print '<td class="colhead" align="left">Комментарий</td>';
    print '<td class="colhead">Действие</td>';
    print '</tr>';

    while ($arr = mysqli_fetch_assoc($res)) {
        $id = (int)$arr['id'];
        $addedBy = (int)$arr['addedby'];

        $username = (!empty($arr['username']))
            ? $arr['username']
            : 'Пользователь удалён';

        $firstIp = bans_db_to_ip($arr['first']);
        $lastIp = bans_db_to_ip($arr['last']);

        print '<tr>';

        print '<td class="text">';
        print bans_h($arr['added']);
        print '</td>';

        print '<td class="text" align="left">';
        print bans_h($firstIp);
        print '</td>';

        print '<td class="text" align="left">';
        print bans_h($lastIp);
        print '</td>';

        print '<td class="text" align="left">';
        if ($addedBy > 0 && !empty($arr['username'])) {
            print '<a href="userdetails.php?id=' . $addedBy . '">' . bans_h($username) . '</a>';
        } else {
            print bans_h($username);
        }
        print '</td>';

        print '<td class="text" align="left">';
        print bans_h($arr['comment']);
        print '</td>';

        print '<td class="text" align="center">';
        print '<a href="bans.php?remove=' . $id . '">Снять бан</a>';
        print '</td>';

        print '</tr>';
    }

    end_table();

    print '</td></tr>';
    print '</table>';
}

/*
 * Форма добавления IP-бана.
 */
if (get_user_class() >= UC_ADMINISTRATOR) {
    print '<br>';
    print '<form method="post" action="bans.php">';

    /*
     * Внешняя таблица центрирует форму.
     * Ширина 420 подходит под три поля по 40 символов.
     */
    print '<table border="0" cellspacing="0" cellpadding="0" align="center" width="420">';
    print '<tr><td>';

    begin_table();

    print '<tr>';
    print '<td class="colhead" colspan="2">Забанить IP-адрес</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="rowhead">Первый IP</td>';
    print '<td class="text"><input type="text" name="first" size="40"></td>';
    print '</tr>';

    print '<tr>';
    print '<td class="rowhead">Последний IP</td>';
    print '<td class="text"><input type="text" name="last" size="40"></td>';
    print '</tr>';

    print '<tr>';
    print '<td class="rowhead">Комментарий</td>';
    print '<td class="text"><input type="text" name="comment" size="40"></td>';
    print '</tr>';

    print '<tr>';
    print '<td class="text" align="center" colspan="2">';
    print '<input type="submit" value="Забанить" class="buttonS">';
    print '</td>';
    print '</tr>';

    end_table();

    print '</td></tr>';
    print '</table>';

    print '</form>';
}

stdfoot();

?>