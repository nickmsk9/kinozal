<?

require_once("include/bittorrent.php");

dbconn(false);
loggedinorreturn();

function myt_h($value) {
	return htmlspecialchars_uni((string)$value);
}

$userid = (int)($_GET['id'] ?? ($_GET['userid'] ?? $CURUSER['id']));

if (!is_valid_id($userid)) {
	stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
}

$res = sql_query("SELECT * FROM users WHERE id = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_assoc($res) or stderr($tracker_lang['error'], $tracker_lang['invalid_id']);

$isOwn = ((int)$CURUSER['id'] === $userid);
$canSeeHidden = $isOwn || get_user_class() >= UC_MODERATOR;
$where = "WHERE t.owner = $userid AND t.banned != 'yes'";

if (!$canSeeHidden) {
	$where .= " AND t.visible = 'yes'";
}

$res = sql_query("SELECT COUNT(*) FROM torrents AS t $where") or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_row($res);
$count = (int)($row[0] ?? 0);

$perpage = 20;
$pagerHref = 'mytorrents.php?id=' . $userid . '&amp;';
list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, $pagerHref);

$torrents = false;
if ($count > 0) {
	$torrents = sql_query("
		SELECT
			t.type,
			t.comments,
			(t.leechers + t.remote_leechers) AS leechers,
			(t.seeders + t.remote_seeders) AS seeders,
			t.multitracker,
			t.last_mt_update,
			IF(t.numratings < $minvotes, NULL, ROUND(t.ratingsum / t.numratings, 1)) AS rating,
			t.id,
			t.owner,
			c.name AS cat_name,
			c.image AS cat_pic,
			t.name,
			t.info_hash,
			t.save_as,
			t.filename,
			t.numfiles,
			t.added,
			t.size,
			t.views,
			t.visible,
			t.free,
			t.hits,
			t.times_completed,
			t.category,
			t.image1
		FROM torrents AS t
		LEFT JOIN categories AS c ON t.category = c.id
		$where
		ORDER BY t.id DESC
		$limit
	") or sqlerr(__FILE__, __LINE__);
}

$profileClass = 'u' . (int)($user['class'] ?? UC_USER);
$pageTitle = $isOwn ? 'Мои раздачи' : 'Раздачи пользователя :: ' . (string)$user['username'];
$hide_right_blocks = true;

stdhead($pageTitle);
?>
<style type="text/css">
.stable {
	float: left;
	margin: 0 5px 5px 0;
	text-align: center;
}
.stable a img {
	border: 0;
	display: block;
	width: 100px;
	height: 150px;
}
</style>

<div class="mn_wrap">
	<div class="mn1_menu">
		<?= function_exists('profile_menu_html') ? profile_menu_html($user, $CURUSER) : '' ?>
	</div>
	<div class="mn1_content">
		<div class="bx1 <?= $profileClass ?>">
			<a href="/userdetails.php?id=<?= $userid ?>" class="<?= $profileClass ?>"><?= myt_h($user['username']) ?></a>
		</div>

		<div class="bx1 justify">
			<b class="<?= $profileClass ?>">Раздачи пользователя</b>
			- В таблице представлен полный список раздач добавленных пользователем. При случае не забудьте поблагодарить пользователя за труд и за предоставленный интересный материал.
		</div>

		<? if ($count < 1) { ?>
			<div class="bx1">Нет залитых в данный момент раздач</div>
		<? } else { ?>
			<? if ($pagertop) { ?>
				<div class="pad0x0x5x0"><?= $pagertop ?></div>
			<? } ?>

			<table class="embedded w100p" cellspacing="0" cellpadding="3">
				<tr>
					<td class="colhead center" colspan="12"><?= $isOwn ? 'Мои раздачи' : 'Раздачи пользователя' ?></td>
				</tr>
				<?
				torrenttable($torrents, "mytorrents");
				?>
			</table>

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
