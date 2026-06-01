<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

if (!function_exists('SiteSettingsAdmin')) {
	function site_admin_h($value)
	{
		return htmlspecialchars_uni((string)$value);
	}

	function site_admin_bool_row($label, $name, $current, $hint = '')
	{
		echo '<tr><td class="rowhead w250">' . site_admin_h($label) . '</td><td>';
		echo '<label><input type="checkbox" name="' . site_admin_h($name) . '" value="1"' . ($current ? ' checked' : '') . '> включено</label>';
		if ($hint !== '') {
			echo '<br><span class="small">' . site_admin_h($hint) . '</span>';
		}
		echo '</td></tr>';
	}

	function site_admin_text_row($label, $name, $value, $size = 60, $hint = '')
	{
		echo '<tr><td class="rowhead w250">' . site_admin_h($label) . '</td><td>';
		echo '<input type="text" name="' . site_admin_h($name) . '" size="' . (int)$size . '" value="' . site_admin_h($value) . '">';
		if ($hint !== '') {
			echo '<br><span class="small">' . site_admin_h($hint) . '</span>';
		}
		echo '</td></tr>';
	}

	function SiteSettingsAdmin()
	{
		global $admin_file, $SITE_ONLINE, $SITENAME, $SITEEMAIL, $maxusers, $max_torrent_size;
		global $deny_signup, $allow_invite_signup, $use_captcha, $use_blocks, $allow_guests_details;

		site_settings_ensure_schema();
		$messages = array();

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_site_settings'])) {
			site_set_setting('site_online', !empty($_POST['site_online']) ? '1' : '0');
			site_set_setting('deny_signup', !empty($_POST['deny_signup']) ? '1' : '0');
			site_set_setting('allow_invite_signup', !empty($_POST['allow_invite_signup']) ? '1' : '0');
			site_set_setting('use_captcha', !empty($_POST['use_captcha']) ? '1' : '0');
			site_set_setting('use_blocks', !empty($_POST['use_blocks']) ? '1' : '0');
			site_set_setting('allow_guests_details', !empty($_POST['allow_guests_details']) ? '1' : '0');
			site_set_setting('site_name', trim((string)($_POST['site_name'] ?? $SITENAME)));
			site_set_setting('site_email', trim((string)($_POST['site_email'] ?? $SITEEMAIL)));
			site_set_setting('maxusers', max(1, (int)($_POST['maxusers'] ?? $maxusers)));
			site_set_setting('max_torrent_size', max(1024, (int)($_POST['max_torrent_size'] ?? $max_torrent_size)));

			site_settings_apply_runtime_overrides();
			$messages[] = 'Настройки сайта сохранены.';
		}

		if ($messages) {
			stdmsg('Настройки сайта', implode('<br>', array_map('site_admin_h', $messages)));
		}

		$site_online = site_setting_bool('site_online', !empty($SITE_ONLINE));
		$signup_closed = site_setting_bool('deny_signup', !empty($deny_signup));
		$invite_signup = site_setting_bool('allow_invite_signup', !empty($allow_invite_signup));
		$captcha = site_setting_bool('use_captcha', !empty($use_captcha));
		$blocks = site_setting_bool('use_blocks', !empty($use_blocks));
		$guest_details = site_setting_bool('allow_guests_details', !empty($allow_guests_details));
		$site_name = site_setting('site_name', (string)$SITENAME);
		$site_email = site_setting('site_email', (string)$SITEEMAIL);
		$users_limit = site_setting_int('maxusers', (int)$maxusers, 1, 10000000);
		$torrent_size = site_setting_int('max_torrent_size', (int)$max_torrent_size, 1024, 1024 * 1024 * 1024);

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>Настройки сайта</b></div>';
		echo '<div class="tp1_body">';
		echo '<form method="post" action="' . site_admin_h($admin_file) . '.php?op=SiteSettingsAdmin">';
		echo '<input type="hidden" name="save_site_settings" value="1">';
		echo '<table class="tables2 w100p">';
		echo '<tr><td class="colhead" colspan="2">Основные параметры</td></tr>';
		site_admin_text_row('Название сайта', 'site_name', $site_name, 60, 'Используется в заголовках страниц и письмах.');
		site_admin_text_row('Email сайта', 'site_email', $site_email, 60, 'Адрес отправителя и контактный адрес по умолчанию.');
		site_admin_text_row('Максимум пользователей', 'maxusers', $users_limit, 12, 'Проверяется при регистрации.');
		site_admin_text_row('Максимальный .torrent, байт', 'max_torrent_size', $torrent_size, 12, 'Используется upload/takeupload для ограничения файла.');
		echo '<tr><td class="colhead" colspan="2">Доступ и поведение</td></tr>';
		site_admin_bool_row('Сайт онлайн', 'site_online', $site_online, 'Если выключить, сайт покажет страницу техработ.');
		site_admin_bool_row('Закрыть регистрацию', 'deny_signup', $signup_closed, 'Обычная регистрация будет отключена.');
		site_admin_bool_row('Регистрация по инвайтам', 'allow_invite_signup', $invite_signup, 'Разрешает форму инвайта при закрытой регистрации.');
		site_admin_bool_row('Капча', 'use_captcha', $captcha, 'Используется на регистрации и восстановлении доступа.');
		site_admin_bool_row('Система блоков', 'use_blocks', $blocks, 'Управляет выводом блоков сайта.');
		site_admin_bool_row('Гости на странице деталей', 'allow_guests_details', $guest_details, 'Разрешает открывать details.php без входа.');
		echo '<tr><td colspan="2" class="center"><input type="submit" class="buttonS" value="Сохранить настройки"></td></tr>';
		echo '</table>';
		echo '</form>';
		echo '</div>';
		echo '</div>';
	}
}

switch ($op) {
	case 'SiteSettingsAdmin':
		SiteSettingsAdmin();
		break;
}

?>
