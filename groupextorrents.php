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
$can_manage = groups_can_manage($group);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'addtorrent') {
	loggedinorreturn();
	if (!$can_manage) {
		stderr('Группа', 'У Вас нет прав добавлять раздачи в эту группу.');
	}
	$torrent_id = (int)($_POST['torrent_id'] ?? 0);
	if ($torrent_id <= 0) {
		stderr('Группа', 'Укажите ID раздачи.');
	}
	$tres = sql_query("SELECT id, name FROM torrents WHERE id = $torrent_id AND visible = 'yes' AND banned != 'yes' LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$torrent = mysqli_fetch_assoc($tres);
	if (!$torrent) {
		stderr('Группа', 'Раздача не найдена или скрыта.');
	}
	sql_query("
		INSERT IGNORE INTO groupex_torrents (group_id, torrent_id, added_by, added_at)
		VALUES ($id, $torrent_id, " . (int)$CURUSER['id'] . ", NOW())
	") or sqlerr(__FILE__, __LINE__);
	groups_log($id, (int)$CURUSER['id'], 'torrent', 'Добавлена раздача #' . $torrent_id);
	groups_refresh_counts($id);
	header('Location: /groupextorrents.php?id=' . $id);
	exit;
}

if (($_GET['action'] ?? '') === 'remove' && $can_manage) {
	$torrent_id = (int)($_GET['torrent_id'] ?? 0);
	if ($torrent_id > 0) {
		sql_query("DELETE FROM groupex_torrents WHERE group_id = $id AND torrent_id = $torrent_id") or sqlerr(__FILE__, __LINE__);
		groups_log($id, (int)$CURUSER['id'], 'torrent', 'Удалена раздача #' . $torrent_id);
		groups_refresh_counts($id);
	}
	header('Location: /groupextorrents.php?id=' . $id);
	exit;
}

$count = groups_torrent_count($id);
list($pagertop, $pagerbottom, $limit_sql) = pager(60, $count, '/groupextorrents.php?id=' . $id . '&amp;');
$page = isset($_GET['page']) ? max(0, (int)$_GET['page']) : 0;
$rows = groups_torrent_rows($id, $page * 60, 60, 'date');

$hide_right_blocks = true;
stdhead('Галерея раздач :: ' . $group['name']);

?>
<div class="bx2">
	<div class="pad0x0x5x0">
		<a href="/groupexlist.php" class="sbab">Список групп</a>
		::
		<a href="/mygroups.php" class="sbab">Мои группы</a>
		::
		<a href="/groupex.php?id=<?= $id ?>" class="sbab"><?= groups_h($group['name']) ?></a>
		::
		<a href="/groupextorrents.php?id=<?= $id ?>" class="sbab">Галерея раздач</a>
	</div>
	<?php groups_group_sidebar($group, $member); ?>
	<div class="mn3_content">
		<?php if ($can_manage) { ?>
			<div class="bx1" id="addtorrent">
				<form method="post" action="/groupextorrents.php?id=<?= $id ?>">
					<table class="tables1 w100p">
						<tr>
							<td class="w150"><b>Добавить раздачу:</b></td>
							<td><input type="text" name="torrent_id" class="w120" value=""> <input type="hidden" name="action" value="addtorrent"><input type="submit" class="buttonS" value="Добавить по ID"></td>
						</tr>
					</table>
				</form>
			</div>
		<?php } ?>
		<div class="bx1">
			<span class="bulet"></span>
			<b>Галерея раздач: <?= (int)$count ?></b>
			<span class="floatright">( <a href="/groupextorrentlist.php?id=<?= $id ?>" class="sba">Список раздач</a> )</span>
			<div class="clr"></div>
		</div>
		<?php if ($pagertop) { ?><div class="pad0x0x5x0"><?= $pagertop ?></div><?php } ?>
		<div class="bx1">
			<?php if (!$rows) { ?>
				<div class="center pad10x10">Раздачи в группе пока не добавлены.</div>
			<?php } ?>
			<?php foreach ($rows as $row) { ?>
				<a href="/details.php?id=<?= (int)$row['id'] ?>" class="thumbnail" title="<?= groups_h($row['name']) ?>">
					<img src="<?= groups_torrent_poster($row) ?>" alt="" style="width:100px; height:140px; object-fit:cover;">
					<span class="small"><?= groups_h(groups_cut($row['name'], 38)) ?></span>
				</a>
			<?php } ?>
			<div class="clr"></div>
		</div>
		<?php if ($pagerbottom) { ?><div class="pad5x5"><?= $pagerbottom ?></div><?php } ?>
	</div>
	<div class="clr"></div>
</div>
<?php
stdfoot();

?>
