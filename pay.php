<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/pay.php';

dbconn(false);
loggedinorreturn();
pay_ensure_schema();

if ((string)($_GET['action'] ?? '') === 'getch') {
	$tab = max(1, min(2, (int)($_GET['tabch'] ?? 1)));
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && pay_setting('chat_enabled', '1') === '1') {
		pay_add_chat_message($tab, $_POST['t'] ?? '');
	}
	header('Content-Type: text/html; charset=' . ($tracker_lang['language_charset'] ?? 'UTF-8'));
	echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><link rel="stylesheet" href="./themes/TBDev/TBDev.css" type="text/css"></head><body style="background:#fff;">';
	echo pay_chat_html($tab, (int)($_GET['imes'] ?? 50));
	echo '</body></html>';
	exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exchange'])) {
	$options = pay_exchange_options();
	$idx = (int)($_POST['exchange'] ?? -1);
	if (isset($options[$idx])) {
		$opt = $options[$idx];
		$error = pay_exchange_bonus((int)$CURUSER['id'], (float)$opt['bonus'], (int)$opt['votes'], $opt['title']);
		header('Location: /pay.php?' . ($error === '' ? 'ok=1' : 'error=' . urlencode($error)));
		exit;
	}
	header('Location: /pay.php?error=' . urlencode('Выбранный обмен не найден.'));
	exit;
}

if (isset($_GET['ok'])) {
	$message = '<div class="success"><b>Голоса начислены.</b><br>Спасибо за поддержку проекта.</div>';
} elseif (isset($_GET['error'])) {
	$message = '<div class="error"><b>Ошибка</b><br>' . pay_h($_GET['error']) . '</div>';
}

$user = pay_user((int)$CURUSER['id']);
$votes = pay_user_votes_from_array($user);
$bonus = (float)($user['bonus'] ?? 0);
$options = pay_exchange_options();

$hide_right_blocks = true;
stdhead('Голоса и рейтинг');

?>
<style type="text/css">
table.smstable {
	border-width: 1px;
	border-color: #fafafa;
	border-collapse: collapse;
}
table.smstable th {
	border-width: 1px;
	padding: 2px;
	border-style: solid;
	border-color: #fafafa;
	background-color: #e0ddcf;
}
table.smstable td {
	border-width: 1px;
	padding: 2px;
	border-style: solid;
	border-color: #fafafa;
	background-color: #efede6;
}
</style>
<?php pay_layout_start('pay', $user); ?>
<?= $message ?>

<div class="bx1 justify">
	На Вашем счете <b><?= (int)$votes ?> голосов</b> и <b><?= number_format($bonus, 2, '.', ' ') ?> бонусов</b>.
	Голоса теперь получаются не через платежные системы, а через обмен бонусов из раздела <a href="/mybonus.php" class="sbab">Мой бонус</a>.
	С помощью голосов можно оставлять отзывы в профилях пользователей, писать пожелания проекту, получать статус Меценат и управлять счетчиками.
</div>

<form action="/pay.php" method="post">
	<div class="bx1">
		<div class="justify">
			<b>Обмен бонусов на голоса</b> - выберите подходящий вариант обмена. Бонусы будут списаны с Вашего бонусного счета, голоса сразу появятся в профиле.
		</div>
		<div class="clr"></div>
		<table class="smstable w100p">
			<tr>
				<th class="w40">Выбор</th>
				<th>Пакет</th>
				<th class="w120">Стоимость</th>
				<th class="w120">Голоса</th>
			</tr>
			<?php foreach ($options as $idx => $opt) { ?>
				<tr>
					<td class="center"><input type="radio" name="exchange" value="<?= (int)$idx ?>"<?= $idx === 0 ? ' checked' : '' ?><?= $bonus < (float)$opt['bonus'] ? ' disabled' : '' ?>></td>
					<td><?= pay_h($opt['title']) ?></td>
					<td class="center"><?= number_format((float)$opt['bonus'], 2, '.', ' ') ?> бонусов</td>
					<td class="center"><?= (int)$opt['votes'] ?></td>
				</tr>
			<?php } ?>
			<?php if (!$options) { ?>
				<tr><td colspan="4" class="center">Пакеты обмена не настроены.</td></tr>
			<?php } ?>
		</table>
		<div class="pad5x5"><input class="buttonS w200" type="submit" value="Получить голоса"></div>
	</div>
</form>

<div class="bx1">
	<div>Сейчас помогли проекту - Благодарим за помощь!</div>
	<div class="bx5x5"><?= pay_user_list_html(pay_recent_helpers(20), 'пока нет операций') ?></div>
</div>

<div class="bx1">
	<div>Ваша помощь проекту за последнее время.</div>
	<div class="pad5x5"><?= pay_format_transaction_rows(pay_user_transactions((int)$CURUSER['id'], 20)) ?></div>
</div>

<div class="bx1">
	<div>Претенденты на Кубок Активный Меценат<i class="i1 cb6"></i></div>
	<div class="bx5x5"><?= pay_user_list_html(pay_top_helpers('active', 20), 'пока нет претендентов') ?></div>
</div>

<div class="bx1">
	<div>Претенденты на Кубок Лучший Меценат<i class="i1 cb7"></i></div>
	<div class="bx5x5"><?= pay_user_list_html(pay_top_helpers('votes', 20), 'пока нет претендентов') ?></div>
</div>

<?php if (pay_setting('chat_enabled', '1') === '1') { ?>
	<?php pay_chat_frame('/pay.php', 1); ?>
<?php } ?>

<?php pay_layout_end(array('/pay.php%', '%/pay.php%')); ?>
<?php

stdfoot();

?>
