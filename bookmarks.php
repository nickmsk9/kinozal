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

stdhead($tracker_lang['bookmarks'] ?? 'Закладки');

$userId = (int)$CURUSER['id'];
$minvotes = isset($minvotes) ? (int)$minvotes : 0;

$res = sql_query("
    SELECT COUNT(`id`)
    FROM `bookmarks`
    WHERE `userid` = " . $userId . "
") or sqlerr(__FILE__, __LINE__);

$row = mysqli_fetch_row($res);
$count = (int)($row[0] ?? 0);

if ($count < 1) {
    stdmsg(
        $tracker_lang['error'] ?? 'Ошибка',
        $tracker_lang['you_have_no_bookmarks'] ?? 'У вас нет закладок.',
        'error'
    );

    stdfoot();
    exit;
}

$perpage = 25;

list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, 'bookmarks.php?');

$res = sql_query("
    SELECT
        `bookmarks`.`id` AS `bookmarkid`,

        `users`.`username`,
        `users`.`class`,
        `users`.`id` AS `owner`,

        `torrents`.`id`,
        `torrents`.`name`,
        `torrents`.`info_hash`,
        `torrents`.`type`,
        `torrents`.`comments`,
        (`torrents`.`leechers` + `torrents`.`remote_leechers`) AS `leechers`,
        (`torrents`.`seeders` + `torrents`.`remote_seeders`) AS `seeders`,
        `torrents`.`multitracker`,
        `torrents`.`last_mt_update`,

        IF(
            `torrents`.`numratings` < " . $minvotes . ",
            NULL,
            ROUND(`torrents`.`ratingsum` / `torrents`.`numratings`)
        ) AS `rating`,

        `categories`.`name` AS `cat_name`,
        `categories`.`image` AS `cat_pic`,

        `torrents`.`save_as`,
        `torrents`.`numfiles`,
        `torrents`.`added`,
        `torrents`.`filename`,
        `torrents`.`size`,
        `torrents`.`views`,
        `torrents`.`visible`,
        `torrents`.`free`,
        `torrents`.`hits`,
        `torrents`.`times_completed`,
        `torrents`.`category`

    FROM `bookmarks`

    INNER JOIN `torrents`
        ON `bookmarks`.`torrentid` = `torrents`.`id`

    LEFT JOIN `users`
        ON `torrents`.`owner` = `users`.`id`

    LEFT JOIN `categories`
        ON `torrents`.`category` = `categories`.`id`

    WHERE `bookmarks`.`userid` = " . $userId . "

    ORDER BY `torrents`.`id` DESC
    " . $limit . "
") or sqlerr(__FILE__, __LINE__);

print '<table class="embedded" cellspacing="0" cellpadding="5" width="95%" align="center">';
print '<tr>';
print '<td class="colhead" align="center" colspan="12">Список закладок</td>';
print '</tr>';

if (!empty($pagertop)) {
    print '<tr>';
    print '<td class="index" colspan="12">';
    print $pagertop;
    print '</td>';
    print '</tr>';
}

torrenttable($res, 'bookmarks');

if (!empty($pagerbottom)) {
    print '<tr>';
    print '<td class="index" colspan="12">';
    print $pagerbottom;
    print '</td>';
    print '</tr>';
}

print '</table>';

stdfoot();

?>