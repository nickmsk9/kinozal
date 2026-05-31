<?

require "include/bittorrent.php";

dbconn(false);
loggedinorreturn();

$userid = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$type = isset($_GET["type"]) ? (int)$_GET["type"] : 1;
$type = $type === 2 ? 2 : 1;

if (!is_valid_id($userid)) {
	stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
}

$res = sql_query("SELECT * FROM users WHERE id = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_assoc($res);

if (!$user) {
	stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
}

$profile_class = "u" . (int)$user["class"];
$profile_name = kz_rep_h($user["username"]);
$title = $type === 2
	? "&#1054;&#1090;&#1076;&#1072;&#1085;&#1085;&#1099;&#1077; &#1086;&#1090;&#1079;&#1099;&#1074;&#1099;"
	: "&#1055;&#1086;&#1083;&#1091;&#1095;&#1077;&#1085;&#1085;&#1099;&#1077; &#1086;&#1090;&#1079;&#1099;&#1074;&#1099;";

$count = kz_reputation_count($userid, $type);
$perpage = 30;
$href = "user_reputation.php?id=" . $userid . ($type === 2 ? "&amp;type=2&amp;" : "&amp;");
list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, $href, array('lastpagedefault' => 0));

$limitNumber = 0;
$offsetNumber = 0;
if (preg_match('/LIMIT\s+([0-9]+),([0-9]+)/i', $limit, $m)) {
	$offsetNumber = (int)$m[1];
	$limitNumber = (int)$m[2];
}

$rows = kz_reputation_rows($userid, $type, 0);
if ($limitNumber > 0) {
	$rows = array_slice($rows, $offsetNumber, $limitNumber);
}

$hide_right_blocks = true;
stdhead("&#1056;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1103; :: " . $user["username"]);

?>
<div class="mn_wrap">
	<div class="mn1_menu">
		<?= kz_profile_menu_html($user, $CURUSER) ?>
	</div>
	<div class="mn1_content">
		<div class="bx1 <?= $profile_class ?>">
			<a href="/userdetails.php?id=<?= $userid ?>" class="<?= $profile_class ?>"><?= $profile_name ?></a><?= function_exists('get_user_icons') ? get_user_icons($user) : '' ?>
		</div>

		<div class="bx1 justify">
			<span class="<?= $profile_class ?>"><?= $title ?></span>
			- &#1047;&#1076;&#1077;&#1089;&#1100; &#1087;&#1088;&#1077;&#1076;&#1089;&#1090;&#1072;&#1074;&#1083;&#1077;&#1085;&#1099; &#1086;&#1090;&#1079;&#1099;&#1074;&#1099; &#1082; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1080;. &#1056;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1103; &#1087;&#1086;&#1083;&#1100;&#1079;&#1086;&#1074;&#1072;&#1090;&#1077;&#1083;&#1103; <a href="/userdetails.php?id=<?= $userid ?>" class="<?= $profile_class ?>"><?= $profile_name ?></a> &#1088;&#1072;&#1074;&#1085;&#1072; <b><?= (int)$user["simpaty"] ?></b>. &#1042;&#1099; &#1084;&#1086;&#1078;&#1077;&#1090;&#1077; <a href="/pay_mode_b.php?userid=<?= $userid ?>" class="sbab">&#1086;&#1089;&#1090;&#1072;&#1074;&#1080;&#1090;&#1100; &#1086;&#1090;&#1079;&#1099;&#1074;</a> &#1080; &#1087;&#1086;&#1074;&#1083;&#1080;&#1103;&#1090;&#1100; &#1085;&#1072; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1102;.
		</div>

		<div class="pad0x0x5x0">
			<ul class="lis">
				<li<?= $type === 1 ? ' class="mn"' : '' ?>><a href="/user_reputation.php?id=<?= $userid ?>">&#1055;&#1086;&#1083;&#1091;&#1095;&#1077;&#1085;&#1085;&#1099;&#1077; &#1086;&#1090;&#1079;&#1099;&#1074;&#1099;</a></li>
				<li<?= $type === 2 ? ' class="mn"' : '' ?>><a href="/user_reputation.php?id=<?= $userid ?>&amp;type=2">&#1054;&#1090;&#1076;&#1072;&#1085;&#1085;&#1099;&#1077; &#1086;&#1090;&#1079;&#1099;&#1074;&#1099;</a></li>
			</ul>
		</div>

		<? if (!empty($pagertop) && $count > $perpage) { ?><div class="pad5x5"><?= $pagertop ?></div><? } ?>
		<?= kz_reputation_table_html($rows, $profile_class, $type, false) ?>
		<? if (!$rows) { ?><div class="bx1 center">&#1054;&#1090;&#1079;&#1099;&#1074;&#1086;&#1074; &#1087;&#1086;&#1082;&#1072; &#1085;&#1077;&#1090;.</div><? } ?>
		<? if (!empty($pagerbottom) && $count > $perpage) { ?><div class="pad5x5"><?= $pagerbottom ?></div><? } ?>
	</div>
	<div class="clr"></div>
</div>
<?

stdfoot();

?>
