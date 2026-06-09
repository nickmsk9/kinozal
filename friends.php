<?

require_once("include/bittorrent.php");

dbconn(false);
loggedinorreturn();

function friends_h($value) {
	return htmlspecialchars_uni((string)$value);
}

function friends_user_menu($user) {
	$id = (int)$user["id"];
	$avatar = !empty($user["avatar"]) ? friends_h($user["avatar"]) : "/pic/default_avatar.gif";
	$reputation = function_exists('reputation_value') ? reputation_value($user) : (isset($user["simpaty"]) ? max(0, (int)$user["simpaty"]) : 0);
	$bonus = function_exists('pay_user_votes_from_array') ? number_format(pay_user_votes_from_array($user), 0, '.', ' ') : (isset($user["bonus"]) ? number_format((float)$user["bonus"], 0, '.', ' ') : 0);

	return '
	<div class="mn1_menu">
		<ul class="men w200">
			<li class="img"><a href="/userdetails.php?id=' . $id . '"><img src="' . $avatar . '" class="p200" alt=""></a></li>
			<li class="tp">Меню пользователя</li>
			<li><span class="bulet"></span><a href="/inbox.php">Личные сообщения</a></li>
			<li><span class="bulet"></span><a href="/userdetails.php?id=' . $id . '">Мой профиль</a></li>
			<li><span class="bulet"></span><a href="/my.php">Редактировать профиль</a></li>
			<li><span class="bulet"></span><a href="/mygroups.php">Мои группы</a></li>
			<li><span class="bulet"></span><a href="/friends.php?id=' . $id . '">Мой список друзей</a></li>
			<li class="sf"><span class="bulet"></span><a href="/mytorrents.php?id=' . $id . '">Мои раздачи</a></li>
			<li class="tp">Репутация<span class="floatright"><a href="/pay_mode_b.php?userid=' . $id . '" title="Понизить репутацию"><img border="0" src="/pic/minus.gif" alt=""></a> <b>' . $reputation . '</b> <a href="/pay_mode_b.php?userid=' . $id . '" title="Повысить репутацию"><img border="0" src="/pic/plus.gif" alt=""></a></span></li>
			<li><span class="bulet"></span><a href="/user_reputation.php?id=' . $id . '">Полученные отзывы</a></li>
			<li><span class="bulet"></span><a href="/user_reputation.php?id=' . $id . '&amp;type=2">Отданные отзывы</a></li>
			<li class="tp">Закладки</li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=1">Раздачи</a></li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=2">Группы</a></li>
			<li><span class="bulet"></span><a href="/bookmarks.php?type=3">Пользователи</a></li>
			<li class="sf"><span class="bulet"></span><a href="/bookmarks.php?type=4">Персоны</a></li>
			<li class="tp">История</li>
			<li><span class="bulet"></span><a href="/hytorrents.php?id=' . $id . '">Скачанного</a></li>
			<li><span class="bulet"></span><a href="/userhistory.php?id=' . $id . '">Комментариев</a></li>
			<li><span class="bulet"></span><a href="/uservotes.php?id=' . $id . '">Голосований</a></li>
			<li class="tp">Голоса<span class="floatright b">' . $bonus . '</span></li>
			<li><span class="bulet"></span><a href="/pay.php">Получить голоса</a></li>
			<li><span class="bulet"></span><a href="/pay_mode.php">Управление голосами</a></li>
			<li><span class="bulet"></span><a href="/pay_mode.php">Оставить пожелание</a></li>
			<li><span class="bulet"></span><a href="/pay_mode.php">Обнулить счетчик скачиваний</a></li>
		</ul>
	</div>';
}

function friends_ago($timestamp) {
	$timestamp = (string)$timestamp;
	if ($timestamp === '' || $timestamp === '0000-00-00 00:00:00') {
		return 'не был на сайте';
	}

	return get_et(sql_ts_to_ut($timestamp)) . ' назад';
}

function friends_delete_url($userid, $type, $targetid) {
	global $CURUSER;

	$hash = friends_h($CURUSER['hash4u'] ?? ($CURUSER['logout_hash'] ?? ''));
	$url = '/friends.php?id=' . (int)$userid . '&amp;action=delete&amp;type=' . friends_h($type) . '&amp;targetid=' . (int)$targetid;
	if ($hash !== '') {
		$url .= '&amp;hash4u=' . $hash;
	}

	return $url;
}

function friends_render_user_card($row, $userid, $type) {
	global $CURUSER;

	$targetid = (int)$row['id'];
	$username = (string)($row['username'] ?? '');
	$userclass = (int)($row['class'] ?? 0);
	$avatar = (($CURUSER["avatars"] ?? 'yes') === 'yes' && !empty($row["avatar"])) ? friends_h($row["avatar"]) : "/pic/default_avatar.gif";
	$torrents = (int)($row['torrents_count'] ?? 0);
	$comments = (int)($row['comments_count'] ?? 0);
	$deleteText = $type === 'friend' ? 'Удалить' : 'Убрать';
	$deleteUrl = friends_delete_url($userid, $type, $targetid);

	if ($username === '') {
		$username = 'Пользователь удален';
	}

	return "
	<div class=\"pad5x0x0x5 mn2\">
		<table class=\"tables2 w100p\">
			<tr>
				<td class=\"w50 top\"><img src=\"" . $avatar . "\" class=\"w50\" alt=\"\"></td>
				<td class=\"top\">
					<a href=\"/userdetails.php?id=" . $targetid . "\" class=\"u" . $userclass . "\">" . friends_h($username) . "</a>" . get_user_icons($row) . "<br>
					<a href=\"/sendmessage.php?receiver=" . $targetid . "\" class=\"sba\">Сообщ.</a>
					|
					<a href=\"" . $deleteUrl . "\" class=\"sba\" onclick=\"return confirm('Удалить пользователя из списка?');\">" . $deleteText . "</a><br>
					" . friends_ago($row['last_access'] ?? '') . "<br>
					раздач <b>" . $torrents . "</b>, коммент. <b>" . $comments . "</b>
				</td>
			</tr>
		</table>
	</div>";
}

function friends_render_grid($rows, $userid, $type, $emptyText) {
	if (!$rows) {
		return '<div class="pad5x5">' . friends_h($emptyText) . '</div>';
	}

	$html = '<table class="tables1 w100p">';
	$index = 0;

	foreach ($rows as $row) {
		if ($index % 2 === 0) {
			$html .= '<tr>';
		}

		$html .= '<td class="w50p top">' . friends_render_user_card($row, $userid, $type) . '</td>';

		if ($index % 2 === 1) {
			$html .= '</tr>';
		}

		$index++;
	}

	if ($index % 2 === 1) {
		$html .= '<td class="w50p top">&nbsp;</td></tr>';
	}

	return $html . '</table>';
}

$userid = (int)($_GET['id'] ?? $CURUSER['id']);
$action = (string)($_GET['action'] ?? '');

if (!$userid) {
	$userid = (int)$CURUSER['id'];
}

if (!is_valid_id($userid)) {
	stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
}

if ($userid !== (int)$CURUSER["id"]) {
	stderr($tracker_lang['error'], $tracker_lang['access_denied']);
}

$res = sql_query("SELECT * FROM users WHERE id = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_assoc($res) or stderr($tracker_lang['error'], $tracker_lang['invalid_id']);

if ($action === 'add') {
	$targetid = (int)($_GET['targetid'] ?? 0);
	$type = (string)($_GET['type'] ?? '');

	if (!is_valid_id($targetid) || $targetid === $userid) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	$target = sql_query("SELECT id FROM users WHERE id = $targetid LIMIT 1") or sqlerr(__FILE__, __LINE__);
	if (mysqli_num_rows($target) !== 1) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	if ($type === 'friend') {
		sql_query("DELETE FROM blocks WHERE userid = $userid AND blockid = $targetid") or sqlerr(__FILE__, __LINE__);
		sql_query("INSERT IGNORE INTO friends (userid, friendid) VALUES ($userid, $targetid)") or sqlerr(__FILE__, __LINE__);
		header("Location: $DEFAULTBASEURL/friends.php?id=$userid#friends");
		exit;
	}

	if ($type === 'block') {
		sql_query("DELETE FROM friends WHERE userid = $userid AND friendid = $targetid") or sqlerr(__FILE__, __LINE__);
		sql_query("INSERT IGNORE INTO blocks (userid, blockid) VALUES ($userid, $targetid)") or sqlerr(__FILE__, __LINE__);
		header("Location: $DEFAULTBASEURL/friends.php?id=$userid#blocks");
		exit;
	}

	stderr($tracker_lang['error'], 'Неизвестный тип списка.');
}

if ($action === 'delete') {
	$targetid = (int)($_GET['targetid'] ?? 0);
	$type = (string)($_GET['type'] ?? '');

	if (!is_valid_id($targetid)) {
		stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
	}

	if ($type === 'friend') {
		sql_query("DELETE FROM friends WHERE userid = $userid AND friendid = $targetid") or sqlerr(__FILE__, __LINE__);
		header("Location: $DEFAULTBASEURL/friends.php?id=$userid#friends");
		exit;
	}

	if ($type === 'block') {
		sql_query("DELETE FROM blocks WHERE userid = $userid AND blockid = $targetid") or sqlerr(__FILE__, __LINE__);
		header("Location: $DEFAULTBASEURL/friends.php?id=$userid#blocks");
		exit;
	}

	stderr($tracker_lang['error'], 'Неизвестный тип списка.');
}

$friends = array();
$res = sql_query("
	SELECT
		f.friendid AS id,
		u.username,
		u.class,
		u.avatar,
		u.title,
		u.country,
		u.gender,
		u.donor,
		u.warned,
		u.enabled,
		u.birthday,
		u.last_access,
		(SELECT COUNT(*) FROM torrents AS t WHERE t.owner = f.friendid) AS torrents_count,
		(SELECT COUNT(*) FROM comments AS c WHERE c.user = f.friendid) AS comments_count
	FROM friends AS f
	LEFT JOIN users AS u ON f.friendid = u.id
	WHERE f.userid = $userid
	ORDER BY u.username ASC
") or sqlerr(__FILE__, __LINE__);
while ($row = mysqli_fetch_assoc($res)) {
	$friends[] = $row;
}

$blocks = array();
$res = sql_query("
	SELECT
		b.blockid AS id,
		u.username,
		u.class,
		u.avatar,
		u.title,
		u.country,
		u.gender,
		u.donor,
		u.warned,
		u.enabled,
		u.birthday,
		u.last_access,
		(SELECT COUNT(*) FROM torrents AS t WHERE t.owner = b.blockid) AS torrents_count,
		(SELECT COUNT(*) FROM comments AS c WHERE c.user = b.blockid) AS comments_count
	FROM blocks AS b
	LEFT JOIN users AS u ON b.blockid = u.id
	WHERE b.userid = $userid
	ORDER BY u.username ASC
") or sqlerr(__FILE__, __LINE__);
while ($row = mysqli_fetch_assoc($res)) {
	$blocks[] = $row;
}

$profile_class_css = 'u' . (int)($user["class"] ?? UC_USER);
$hide_right_blocks = true;

stdhead("Мои списки пользователей");
?>
<div class="mn_wrap">
	<?= friends_user_menu($user) ?>
	<div class="mn1_content">
		<div class="bx1 <?= $profile_class_css ?>">
			<a href="/userdetails.php?id=<?= $userid ?>" class="<?= $profile_class_css ?>"><?= friends_h($user['username']) ?></a>
		</div>

		<div class="bx1_0" id="friends">
			<table class="tables1 w100p">
				<tr>
					<td class="<?= $profile_class_css ?>" colspan="2">
						<span class="bulet"></span>
						Список Ваших друзей
					</td>
				</tr>
				<tr>
					<td>
						<?= friends_render_grid($friends, $userid, 'friend', 'Ваш список друзей пуст.') ?>
					</td>
				</tr>
			</table>
		</div>

		<div class="bx1" id="blocks">
			<div class="<?= $profile_class_css ?>">
				<span class="bulet"></span>
				Список игнорированных
			</div>
			<?= friends_render_grid($blocks, $userid, 'block', 'Ваш список игнорированных пуст.') ?>
		</div>

		<div class="bx1 center">
			<a href="/users.php" class="sba">Найти пользователя / список пользователей</a>
		</div>
	</div>
	<div class="clr"></div>
</div>
<?
stdfoot();

?>
