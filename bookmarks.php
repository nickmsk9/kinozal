<?php

require_once __DIR__ . '/include/bittorrent.php';

dbconn(false);
loggedinorreturn();

stdhead($tracker_lang['bookmarks'] ?? 'Закладки');

$userId = (int)$CURUSER['id'];
$minvotes = isset($minvotes) ? (int)$minvotes : 0;

/*
 * Считаем закладки пользователя.
 */
$res = sql_query("
    SELECT COUNT(*)
    FROM `bookmarks`
    WHERE `userid` = " . $userId . "
") or sqlerr(__FILE__, __LINE__);

$row = mysqli_fetch_row($res);
$count = (int)($row[0] ?? 0);

if ($count < 1) {
    print '<table class="tables1" width="100%" border="1" cellspacing="0" cellpadding="5">';
    print '<tr>';
    print '<td class="colhead" align="center">Закладки</td>';
    print '</tr>';
    print '<tr>';
    print '<td class="text" align="center"><b>' . ($tracker_lang['you_have_no_bookmarks'] ?? 'У вас нет закладок.') . '</b></td>';
    print '</tr>';
    print '</table>';

    stdfoot();
    exit;
}

$perpage = 25;

list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, 'bookmarks.php?');

/*
 * Получаем список торрентов из закладок.
 */
$res = sql_query("
    SELECT
        b.`id` AS `bookmarkid`,

        u.`username`,
        u.`class`,
        u.`id` AS `owner`,

        t.`id`,
        t.`name`,
        t.`info_hash`,
        t.`type`,
        t.`comments`,
        (t.`leechers` + t.`remote_leechers`) AS `leechers`,
        (t.`seeders` + t.`remote_seeders`) AS `seeders`,
        t.`multitracker`,
        t.`last_mt_update`,

        IF(
            t.`numratings` < " . $minvotes . ",
            NULL,
            ROUND(t.`ratingsum` / t.`numratings`)
        ) AS `rating`,

        c.`name` AS `cat_name`,
        c.`image` AS `cat_pic`,

        t.`save_as`,
        t.`numfiles`,
        t.`added`,
        t.`filename`,
        t.`size`,
        t.`views`,
        t.`visible`,
        t.`free`,
        t.`hits`,
        t.`times_completed`,
        t.`category`

    FROM `bookmarks` AS b

    INNER JOIN `torrents` AS t
        ON t.`id` = b.`torrentid`

    LEFT JOIN `users` AS u
        ON u.`id` = t.`owner`

    LEFT JOIN `categories` AS c
        ON c.`id` = t.`category`

    WHERE b.`userid` = " . $userId . "

    ORDER BY t.`id` DESC
    " . $limit . "
") or sqlerr(__FILE__, __LINE__);

/*
 * Заголовок страницы.
 * Не оборачиваем torrenttable() в эту таблицу.
 */
print '<table class="tables1" width="100%" border="1" cellspacing="0" cellpadding="5">';
print '<tr>';
print '<td class="colhead" align="center">Список закладок</td>';
print '</tr>';
print '</table>';

if (!empty($pagertop)) {
    print '<br>';
    print '<table class="tables1" width="100%" border="1" cellspacing="0" cellpadding="5">';
    print '<tr>';
    print '<td class="text" align="center">';
    print $pagertop;
    print '</td>';
    print '</tr>';
    print '</table>';
    print '<br>';
} else {
    print '<br>';
}

/*
 * torrenttable() должна сама вывести таблицу торрентов.
 */
torrenttable($res, 'bookmarks');

if (!empty($pagerbottom)) {
    print '<br>';
    print '<table class="tables1" width="100%" border="1" cellspacing="0" cellpadding="5">';
    print '<tr>';
    print '<td class="text" align="center">';
    print $pagerbottom;
    print '</td>';
    print '</tr>';
    print '</table>';
}

stdfoot();

?>