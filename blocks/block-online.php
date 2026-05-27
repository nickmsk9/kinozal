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

$dt = time() - 300;

// Единый запрос: последний зарегистрированный + текущие онлайн
if (!empty($use_sessions)) {
	$sql_online = "SELECT 1 AS sort_order, s.uid AS id, s.username, s.class
	               FROM sessions AS s
	               WHERE s.time > " . sqlesc($dt);
} else {
	$sql_online = "SELECT 1 AS sort_order, u.id, u.username, u.class
	               FROM users AS u
	               WHERE u.last_access > " . sqlesc(get_date_time($dt));
}

$sql_latest = "SELECT 0 AS sort_order, id, username, -1 AS class
               FROM users
               WHERE status = 'confirmed'
               ORDER BY id DESC
               LIMIT 1";

$full_sql = "($sql_latest) UNION ALL ($sql_online) ORDER BY sort_order, class DESC";
$result   = sql_query($full_sql);

$users        = 0;
$guests       = 0;
$staff        = 0;
$total        = 0;
$title_who    = [];
$parsed_ids   = [];
$parsed_names = [];
$latestuser   = 'Нет пользователей';

if ($result && mysqli_num_rows($result) > 0) {
	$row = mysqli_fetch_assoc($result);

	// Если первая строка — последний пользователь
	if ((int)$row['sort_order'] === 0) {
		$user_id  = (int)$row['id'];
		$username = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');

		if (!empty($CURUSER)) {
			$latestuser = '<a href="userdetails.php?id=' . $user_id . '" class="online">' . $username . '</a>';
		} else {
			$latestuser = $username;
		}
		$row = mysqli_fetch_assoc($result);
	}

	// Обработка онлайн-пользователей
	while ($row) {
		if ((int)$row['sort_order'] !== 1) {
			break;
		}

		$uid   = isset($row['id']) ? (int)$row['id'] : 0;
		$uname = isset($row['username']) ? trim($row['username']) : '';
		$class = isset($row['class']) ? (int)$row['class'] : 0;

		$is_guest = ($uid <= 0 || $uname === '');

		if ($is_guest) {
			$guests++;
			$total++;
			$row = mysqli_fetch_assoc($result);
			continue;
		}

		if (in_array($uid, $parsed_ids, true)) {
			$row = mysqli_fetch_assoc($result);
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
			$title_who[]    = '<a href="userdetails.php?id=' . $uid . '" class="online">'
				. get_user_class_color($class, htmlspecialchars($uname, ENT_QUOTES, 'UTF-8'))
				. '</a>';
		}

		$row = mysqli_fetch_assoc($result);
	}
}

// Вывод без изменений
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