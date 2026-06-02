<?php

if (!defined('IN_TRACKER') && !defined('ADMIN_FILE') && !defined('BLOCK_FILE')) {
	die('Direct access denied.');
}

function pay_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function pay_table_exists($table)
{
	$table = trim((string)$table);
	if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
		return false;
	}

	$res = sql_query("SHOW TABLES LIKE " . sqlesc($table, true)) or sqlerr(__FILE__, __LINE__);
	return mysqli_num_rows($res) > 0;
}

function pay_column_exists($table, $column)
{
	$table = trim((string)$table);
	$column = trim((string)$column);
	if ($table === '' || $column === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table . $column)) {
		return false;
	}

	$res = sql_query("SHOW COLUMNS FROM `$table` LIKE " . sqlesc($column, true)) or sqlerr(__FILE__, __LINE__);
	return mysqli_num_rows($res) > 0;
}

function pay_add_column($table, $column, $definition)
{
	$table = trim((string)$table);
	$column = trim((string)$column);
	if ($table === '' || $column === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table . $column)) {
		return;
	}
	if (pay_column_exists($table, $column)) {
		return;
	}

	try {
		sql_query("ALTER TABLE `$table` ADD COLUMN $definition") or sqlerr(__FILE__, __LINE__);
	} catch (mysqli_sql_exception $e) {
		if ((int)$e->getCode() !== 1060) {
			throw $e;
		}
	}
}

function pay_ensure_schema()
{
	static $ready = false;

	if ($ready) {
		return;
	}

	$ready = true;

	/*
	 * Нельзя проверять/создавать структуру БД на каждой странице.
	 * Для обновления БД временно включи:
	 *
	 * define('KZ_AUTO_MIGRATIONS', true);
	 *
	 * После одного запуска верни false.
	 */
	if (!defined('KZ_AUTO_MIGRATIONS') || KZ_AUTO_MIGRATIONS !== true) {
		return;
	}

	pay_add_column('users', 'pay_votes', "pay_votes int(10) unsigned NOT NULL DEFAULT '0' AFTER bonus");
	pay_add_column('users', 'pay_donor_until', 'pay_donor_until datetime NULL DEFAULT NULL AFTER donor');
	pay_add_column('users', 'pay_vip_until', 'pay_vip_until datetime NULL DEFAULT NULL AFTER pay_donor_until');

	sql_query("
		CREATE TABLE IF NOT EXISTS pay_settings (
			setting_key varchar(80) NOT NULL,
			setting_value text NOT NULL,
			PRIMARY KEY (setting_key)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS pay_transactions (
			id int(10) unsigned NOT NULL auto_increment,
			userid int(10) unsigned NOT NULL default '0',
			username varchar(40) NOT NULL default '',
			operation varchar(40) NOT NULL default '',
			bonus_delta decimal(10,2) NOT NULL default '0.00',
			votes_delta int(10) NOT NULL default '0',
			uploaded_delta bigint(20) NOT NULL default '0',
			details text NOT NULL,
			ip varchar(45) NOT NULL default '',
			created_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			KEY userid_created (userid, created_at),
			KEY operation_created (operation, created_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS pay_wishes (
			id int(10) unsigned NOT NULL auto_increment,
			userid int(10) unsigned NOT NULL default '0',
			username varchar(40) NOT NULL default '',
			userclass tinyint(3) unsigned NOT NULL default '0',
			text text NOT NULL,
			cost_votes int(10) unsigned NOT NULL default '0',
			active enum('yes','no') NOT NULL default 'yes',
			added datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			KEY active_added (active, added),
			KEY userid (userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS pay_chat (
			id int(10) unsigned NOT NULL auto_increment,
			tab tinyint(3) unsigned NOT NULL default '1',
			userid int(10) unsigned NOT NULL default '0',
			username varchar(40) NOT NULL default '',
			userclass tinyint(3) unsigned NOT NULL default '0',
			text text NOT NULL,
			ip varchar(45) NOT NULL default '',
			visible enum('yes','no') NOT NULL default 'yes',
			added datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			KEY tab_added (tab, added),
			KEY userid (userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	$defaults = array(
		'exchange_options' => "25:1:25 бонусов - получить 1 голос\n100:5:100 бонусов - получить 5 голосов\n180:10:180 бонусов - получить 10 голосов\n350:25:350 бонусов - получить 25 голосов",
		'donor_cost' => '75',
		'wish_cost' => '5',
		'reset_counter_cost' => '5',
		'delete_history_cost' => '5',
		'vip_cost' => '1500',
		'vip_enabled' => '0',
		'reputation_vote_cost' => '1',
		'home_block_enabled' => '1',
		'chat_enabled' => '1',
	);

	foreach ($defaults as $key => $value) {
		sql_query("
			INSERT INTO pay_settings (setting_key, setting_value)
			VALUES (" . sqlesc($key, true) . ", " . sqlesc($value, true) . ")
			ON DUPLICATE KEY UPDATE setting_value = setting_value
		") or sqlerr(__FILE__, __LINE__);
	}

	pay_install_home_block();
}

function pay_install_home_block()
{
	if (!pay_table_exists('orbital_blocks')) {
		return;
	}

	pay_prune_home_block_duplicates();

	$res = sql_query("SELECT bid FROM orbital_blocks WHERE blockfile = 'block-pay.php' LIMIT 1") or sqlerr(__FILE__, __LINE__);
	if (mysqli_fetch_assoc($res)) {
		return;
	}

	sql_query("
		INSERT INTO orbital_blocks (bkey, title, content, bposition, weight, active, time, blockfile, view, expire, action, which, allow_hide)
		VALUES ('', 'Меценаты', '', 'c', 100, 1, '0', 'block-pay.php', 1, '0', 'd', 'ihome,', 'yes')
	") or sqlerr(__FILE__, __LINE__);

	pay_prune_home_block_duplicates();
}

function pay_prune_home_block_duplicates()
{
	sql_query("
		DELETE FROM orbital_blocks
		WHERE blockfile = 'block-pay.php'
		  AND bid NOT IN (
			  SELECT keep_bid FROM (
				  SELECT MIN(bid) AS keep_bid
				  FROM orbital_blocks
				  WHERE blockfile = 'block-pay.php'
			  ) AS keep_pay_block
		  )
	") or sqlerr(__FILE__, __LINE__);
}

function pay_setting($key, $default = '')
{
	static $settings = null;

	$key = (string)$key;

	if ($settings === null) {
		$settings = function_exists('tracker_cache_remember')
			? tracker_cache_remember('pay:settings', 300, function () {
				$cached_settings = array();
				$res = sql_query("SELECT setting_key, setting_value FROM pay_settings");
				if ($res) {
					while ($row = mysqli_fetch_assoc($res)) {
						$cached_settings[(string)$row['setting_key']] = (string)$row['setting_value'];
					}
				}
				return $cached_settings;
			})
			: array();

		if (!$settings) {
			$res = sql_query("SELECT setting_key, setting_value FROM pay_settings");
			if ($res) {
				while ($row = mysqli_fetch_assoc($res)) {
					$settings[(string)$row['setting_key']] = (string)$row['setting_value'];
				}
			}
		}
	}

	return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function pay_set_setting($key, $value)
{
	pay_ensure_schema();
	sql_query("
		INSERT INTO pay_settings (setting_key, setting_value)
		VALUES (" . sqlesc((string)$key, true) . ", " . sqlesc((string)$value, true) . ")
		ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
	") or sqlerr(__FILE__, __LINE__);
}

function pay_int_setting($key, $default = 0)
{
	return max(0, (int)pay_setting($key, $default));
}

function pay_exchange_options()
{
	$raw = pay_setting('exchange_options', '');
	$options = array();
	foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
		$line = trim((string)$line);
		if ($line === '' || strpos($line, ':') === false) {
			continue;
		}
		$parts = explode(':', $line, 3);
		$bonus = max(0, (float)str_replace(',', '.', $parts[0]));
		$votes = max(0, (int)$parts[1]);
		if ($bonus <= 0 || $votes <= 0) {
			continue;
		}
		$options[] = array(
			'bonus' => $bonus,
			'votes' => $votes,
			'title' => trim((string)($parts[2] ?? '')) ?: (number_format($bonus, 0, '.', ' ') . ' бонусов - получить ' . $votes . ' голосов'),
		);
	}

	return $options;
}

function pay_user_votes_from_array($user)
{
	return isset($user['pay_votes']) ? (int)$user['pay_votes'] : 0;
}

function pay_user($userid)
{
	pay_ensure_schema();
	$userid = (int)$userid;
	$res = sql_query("SELECT * FROM users WHERE id = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$user = mysqli_fetch_assoc($res);
	if ($user) {
		pay_refresh_user_perks($user);
	}
	return $user ?: null;
}

function pay_refresh_user_perks($user)
{
	$userid = (int)($user['id'] ?? 0);
	if ($userid <= 0) {
		return;
	}

	$updates = array();
	if (!empty($user['pay_donor_until']) && $user['pay_donor_until'] !== '0000-00-00 00:00:00' && strtotime($user['pay_donor_until']) < time() && ($user['donor'] ?? 'no') === 'yes') {
		$updates[] = "donor = 'no'";
	}
	if (!empty($user['pay_vip_until']) && $user['pay_vip_until'] !== '0000-00-00 00:00:00' && strtotime($user['pay_vip_until']) < time() && (int)($user['class'] ?? UC_USER) === UC_VIP) {
		$updates[] = 'class = ' . UC_POWER_USER;
	}
	if ($updates) {
		sql_query('UPDATE users SET ' . implode(', ', $updates) . " WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
	}
}

function pay_log($userid, $operation, $bonus_delta, $votes_delta, $uploaded_delta, $details)
{
	$userid = (int)$userid;
	$username = '';
	if ($userid > 0) {
		$res = sql_query("SELECT username FROM users WHERE id = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);
		if ($row = mysqli_fetch_assoc($res)) {
			$username = (string)$row['username'];
		}
	}

	sql_query("
		INSERT INTO pay_transactions (userid, username, operation, bonus_delta, votes_delta, uploaded_delta, details, ip, created_at)
		VALUES (
			$userid,
			" . sqlesc($username, true) . ",
			" . sqlesc((string)$operation, true) . ",
			" . (float)$bonus_delta . ",
			" . (int)$votes_delta . ",
			" . (int)$uploaded_delta . ",
			" . sqlesc((string)$details, true) . ",
			" . sqlesc(function_exists('getip') ? getip() : ($_SERVER['REMOTE_ADDR'] ?? ''), true) . ",
			NOW()
		)
	") or sqlerr(__FILE__, __LINE__);
}

function pay_exchange_bonus($userid, $bonus_cost, $votes, $title)
{
	global $link;

	pay_ensure_schema();
	$userid = (int)$userid;
	$bonus_cost = max(0, (float)$bonus_cost);
	$votes = max(0, (int)$votes);
	if ($userid <= 0 || $bonus_cost <= 0 || $votes <= 0) {
		return 'Неверная операция обмена.';
	}

	sql_query("
		UPDATE users
		SET bonus = bonus - $bonus_cost,
			pay_votes = pay_votes + $votes
		WHERE id = $userid
		  AND bonus >= $bonus_cost
	") or sqlerr(__FILE__, __LINE__);

	if (mysqli_affected_rows($link) < 1) {
		return 'Недостаточно бонусов для обмена.';
	}

	pay_log($userid, 'exchange', -$bonus_cost, $votes, 0, $title);
	return '';
}

function pay_charge_votes($userid, $cost, $operation, $details)
{
	global $link;

	pay_ensure_schema();
	$userid = (int)$userid;
	$cost = max(0, (int)$cost);
	if ($cost <= 0) {
		pay_log($userid, $operation, 0, 0, 0, $details);
		return true;
	}

	sql_query("
		UPDATE users
		SET pay_votes = pay_votes - $cost
		WHERE id = $userid
		  AND pay_votes >= $cost
	") or sqlerr(__FILE__, __LINE__);

	if (mysqli_affected_rows($link) < 1) {
		return false;
	}

	pay_log($userid, $operation, 0, -$cost, 0, $details);
	return true;
}

function pay_credit_votes($userid, $votes, $details, $operation = 'admin_credit')
{
	$userid = (int)$userid;
	$votes = (int)$votes;
	if ($userid <= 0 || $votes === 0) {
		return false;
	}

	if ($votes > 0) {
		sql_query("UPDATE users SET pay_votes = pay_votes + $votes WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
	} else {
		sql_query("UPDATE users SET pay_votes = GREATEST(0, pay_votes - " . abs($votes) . ") WHERE id = $userid") or sqlerr(__FILE__, __LINE__);
	}
	pay_log($userid, $operation, 0, $votes, 0, $details);
	return true;
}

function pay_extend_mysql_datetime($column, $months = 1)
{
	$months = max(1, (int)$months);
	return "$column = IF($column IS NULL OR $column < NOW(), DATE_ADD(NOW(), INTERVAL $months MONTH), DATE_ADD($column, INTERVAL $months MONTH))";
}

function pay_user_link($row)
{
	$userid = (int)($row['userid'] ?? $row['id'] ?? 0);
	$username = (string)($row['username'] ?? '');
	$class = (int)($row['class'] ?? $row['userclass'] ?? UC_USER);
	if ($userid <= 0 || $username === '') {
		return '<i>unknown</i>';
	}
	$icons = function_exists('get_user_icons') ? get_user_icons(array_merge($row, array('id' => $userid, 'class' => $class))) : '';
	return '<a href="/userdetails.php?id=' . $userid . '" class="u' . $class . '">' . pay_h($username) . '</a>' . $icons;
}

function pay_recent_helpers($limit = 20)
{
	pay_ensure_schema();
	$limit = max(1, (int)$limit);

	return function_exists('tracker_cache_remember')
		? tracker_cache_remember('pay:recent-helpers:' . $limit, 120, function () use ($limit) {
			$res = sql_query("
				SELECT u.id AS userid, u.username, u.class, u.donor, u.warned, u.enabled, MAX(t.created_at) AS last_at, SUM(t.votes_delta) AS votes_sum, COUNT(*) AS ops
				FROM pay_transactions AS t
				INNER JOIN users AS u ON u.id = t.userid
				WHERE t.operation = 'exchange'
				  AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
				GROUP BY u.id, u.username, u.class, u.donor, u.warned, u.enabled
				ORDER BY last_at DESC
				LIMIT $limit
			") or sqlerr(__FILE__, __LINE__);

			$rows = array();
			while ($row = mysqli_fetch_assoc($res)) {
				$rows[] = $row;
			}
			return $rows;
		})
		: pay_recent_helpers_uncached($limit);
}

function pay_top_helpers($mode = 'active', $limit = 20)
{
	pay_ensure_schema();
	$limit = max(1, (int)$limit);
	$order = $mode === 'votes' ? 'votes_sum DESC, ops DESC' : 'ops DESC, votes_sum DESC';

	return function_exists('tracker_cache_remember')
		? tracker_cache_remember('pay:top-helpers:' . $mode . ':' . $limit, 120, function () use ($limit, $order) {
			return pay_top_helpers_query($limit, $order);
		})
		: pay_top_helpers_query($limit, $order);
}

function pay_recent_helpers_uncached($limit)
{
	$res = sql_query("
		SELECT u.id AS userid, u.username, u.class, u.donor, u.warned, u.enabled, MAX(t.created_at) AS last_at, SUM(t.votes_delta) AS votes_sum, COUNT(*) AS ops
		FROM pay_transactions AS t
		INNER JOIN users AS u ON u.id = t.userid
		WHERE t.operation = 'exchange'
		  AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
		GROUP BY u.id, u.username, u.class, u.donor, u.warned, u.enabled
		ORDER BY last_at DESC
		LIMIT $limit
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function pay_top_helpers_query($limit, $order)
{
	$res = sql_query("
		SELECT u.id AS userid, u.username, u.class, u.donor, u.warned, u.enabled, SUM(GREATEST(t.votes_delta, 0)) AS votes_sum, COUNT(*) AS ops
		FROM pay_transactions AS t
		INNER JOIN users AS u ON u.id = t.userid
		WHERE t.operation = 'exchange'
		  AND t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
		GROUP BY u.id, u.username, u.class, u.donor, u.warned, u.enabled
		ORDER BY $order
		LIMIT $limit
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function pay_user_list_html($rows, $empty = 'пока нет операций')
{
	if (!$rows) {
		return '<span class="small">' . pay_h($empty) . '</span>';
	}
	$out = array();
	foreach ($rows as $row) {
		$out[] = '<span class="nowrap"><img src="/pic/emty.gif" class="i2 c' . max(1, min(37, (int)(($row['userid'] ?? 1) % 37 + 1))) . '">' . pay_user_link($row) . '</span>';
	}
	return implode(', ', $out);
}

function pay_user_transactions($userid, $limit = 20)
{
	pay_ensure_schema();
	$userid = (int)$userid;
	$limit = max(1, (int)$limit);
	$res = sql_query("
		SELECT *
		FROM pay_transactions
		WHERE userid = $userid
		ORDER BY created_at DESC, id DESC
		LIMIT $limit
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function pay_format_transaction_rows($rows)
{
	if (!$rows) {
		return '<table class="tables1 w100p"><tr><td colspan="4">Нет операций за последнее время.</td></tr></table>';
	}

	$html = '<table class="tables1 w100p"><tr><td class="colhead">Дата</td><td class="colhead">Операция</td><td class="colhead">Бонусы</td><td class="colhead">Голоса</td></tr>';
	foreach ($rows as $row) {
		$html .= '<tr>';
		$html .= '<td>' . pay_h($row['created_at']) . '</td>';
		$html .= '<td>' . pay_h($row['details']) . '</td>';
		$html .= '<td>' . number_format((float)$row['bonus_delta'], 2, '.', ' ') . '</td>';
		$html .= '<td>' . ((int)$row['votes_delta'] > 0 ? '+' : '') . (int)$row['votes_delta'] . '</td>';
		$html .= '</tr>';
	}
	return $html . '</table>';
}

function pay_tabs($active)
{
	$items = array(
		'pay' => array('/pay.php', 'Голоса и рейтинг'),
		'mode' => array('/pay_mode.php', 'Управление голосами'),
		'wishes' => array('/pay_wishes.php', 'Пожелания'),
		'help' => array('/pay_help.php', 'Техподдержка'),
	);
	echo '<div class="pad0x0x5x0"><ul class="lis">';
	foreach ($items as $key => $item) {
		echo '<li' . ($active === $key ? ' class="mn"' : '') . '><a href="' . pay_h($item[0]) . '">' . pay_h($item[1]) . '</a></li>';
	}
	echo '</ul></div>';
}

function pay_sidebar($user)
{
	$votes = pay_user_votes_from_array($user);
	$bonus = isset($user['bonus']) ? (float)$user['bonus'] : 0;
	$active = pay_top_helpers('active', 1);
	$best = pay_top_helpers('votes', 1);
	?>
	<div class="mn3_menu">
		<ul class="men">
			<li class="img"><a href="/pay.php"><img src="/themes/TBDev/images/bnr_pay_sm.jpg" height="75" class="block w200" alt=""></a></li>
			<li class="tp">Раздел Меценатов и ВИП</li>
			<li class="justify">На Вашем счете <b><?= (int)$votes ?> голосов</b> и <b><?= number_format($bonus, 2, '.', ' ') ?> бонусов</b>. Бонусы можно обменивать на голоса, а голоса тратить на возможности проекта.</li>
			<li class="tp">Кубки меценатов</li>
			<li class="justify"><i class="i1 cb6"></i> Кубок Активный Меценат: <?= $active ? pay_user_link($active[0]) : 'пока нет претендента' ?>.</li>
			<li class="justify"><i class="i1 cb7"></i> Кубок Лучший Меценат: <?= $best ? pay_user_link($best[0]) : 'пока нет претендента' ?>.</li>
			<li class="tp">Сообщить о проблеме</li>
			<li class="justify">Если возникли вопросы по обмену или голосам, обратитесь в <a href="/pay_help.php" class="sbab">техподдержку</a>.</li>
		</ul>
	</div>
	<?php
}

function pay_layout_start($active, $user)
{
	echo '<div class="bx2">';
	pay_tabs($active);
	pay_sidebar($user);
	echo '<div class="mn3_content">';
}

function pay_layout_end($patterns)
{
	echo '</div><div class="clr"></div></div>';
	echo page_online_box($patterns, 'никого нет на странице');
}

function pay_chat_rows($tab, $limit = 50)
{
	pay_ensure_schema();
	$tab = max(1, min(2, (int)$tab));
	$limit = max(1, (int)$limit);
	$res = sql_query("
		SELECT *
		FROM pay_chat
		WHERE tab = $tab AND visible = 'yes'
		ORDER BY added DESC, id DESC
		LIMIT $limit
	") or sqlerr(__FILE__, __LINE__);
	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return array_reverse($rows);
}

function pay_add_chat_message($tab, $text)
{
	global $CURUSER;

	if (pay_setting('chat_enabled', '1') !== '1') {
		return;
	}

	$tab = max(1, min(2, (int)$tab));
	$text = trim((string)$text);
	if ($text === '') {
		return;
	}
	if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > 2000) {
		$text = mb_substr($text, 0, 2000, 'UTF-8');
	} else {
		$text = substr($text, 0, 2000);
	}

	sql_query("
		INSERT INTO pay_chat (tab, userid, username, userclass, text, ip, visible, added)
		VALUES (
			$tab,
			" . (int)$CURUSER['id'] . ",
			" . sqlesc($CURUSER['username'], true) . ",
			" . (int)$CURUSER['class'] . ",
			" . sqlesc($text, true) . ",
			" . sqlesc(function_exists('getip') ? getip() : ($_SERVER['REMOTE_ADDR'] ?? ''), true) . ",
			'yes',
			NOW()
		)
	") or sqlerr(__FILE__, __LINE__);
}

function pay_chat_html($tab, $limit = 50)
{
	$rows = pay_chat_rows($tab, $limit);
	if (!$rows) {
		return '<div class="pad10x10 center">Сообщений пока нет.</div>';
	}
	$html = '';
	foreach ($rows as $row) {
		$html .= '<div class="bx5x5">';
		$html .= pay_user_link($row) . ' <span class="small">' . pay_h($row['added']) . '</span>';
		$html .= '<div class="pad5x5">' . nl2br(pay_h($row['text'])) . '</div>';
		$html .= '</div>';
	}
	return $html;
}

function pay_chat_frame($endpoint, $default_tab)
{
	if (pay_setting('chat_enabled', '1') !== '1') {
		echo '<div class="bx1_0 center">Чат раздела временно закрыт.</div>';
		return;
	}

	$default_tab = max(1, min(2, (int)$default_tab));
	$endpoint = pay_h($endpoint);
	?>
	<script type="text/javascript">
	var select_tabch = <?= $default_tab ?>;
	function fins(it) {
		select_tabch = it;
		document.getElementById('1_tabch').className = '';
		document.getElementById('2_tabch').className = '';
		document.getElementById(select_tabch + '_tabch').className = 'mn';
		refr();
		return false;
	}
	function refr() {
		document.getElementById('start_chbox').innerHTML = '<iframe src="<?= $endpoint ?>?action=getch&tabch=' + select_tabch + '" width="100%" height="400" frameborder="0" name="chbox" marginwidth="0" marginheight="0" scrolling="yes" style="border-style:none;border:0;overflow-x:auto;overflow-y:visible;display:block;"></iframe>';
		return false;
	}
	function history_chat() {
		window.open('<?= $endpoint ?>?action=getch&tabch=' + select_tabch + '&imes=200', 'history_chat', 'toolbars=0,scrollbars=1,location=0,statusbars=0,menubars=0,resizable=1,width=600,height=450,left=70,top=50');
	}
	function sendTD() {
		var f = document.forms['mss'];
		if (!f || f.elements['t'].value.length < 5) {
			mess_out('ОШИБКА! Минимум 5 символов!');
			return false;
		}
		f.action = '<?= $endpoint ?>?action=getch&tabch=' + select_tabch;
		f.submit();
		f.elements['t'].value = '';
		return false;
	}
	</script>
	<table class="w100p">
		<tr><td class="mn2">
			<ul class="lis">
				<li id="1_tabch"<?= $default_tab === 1 ? ' class="mn"' : '' ?>><a onclick="return fins(1)" href="">Вопросы и предложения</a></li>
				<li id="2_tabch"<?= $default_tab === 2 ? ' class="mn"' : '' ?>><a onclick="return fins(2)" href="">Проблемы транзакций</a></li>
			</ul>
		</td></tr>
		<tr><td>
			<div id="start_chbox" class="bx2_0">
				<div style="height:400px; overflow-y:scroll; -webkit-overflow-scrolling:touch;">
					<iframe src="<?= $endpoint ?>?action=getch&amp;tabch=<?= $default_tab ?>" width="100%" height="400" frameborder="0" name="chbox" marginwidth="0" marginheight="0" scrolling="yes" style="border-style:none;border:0;overflow-x:auto;overflow-y:visible;display:block;"></iframe>
				</div>
			</div>
			<form action="" target="chbox" name="mss" method="post">
				<div class="bx1">
					<div class="cmet_e_inp"><textarea id="t" name="t" cols="70" rows="5" class="w98p"></textarea></div>
					<div class="cmet_e_inp">
						<input class="buttonS" type="button" value="Отправить" onclick="sendTD();">
						<span class="floatright">[ <a href="javascript:history_chat();">история</a> ] [ <a onclick="return refr()" href="">перезагрузить</a> ]</span>
					</div>
				</div>
			</form>
		</td></tr>
	</table>
	<?php
}

function pay_wishes($limit = 50, $offset = 0)
{
	pay_ensure_schema();
	$limit = max(1, (int)$limit);
	$offset = max(0, (int)$offset);
	$res = sql_query("
		SELECT *
		FROM pay_wishes
		WHERE active = 'yes'
		ORDER BY added DESC, id DESC
		LIMIT $offset,$limit
	") or sqlerr(__FILE__, __LINE__);
	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function pay_wishes_count()
{
	pay_ensure_schema();
	$res = sql_query("SELECT COUNT(*) FROM pay_wishes WHERE active = 'yes'") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_row($res);
	return (int)($row[0] ?? 0);
}

function pay_home_block_html()
{
	if (isset($GLOBALS['index_pay_enabled'])) {
		if ((string)$GLOBALS['index_pay_enabled'] !== '1') {
			return '<div class="bx1"><ul class="men"><li class="mn2"><span class="bulet"></span><a href="/pay.php" class="sbab">Раздел меценатов временно скрыт</a></li></ul></div>';
		}

		$recent = isset($GLOBALS['index_pay_recent']) && is_array($GLOBALS['index_pay_recent']) ? $GLOBALS['index_pay_recent'] : array();
		$best = isset($GLOBALS['index_pay_best']) && is_array($GLOBALS['index_pay_best']) ? $GLOBALS['index_pay_best'] : array();

		return '<div class="bx1"><ul class="men">'
			. '<li class="mn2"><span class="bulet"></span><a href="/pay.php" class="sbab">Спасибо за помощь, поднимите свой рейтинг и помогите проекту</a><div class="pad5x5">' . pay_user_list_html($recent, 'пока никто не обменивал бонусы') . '</div></li>'
			. '<li class="mn2"><span class="bulet"></span><a href="/pay.php" class="sbab">Спасибо Меценатам за их поддержку</a><div class="pad5x5">' . pay_user_list_html($best, 'пока нет меценатов') . '</div></li>'
			. '</ul></div>';
	}

	if (pay_setting('home_block_enabled', '1') !== '1') {
		return '<div class="bx1"><ul class="men"><li class="mn2"><span class="bulet"></span><a href="/pay.php" class="sbab">Раздел меценатов временно скрыт</a></li></ul></div>';
	}

	$recent = pay_recent_helpers(20);
	$best = pay_top_helpers('votes', 8);

	return '<div class="bx1"><ul class="men">'
		. '<li class="mn2"><span class="bulet"></span><a href="/pay.php" class="sbab">Спасибо за помощь, поднимите свой рейтинг и помогите проекту</a><div class="pad5x5">' . pay_user_list_html($recent, 'пока никто не обменивал бонусы') . '</div></li>'
		. '<li class="mn2"><span class="bulet"></span><a href="/pay.php" class="sbab">Спасибо Меценатам за их поддержку</a><div class="pad5x5">' . pay_user_list_html($best, 'пока нет меценатов') . '</div></li>'
		. '</ul></div>';
}

?>
