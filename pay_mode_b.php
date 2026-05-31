<?

require "include/bittorrent.php";

dbconn(false);
loggedinorreturn();

$userid = isset($_GET["userid"]) ? (int)$_GET["userid"] : (int)($_POST["userid"] ?? 0);

if (!is_valid_id($userid)) {
	stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
}

$res = sql_query("SELECT * FROM users WHERE id = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_assoc($res);

if (!$user) {
	stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
}

$vote = (string)($_GET["vote"] ?? $_POST["vote"] ?? "plus");
$vote = $vote === "minus" ? "minus" : "plus";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	kz_reputation_add($userid, $vote, $_POST["description"] ?? "");
	header("Location: userdetails.php?id=" . $userid);
	exit;
}

$profile_class = "u" . (int)$user["class"];
$profile_name = kz_rep_h($user["username"]);
$left_today = kz_reputation_left_today((int)$CURUSER["id"]);
$daily_limit = kz_reputation_daily_limit();
$checked_plus = $vote === "plus" ? " checked" : "";
$checked_minus = $vote === "minus" ? " checked" : "";
$viewer = function_exists('kz_pay_user') ? kz_pay_user((int)$CURUSER['id']) : $CURUSER;
$vote_cost = function_exists('kz_pay_int_setting') ? kz_pay_int_setting('reputation_vote_cost', 1) : 1;
$viewer_votes = function_exists('kz_pay_user_votes_from_array') ? kz_pay_user_votes_from_array($viewer) : 0;

$hide_right_blocks = true;
stdhead("&#1056;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1103; :: " . $user["username"]);

?>
<div class="mn_wrap">
<div class="mn1_menu">
<?= kz_profile_menu_html($user, $viewer ?: $CURUSER) ?>
</div>
<div class="mn1_content">
<div class="bx1 <?= $profile_class ?>"><a href="/userdetails.php?id=<?= $userid ?>" class="<?= $profile_class ?>"><?= $profile_name ?></a><?= function_exists('get_user_icons') ? get_user_icons($user) : '' ?></div>
<div class="bx1">
	<div class="<?= $profile_class ?>"><span class="bulet"></span>&#1054;&#1089;&#1090;&#1072;&#1074;&#1080;&#1090;&#1100; &#1086;&#1090;&#1079;&#1099;&#1074; &#1082; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1080;</div>
	<div class="pad5x5">
		&#1057;&#1091;&#1090;&#1086;&#1095;&#1085;&#1099;&#1081; &#1083;&#1080;&#1084;&#1080;&#1090;: <b><?= $daily_limit ?></b>,
		&#1086;&#1089;&#1090;&#1072;&#1083;&#1086;&#1089;&#1100; &#1089;&#1077;&#1075;&#1086;&#1076;&#1085;&#1103;: <b><?= $left_today ?></b>.
		Стоимость отзыва: <b><?= (int)$vote_cost ?></b> голосов. На Вашем счете: <b><?= (int)$viewer_votes ?></b>.
	</div>
</div>
<div class="bx1_0">
<form method="post" action="pay_mode_b.php">
<input type="hidden" name="userid" value="<?= $userid ?>">
<table class="tables1 w100p">
<tr>
	<td class="w135">&#1054;&#1094;&#1077;&#1085;&#1082;&#1072;</td>
	<td>
		<label><input type="radio" name="vote" value="plus"<?= $checked_plus ?>> <img border="0" src="/pic/plus.gif" alt=""> &#1055;&#1086;&#1074;&#1099;&#1089;&#1080;&#1090;&#1100;</label>
		&nbsp;
		<label><input type="radio" name="vote" value="minus"<?= $checked_minus ?>> <img border="0" src="/pic/minus.gif" alt=""> &#1055;&#1086;&#1085;&#1080;&#1079;&#1080;&#1090;&#1100;</label>
	</td>
</tr>
<tr>
	<td class="w135 top">&#1054;&#1090;&#1079;&#1099;&#1074;</td>
	<td><textarea name="description" rows="6" class="w100p"></textarea></td>
</tr>
<tr><td colspan="2" class="center"><input type="submit" class="buttonS" value="&#1054;&#1089;&#1090;&#1072;&#1074;&#1080;&#1090;&#1100; &#1086;&#1090;&#1079;&#1099;&#1074;"></td></tr>
</table>
</form>
</div>
</div>
<div class="clr"></div>
</div>
<?

stdfoot();

?>
