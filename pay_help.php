<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/pay.php';

dbconn(false);
loggedinorreturn();
pay_ensure_schema();

if ((string)($_GET['action'] ?? '') === 'getch') {
	$tab = max(1, min(2, (int)($_GET['tabch'] ?? 2)));
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && pay_setting('chat_enabled', '1') === '1') {
		pay_add_chat_message($tab, $_POST['t'] ?? '');
	}
	header('Content-Type: text/html; charset=' . ($tracker_lang['language_charset'] ?? 'UTF-8'));
	$theme = select_theme();
	$theme_path = './themes/' . rawurlencode($theme) . '/';
	echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
	if (file_exists(__DIR__ . '/themes/' . $theme . '/engine.css')) {
		echo '<link rel="stylesheet" href="' . $theme_path . 'engine.css" type="text/css">';
	}
	echo '<link rel="stylesheet" href="' . $theme_path . 'TBDev.css" type="text/css"></head><body style="background:#fff;">';
	echo pay_chat_html($tab, (int)($_GET['imes'] ?? 50));
	echo '</body></html>';
	exit;
}

$user = pay_user((int)$CURUSER['id']);

$hide_right_blocks = true;
stdhead('Техподдержка');

?>
<?php pay_layout_start('help', $user); ?>

<div class="bx1 justify">
	Если у Вас возникли проблемы с обменом бонусов, голосами или действиями в разделе Меценатов, сообщите нам об этом. При отправке сообщения указывайте подробности: что нажимали, какая сумма бонусов была на счете и какая ошибка появилась.
</div>

<?php if (pay_setting('chat_enabled', '1') === '1') { ?>
	<?php pay_chat_frame('/pay_help.php', 2); ?>
<?php } else { ?>
	<div class="bx1 center">Чат техподдержки временно отключен.</div>
<?php } ?>

<?php pay_layout_end(array('/pay_help.php%', '%/pay_help.php%')); ?>
<?php

stdfoot();

?>
