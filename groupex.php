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

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_GET['zaboraction'] ?? '') === 'addcomment') {
	loggedinorreturn();
	$member = kz_groups_member($id, (int)$CURUSER['id']);
	if (!$member || $member['status'] !== 'member') {
		stderr('Забор группы', 'Писать на заборе могут только участники группы.');
	}

	$text = trim((string)($_POST['text'] ?? ''));
	if ($text === '') {
		stderr('Забор группы', 'Введите текст надписи.');
	}
	if (function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') > 5000 : strlen($text) > 5000) {
		stderr('Забор группы', 'Слишком длинная надпись.');
	}

	sql_query("
		INSERT INTO groupex_zabor (group_id, userid, text, ori_text, added_at)
		VALUES ($id, " . (int)$CURUSER['id'] . ', ' . sqlesc($text) . ', ' . sqlesc($text) . ", NOW())
	") or sqlerr(__FILE__, __LINE__);
	kz_groups_log($id, (int)$CURUSER['id'], 'zabor', 'Добавлена надпись на заборе');
	kz_groups_refresh_counts($id);

	header('Location: /groupex.php?id=' . $id . '#zabor');
	exit;
}

$hide_right_blocks = true;
stdhead('Группа :: ' . $group['name']);
kz_groups_subcat_script();

$torrent_rows = kz_groups_torrent_rows($id, 0, 5);

$members_res = sql_query("
	SELECT u.id, u.username, u.class, u.donor, u.gender, u.birthday, u.warned, u.enabled, u.uploaded, u.downloaded, gm.role
	FROM groupex_members AS gm
	INNER JOIN users AS u ON u.id = gm.userid
	WHERE gm.group_id = $id
	  AND gm.status = 'member'
	ORDER BY FIELD(gm.role, 'owner', 'moderator', 'member'), gm.added_at ASC
	LIMIT 20
") or sqlerr(__FILE__, __LINE__);

$zabor_res = sql_query("
	SELECT z.*, u.username, u.class, u.avatar, u.donor, u.gender, u.birthday, u.warned, u.enabled, u.uploaded, u.downloaded
	FROM groupex_zabor AS z
	LEFT JOIN users AS u ON u.id = z.userid
	WHERE z.group_id = $id
	ORDER BY z.added_at DESC, z.id DESC
	LIMIT 5
") or sqlerr(__FILE__, __LINE__);

?>
<div class="bx2">
	<div class="pad0x0x5x0">
		<a href="/groupexlist.php" class="sbab">Список групп</a>
		::
		<a href="/mygroups.php" class="sbab">Мои группы</a>
		::
		<a href="/groupex.php?id=<?= $id ?>" class="sbab"><?= kz_groups_h($group['name']) ?></a>
	</div>
	<?php kz_groups_group_sidebar($group, $member); ?>
	<div class="mn3_content">
		<div class="bx1">
			<table class="tables1">
				<tr>
					<td class="w150">Название:</td>
					<td><a href="/groupex.php?id=<?= $id ?>"><?= kz_groups_h($group['name']) ?></a></td>
				</tr>
				<tr>
					<td>Тип:</td>
					<td><a class="sba" href="<?= kz_groups_search_href('type', (int)$group['type']) ?>"><?= kz_groups_h(kz_groups_type_name((int)$group['type'])) ?></a></td>
				</tr>
				<tr>
					<td>Категория:</td>
					<td><a class="sba" href="<?= kz_groups_search_href('cat', (int)$group['cat']) ?>"><?= kz_groups_h(kz_groups_category_name((int)$group['cat'])) ?></a></td>
				</tr>
				<tr>
					<td>Подкатегория:</td>
					<td><a class="sba" href="<?= kz_groups_search_href('subcatsel', (int)$group['subcat']) ?>"><?= kz_groups_h(kz_groups_subcategory_name((int)$group['subcat'])) ?></a></td>
				</tr>
				<tr>
					<td>Время создания:</td>
					<td><?= kz_groups_h(kz_groups_date($group['created_at'])) ?></td>
				</tr>
				<tr>
					<td>Создатель:</td>
					<td><?= kz_groups_user_link((int)$group['owner_id'], $group['owner_username'] ?? '', (int)($group['owner_class'] ?? 0), $group) ?></td>
				</tr>
			</table>
		</div>
		<div class="bx1">
			<?= kz_groups_text($group['description']) ?>
		</div>
		<div class="bx1">
			<table class="tables1 w100p">
				<tr>
					<td><b>Раздачи: в группе <?= (int)$group['torrents_count'] ?> раздач</b></td>
					<td class="right">( <a href="/groupextorrents.php?id=<?= $id ?>" class="sba">Все раздачи</a> )</td>
				</tr>
			</table>
		</div>
		<?php kz_groups_torrent_table($torrent_rows); ?>
		<div class="bx2_0">
			<div class="pad5x5 b">
				<span class="bulet"></span>
				Участники: в группе <?= (int)$group['members_count'] ?> участников
				<div class="floatright">( <a href="/groupexmembers.php?id=<?= $id ?>" class="sba">Весь список</a> )</div>
				<div class="clr"></div>
			</div>
			<div class="mn2 pad5x5">
				<?php
				$members = array();
				while ($user = mysqli_fetch_assoc($members_res)) {
					$members[] = kz_groups_user_link((int)$user['id'], $user['username'], (int)$user['class'], $user);
				}
				echo $members ? implode(', ', $members) : 'Участников пока нет.';
				?>
			</div>
		</div>
		<div class="bx2_0" id="zabor">
			<div class="pad5x5">
				<span class="bulet"></span>
				<b>Забор
					<?php if ($member && $member['status'] === 'member') { ?>
						( <a onclick="$('#cmtcomm').toggle(); return false;" href="#" class="sba" id="cmfoc">Нацарапать на заборе</a> )
					<?php } ?>
				</b>
				<div class="floatright">
					Отображено 5 из <?= (int)$group['zabor_count'] ?> надписей. ( <a href="/groupexzabor.php?id=<?= $id ?>" class="sba">Просмотреть надписи</a> )
				</div>
				<div class="clr"></div>
			</div>
			<?php if ($member && $member['status'] === 'member') { ?>
				<form id="cmt" method="post" action="/groupex.php?id=<?= $id ?>&amp;zaboraction=addcomment">
					<div class="pad10x10 displaynone" id="cmtcomm">
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
						<input type="submit" value="Нацарапать" class="buttonS">
					</div>
				</form>
			<?php } ?>
			<?php
			$zabor_found = false;
			while ($comment = mysqli_fetch_assoc($zabor_res)) {
				$zabor_found = true;
				$avatar = trim((string)($comment['avatar'] ?? ''));
				if ($avatar !== '') {
					echo '<div class="mn2 cmet_bx"><img class="cmet_ava" src="' . kz_groups_h($avatar) . '" alt=""><div class="cmet_sbx">';
				} else {
					echo '<div class="mn2 cmet_bx"><div class="cmet_sbx">';
				}
				echo '<dl class="mn"><dt>' . kz_groups_user_link((int)$comment['userid'], $comment['username'] ?? '', (int)($comment['class'] ?? 0), $comment) . ' | ' . kz_groups_h(kz_groups_date($comment['added_at'])) . '</dt><dd></dd></dl>';
				echo '<div class="tx">' . kz_groups_text($comment['text']) . '</div></div><div class="clr"></div></div>';
			}
			if (!$zabor_found) {
				echo '<div class="mn2 pad10x10 center">На заборе пока нет надписей.</div>';
			}
			?>
		</div>
	</div>
	<div class="clr"></div>
</div>
<div class="bx2_0">
	<ul class="men">
		<li class="tp2 center">Кто ОнЛайн здесь, на этой странице [ <a class="sba" href="/pay.php">помочь проекту</a> ]</li>
		<li><div class="pad5x5"><?= kz_page_online_box(array('/groupex.php?id=' . $id . '%'), 'никого нет на странице') ?></div></li>
	</ul>
</div>
<?php
stdfoot();

?>
