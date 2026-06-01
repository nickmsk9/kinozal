<?php

require_once("include/bittorrent.php");

dbconn(false);
loggedinorreturn();

function uv_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function uv_rating_average($ratingsum, $numratings)
{
	$numratings = (int)$numratings;
	if ($numratings <= 0) {
		return '0.000';
	}

	return number_format(((float)$ratingsum / $numratings), 3, '.', '');
}

function uv_recount_torrent_rating($torrentid)
{
	$torrentid = (int)$torrentid;
	if ($torrentid <= 0) {
		return;
	}

	$res = sql_query("SELECT COUNT(*) AS votes, COALESCE(SUM(rating), 0) AS sumrating FROM ratings WHERE torrent = $torrentid") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);
	$votes = (int)($row['votes'] ?? 0);
	$sum = (int)($row['sumrating'] ?? 0);

	sql_query("UPDATE torrents SET numratings = $votes, ratingsum = $sum WHERE id = $torrentid") or sqlerr(__FILE__, __LINE__);
}

$userid = (int)($_GET['id'] ?? ($_GET['userid'] ?? 0));

if (!is_valid_id($userid)) {
	stderr($tracker_lang['error'], $tracker_lang['invalid_id'] ?? 'Invalid ID');
}

if ((int)$CURUSER['id'] !== $userid && get_user_class() < UC_MODERATOR) {
	stderr($tracker_lang['error'], 'Нет доступа');
}

$res = sql_query("SELECT * FROM users WHERE id = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_assoc($res) or stderr($tracker_lang['error'], $tracker_lang['invalid_id'] ?? 'Invalid ID');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (int)($_POST['action'] ?? 0) === 1) {
	$ratingIds = array();
	foreach ((array)($_POST['cbox'] ?? array()) as $ratingId) {
		$ratingId = (int)$ratingId;
		if ($ratingId > 0) {
			$ratingIds[] = $ratingId;
		}
	}
	$ratingIds = array_values(array_unique($ratingIds));

	if ($ratingIds) {
		$idList = implode(',', $ratingIds);
		$res = sql_query("SELECT id, torrent FROM ratings WHERE user = $userid AND id IN ($idList)") or sqlerr(__FILE__, __LINE__);
		$deleteIds = array();
		$torrentIds = array();

		while ($row = mysqli_fetch_assoc($res)) {
			$deleteIds[] = (int)$row['id'];
			$torrentIds[] = (int)$row['torrent'];
		}

		if ($deleteIds) {
			sql_query("DELETE FROM ratings WHERE user = $userid AND id IN (" . implode(',', $deleteIds) . ")") or sqlerr(__FILE__, __LINE__);
			foreach (array_unique($torrentIds) as $torrentId) {
				uv_recount_torrent_rating($torrentId);
			}
		}
	}

	header('Location: /uservotes.php?id=' . $userid);
	exit;
}

$res = sql_query("SELECT COUNT(*) FROM ratings WHERE user = $userid") or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_row($res);
$count = (int)($row[0] ?? 0);

$perpage = 25;
$href = 'uservotes.php?id=' . $userid . '&amp;';
list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, $href);

$votes = false;
if ($count > 0) {
	$votes = sql_query("
		SELECT
			r.id AS rating_id,
			r.torrent,
			r.rating,
			r.added,
			t.id AS torrent_id,
			t.name,
			t.free,
			t.numratings,
			t.ratingsum
		FROM ratings AS r
		LEFT JOIN torrents AS t ON t.id = r.torrent
		WHERE r.user = $userid
		ORDER BY r.added DESC, r.id DESC
		$limit
	") or sqlerr(__FILE__, __LINE__);
}

$profileClass = 'u' . (int)($user['class'] ?? UC_USER);
$profileName = uv_h($user['username']);
$hide_right_blocks = true;

stdhead('История голосований');
?>

<div class="mn_wrap">
	<div class="mn1_menu">
		<?= function_exists('profile_menu_html') ? profile_menu_html($user, $CURUSER) : '' ?>
	</div>
	<div class="mn1_content">
		<div class="bx1 <?= $profileClass ?>">
			<a href="/userdetails.php?id=<?= $userid ?>" class="<?= $profileClass ?>"><?= $profileName ?></a><?= function_exists('get_user_icons') ? get_user_icons($user) : '' ?>
		</div>

		<div class="bx1 justify n">
			<b class="<?= $profileClass ?>">История голосований</b>
			- В таблице представлены голосования к раздачам, которые были добавлены. Убедительная просьба ставить релевантные оценки раздаваемому материалу.
		</div>

		<form action="/uservotes.php?id=<?= $userid ?>" method="post">
			<div class="bx1">
				<table class="tables3 w100p">
					<tr>
						<td>Администрирование голосований</td>
						<td class="right"><label for="checkAll">выделить все голосования</label></td>
						<td class="w15"><input id="checkAll" type="checkbox" onclick="checkAllF(1)" value=""></td>
						<td class="right w150">
							<input name="action" type="hidden" value="1">
							<input value="Удалить выбранные" id="Submit" type="submit">
						</td>
					</tr>
				</table>
			</div>

			<?php if (!empty($pagertop) && $count > $perpage) { ?><div class="pad5x5"><?= $pagertop ?></div><?php } ?>

			<?php if ($count < 1 || !$votes || mysqli_num_rows($votes) < 1) { ?>
				<div class="bx1 b">Нет голосований к раздачам</div>
			<?php } else { ?>
				<div class="bx2_0">
					<table class="w100p">
						<?php while ($vote = mysqli_fetch_assoc($votes)) {
							$ratingId = (int)$vote['rating_id'];
							$torrentId = (int)($vote['torrent_id'] ?? $vote['torrent']);
							$torrentName = trim((string)($vote['name'] ?? ''));
							$title = $torrentName !== '' ? uv_h(cut_text($torrentName, 120)) : '[Удалена]';
							$linkClass = 'r0';
							if (($vote['free'] ?? '') === 'yes') {
								$linkClass = 'r1';
							} elseif (($vote['free'] ?? '') === 'silver') {
								$linkClass = 'r2';
							}
							$numratings = (int)($vote['numratings'] ?? 0);
							$ratingsum = (int)($vote['ratingsum'] ?? 0);
							$avg = uv_rating_average($ratingsum, $numratings);
						?>
							<tr class="mn" style="line-height:19px;">
								<td class="w15"><input name="cbox[]" type="checkbox" onclick="checkAllF(2)" value="<?= $ratingId ?>"></td>
								<td>
									<?php if ($torrentId > 0 && $torrentName !== '') { ?>
										<a href="/details.php?id=<?= $torrentId ?>" class="<?= $linkClass ?>"><?= $title ?></a>
									<?php } else { ?>
										<?= $title ?>
									<?php } ?>
								</td>
							</tr>
							<tr class="mn2">
								<td class="pad10x10" colspan="2">
									Оценка раздачи <?= (int)$vote['rating'] ?> из 10 [ Средняя оценка: <?= $avg ?>, всего оценок: <?= $numratings ?> ]
									<span class="floatright"><?= uv_h($vote['added']) ?></span>
								</td>
							</tr>
						<?php } ?>
					</table>
				</div>
			<?php } ?>

			<?php if (!empty($pagerbottom) && $count > $perpage) { ?><div class="pad5x5"><?= $pagerbottom ?></div><?php } ?>
		</form>
	</div>
	<div class="clr"></div>
</div>

<script type="text/javascript">
function checkAllF(ref) {
	var chkAll = document.getElementById('checkAll');
	var checks = document.getElementsByName('cbox[]');
	var allChecked = true;

	for (var i = 0; i < checks.length; i++) {
		if (ref === 1) {
			checks[i].checked = chkAll.checked;
		}
		if (!checks[i].checked) {
			allChecked = false;
		}
	}

	if (ref !== 1) {
		chkAll.checked = allChecked;
	}
}
</script>
<?php
stdfoot();

?>
