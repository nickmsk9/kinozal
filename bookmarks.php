<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/groupex.php';

dbconn(false);
loggedinorreturn();

$bookmarkType = (int)($_GET['type'] ?? 1);

if ($bookmarkType === 2) {
    kz_groups_ensure_schema();

    $userId = (int)$CURUSER['id'];

    if (isset($_GET['add'])) {
        $groupId = (int)$_GET['add'];
        if (kz_groups_fetch($groupId)) {
            kz_groups_add_bookmark($groupId, $userId);
        }
        header('Location: /bookmarks.php?type=2');
        exit;
    }

    if (isset($_GET['delete'])) {
        kz_groups_remove_bookmark((int)$_GET['delete'], $userId);
        header('Location: /bookmarks.php?type=2');
        exit;
    }

    $res = sql_query("
        SELECT COUNT(*)
        FROM groupex_bookmarks AS b
        INNER JOIN groupex_groups AS g ON g.id = b.group_id
        WHERE b.userid = $userId
          AND g.visible = 'yes'
    ") or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_row($res);
    $count = (int)($row[0] ?? 0);

    $perpage = 25;
    list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, 'bookmarks.php?type=2&amp;');

    $groups = sql_query("
        SELECT g.*, b.added_at AS bookmark_added
        FROM groupex_bookmarks AS b
        INNER JOIN groupex_groups AS g ON g.id = b.group_id
        WHERE b.userid = $userId
          AND g.visible = 'yes'
        ORDER BY b.added_at DESC, g.name ASC
        $limit
    ") or sqlerr(__FILE__, __LINE__);

    stdhead('Закладки :: Группы');
    ?>
    <div class="bx2">
        <div class="pad0x0x5x0">
            <a href="/bookmarks.php?type=1" class="sbab">Раздачи</a>
            ::
            <a href="/bookmarks.php?type=2" class="sbab">Группы</a>
            ::
            <a href="/groupexlist.php" class="sbab">Список групп</a>
        </div>
        <div class="bx1">
            <span class="bulet"></span>
            <b>Закладки групп</b>
            <span class="floatright">Всего: <b><?= (int)$count ?></b></span>
            <div class="clr"></div>
        </div>
        <?php if ($pagertop) { ?><div class="pad0x0x5x0"><?= $pagertop ?></div><?php } ?>
        <div class="bx2_0">
            <?php
            if ($count < 1) {
                echo '<div class="pad10x10 center">В закладках пока нет групп.</div>';
            } else {
                while ($group = mysqli_fetch_assoc($groups)) {
                    kz_groups_group_card($group);
                }
            }
            ?>
        </div>
        <?php if ($pagerbottom) { ?><div class="pad5x5"><?= $pagerbottom ?></div><?php } ?>
    </div>
    <?php
    stdfoot();
    exit;
}

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
