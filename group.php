<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/kz_group_page.php';

dbconn(false);
loggedinorreturn();
kz_group_page_ensure_schema();

$res = sql_query("
	SELECT COUNT(*)
	FROM group_page_items AS i
	INNER JOIN groupex_groups AS g ON g.id = i.group_id
	WHERE i.active = 'yes' AND g.visible = 'yes'
") or sqlerr(__FILE__, __LINE__);
$has_items = (int)(mysqli_fetch_row($res)[0] ?? 0) > 0;

if ($has_items) {
	$count = get_row_count('group_page_items AS i INNER JOIN groupex_groups AS g ON g.id = i.group_id', "WHERE i.active = 'yes' AND g.visible = 'yes'");
} else {
	$count = get_row_count('groupex_groups', "WHERE visible = 'yes'");
}

$perpage = 30;
list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, '/group.php?');
$groups = $has_items ? kz_group_page_rows(true) : kz_group_page_public_rows($limit);

if ($has_items && $limit) {
	$page = isset($_GET['page']) ? max(0, (int)$_GET['page']) : 0;
	$groups = array_slice($groups, $page * $perpage, $perpage);
}

$hide_right_blocks = true;
stdhead('Группы');

?>
<div class="mn_wrap">
	<div class="bx1">
		<span class="bulet"></span><b>Группы</b>
		<span class="floatright"><a href="/groupexlist.php" class="sba">Все группы</a> | <a href="/groupexcreate.php" class="sba">Создать группу</a></span>
		<div class="clr"></div>
	</div>

	<?php if ($pagertop) { ?>
		<div class="pad0x0x5x0"><?= $pagertop ?></div>
	<?php } ?>

	<?php if (!$groups) { ?>
		<div class="bx2_0">
			<table class="tables2 w100p">
				<tr><td class="center pad10x10">Группы пока не добавлены.</td></tr>
			</table>
		</div>
	<?php } else { ?>
		<?php foreach ($groups as $group) { ?>
			<?php kz_group_page_card($group); ?>
		<?php } ?>
	<?php } ?>

	<?php if ($pagerbottom) { ?>
		<div class="pad5x5"><?= $pagerbottom ?></div>
	<?php } ?>
</div>
<?php

stdfoot();

?>
