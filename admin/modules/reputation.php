<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

if (!function_exists('ReputationAdmin')) {
	function ReputationAdmin()
	{
		global $admin_file;

		kz_reputation_install_schema();

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_reputation_settings'])) {
			$daily = max(0, (int)($_POST['reputation_daily_limit'] ?? 1));
			$signup = max(0, (int)($_POST['reputation_signup_value'] ?? 1));

			kz_reputation_set_setting('reputation_daily_limit', $daily);
			kz_reputation_set_setting('reputation_signup_value', $signup);

			stdmsg(
				'&#1056;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1103;',
				'&#1053;&#1072;&#1089;&#1090;&#1088;&#1086;&#1081;&#1082;&#1080; &#1089;&#1086;&#1093;&#1088;&#1072;&#1085;&#1077;&#1085;&#1099;.'
			);
		}

		$daily = kz_reputation_daily_limit();
		$signup = kz_reputation_signup_value();

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>&#1056;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1103;</b></div>';
		echo '<div class="tp1_body">';
		echo '<form method="post" action="' . htmlspecialchars_uni($admin_file) . '.php?op=ReputationAdmin">';
		echo '<input type="hidden" name="save_reputation_settings" value="1">';
		echo '<table class="tables2 w100p">';
		echo '<tr><td class="w250">&#1054;&#1090;&#1079;&#1099;&#1074;&#1086;&#1074; &#1074; &#1089;&#1091;&#1090;&#1082;&#1080; &#1085;&#1072; &#1087;&#1086;&#1083;&#1100;&#1079;&#1086;&#1074;&#1072;&#1090;&#1077;&#1083;&#1103;</td><td><input type="text" name="reputation_daily_limit" value="' . (int)$daily . '" size="8"></td></tr>';
		echo '<tr><td>&#1053;&#1072;&#1095;&#1072;&#1083;&#1100;&#1085;&#1072;&#1103; &#1088;&#1077;&#1087;&#1091;&#1090;&#1072;&#1094;&#1080;&#1103; &#1087;&#1088;&#1080; &#1088;&#1077;&#1075;&#1080;&#1089;&#1090;&#1088;&#1072;&#1094;&#1080;&#1080;</td><td><input type="text" name="reputation_signup_value" value="' . (int)$signup . '" size="8"></td></tr>';
		echo '<tr><td colspan="2" class="center"><input type="submit" class="buttonS" value="&#1057;&#1086;&#1093;&#1088;&#1072;&#1085;&#1080;&#1090;&#1100;"></td></tr>';
		echo '</table>';
		echo '</form>';
		echo '</div>';
		echo '</div>';
	}
}

switch ($op) {
	case 'ReputationAdmin':
		ReputationAdmin();
		break;
}

?>
