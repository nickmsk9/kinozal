<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/groupex.php';

dbconn(false);
kz_groups_ensure_schema();

$id = (int)($_GET['id'] ?? 0);
$group = kz_groups_fetch($id);
if (!$group) {
	stderr('Группа', 'Группа не найдена.');
}

$member = !empty($CURUSER) ? kz_groups_member($id, (int)$CURUSER['id']) : null;
$can_manage = kz_groups_can_manage($group);

$count_res = sql_query("SELECT COUNT(*) FROM groupex_members WHERE group_id = $id AND status = 'member'") or sqlerr(__FILE__, __LINE__);
$count_row = mysqli_fetch_row($count_res);
$count = (int)($count_row[0] ?? 0);
list($pagertop, $pagerbottom, $limit) = pager(50, $count, '/groupexmembers.php?id=' . $id . '&amp;');

$members_res = sql_query("
	SELECT gm.*, u.username, u.class, u.donor, u.gender, u.birthday, u.warned, u.enabled, u.uploaded, u.downloaded, u.last_access
	FROM groupex_members AS gm
	INNER JOIN users AS u ON u.id = gm.userid
	WHERE gm.group_id = $id
	  AND gm.status = 'member'
	ORDER BY FIELD(gm.role, 'owner', 'moderator', 'member'), gm.added_at ASC
	$limit
") or sqlerr(__FILE__, __LINE__);

$pending_res = null;
if ($can_manage) {
	$pending_res = sql_query("
		SELECT gm.*, u.username, u.class, u.donor, u.gender, u.birthday, u.warned, u.enabled, u.uploaded, u.downloaded
		FROM groupex_members AS gm
		INNER JOIN users AS u ON u.id = gm.userid
		WHERE gm.group_id = $id
		  AND gm.status = 'pending'
		ORDER BY gm.added_at ASC
	") or sqlerr(__FILE__, __LINE__);
}

$hide_right_blocks = true;
stdhead('Участники группы :: ' . $group['name']);

?>
<div class="bx2">
	<div class="pad0x0x5x0">
		<a href="/groupexlist.php" class="sbab">Список групп</a>
		::
		<a href="/mygroups.php" class="sbab">Мои группы</a>
		::
		<a href="/groupex.php?id=<?= $id ?>" class="sbab"><?= kz_groups_h($group['name']) ?></a>
		::
		<a href="/groupexmembers.php?id=<?= $id ?>" class="sbab">Участники</a>
	</div>
	<?php kz_groups_group_sidebar($group, $member); ?>
	<div class="mn3_content">
		<?php if ($can_manage) { ?>
			<div class="bx1">
				<div class="b"><span class="bulet"></span>Заявки на вступление</div>
				<table class="tables1 w100p">
					<?php
					$pending_found = false;
					while ($row = mysqli_fetch_assoc($pending_res)) {
						$pending_found = true;
						echo '<tr><td>' . kz_groups_user_link((int)$row['userid'], $row['username'], (int)$row['class'], $row) . '</td>';
						echo '<td class="right"><a class="sba" href="/groupexinvite.php?id=' . $id . '&amp;action=approve&amp;userid=' . (int)$row['userid'] . '">Принять</a> :: <a class="sba" href="/groupexinvite.php?id=' . $id . '&amp;action=decline&amp;userid=' . (int)$row['userid'] . '">Отклонить</a></td></tr>';
					}
					if (!$pending_found) {
						echo '<tr><td class="center">Новых заявок нет.</td></tr>';
					}
					?>
				</table>
			</div>
		<?php } ?>
		<div class="bx1">
			<span class="bulet"></span>
			<b>Участники: <?= (int)$count ?></b>
		</div>
		<?php if ($pagertop) { ?>
			<div class="pad0x0x5x0"><?= $pagertop ?></div>
		<?php } ?>
		<div class="bx2_0">
			<table class="tables1 w100p">
				<tr class="mn">
					<td>Пользователь</td>
					<td class="center w120">Статус</td>
					<td class="center w150">Вступил</td>
					<?php if ($can_manage) { ?><td class="right w200">Управление</td><?php } ?>
				</tr>
				<?php
				$found = false;
				while ($row = mysqli_fetch_assoc($members_res)) {
					$found = true;
					$role = $row['role'] === 'owner' ? 'Руководитель' : ($row['role'] === 'moderator' ? 'Модератор' : 'Участник');
					echo '<tr class="bg"><td>' . kz_groups_user_link((int)$row['userid'], $row['username'], (int)$row['class'], $row) . '</td>';
					echo '<td class="center">' . kz_groups_h($role) . '</td>';
					echo '<td class="center">' . kz_groups_h(kz_groups_date($row['added_at'])) . '</td>';
					if ($can_manage) {
						echo '<td class="right">';
						if ($row['role'] !== 'owner') {
							if ($row['role'] === 'moderator') {
								echo '<a class="sba" href="/groupexinvite.php?id=' . $id . '&amp;action=make_member&amp;userid=' . (int)$row['userid'] . '">Снять модератора</a> :: ';
							} else {
								echo '<a class="sba" href="/groupexinvite.php?id=' . $id . '&amp;action=make_moderator&amp;userid=' . (int)$row['userid'] . '">Назначить модератором</a> :: ';
							}
							echo '<a class="sba" href="/groupexinvite.php?id=' . $id . '&amp;action=kick&amp;userid=' . (int)$row['userid'] . '">Исключить</a>';
						} else {
							echo '&nbsp;';
						}
						echo '</td>';
					}
					echo '</tr>';
				}
				if (!$found) {
					echo '<tr><td colspan="' . ($can_manage ? 4 : 3) . '" class="center">Участников пока нет.</td></tr>';
				}
				?>
			</table>
		</div>
		<?php if ($pagerbottom) { ?>
			<div class="pad5x5"><?= $pagerbottom ?></div>
		<?php } ?>
	</div>
	<div class="clr"></div>
</div>
<?php
stdfoot();

?>
