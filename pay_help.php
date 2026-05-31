<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/kz_pay.php';

dbconn(false);
loggedinorreturn();
kz_pay_ensure_schema();

if ((string)($_GET['action'] ?? '') === 'getch') {
	$tab = max(1, min(2, (int)($_GET['tabch'] ?? 2)));
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && kz_pay_setting('chat_enabled', '1') === '1') {
		kz_pay_add_chat_message($tab, $_POST['t'] ?? '');
	}
	header('Content-Type: text/html; charset=' . ($tracker_lang['language_charset'] ?? 'UTF-8'));
	echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><link rel="stylesheet" href="./themes/TBDev/TBDev.css" type="text/css"></head><body style="background:#fff;">';
	echo kz_pay_chat_html($tab, (int)($_GET['imes'] ?? 50));
	echo '</body></html>';
	exit;
}

$user = kz_pay_user((int)$CURUSER['id']);

$hide_right_blocks = true;
stdhead('Техподдержка');

?>
<?php kz_pay_layout_start('help', $user); ?>

<div class="bx1 justify">
	Если у Вас возникли проблемы с обменом бонусов, голосами или действиями в разделе Меценатов, сообщите нам об этом. При отправке сообщения указывайте подробности: что нажимали, какая сумма бонусов была на счете и какая ошибка появилась.
</div>

<?php if (kz_pay_setting('chat_enabled', '1') === '1') { ?>
	<?php kz_pay_chat_frame('/pay_help.php', 2); ?>
<?php } else { ?>
	<div class="bx1 center">Чат техподдержки временно отключен.</div>
<?php } ?>

<?php kz_pay_layout_end(array('/pay_help.php%', '%/pay_help.php%')); ?>
<?php

stdfoot();

?>
