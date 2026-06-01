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

$count_res = sql_query("SELECT COUNT(*) FROM groupex_log WHERE group_id = $id") or sqlerr(__FILE__, __LINE__);
$count_row = mysqli_fetch_row($count_res);
$count = (int)($count_row[0] ?? 0);
list($pagertop, $pagerbottom, $limit) = pager(50, $count, '/groupexlog.php?id=' . $id . '&amp;');

$log_res = sql_query("
	SELECT l.*, u.username, u.class, u.donor, u.gender, u.birthday, u.warned, u.enabled, u.uploaded, u.downloaded
	FROM groupex_log AS l
	LEFT JOIN users AS u ON u.id = l.userid
	WHERE l.group_id = $id
	ORDER BY l.added_at DESC, l.id DESC
	$limit
") or sqlerr(__FILE__, __LINE__);

$hide_right_blocks = true;
stdhead('Журнал группы :: ' . $group['name']);

?>
<div class="bx2">
	<div class="pad0x0x5x0">
		<a href="/groupexlist.php" class="sbab">Список групп</a>
		::
		<a href="/mygroups.php" class="sbab">Мои группы</a>
		::
		<a href="/groupex.php?id=<?= $id ?>" class="sbab"><?= groups_h($group['name']) ?></a>
		::
		<a href="/groupexlog.php?id=<?= $id ?>" class="sbab">Журнал группы</a>
	</div>
	<?php groups_group_sidebar($group, $member); ?>
	<div class="mn3_content">
		<div class="bx1"><span class="bulet"></span><b>Журнал группы</b></div>
		<?php if ($pagertop) { ?><div class="pad0x0x5x0"><?= $pagertop ?></div><?php } ?>
		<div class="bx2_0">
			<table class="tables1 w100p">
				<tr class="mn">
					<td class="w150">Дата</td>
					<td class="w150">Пользователь</td>
					<td class="w120">Действие</td>
					<td>Описание</td>
				</tr>
				<?php
				$found = false;
				while ($row = mysqli_fetch_assoc($log_res)) {
					$found = true;
					echo '<tr class="bg">';
					echo '<td>' . groups_h(groups_date($row['added_at'])) . '</td>';
					echo '<td>' . groups_user_link((int)$row['userid'], $row['username'] ?? '', (int)($row['class'] ?? 0), $row) . '</td>';
					echo '<td>' . groups_h($row['action']) . '</td>';
					echo '<td>' . groups_h($row['text']) . '</td>';
					echo '</tr>';
				}
				if (!$found) {
					echo '<tr><td colspan="4" class="center">В журнале пока нет записей.</td></tr>';
				}
				?>
			</table>
		</div>
		<?php if ($pagerbottom) { ?><div class="pad5x5"><?= $pagerbottom ?></div><?php } ?>
	</div>
	<div class="clr"></div>
</div>
<?php
stdfoot();

?>
