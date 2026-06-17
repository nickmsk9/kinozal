<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/groupex.php';
require_once __DIR__ . '/include/persons.php';

dbconn(false);
loggedinorreturn();

function bookmarks_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function bookmarks_ensure_schema()
{
	sql_query("
		CREATE TABLE IF NOT EXISTS user_bookmarks (
			id int(10) unsigned NOT NULL auto_increment,
			userid int(10) unsigned NOT NULL default '0',
			target_userid int(10) unsigned NOT NULL default '0',
			added_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY user_target (userid, target_userid),
			KEY target_userid (target_userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS person_bookmarks (
			id int(10) unsigned NOT NULL auto_increment,
			userid int(10) unsigned NOT NULL default '0',
			person_id int(10) unsigned NOT NULL default '0',
			added_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY user_person (userid, person_id),
			KEY person_id (person_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);
}

function bookmarks_tabs($type)
{
	$tabs = array(
		1 => 'Закладки раздач',
		2 => 'Закладки групп',
		3 => 'Пользователи в закладках',
		4 => 'Персоны в закладках',
	);

	$html = '<div class="pad0x0x5x0"><ul class="lis">';
	foreach ($tabs as $id => $title) {
		$html .= '<li' . ($type === $id ? ' class="mn"' : '') . '><a href="/bookmarks.php?type=' . $id . '">' . bookmarks_h($title) . '</a></li>';
	}
	return $html . '</ul></div>';
}

function bookmarks_intro($type, $profileClass)
{
	$titles = array(
		1 => 'Закладки раздач',
		2 => 'Закладки групп',
		3 => 'Пользователи в закладках',
		4 => 'Персоны в закладках',
	);
	$text = array(
		1 => 'Здесь представлены раздачи, которые были добавлены в закладки. Убедительная просьба! Не добавляйте более 10-15 раздач в закладки.',
		2 => 'Здесь представлены группы, которые были добавлены в закладки.',
		3 => 'Здесь представлены пользователи, которых Вы добавили в закладки.',
		4 => 'Здесь представлены персоны, которые были добавлены в закладки.',
	);

	return '<div class="bx1 justify"><span class="' . bookmarks_h($profileClass) . '">' . bookmarks_h($titles[$type]) . '</span> - ' . bookmarks_h($text[$type]) . '</div>';
}

function bookmarks_user_card($row, $viewerId)
{
	$id = (int)$row['id'];
	$username = bookmarks_h($row['username'] ?? 'Пользователь');
	$class = 'u' . (int)($row['class'] ?? UC_USER);
	$avatar = !empty($row['avatar']) ? bookmarks_h($row['avatar']) : '/pic/default_avatar.gif';

	return '<div class="pad5x0x0x5 mn2">'
		. '<table class="tables2 w100p"><tr>'
		. '<td class="w50 top"><img src="' . $avatar . '" class="w50 rot180" alt=""></td>'
		. '<td class="top"><a href="/userdetails.php?id=' . $id . '" class="' . $class . '">' . $username . '</a>' . get_user_icons($row) . '<br>'
		. '<a href="/sendmessage.php?receiver=' . $id . '" class="sba">Сообщ.</a> | '
		. '<a href="/bookmarks.php?type=3&amp;delete=' . $id . '" class="sba" onclick="return confirm(\'Убрать пользователя из закладок?\');">Удалить</a><br>'
		. 'раздач <b>' . (int)($row['torrents_count'] ?? 0) . '</b>, коммент. <b>' . (int)($row['comments_count'] ?? 0) . '</b>'
		. '</td></tr></table></div>';
}

function bookmarks_person_card($row)
{
	$id = (int)$row['id'];
	$name = bookmarks_h($row['name'] ?? '');
	$poster = !empty($row['poster_url']) ? bookmarks_h($row['poster_url']) : '/pic/default_avatar.gif';
	$url = persons_url((string)$row['name'], $id);

	return '<div class="pad5x0x0x5 mn2">'
		. '<table class="tables2 w100p"><tr>'
		. '<td class="w50 top"><img src="' . $poster . '" class="w50 rot180" alt=""></td>'
		. '<td class="top"><a href="' . bookmarks_h($url) . '" class="sba">' . $name . '</a><br>'
		. '<a href="/bookmarks.php?type=4&amp;delete=' . $id . '" class="sba" onclick="return confirm(\'Убрать персону из закладок?\');">Удалить</a><br>'
		. bookmarks_h($row['career'] ?? '')
		. '</td></tr></table></div>';
}

function bookmarks_grid($rows, $callback, $emptyText)
{
	if (!$rows) {
		return '<div class="bx1 justify b">' . bookmarks_h($emptyText) . '</div>';
	}

	$html = '<div class="bx1_0"><table class="tables1 w100p">';
	$i = 0;
	foreach ($rows as $row) {
		if ($i % 2 === 0) {
			$html .= '<tr>';
		}
		$html .= '<td class="w50p top">' . $callback($row) . '</td>';
		if ($i % 2 === 1) {
			$html .= '</tr>';
		}
		$i++;
	}
	if ($i % 2 === 1) {
		$html .= '<td class="w50p top">&nbsp;</td></tr>';
	}

	return $html . '</table></div>';
}

bookmarks_ensure_schema();
groups_ensure_schema();
persons_ensure_schema();

$userId = (int)$CURUSER['id'];
$bookmarkType = (int)($_GET['type'] ?? 1);
if ($bookmarkType < 1 || $bookmarkType > 4) {
	$bookmarkType = 1;
}

if ($bookmarkType === 2) {
	if (isset($_GET['add'])) {
		$groupId = (int)$_GET['add'];
		if (groups_fetch($groupId)) {
			groups_add_bookmark($groupId, $userId);
		}
		header('Location: /bookmarks.php?type=2');
		exit;
	}

	if (isset($_GET['delete'])) {
		groups_remove_bookmark((int)$_GET['delete'], $userId);
		header('Location: /bookmarks.php?type=2');
		exit;
	}
}

if ($bookmarkType === 3) {
	if (isset($_GET['add'])) {
		$targetId = (int)$_GET['add'];
		if (is_valid_id($targetId) && $targetId !== $userId) {
			$exists = sql_query("SELECT id FROM users WHERE id = $targetId LIMIT 1") or sqlerr(__FILE__, __LINE__);
			if (mysqli_num_rows($exists) === 1) {
				sql_query("INSERT IGNORE INTO user_bookmarks (userid, target_userid, added_at) VALUES ($userId, $targetId, NOW())") or sqlerr(__FILE__, __LINE__);
			}
		}
		header('Location: /bookmarks.php?type=3');
		exit;
	}

	if (isset($_GET['delete'])) {
		$targetId = (int)$_GET['delete'];
		sql_query("DELETE FROM user_bookmarks WHERE userid = $userId AND target_userid = $targetId") or sqlerr(__FILE__, __LINE__);
		header('Location: /bookmarks.php?type=3');
		exit;
	}
}

if ($bookmarkType === 4) {
	if (isset($_GET['add'])) {
		$personId = (int)$_GET['add'];
		if (persons_find($personId, '')) {
			sql_query("INSERT IGNORE INTO person_bookmarks (userid, person_id, added_at) VALUES ($userId, $personId, NOW())") or sqlerr(__FILE__, __LINE__);
		}
		header('Location: /bookmarks.php?type=4');
		exit;
	}

	if (isset($_GET['delete'])) {
		$personId = (int)$_GET['delete'];
		sql_query("DELETE FROM person_bookmarks WHERE userid = $userId AND person_id = $personId") or sqlerr(__FILE__, __LINE__);
		header('Location: /bookmarks.php?type=4');
		exit;
	}
}

$profileClass = 'u' . (int)($CURUSER['class'] ?? UC_USER);
$profileName = bookmarks_h($CURUSER['username'] ?? '');
$hide_right_blocks = true;

stdhead('Закладки');
?>
<div class="mn_wrap">
	<div class="mn1_menu">
		<?= profile_menu_html($CURUSER, $CURUSER) ?>
	</div>
	<div class="mn1_content">
		<div class="bx1 <?= $profileClass ?>">
			<a href="/userdetails.php?id=<?= $userId ?>" class="<?= $profileClass ?>"><?= $profileName ?></a>
		</div>

		<?= bookmarks_intro($bookmarkType, $profileClass) ?>
		<?= bookmarks_tabs($bookmarkType) ?>

		<?php
		if ($bookmarkType === 1) {
			$minvotes = isset($minvotes) ? (int)$minvotes : 0;
			$res = sql_query("SELECT COUNT(*) FROM bookmarks WHERE userid = $userId") or sqlerr(__FILE__, __LINE__);
			$row = mysqli_fetch_row($res);
			$count = (int)($row[0] ?? 0);
			$perpage = 25;
			list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, 'bookmarks.php?type=1&amp;');

			if ($count < 1) {
				echo '<div class="bx1 justify b">Нет сделанных закладок</div>';
			} else {
				$res = sql_query("
					SELECT
						b.id AS bookmarkid,
						u.username,
						u.class,
						u.id AS owner,
						t.id,
						t.name,
						t.info_hash,
						t.type,
						t.comments,
						(t.leechers + t.remote_leechers) AS leechers,
						(t.seeders + t.remote_seeders) AS seeders,
						t.multitracker,
						t.last_mt_update,
						IF(t.numratings < $minvotes, NULL, ROUND(t.ratingsum / t.numratings)) AS rating,
						c.name AS cat_name,
						c.image AS cat_pic,
						t.save_as,
						t.numfiles,
						t.added,
						t.filename,
						t.size,
						t.views,
						t.visible,
						t.free,
						t.hits,
						t.times_completed,
						t.category
					FROM bookmarks AS b
					INNER JOIN torrents AS t ON t.id = b.torrentid
					LEFT JOIN users AS u ON u.id = t.owner
					LEFT JOIN categories AS c ON c.id = t.category
					WHERE b.userid = $userId
					ORDER BY t.id DESC
					$limit
				") or sqlerr(__FILE__, __LINE__);

				if ($pagertop) {
					echo '<div class="pad0x0x5x0">' . $pagertop . '</div>';
				}
				echo '<table class="embedded w100p" cellspacing="0" cellpadding="3">';
				torrenttable($res, 'bookmarks');
				echo '</table>';
				if ($pagerbottom) {
					echo '<div class="pad5x5">' . $pagerbottom . '</div>';
				}
			}
		} elseif ($bookmarkType === 2) {
			$res = sql_query("
				SELECT g.*, b.added_at AS bookmark_added
				FROM groupex_bookmarks AS b
				INNER JOIN groupex_groups AS g ON g.id = b.group_id
				WHERE b.userid = $userId
				  AND g.visible = 'yes'
				ORDER BY b.added_at DESC, g.name ASC
			") or sqlerr(__FILE__, __LINE__);
			$groups = array();
			while ($group = mysqli_fetch_assoc($res)) {
				$groups[] = $group;
			}
			if (!$groups) {
				echo '<div class="bx1 justify b">Нет сделанных закладок</div>';
			} else {
				echo '<div class="bx2_0">';
				foreach ($groups as $group) {
					groups_group_card($group);
				}
				echo '</div>';
			}
		} elseif ($bookmarkType === 3) {
			$res = sql_query("
				SELECT
					u.*
				FROM user_bookmarks AS b
				INNER JOIN users AS u ON u.id = b.target_userid
				WHERE b.userid = $userId
				ORDER BY b.added_at DESC, u.username ASC
			") or sqlerr(__FILE__, __LINE__);
			$users = array();
			while ($row = mysqli_fetch_assoc($res)) {
				$users[] = $row;
			}
			if (function_exists('tracker_attach_user_content_counts')) {
				$users = tracker_attach_user_content_counts($users);
			}
			echo bookmarks_grid($users, function ($row) {
				global $userId;
				return bookmarks_user_card($row, $userId);
			}, 'Нет сделанных закладок');
		} else {
			$res = sql_query("
				SELECT p.*
				FROM person_bookmarks AS b
				INNER JOIN persons AS p ON p.id = b.person_id
				WHERE b.userid = $userId
				ORDER BY b.added_at DESC, p.name ASC
			") or sqlerr(__FILE__, __LINE__);
			$persons = array();
			while ($row = mysqli_fetch_assoc($res)) {
				$persons[] = $row;
			}
			echo bookmarks_grid($persons, 'bookmarks_person_card', 'Нет сделанных закладок');
		}
		?>
	</div>
	<div class="clr"></div>
</div>
<?php
stdfoot();

?>
