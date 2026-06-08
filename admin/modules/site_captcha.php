<?php

if (!defined('ADMIN_FILE')) die('Illegal File Access');

require_once 'admin/site_settings_helpers.php';

if (!function_exists('SiteCaptchaAdmin')) {
	function SiteCaptchaAdmin()
	{
		global $admin_file, $use_captcha;

		site_settings_ensure_schema();
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
			site_set_setting('use_captcha', !empty($_POST['use_captcha']) ? '1' : '0');
			site_set_setting('captcha_length', max(4, min(8, (int)($_POST['captcha_length'] ?? 5))));
			site_set_setting('captcha_width', max(120, min(320, (int)($_POST['captcha_width'] ?? 180))));
			site_set_setting('captcha_height', max(36, min(120, (int)($_POST['captcha_height'] ?? 56))));
			site_set_setting('captcha_front_lines', max(0, min(12, (int)($_POST['captcha_front_lines'] ?? 2))));
			site_set_setting('captcha_behind_lines', max(0, min(20, (int)($_POST['captcha_behind_lines'] ?? 4))));
			site_set_setting('captcha_max_angle', max(0, min(35, (int)($_POST['captcha_max_angle'] ?? 12))));
			site_set_setting('captcha_max_offset', max(0, min(20, (int)($_POST['captcha_max_offset'] ?? 6))));
			site_set_setting('captcha_distortion', !empty($_POST['captcha_distortion']) ? '1' : '0');
			site_admin_saved('Настройки капчи');
		}

		echo '<div class="mn_wrap"><div class="tp1_title"><b>Капча</b></div><div class="tp1_body">';
		site_admin_form_open($admin_file, 'SiteCaptchaAdmin');
		echo '<tr><td class="colhead" colspan="2">Включение</td></tr>';
		site_admin_bool_row('Капча', 'use_captcha', site_setting_bool('use_captcha', !empty($use_captcha)), 'Используется на регистрации и восстановлении доступа.');
		echo '<tr><td class="colhead" colspan="2">Геометрия и сложность</td></tr>';
		site_admin_text_row('Длина кода', 'captcha_length', site_setting_int('captcha_length', 5, 4, 8), 8, 'От 4 до 8 символов.');
		site_admin_text_row('Ширина картинки', 'captcha_width', site_setting_int('captcha_width', 180, 120, 320), 8, 'От 120 до 320 пикселей.');
		site_admin_text_row('Высота картинки', 'captcha_height', site_setting_int('captcha_height', 56, 36, 120), 8, 'От 36 до 120 пикселей.');
		site_admin_text_row('Линий поверх текста', 'captcha_front_lines', site_setting_int('captcha_front_lines', 2, 0, 12), 8, 'От 0 до 12.');
		site_admin_text_row('Линий за текстом', 'captcha_behind_lines', site_setting_int('captcha_behind_lines', 4, 0, 20), 8, 'От 0 до 20.');
		site_admin_text_row('Максимальный угол', 'captcha_max_angle', site_setting_int('captcha_max_angle', 12, 0, 35), 8, 'От 0 до 35 градусов.');
		site_admin_text_row('Максимальное смещение', 'captcha_max_offset', site_setting_int('captcha_max_offset', 6, 0, 20), 8, 'От 0 до 20 пикселей.');
		site_admin_bool_row('Искажение', 'captcha_distortion', site_setting_bool('captcha_distortion', true), 'Делает картинку сложнее для распознавания.');
		site_admin_form_close();
		echo '</div></div>';
	}
}

switch ($op) {
	case 'SiteCaptchaAdmin':
		SiteCaptchaAdmin();
		break;
}

?>
