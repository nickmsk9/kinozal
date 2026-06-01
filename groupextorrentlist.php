<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/groupex.php';

dbconn(false);
groups_ensure_schema();

$id = (int)($_GET['id'] ?? 0);
$group = groups_fetch($id);
if (!$group) {
	stderr('Группа', 'Группа не найдена.');
}

$member = !empty($CURUSER) ? groups_member($id, (int)$CURUSER['id']) : null;
$count = groups_torrent_count($id);
list($pagertop, $pagerbottom, $limit_sql) = pager(50, $count, '/groupextorrentlist.php?id=' . $id . '&amp;');
$page = isset($_GET['page']) ? max(0, (int)$_GET['page']) : 0;
$rows = groups_torrent_rows($id, $page * 50, 50, 'date');

$hide_right_blocks = true;
stdhead('Список раздач :: ' . $group['name']);

?>
<div class="bx2">
	<div class="pad0x0x5x0">
		<a href="/groupexlist.php" class="sbab">Список групп</a>
		::
		<a href="/mygroups.php" class="sbab">Мои группы</a>
		::
		<a href="/groupex.php?id=<?= $id ?>" class="sbab"><?= groups_h($group['name']) ?></a>
		::
		<a href="/groupextorrentlist.php?id=<?= $id ?>" class="sbab">Список раздач</a>
	</div>
	<?php groups_group_sidebar($group, $member); ?>
	<div class="mn3_content">
		<div class="bx1">
			<span class="bulet"></span>
			<b>Список раздач: <?= (int)$count ?></b>
			<span class="floatright">( <a href="/groupextorrents.php?id=<?= $id ?>" class="sba">Галерея раздач</a> )</span>
			<div class="clr"></div>
		</div>
		<?php if ($pagertop) { ?><div class="pad0x0x5x0"><?= $pagertop ?></div><?php } ?>
		<?php groups_torrent_table($rows); ?>
		<?php if ($pagerbottom) { ?><div class="pad5x5"><?= $pagerbottom ?></div><?php } ?>
	</div>
	<div class="clr"></div>
</div>
<?php
stdfoot();

?>
