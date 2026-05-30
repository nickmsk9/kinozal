<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/groupex.php';

dbconn(false);
kz_groups_ensure_schema();

$name = kz_groups_request_text($_GET['name'] ?? '');
$userid = (int)($_GET['userid'] ?? 0);
$type = (int)($_GET['type'] ?? 0);
$cat = (int)($_GET['cat'] ?? 0);
$subcat = (int)($_GET['subcatsel'] ?? 0);
$sort = (int)($_GET['sort'] ?? 0);

$where = array("visible = 'yes'");
if ($name !== '') {
    $where[] = 'name LIKE ' . sqlesc('%' . $name . '%', true);
}
if ($userid > 0) {
    $where[] = 'owner_id = ' . $userid;
}
if (isset(kz_groups_types()[$type])) {
    $where[] = 'type = ' . $type;
}
if (isset(kz_groups_categories()[$cat])) {
    $where[] = 'cat = ' . $cat;
}
if ($subcat > 0) {
    $where[] = 'subcat = ' . $subcat;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);
$order_sql = 'created_at DESC, id DESC';
if ($sort === 1) {
    $order_sql = 'members_count DESC, created_at DESC';
} elseif ($sort === 2) {
    $order_sql = 'torrents_count DESC, created_at DESC';
} elseif ($sort === 3) {
    $order_sql = 'zabor_count DESC, created_at DESC';
}

$res = sql_query("SELECT COUNT(*) FROM groupex_groups $where_sql") or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_row($res);
$count = (int)($row[0] ?? 0);

$params = $_GET;
unset($params['page']);
$href = '/groupexlist.php?';
if ($params) {
    $href .= http_build_query($params, '', '&amp;') . '&amp;';
}

$perpage = 20;
list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, $href);

$groups_res = sql_query("
	SELECT *
	FROM groupex_groups
	$where_sql
	ORDER BY $order_sql
	$limit
") or sqlerr(__FILE__, __LINE__);

$hide_right_blocks = true;
stdhead('Список групп');
kz_groups_subcat_script(array('gsearch_subcatsel' => $subcat));

?>
<div class="bx2">
    <div class="pad0x0x5x0">
        <a href="/groupexlist.php" class="sbab">Список групп</a>
        ::
        <a href="/mygroups.php" class="sbab">Мои группы</a>
    </div>
    <?php kz_groups_search_sidebar(); ?>
    <div class="mn3_content">
        <div class="bx1">
            <span class="bulet"></span>
            <b>Каталог групп</b>
            <span class="floatright">Найдено групп: <b><?= (int)$count ?></b></span>
            <div class="clr"></div>
        </div>
        <?php if ($pagertop) { ?>
            <div class="pad0x0x5x0"><?= $pagertop ?></div>
        <?php } ?>
        <div class="bx2_0">
            <?php if ($count < 1) { ?>
                <div class="pad10x10 center">По заданным параметрам группы не найдены.</div>
            <?php } else { ?>
                <?php while ($group = mysqli_fetch_assoc($groups_res)) { ?>
                    <?php kz_groups_group_card($group); ?>
                <?php } ?>
            <?php } ?>
        </div>
        <?php if ($pagerbottom) { ?>
            <div class="pad5x5"><?= $pagerbottom ?></div>
        <?php } ?>
    </div>
    <div class="clr"></div>
</div>

<div class="bx2_0">
    <ul class="men">
        <li class="tp2 center">
            Кто ОнЛайн здесь, на этой странице
            [ <a class="sba" href="/pay.php">помочь проекту</a> ]
        </li>

        <li>
            <div class="pad5x5">
                <?= kz_page_online_box(array('/groupexlist.php%'), 'никого нет на странице'); ?>
            </div>
        </li>
    </ul>
</div>
<?php
stdfoot();

?>
