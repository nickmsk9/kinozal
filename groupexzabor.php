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

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'addcomment') {
	loggedinorreturn();
	tracker_require_form_token('POST');
	$member = groups_member($id, (int)$CURUSER['id']);
	if (!$member || $member['status'] !== 'member') {
		stderr('Забор группы', 'Писать на заборе могут только участники группы.');
	}
	$text = trim((string)($_POST['text'] ?? ''));
	if ($text === '') {
		stderr('Забор группы', 'Введите текст надписи.');
	}
	sql_query("
		INSERT INTO groupex_zabor (group_id, userid, text, ori_text, added_at)
		VALUES ($id, " . (int)$CURUSER['id'] . ', ' . sqlesc($text) . ', ' . sqlesc($text) . ", NOW())
	") or sqlerr(__FILE__, __LINE__);
	groups_log($id, (int)$CURUSER['id'], 'zabor', 'Добавлена надпись на заборе');
	groups_refresh_counts($id);
	header('Location: /groupexzabor.php?id=' . $id);
	exit;
}

$count = (int)($group['zabor_count'] ?? 0);
list($pagertop, $pagerbottom, $limit) = pager(20, $count, '/groupexzabor.php?id=' . $id . '&amp;');

$zabor_res = sql_query("
	SELECT z.*, u.username, u.class, u.avatar, u.donor, u.gender, u.birthday, u.warned, u.enabled, u.uploaded, u.downloaded
	FROM groupex_zabor AS z
	LEFT JOIN users AS u ON u.id = z.userid
	WHERE z.group_id = $id
	ORDER BY z.added_at DESC, z.id DESC
	$limit
") or sqlerr(__FILE__, __LINE__);

$hide_right_blocks = true;
stdhead('Забор группы :: ' . $group['name']);
groups_subcat_script();

?>
<div class="bx2">
	<div class="pad0x0x5x0">
		<a href="/groupexlist.php" class="sbab">Список групп</a>
		::
		<a href="/mygroups.php" class="sbab">Мои группы</a>
		::
		<a href="/groupex.php?id=<?= $id ?>" class="sbab"><?= groups_h($group['name']) ?></a>
		::
		<a href="/groupexzabor.php?id=<?= $id ?>" class="sbab">Забор</a>
	</div>
	<?php groups_group_sidebar($group, $member); ?>
	<div class="mn3_content">
		<div class="bx1">
			<span class="bulet"></span>
			<b>Забор: <?= (int)$count ?> надписей</b>
		</div>
		<?php if ($member && $member['status'] === 'member') { ?>
			<form method="post" action="/groupexzabor.php?id=<?= $id ?>">
				<input type="hidden" name="hash4u" value="<?= groups_h($CURUSER['hash4u'] ?? tracker_user_form_token()) ?>">
				<div class="bx1">
					<div class="cmet_e_but">
						<ul>
							<li><input class="buttonS" type="button" value="b" style="font-weight:bold;" onclick="return kzGroupsInsertCode('text','b')"></li>
							<li><input class="buttonS" type="button" value="i" style="font-style:italic;" onclick="return kzGroupsInsertCode('text','i')"></li>
							<li><input class="buttonS" type="button" value="u" style="text-decoration:underline;" onclick="return kzGroupsInsertCode('text','u')"></li>
							<li><input class="buttonS" type="button" value="quote" onclick="return kzGroupsInsertCode('text','quote')"></li>
							<li><input class="buttonS" type="button" value="url" onclick="return kzGroupsInsertCode('text','url')"></li>
							<li><input class="buttonS" type="button" value="img" onclick="return kzGroupsInsertCode('text','img')"></li>
						</ul>
						<div class="clr"></div>
					</div>
					<div class="cmet_e_inp"><textarea id="text" name="text" cols="70" rows="5" class="w98p"></textarea></div>
					<input type="hidden" name="action" value="addcomment">
					<input type="submit" value="Нацарапать" class="buttonS">
				</div>
			</form>
		<?php } ?>
		<?php if ($pagertop) { ?><div class="pad0x0x5x0"><?= $pagertop ?></div><?php } ?>
		<div class="bx2_0">
			<?php
			$found = false;
			while ($comment = mysqli_fetch_assoc($zabor_res)) {
				$found = true;
				$avatar = trim((string)($comment['avatar'] ?? ''));
				if ($avatar !== '') {
					echo '<div class="mn2 cmet_bx"><img class="cmet_ava" src="' . groups_h($avatar) . '" alt=""><div class="cmet_sbx">';
				} else {
					echo '<div class="mn2 cmet_bx"><div class="cmet_sbx">';
				}
				echo '<dl class="mn"><dt>' . groups_user_link((int)$comment['userid'], $comment['username'] ?? '', (int)($comment['class'] ?? 0), $comment) . ' | ' . groups_h(groups_date($comment['added_at'])) . '</dt><dd></dd></dl>';
				echo '<div class="tx">' . groups_text($comment['text']) . '</div></div><div class="clr"></div></div>';
			}
			if (!$found) {
				echo '<div class="pad10x10 center">На заборе пока нет надписей.</div>';
			}
			?>
		</div>
		<?php if ($pagerbottom) { ?><div class="pad5x5"><?= $pagerbottom ?></div><?php } ?>
	</div>
	<div class="clr"></div>
</div>
<?php
stdfoot();

?>
