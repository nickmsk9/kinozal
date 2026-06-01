<?

require_once("include/bittorrent.php");

dbconn(false);
loggedinorreturn();

function hyt_h($value) {
	return htmlspecialchars_uni((string)$value);
}

function hyt_date($value) {
	$value = (string)$value;
	if ($value === '' || $value === '0000-00-00 00:00:00') {
		return '-';
	}

	$stamp = strtotime($value);
	if (!$stamp) {
		return hyt_h($value);
	}

	return date('d.m.Y', $stamp);
}

$userid = (int)($_GET['id'] ?? ($_GET['userid'] ?? $CURUSER['id']));

if (!is_valid_id($userid)) {
	stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
}

$res = sql_query("SELECT * FROM users WHERE id = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_assoc($res) or stderr($tracker_lang['error'], $tracker_lang['invalid_id']);

$isOwn = ((int)$CURUSER['id'] === $userid);
if (!$isOwn && get_user_class() < UC_MODERATOR) {
	stderr($tracker_lang['error'], 'Нет доступа');
}

$where = "WHERE s.userid = $userid AND s.finished = 'yes' AND t.id IS NOT NULL";
if (get_user_class() < UC_MODERATOR) {
	$where .= " AND t.visible = 'yes' AND t.banned != 'yes'";
}

$res = sql_query("
	SELECT COUNT(*)
	FROM snatched AS s
	INNER JOIN torrents AS t ON t.id = s.torrent
	$where
") or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_row($res);
$count = (int)($row[0] ?? 0);

$perpage = 25;
$pagerHref = 'hytorrents.php?id=' . $userid . '&amp;';
list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, $pagerHref);

$torrents = false;
if ($count > 0) {
	$torrents = sql_query("
		SELECT
			t.id,
			t.category,
			t.name,
			t.comments,
			t.size,
			t.hits,
			t.times_completed,
			(t.seeders + t.remote_seeders) AS seeders,
			(t.leechers + t.remote_leechers) AS leechers,
			t.added,
			t.free,
			c.name AS cat_name,
			c.image AS cat_pic,
			s.completedat,
			s.last_action
		FROM snatched AS s
		INNER JOIN torrents AS t ON t.id = s.torrent
		LEFT JOIN categories AS c ON c.id = t.category
		$where
		ORDER BY s.completedat DESC, s.last_action DESC, s.id DESC
		$limit
	") or sqlerr(__FILE__, __LINE__);
}

$profileClass = 'u' . (int)($user['class'] ?? UC_USER);
$hide_right_blocks = true;

stdhead('История скачанного');
?>

<div class="mn_wrap">
	<div class="mn1_menu">
		<?= function_exists('profile_menu_html') ? profile_menu_html($user, $CURUSER) : '' ?>
	</div>
	<div class="mn1_content">
		<div class="bx1 <?= $profileClass ?>">
			<a href="/userdetails.php?id=<?= $userid ?>" class="<?= $profileClass ?>"><?= hyt_h($user['username']) ?></a>
		</div>

		<div class="bx1 justify">
			<b class="<?= $profileClass ?>">История скачанного</b>
			- В таблице представлены раздачи, которые были скачаны. Если в списке присутствуют раздачи, которые Вы не скачивали, то смените PassKey в настройках профиля.
		</div>

		<? if ($count < 1) { ?>
			<div class="bx1">История скачанного пуста</div>
		<? } else { ?>
			<? if ($pagertop) { ?>
				<div class="pad0x0x5x0"><?= $pagertop ?></div>
			<? } ?>

			<div class="bx2_0">
				<table class="t_peer w100p">
					<tr class="mn">
						<td class="z w90"></td>
						<td class="w90p"></td>
						<td class="z">Комм.</td>
						<td class="z">Размер</td>
						<td class="z">Скач.</td>
						<td class="z">Сидов</td>
						<td class="z">Пиров</td>
						<td class="z">Залит</td>
					</tr>
					<?
					$rowIndex = 0;
					while ($torrent = mysqli_fetch_assoc($torrents)) {
						$title = hyt_h($torrent['name']);
						$catPic = !empty($torrent['cat_pic']) ? hyt_h($torrent['cat_pic']) : '';
						$catName = !empty($torrent['cat_name']) ? hyt_h($torrent['cat_name']) : '';
						$linkClass = 'r0';
						if ($torrent['free'] === 'yes') {
							$linkClass = 'r1';
						} elseif ($torrent['free'] === 'silver') {
							$linkClass = 'r2';
						}
					?>
						<tr class="<?= $rowIndex === 0 ? 'first bg' : 'bg' ?>">
							<td class="bt">
								<? if ($catPic !== '') { ?>
									<img src="pic/cat/<?= $catPic ?>" class="p90x32 pointer" onclick="cat(<?= (int)$torrent['category'] ?>);" alt="<?= $catName ?>">
								<? } ?>
							</td>
							<td class="nam"><a href="details.php?id=<?= (int)$torrent['id'] ?>&amp;hit=1" class="<?= $linkClass ?>"><?= $title ?></a></td>
							<td class="s"><?= (int)$torrent['comments'] ?></td>
							<td class="s"><?= mksize((int)$torrent['size']) ?></td>
							<td class="s"><?= (int)$torrent['times_completed'] ?></td>
							<td class="sl_s"><?= (int)$torrent['seeders'] ?></td>
							<td class="sl_p"><?= (int)$torrent['leechers'] ?></td>
							<td class="s"><?= hyt_date($torrent['added']) ?></td>
						</tr>
					<?
						$rowIndex++;
					}
					?>
				</table>
			</div>

			<? if ($pagerbottom) { ?>
				<div class="pad5x5"><?= $pagerbottom ?></div>
			<? } ?>
		<? } ?>
	</div>
	<div class="clr"></div>
</div>
<?
stdfoot();

?>
