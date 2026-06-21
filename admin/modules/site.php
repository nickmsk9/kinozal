<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

require_once 'admin/site_settings_helpers.php';

if (!function_exists('SiteSettingsAdmin')) {
	function site_admin_card($admin_file, $op, $title, $text)
	{
		echo '<td class="top w33p" style="padding:8px;">';
		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b><a href="' . site_admin_h($admin_file) . '.php?op=' . site_admin_h($op) . '">' . site_admin_h($title) . '</a></b></div>';
		echo '<div class="tp1_body">' . site_admin_h($text) . '</div>';
		echo '</div>';
		echo '</td>';
	}

	function site_admin_count_value($sql)
	{
		$res = sql_query($sql) or sqlerr(__FILE__, __LINE__);
		$row = mysqli_fetch_row($res);
		return (int)($row[0] ?? 0);
	}

	function site_admin_cache_state()
	{
		if (!function_exists('tracker_cache_enabled') || !tracker_cache_enabled()) {
			return 'выключен';
		}

		if (function_exists('tracker_cache_redis') && tracker_cache_redis()) {
			return 'Redis';
		}

		return 'fallback';
	}

	function site_admin_db_time()
	{
		$res = sql_query('SELECT NOW() AS db_now, @@session.time_zone AS session_tz') or sqlerr(__FILE__, __LINE__);
		$row = mysqli_fetch_assoc($res);

		return $row ?: array('db_now' => '', 'session_tz' => '');
	}

	function site_admin_metric($label, $value, $link = '')
	{
		$html = '<b>' . site_admin_h($value) . '</b><br><span class="small">' . site_admin_h($label) . '</span>';
		if ($link !== '') {
			$html = '<a class="sbab" href="' . site_admin_h($link) . '">' . $html . '</a>';
		}
		echo '<td class="center" style="padding:8px;">' . $html . '</td>';
	}

	function site_admin_clear_cache()
	{
		$ok = false;

		if (function_exists('tracker_cache_delete')) {
			$ok = tracker_cache_delete('site_settings:all') || $ok;
		}

		if (function_exists('tracker_cache_delete_pattern')) {
			$ok = tracker_cache_delete_pattern('*') || $ok;
		}

		stdmsg('Кэш трекера', $ok ? 'Кэш очищен или версии групп сброшены.' : 'Кэш недоступен, локальный runtime-кэш будет пересобран при следующем запросе.');
	}

	function site_admin_save_quick_settings()
	{
		global $SITE_ONLINE, $deny_signup, $use_captcha, $use_blocks, $use_sessions, $use_ttl, $allow_guests_details;
		global $use_wait, $SITENAME, $maxusers, $max_torrent_size, $ttl_days, $points_per_hour;

		site_set_setting('site_online', !empty($_POST['site_online']) ? '1' : '0');
		site_set_setting('deny_signup', !empty($_POST['deny_signup']) ? '1' : '0');
		site_set_setting('use_captcha', !empty($_POST['use_captcha']) ? '1' : '0');
		site_set_setting('use_blocks', !empty($_POST['use_blocks']) ? '1' : '0');
		site_set_setting('use_sessions', !empty($_POST['use_sessions']) ? '1' : '0');
		site_set_setting('use_ttl', !empty($_POST['use_ttl']) ? '1' : '0');
		site_set_setting('allow_guests_details', !empty($_POST['allow_guests_details']) ? '1' : '0');
		site_set_setting('use_wait', !empty($_POST['use_wait']) ? '1' : '0');

		$site_name = trim((string)($_POST['site_name'] ?? $SITENAME));
		if ($site_name !== '') {
			site_set_setting('site_name', $site_name);
		}

		site_set_setting('maxusers', max(1, (int)($_POST['maxusers'] ?? $maxusers)));
		site_set_setting('max_torrent_size', max(1024, min(1024 * 1024 * 1024, (int)($_POST['max_torrent_size'] ?? $max_torrent_size))));
		site_set_setting('ttl_days', max(1, min(3650, (int)($_POST['ttl_days'] ?? $ttl_days))));
		site_set_setting('points_per_hour', max(0, min(100000, (int)($_POST['points_per_hour'] ?? $points_per_hour))));

		site_admin_saved('Управление трекером');
	}

	function site_admin_render_snapshot($admin_file)
	{
		$db_time = site_admin_db_time();
		$public_torrents = site_admin_count_value("SELECT COUNT(*) FROM torrents WHERE visible = 'yes' AND banned != 'yes' AND is_test = 'no'");
		$unmoderated = site_admin_count_value("SELECT COUNT(*) FROM torrents WHERE moderated = 'no' AND is_test = 'no' AND visible = 'yes' AND banned != 'yes'");
		$peers = site_admin_count_value('SELECT COUNT(*) FROM peers');
		$online = site_admin_count_value('SELECT COUNT(*) FROM sessions WHERE time >= ' . (time() - 900));

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>Состояние трекера</b></div>';
		echo '<div class="tp1_body">';
		echo '<table class="tables2 w100p">';
		echo '<tr>';
		site_admin_metric('публичных раздач', number_format($public_torrents, 0, '.', ' '), '/browse.php');
		site_admin_metric('без модерации', number_format($unmoderated, 0, '.', ' '), '/browse.php');
		site_admin_metric('активных пиров', number_format($peers, 0, '.', ' '));
		site_admin_metric('онлайн за 15 минут', number_format($online, 0, '.', ' '));
		echo '</tr><tr>';
		site_admin_metric('PHP время', date('Y-m-d H:i:s') . ' ' . (defined('TRACKER_TIMEZONE') ? TRACKER_TIMEZONE : date_default_timezone_get()));
		site_admin_metric('MySQL время', ($db_time['db_now'] ?? '') . ' ' . ($db_time['session_tz'] ?? ''));
		site_admin_metric('кэш', site_admin_cache_state());
		site_admin_metric('обслуживание БД', 'открыть', site_admin_h($admin_file) . '.php?op=StatusDB');
		echo '</tr>';
		echo '</table>';
		echo '</div></div>';
	}

	function site_admin_render_quick_form($admin_file)
	{
		global $SITE_ONLINE, $deny_signup, $use_captcha, $use_blocks, $use_sessions, $use_ttl, $allow_guests_details;
		global $use_wait, $SITENAME, $maxusers, $max_torrent_size, $ttl_days, $points_per_hour;

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>Быстрое управление</b></div>';
		echo '<div class="tp1_body">';
		echo '<form method="post" action="' . site_admin_h($admin_file) . '.php?op=SiteSettingsAdmin">';
		echo '<input type="hidden" name="site_quick_save" value="1">';
		echo '<table class="tables2 w100p">';
		echo '<tr><td class="colhead" colspan="4">Переключатели</td></tr>';
		echo '<tr>';
		echo '<td><label><input type="checkbox" name="site_online" value="1"' . (site_setting_bool('site_online', !empty($SITE_ONLINE)) ? ' checked' : '') . '> сайт онлайн</label></td>';
		echo '<td><label><input type="checkbox" name="deny_signup" value="1"' . (site_setting_bool('deny_signup', !empty($deny_signup)) ? ' checked' : '') . '> регистрация закрыта</label></td>';
		echo '<td><label><input type="checkbox" name="use_captcha" value="1"' . (site_setting_bool('use_captcha', !empty($use_captcha)) ? ' checked' : '') . '> капча</label></td>';
		echo '<td><label><input type="checkbox" name="allow_guests_details" value="1"' . (site_setting_bool('allow_guests_details', !empty($allow_guests_details)) ? ' checked' : '') . '> гости в details</label></td>';
		echo '</tr><tr>';
		echo '<td><label><input type="checkbox" name="use_blocks" value="1"' . (site_setting_bool('use_blocks', !empty($use_blocks)) ? ' checked' : '') . '> блоки</label></td>';
		echo '<td><label><input type="checkbox" name="use_sessions" value="1"' . (site_setting_bool('use_sessions', !empty($use_sessions)) ? ' checked' : '') . '> сессии онлайн</label></td>';
		echo '<td><label><input type="checkbox" name="use_ttl" value="1"' . (site_setting_bool('use_ttl', !empty($use_ttl)) ? ' checked' : '') . '> TTL раздач</label></td>';
		echo '<td><label><input type="checkbox" name="use_wait" value="1"' . (site_setting_bool('use_wait', !empty($use_wait)) ? ' checked' : '') . '> ожидание рейтинга</label></td>';
		echo '</tr>';
		echo '<tr><td class="colhead" colspan="4">Ключевые лимиты</td></tr>';
		echo '<tr>';
		echo '<td colspan="2">Название<br><input type="text" name="site_name" class="w98p" value="' . site_admin_h(site_setting('site_name', (string)$SITENAME)) . '"></td>';
		echo '<td>Максимум пользователей<br><input type="text" name="maxusers" size="12" value="' . site_admin_h(site_setting_int('maxusers', (int)$maxusers, 1, 10000000)) . '"></td>';
		echo '<td>Размер .torrent, байт<br><input type="text" name="max_torrent_size" size="12" value="' . site_admin_h(site_setting_int('max_torrent_size', (int)$max_torrent_size, 1024, 1024 * 1024 * 1024)) . '"></td>';
		echo '</tr><tr>';
		echo '<td>TTL, дней<br><input type="text" name="ttl_days" size="8" value="' . site_admin_h(site_setting_int('ttl_days', (int)$ttl_days, 1, 3650)) . '"></td>';
		echo '<td>Бонусов в час<br><input type="text" name="points_per_hour" size="8" value="' . site_admin_h(site_setting_int('points_per_hour', (int)$points_per_hour, 0, 100000)) . '"></td>';
		echo '<td colspan="2" class="center"><input type="submit" class="buttonS" value="Сохранить быстрое управление"></td>';
		echo '</tr>';
		echo '</table>';
		echo '</form>';
		echo '<form method="post" action="' . site_admin_h($admin_file) . '.php?op=SiteSettingsAdmin" style="margin-top:8px;">';
		echo '<input type="hidden" name="site_clear_cache" value="1">';
		echo '<input type="submit" class="buttonS" value="Очистить кэш трекера">';
		echo '</form>';
		echo '</div></div>';
	}

	function SiteSettingsAdmin()
	{
		global $admin_file, $SITE_ONLINE, $deny_signup, $use_captcha, $use_blocks, $use_sessions, $use_ttl;

		site_settings_ensure_schema();

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_quick_save'])) {
			site_admin_save_quick_settings();
		} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_clear_cache'])) {
			site_admin_clear_cache();
		}

		$site_online = site_setting_bool('site_online', !empty($SITE_ONLINE));
		$signup_closed = site_setting_bool('deny_signup', !empty($deny_signup));
		$captcha = site_setting_bool('use_captcha', !empty($use_captcha));
		$blocks = site_setting_bool('use_blocks', !empty($use_blocks));
		$sessions = site_setting_bool('use_sessions', !empty($use_sessions));
		$ttl = site_setting_bool('use_ttl', !empty($use_ttl));

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>Управление сайтом</b></div>';
		echo '<div class="tp1_body">';
		echo '<table class="tables2 w100p">';
		echo '<tr><td class="colhead center" colspan="6">Состояние</td></tr><tr>';
		site_admin_status_cell('Сайт', $site_online ? 'онлайн' : 'техработы', $site_online);
		site_admin_status_cell('Регистрация', $signup_closed ? 'закрыта' : 'открыта', !$signup_closed);
		site_admin_status_cell('Капча', $captcha ? 'включена' : 'выключена', $captcha);
		site_admin_status_cell('Блоки', $blocks ? 'включены' : 'выключены', $blocks);
		site_admin_status_cell('Сессии', $sessions ? 'пишутся' : 'выключены', $sessions);
		site_admin_status_cell('TTL', $ttl ? 'включен' : 'выключен', $ttl);
		echo '</tr></table>';
		echo '</div></div>';

		site_admin_render_snapshot($admin_file);
		site_admin_render_quick_form($admin_file);

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>Подробные разделы</b></div>';
		echo '<div class="tp1_body">';
		echo '<table class="w100p"><tr>';
		site_admin_card($admin_file, 'SiteGeneralAdmin', 'Основное', 'Название, контактные адреса, лимит пользователей.');
		site_admin_card($admin_file, 'SiteAccessAdmin', 'Доступ', 'Онлайн-режим, регистрация, гости и защита.');
		site_admin_card($admin_file, 'SiteCaptchaAdmin', 'Капча', 'Размер, сложность и поведение защитного кода.');
		echo '</tr><tr>';
		site_admin_card($admin_file, 'SiteAppearanceAdmin', 'Внешний вид', 'Тема, язык и блоки интерфейса.');
		site_admin_card($admin_file, 'SiteTorrentsAdmin', 'Торренты и медиа', 'Размеры файлов, TTL, бонусы, аватары.');
		site_admin_card($admin_file, 'SiteSystemAdmin', 'Система', 'Сессии, IP-баны и служебные переключатели.');
		echo '</tr><tr>';
		site_admin_card($admin_file, 'TestTorrentsAdmin', 'Проверка раздач', 'Очередь тестовых раздач, решения проверки и уведомления авторам.');
		site_admin_card($admin_file, 'StatusDB', 'База данных', 'Проверка, анализ, оптимизация и ремонт таблиц.');
		site_admin_card($admin_file, 'MultitrackerAdmin', 'Мультитрекер', 'Внешние announce-адреса, ошибки и синхронизация.');
		echo '</tr></table>';
		echo '</div></div>';
	}
}

switch ($op) {
	case 'SiteSettingsAdmin':
		SiteSettingsAdmin();
		break;
}

?>
