<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/kz_pay.php';

dbconn(false);
loggedinorreturn();
kz_pay_ensure_schema();

function pay_mode_redirect($message, $is_error = false)
{
	header('Location: /pay_mode.php?' . ($is_error ? 'error=' : 'ok=') . urlencode($message));
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$act = (string)($_POST['act'] ?? '');
	$userid = (int)$CURUSER['id'];

	if ($act === 'maecenas') {
		$cost = kz_pay_int_setting('donor_cost', 75);
		if (!kz_pay_charge_votes($userid, $cost, 'donor', 'Статус Меценат на 1 месяц')) {
			pay_mode_redirect('Недостаточно голосов для статуса Меценат.', true);
		}
		sql_query("
			UPDATE users
			SET donor = 'yes',
				" . kz_pay_extend_mysql_datetime('pay_donor_until', 1) . "
			WHERE id = $userid
		") or sqlerr(__FILE__, __LINE__);
		pay_mode_redirect('Статус Меценат включен или продлен на 1 месяц.');
	}

	if ($act === 'wish') {
		$text = trim((string)($_POST['tx_wish'] ?? ''));
		if ($text === '' || (function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text)) < 3) {
			pay_mode_redirect('Введите текст пожелания.', true);
		}
		if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > 1000) {
			$text = mb_substr($text, 0, 1000, 'UTF-8');
		} else {
			$text = substr($text, 0, 1000);
		}
		$cost = kz_pay_int_setting('wish_cost', 5);
		if (!kz_pay_charge_votes($userid, $cost, 'wish', 'Пожелание проекту')) {
			pay_mode_redirect('Недостаточно голосов для пожелания.', true);
		}
		sql_query("
			INSERT INTO pay_wishes (userid, username, userclass, text, cost_votes, active, added)
			VALUES ($userid, " . sqlesc($CURUSER['username'], true) . ', ' . (int)$CURUSER['class'] . ', ' . sqlesc($text, true) . ", $cost, 'yes', NOW())
		") or sqlerr(__FILE__, __LINE__);
		pay_mode_redirect('Пожелание добавлено.');
	}

	if ($act === 'tcounter') {
		$count = function_exists('kz_torrent_downloads_today') ? kz_torrent_downloads_today($userid) : 0;
		if ($count < 1) {
			pay_mode_redirect('Сегодня счетчик скачиваний уже пуст.', true);
		}
		$cost = kz_pay_int_setting('reset_counter_cost', 5);
		if (!kz_pay_charge_votes($userid, $cost, 'reset_counter', 'Обнуление счетчика скачиваний за сутки')) {
			pay_mode_redirect('Недостаточно голосов для обнуления счетчика.', true);
		}
		sql_query("DELETE FROM user_torrent_downloads WHERE userid = $userid AND download_date = CURDATE()") or sqlerr(__FILE__, __LINE__);
		pay_mode_redirect('Счетчик скачиваний за сегодня обнулен.');
	}

	if ($act === 'tdelhistory') {
		$cost = kz_pay_int_setting('delete_history_cost', 5);
		if (!kz_pay_charge_votes($userid, $cost, 'delete_history', 'Удаление истории скачиваний')) {
			pay_mode_redirect('Недостаточно голосов для удаления истории.', true);
		}
		sql_query("DELETE FROM snatched WHERE userid = $userid") or sqlerr(__FILE__, __LINE__);
		sql_query("DELETE FROM user_torrent_downloads WHERE userid = $userid") or sqlerr(__FILE__, __LINE__);
		pay_mode_redirect('История скачиваний удалена.');
	}

	if ($act === 'vip') {
		if (kz_pay_setting('vip_enabled', '0') !== '1') {
			pay_mode_redirect('Получение ВИП временно недоступно.', true);
		}
		$cost = kz_pay_int_setting('vip_cost', 1500);
		if (!kz_pay_charge_votes($userid, $cost, 'vip', 'Звание ВИП на 1 месяц')) {
			pay_mode_redirect('Недостаточно голосов для звания ВИП.', true);
		}
		sql_query("
			UPDATE users
			SET class = GREATEST(class, " . UC_VIP . '),
				' . kz_pay_extend_mysql_datetime('pay_vip_until', 1) . "
			WHERE id = $userid
		") or sqlerr(__FILE__, __LINE__);
		pay_mode_redirect('Звание ВИП включено или продлено на 1 месяц.');
	}

	pay_mode_redirect('Неизвестная операция.', true);
}

$user = kz_pay_user((int)$CURUSER['id']);
$votes = kz_pay_user_votes_from_array($user);
$today = function_exists('kz_torrent_downloads_today') ? kz_torrent_downloads_today((int)$CURUSER['id']) : 0;
$daily = function_exists('kz_user_effective_torrent_limit') ? kz_user_effective_torrent_limit($user) : 20;
$message = '';
if (isset($_GET['ok'])) {
	$message = '<div class="success"><b>Голоса</b><br>' . kz_pay_h($_GET['ok']) . '</div>';
} elseif (isset($_GET['error'])) {
	$message = '<div class="error"><b>Ошибка</b><br>' . kz_pay_h($_GET['error']) . '</div>';
}

$hide_right_blocks = true;
stdhead('Управление голосами');

?>
<?php kz_pay_layout_start('mode', $user); ?>
<?= $message ?>

<div class="bx1 justify">
	На Вашем счете <b><?= (int)$votes ?> голосов</b>. Здесь можно управлять голосами. При каждой операции с профиля снимается указанное количество голосов. Дополнительные голоса можно получить в разделе <a href="/pay.php" class="sbab">Голоса и рейтинг</a>.
</div>

<form action="/pay_mode.php" method="post" onsubmit="return confirm('Получить или продлить статус Меценат?');">
	<div class="bx1 justify">
		<span class="bulet"></span><span class="u9">Статус Меценат<i class="i1 s1"></i></span>
		- статус сроком на 1 месяц. Если статус уже есть, он будет продлен еще на 1 месяц. Стоимость: <b><?= kz_pay_int_setting('donor_cost', 75) ?> голосов</b>.
		<ul class="men"><li><input class="buttonS w200" type="submit" value="Получить статус Меценат"></li></ul>
	</div>
	<input type="hidden" name="act" value="maecenas">
</form>

<form action="/pay_mode.php" method="post">
	<div class="bx1 justify">
		<span class="bulet"></span><span class="u9">Оставить пожелание</span>
		- последние пожелания отображаются в разделе <a href="/pay_wishes.php" class="sbab">Пожелания</a>. Стоимость: <b><?= kz_pay_int_setting('wish_cost', 5) ?> голосов</b>.
		<ul class="men">
			<li><table class="tables1 w100p"><tr><td><textarea class="w98p" rows="2" cols="40" name="tx_wish" id="tx_wish"></textarea></td></tr></table></li>
			<li><input class="buttonS w200" type="submit" value="Оставить пожелание"></li>
		</ul>
	</div>
	<input type="hidden" name="act" value="wish">
</form>

<form action="/pay_mode.php" method="post" onsubmit="return confirm('Обнулить счетчик скачиваний за сегодня?');">
	<div class="bx1 justify">
		<span class="bulet"></span><span class="u9">Обнулить счетчик скачиваний за сутки</span>
		- за сегодня Вы скачали <b><?= (int)$today ?></b> торрент-файлов, доступно в сутки <b><?= (int)$daily ?></b>. Стоимость: <b><?= kz_pay_int_setting('reset_counter_cost', 5) ?> голосов</b>.
		<ul class="men"><li><input class="buttonS w200" type="submit" value="Обнулить счетчик"></li></ul>
	</div>
	<input type="hidden" name="act" value="tcounter">
</form>

<form action="/pay_mode.php" method="post" onsubmit="return confirm('Удалить историю скачиваний?');">
	<div class="bx1 justify">
		<span class="bulet"></span><span class="u9">Удалить историю скачиваний</span>
		- история скачанного будет удалена из профиля. Стоимость: <b><?= kz_pay_int_setting('delete_history_cost', 5) ?> голосов</b>.
		<ul class="men"><li><input class="buttonS w200" type="submit" value="Удалить историю"></li></ul>
	</div>
	<input type="hidden" name="act" value="tdelhistory">
</form>

<form action="/pay_mode.php" method="post" onsubmit="return confirm('Получить или продлить звание ВИП?');">
	<div class="bx1 justify">
		<span class="bulet"></span><span class="u9">Звание <span class="u3">ВИП</span></span>
		- звание на 1 месяц. Стоимость: <b><?= kz_pay_int_setting('vip_cost', 1500) ?> голосов</b>.
		<ul class="men">
			<li><input class="buttonS w200" type="submit" value="Получить звание ВИП"<?= kz_pay_setting('vip_enabled', '0') === '1' ? '' : ' disabled' ?>><?= kz_pay_setting('vip_enabled', '0') === '1' ? '' : ' Временно эта возможность недоступна' ?></li>
		</ul>
	</div>
	<input type="hidden" name="act" value="vip">
</form>

<?php kz_pay_layout_end(array('/pay_mode.php%', '%/pay_mode.php%')); ?>
<?php

stdfoot();

?>
