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
		global $deny_signup, $use_captcha, $use_blocks, $allow_guests_details;

		site_settings_ensure_schema();
		$messages = array();

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_site_settings'])) {
			site_set_setting('site_online', !empty($_POST['site_online']) ? '1' : '0');
			site_set_setting('deny_signup', !empty($_POST['deny_signup']) ? '1' : '0');
			site_set_setting('use_captcha', !empty($_POST['use_captcha']) ? '1' : '0');
			site_set_setting('captcha_length', max(4, min(8, (int)($_POST['captcha_length'] ?? 5))));
			site_set_setting('captcha_width', max(120, min(320, (int)($_POST['captcha_width'] ?? 180))));
			site_set_setting('captcha_height', max(36, min(120, (int)($_POST['captcha_height'] ?? 56))));
			site_set_setting('captcha_front_lines', max(0, min(12, (int)($_POST['captcha_front_lines'] ?? 2))));
			site_set_setting('captcha_behind_lines', max(0, min(20, (int)($_POST['captcha_behind_lines'] ?? 4))));
			site_set_setting('captcha_max_angle', max(0, min(35, (int)($_POST['captcha_max_angle'] ?? 12))));
			site_set_setting('captcha_max_offset', max(0, min(20, (int)($_POST['captcha_max_offset'] ?? 6))));
			site_set_setting('captcha_distortion', !empty($_POST['captcha_distortion']) ? '1' : '0');
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
		$captcha = site_setting_bool('use_captcha', !empty($use_captcha));
		$captcha_length = site_setting_int('captcha_length', 5, 4, 8);
		$captcha_width = site_setting_int('captcha_width', 180, 120, 320);
		$captcha_height = site_setting_int('captcha_height', 56, 36, 120);
		$captcha_front_lines = site_setting_int('captcha_front_lines', 2, 0, 12);
		$captcha_behind_lines = site_setting_int('captcha_behind_lines', 4, 0, 20);
		$captcha_max_angle = site_setting_int('captcha_max_angle', 12, 0, 35);
		$captcha_max_offset = site_setting_int('captcha_max_offset', 6, 0, 20);
		$captcha_distortion = site_setting_bool('captcha_distortion', true);
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
		site_admin_bool_row('Капча', 'use_captcha', $captcha, 'Используется на регистрации и восстановлении доступа.');
		echo '<tr id="captcha-settings"><td class="colhead" colspan="2">Капча</td></tr>';
		site_admin_text_row('Длина кода', 'captcha_length', $captcha_length, 8, 'От 4 до 8 символов.');
		site_admin_text_row('Ширина картинки', 'captcha_width', $captcha_width, 8, 'От 120 до 320 пикселей.');
		site_admin_text_row('Высота картинки', 'captcha_height', $captcha_height, 8, 'От 36 до 120 пикселей.');
		site_admin_text_row('Линий поверх текста', 'captcha_front_lines', $captcha_front_lines, 8, 'От 0 до 12.');
		site_admin_text_row('Линий за текстом', 'captcha_behind_lines', $captcha_behind_lines, 8, 'От 0 до 20.');
		site_admin_text_row('Максимальный угол', 'captcha_max_angle', $captcha_max_angle, 8, 'От 0 до 35 градусов.');
		site_admin_text_row('Максимальное смещение', 'captcha_max_offset', $captcha_max_offset, 8, 'От 0 до 20 пикселей.');
		site_admin_bool_row('Искажение', 'captcha_distortion', $captcha_distortion, 'Делает картинку сложнее для распознавания.');
		echo '<tr><td class="colhead" colspan="2">Блоки и доступ</td></tr>';
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
