<?php

if (!defined('ADMIN_FILE')) die('Illegal File Access');

require_once 'admin/site_settings_helpers.php';

if (!function_exists('SiteAccessAdmin')) {
	function SiteAccessAdmin()
	{
		global $admin_file, $SITE_ONLINE, $deny_signup, $use_email_act, $enable_adv_antidreg, $allow_guests_details;

		site_settings_ensure_schema();
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
			site_set_setting('site_online', !empty($_POST['site_online']) ? '1' : '0');
			site_set_setting('deny_signup', !empty($_POST['deny_signup']) ? '1' : '0');
			site_set_setting('use_email_act', !empty($_POST['use_email_act']) ? '1' : '0');
			site_set_setting('enable_adv_antidreg', !empty($_POST['enable_adv_antidreg']) ? '1' : '0');
			site_set_setting('allow_guests_details', !empty($_POST['allow_guests_details']) ? '1' : '0');
			site_admin_saved('Настройки доступа');
		}

		echo '<div class="mn_wrap"><div class="tp1_title"><b>Доступ и регистрация</b></div><div class="tp1_body">';
		site_admin_form_open($admin_file, 'SiteAccessAdmin');
		echo '<tr><td class="colhead" colspan="2">Режим работы</td></tr>';
		site_admin_bool_row('Сайт онлайн', 'site_online', site_setting_bool('site_online', !empty($SITE_ONLINE)), 'Если выключить, сайт покажет страницу техработ.');
		site_admin_bool_row('Закрыть регистрацию', 'deny_signup', site_setting_bool('deny_signup', !empty($deny_signup)), 'Обычная регистрация будет отключена.');
		echo '<tr><td class="colhead" colspan="2">Регистрация</td></tr>';
		site_admin_bool_row('Активация по email', 'use_email_act', site_setting_bool('use_email_act', !empty($use_email_act)), 'Работает только если на сервере настроена PHP-функция mail().');
		site_admin_bool_row('Защита от повторной регистрации', 'enable_adv_antidreg', site_setting_bool('enable_adv_antidreg', !empty($enable_adv_antidreg)), 'Проверяет старые cookie и IP.');
		echo '<tr><td class="colhead" colspan="2">Гости</td></tr>';
		site_admin_bool_row('Гости на странице деталей', 'allow_guests_details', site_setting_bool('allow_guests_details', !empty($allow_guests_details)), 'Разрешает открывать details.php без входа.');
		site_admin_form_close();
		echo '</div></div>';
	}
}

switch ($op) {
	case 'SiteAccessAdmin':
		SiteAccessAdmin();
		break;
}

?>
