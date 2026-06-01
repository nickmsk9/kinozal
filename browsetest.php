<?php

require_once("include/bittorrent.php");
require_once("include/kz_test_torrents.php");
require_once("include/kz_multitracker.php");

dbconn(false);
parked();
kz_test_torrents_ensure_schema();
kz_mt_ensure_schema();

function browsetest_fmt_added($datetime)
{
	if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
		return 'неизвестно';
	}

	$ts = strtotime($datetime);
	if (!$ts) {
		return kz_test_torrents_h($datetime);
	}

	if (date('Y-m-d', $ts) === date('Y-m-d')) {
		return 'сегодня в ' . date('H:i', $ts);
	}

	if (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) {
		return 'вчера в ' . date('H:i', $ts);
	}

	return date('d.m.Y в H:i', $ts);
}

function browsetest_redirect()
{
	header('Location: browsetest.php');
	exit;
}

$can_manage_tests = kz_test_torrents_can_manage();
$current_user_id = !empty($CURUSER['id']) ? (int)$CURUSER['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	loggedinorreturn();

	$action = isset($_POST['action']) ? (string)$_POST['action'] : '';
	$torrent_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

	if ($torrent_id <= 0 || !$can_manage_tests) {
		browsetest_redirect();
	}

	if ($action === 'approve') {
		sql_query("
			UPDATE torrents
			SET is_test = 'no',
				test_approved_at = NOW(),
				test_approved_by = " . (int)$CURUSER['id'] . ",
				test_helper_user_id = 0,
				test_helper_until = NULL,
				moderated = 'yes',
				moderatedby = " . (int)$CURUSER['id'] . ",
				added = NOW(),
				last_action = NOW()
			WHERE id = $torrent_id
			  AND is_test = 'yes'
		") or sqlerr(__FILE__, __LINE__);
		browsetest_redirect();
	}

	if ($action === 'help') {
		sql_query("
			UPDATE torrents
			SET test_helper_user_id = " . (int)$CURUSER['id'] . ",
				test_helper_until = DATE_ADD(NOW(), INTERVAL 60 MINUTE)
			WHERE id = $torrent_id
			  AND is_test = 'yes'
			  AND (
				  test_helper_user_id = 0
				  OR test_helper_until IS NULL
				  OR test_helper_until <= NOW()
				  OR test_helper_user_id = " . (int)$CURUSER['id'] . "
			  )
		") or sqlerr(__FILE__, __LINE__);
		browsetest_redirect();
	}

	if ($action === 'unhelp') {
		$extra = get_user_class() >= UC_MODERATOR ? '' : ' AND test_helper_user_id = ' . (int)$CURUSER['id'];
		sql_query("
			UPDATE torrents
			SET test_helper_user_id = 0,
				test_helper_until = NULL
			WHERE id = $torrent_id
			  AND is_test = 'yes'
			  $extra
		") or sqlerr(__FILE__, __LINE__);
		browsetest_redirect();
	}

	browsetest_redirect();
}

$where = "WHERE t.visible = 'yes' AND t.banned != 'yes' AND t.is_test = 'yes'";
$orderby = "ORDER BY t.added DESC, t.id DESC";

$res = sql_query("SELECT COUNT(*) FROM torrents AS t $where") or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_array($res);
$num_torrents = (int)$row[0];

$torrentsperpage = !empty($CURUSER["torrentsperpage"]) ? (int)$CURUSER["torrentsperpage"] : 25;

if ($num_torrents) {
	list($pagertop, $pagerbottom, $limit) = pager($torrentsperpage, $num_torrents, "browsetest.php?");
	$query = "
		SELECT
			t.id,
			t.category,
			(t.leechers + t.remote_leechers) AS leechers,
			(t.seeders + t.remote_seeders) AS seeders,
			t.free,
			t.name,
			t.size,
			t.added,
			t.comments,
			t.owner,
			t.test_helper_user_id,
			t.test_helper_until,
			c.name AS cat_name,
			c.image AS cat_pic,
			u.username,
			u.class,
			hu.username AS helper_username,
			hu.class AS helper_class
		FROM torrents AS t
		LEFT JOIN categories AS c ON t.category = c.id
		LEFT JOIN users AS u ON t.owner = u.id
		LEFT JOIN users AS hu ON hu.id = t.test_helper_user_id AND t.test_helper_until > NOW()
		$where
		$orderby
		$limit
	";
	$torrents = sql_query($query) or sqlerr(__FILE__, __LINE__);
}

$hide_right_blocks = true;
stdhead('Тестовые раздачи');

?>
<div class="bx1_0">
	<span class="u8">Тестовые раздачи</span> - Это раздачи, которые предоставлены пользователями и нуждаются в оформлении.
	Если у Вас есть чем поделиться, Вы тоже можете <a href="upload.php?test=1" class="sba">залить раздачу</a>.
	Школа Кинооператоров <a href="testhelp.php" class="sba">здесь</a>
</div>

<div class="bx2_0">
	<table class="t_peer w100p">
		<tr class="mn">
			<td class="z w90"></td>
			<td class="w90p"></td>
			<td class="z">Помогает</td>
			<td class="z">Комм.</td>
			<td class="z">Размер</td>
			<td class="z">Сидов</td>
			<td class="z">Пиров</td>
			<td class="z">Залит</td>
			<td class="zl">Раздает</td>
		</tr>
		<?php if ($num_torrents && isset($torrents)) { ?>
			<?php $rowIndex = 0; ?>
			<?php while ($row = mysqli_fetch_assoc($torrents)) { ?>
				<?php
				$id = (int)$row['id'];
				$title = kz_test_torrents_h($row['name']);
				$catPic = !empty($row['cat_pic']) ? kz_test_torrents_h($row['cat_pic']) : '';
				$catName = !empty($row['cat_name']) ? kz_test_torrents_h($row['cat_name']) : '';
				$comments = (int)$row['comments'];
				$sizeText = mksize((int)$row['size']);
				$seeders = (int)$row['seeders'];
				$leechers = (int)$row['leechers'];
				$addedText = browsetest_fmt_added($row['added']);
				$helperId = !empty($row['helper_username']) ? (int)$row['test_helper_user_id'] : 0;
				$linkClass = 'r0';
				if ($row['free'] === 'yes') {
					$linkClass = 'r1';
				} elseif ($row['free'] === 'silver') {
					$linkClass = 'r2';
				}
				?>
				<tr class="<?= $rowIndex === 0 ? 'first bg' : 'bg' ?>">
					<td class="bt">
						<?php if ($catPic !== '') { ?>
							<img src="pic/cat/<?= $catPic ?>" class="p90x32 pointer" onclick="cat(<?= (int)$row['category'] ?>);" alt="<?= $catName ?>">
						<?php } ?>
					</td>
					<td class="nam">
						<a href="details.php?id=<?= $id ?>&amp;hit=1" class="<?= $linkClass ?>"><?= $title ?></a>
						<?php if ($can_manage_tests) { ?>
							<form method="post" action="browsetest.php" style="display:inline;">
								<input type="hidden" name="id" value="<?= $id ?>">
								<input type="hidden" name="action" value="approve">
								<input type="submit" class="buttonS" value="Одобрить">
							</form>
						<?php } ?>
					</td>
					<td class="s">
						<?php if (!empty($row['helper_username'])) { ?>
							<a href="userdetails.php?id=<?= $helperId ?>" class="u<?= (int)$row['helper_class'] ?>"><?= kz_test_torrents_h($row['helper_username']) ?></a>
						<?php } ?>
						<?php if ($can_manage_tests) { ?>
							<?php if ($helperId === 0 || $helperId === $current_user_id) { ?>
								<form method="post" action="browsetest.php" style="display:inline;">
									<input type="hidden" name="id" value="<?= $id ?>">
									<input type="hidden" name="action" value="help">
									<input type="submit" class="buttonS" value="<?= $helperId === $current_user_id ? 'Продлить' : 'Помогу' ?>">
								</form>
							<?php } ?>
							<?php if ($helperId === $current_user_id || ($helperId > 0 && get_user_class() >= UC_MODERATOR)) { ?>
								<form method="post" action="browsetest.php" style="display:inline;">
									<input type="hidden" name="id" value="<?= $id ?>">
									<input type="hidden" name="action" value="unhelp">
									<input type="submit" class="buttonS" value="Снять">
								</form>
							<?php } ?>
						<?php } ?>
					</td>
					<td class="s"><?= $comments ?></td>
					<td class="s"><?= $sizeText ?></td>
					<td class="sl_s"><?= $seeders ?></td>
					<td class="sl_p"><?= $leechers ?></td>
					<td class="s"><?= $addedText ?></td>
					<td class="sl">
						<?php if (!empty($row['username'])) { ?>
							<a href="userdetails.php?id=<?= (int)$row['owner'] ?>" class="u<?= (int)$row['class'] ?>"><?= kz_test_torrents_h($row['username']) ?></a>
						<?php } else { ?>
							<i>(unknown)</i>
						<?php } ?>
					</td>
				</tr>
				<?php $rowIndex++; ?>
			<?php } ?>
		<?php } else { ?>
			<tr>
				<td colspan="9" class="center" style="padding:18px 8px;">Тестовые раздачи не найдены.</td>
			</tr>
		<?php } ?>
	</table>
</div>

<?php if ($num_torrents && isset($pagerbottom) && $pagerbottom) { ?>
	<div class="small" style="padding:6px 0 0 0;"><?= $pagerbottom ?></div>
<?php } ?>

<?= kz_page_online_box(array('/browsetest.php%', '%/browsetest.php%'), 'пока никого') ?>

<?php
stdfoot();
?>
