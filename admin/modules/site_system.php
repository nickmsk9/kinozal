<?php

if (!defined('ADMIN_FILE')) die('Illegal File Access');

require_once 'admin/site_settings_helpers.php';

if (!function_exists('SiteSystemAdmin')) {
	function SiteSystemAdmin()
	{
		global $admin_file, $use_ipbans, $use_sessions;

		site_settings_ensure_schema();
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
			site_set_setting('use_ipbans', !empty($_POST['use_ipbans']) ? '1' : '0');
			site_set_setting('use_sessions', !empty($_POST['use_sessions']) ? '1' : '0');
			site_admin_saved('Системные настройки');
		}

		echo '<div class="mn_wrap"><div class="tp1_title"><b>Система</b></div><div class="tp1_body">';
		echo '<table class="tables2 w100p"><tr><td class="colhead" colspan="2">Почта</td></tr>';
		echo '<tr><td class="rowhead w250">SMTP</td><td><b>Удален из движка</b><br><span class="small">Отправка, где она еще нужна, идет только через PHP mail() на уровне сервера.</span></td></tr>';
		echo '</table><br>';
		site_admin_form_open($admin_file, 'SiteSystemAdmin');
		echo '<tr><td class="colhead" colspan="2">Служебные переключатели</td></tr>';
		site_admin_bool_row('IP-баны', 'use_ipbans', site_setting_bool('use_ipbans', !empty($use_ipbans)), 'Проверяет таблицу bans при входе.');
		site_admin_bool_row('Сессии онлайн', 'use_sessions', site_setting_bool('use_sessions', !empty($use_sessions)), 'Используется блоком онлайн и учетом активности.');
		site_admin_form_close();
		echo '</div></div>';
	}
}

switch ($op) {
	case 'SiteSystemAdmin':
		SiteSystemAdmin();
		break;
}

?>
