<?php

if (!defined('BLOCK_FILE')) {
	header('Location: ../index.php');
	exit;
}

global $CURUSER, $use_sessions, $tracker_lang, $content;

$blocktitle = isset($tracker_lang['whos_online'])
	? $tracker_lang['whos_online']
	: 'Кто онлайн';

$content = isset($content) ? $content : '';

$latestuser = 'Нет пользователей';

$res_latest = sql_query("
	SELECT id, username
	FROM users
	WHERE status = 'confirmed'
	ORDER BY id DESC
	LIMIT 1
");

if ($res_latest && mysqli_num_rows($res_latest) > 0) {
	$a = mysqli_fetch_assoc($res_latest);

	$user_id = (int)$a['id'];
	$username = htmlspecialchars($a['username'], ENT_QUOTES, 'UTF-8');

	if (!empty($CURUSER)) {
		$latestuser = '<a href="userdetails.php?id=' . $user_id . '" class="online">' . $username . '</a>';
	} else {
		$latestuser = $username;
	}
}

$title_who = array();

$dt = time() - 300;

if (!empty($use_sessions)) {
	$result = sql_query("
		SELECT s.uid AS id, s.username, s.class
		FROM sessions AS s
		WHERE s.time > " . sqlesc($dt) . "
		ORDER BY s.class DESC
	");
} else {
	$result = sql_query("
		SELECT u.id, u.username, u.class
		FROM users AS u
		WHERE u.last_access > " . sqlesc(get_date_time($dt)) . "
		ORDER BY u.class DESC
	");
}

$users = 0;
$guests = 0;
$staff = 0;
$total = 0;

$parsed_names = array();
$parsed_ids = array();

if ($result) {
	while ($row = mysqli_fetch_assoc($result)) {
		$uid = isset($row['id']) ? (int)$row['id'] : 0;
		$uname = isset($row['username']) ? trim($row['username']) : '';
		$class = isset($row['class']) ? (int)$row['class'] : 0;

		$is_guest = ($uid <= 0 || $uname === '');

		if ($is_guest) {
			$guests++;
			$total++;
			continue;
		}

		if (in_array($uid, $parsed_ids, true)) {
			continue;
		}

		$parsed_ids[] = $uid;
		$total++;

		if ($class >= UC_MODERATOR) {
			$staff++;
		} else {
			$users++;
		}

		if (!in_array($uname, $parsed_names, true)) {
			$parsed_names[] = $uname;

			$title_who[] = '<a href="userdetails.php?id=' . $uid . '" class="online">'
				. get_user_class_color($class, htmlspecialchars($uname, ENT_QUOTES, 'UTF-8'))
				. '</a>';
		}
	}
}

$content .= '<table border="0" width="100%">
<tr valign="middle">
	<td align="left" class="embedded">
		<b>Последний пользователь: </b> ' . $latestuser . '<hr>
	</td>
</tr>
</table>' . "\n";

if (!empty($title_who)) {
	$content .= '<table border="0" width="100%">
<tr valign="middle">
	<td align="left" class="embedded">
		<b>Кто онлайн: </b><hr>
	</td>
</tr>
<tr>
	<td class="embedded">' . implode(', ', $title_who) . '<hr></td>
</tr>
</table>' . "\n";
} else {
	$content .= '<table border="0" width="100%">
<tr valign="middle">
	<td align="left" class="embedded">
		<b>Кто онлайн: </b>Нет пользователей за последние 5 минут.<hr>
	</td>
</tr>
</table>' . "\n";
}

$content .= '<table border="0" width="100%">
<tr valign="middle">
	<td colspan="2" align="left" class="embedded"><b>В сети: </b></td>
</tr>' . "\n";

$content .= '<tr>
	<td class="embedded"><img src="pic/info/admin.gif" alt=""></td>
	<td width="90%" class="embedded">Админы: ' . $staff . '</td>
</tr>' . "\n";

$content .= '<tr>
	<td class="embedded"><img src="pic/info/member.gif" alt=""></td>
	<td width="90%" class="embedded">Пользователи: ' . $users . '</td>
</tr>' . "\n";

$content .= '<tr>
	<td class="embedded"><img src="pic/info/guest.gif" alt=""></td>
	<td width="90%" class="embedded">Гости: ' . $guests . '</td>
</tr>' . "\n";

$content .= '<tr>
	<td class="embedded"><img src="pic/info/group.gif" alt=""></td>
	<td width="90%" class="embedded">Всего: ' . $total . '</td>
</tr>
</table>' . "\n";

?>