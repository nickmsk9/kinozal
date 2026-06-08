<?php

if (!defined('ADMIN_FILE')) die('Illegal File Access');

require_once 'admin/site_settings_helpers.php';

if (!function_exists('SiteGeneralAdmin')) {
	function SiteGeneralAdmin()
	{
		global $admin_file, $SITENAME, $SITEEMAIL, $admin_email, $website_name, $maxusers;

		site_settings_ensure_schema();
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
			site_set_setting('site_name', trim((string)($_POST['site_name'] ?? $SITENAME)));
			site_set_setting('website_name', trim((string)($_POST['website_name'] ?? $website_name)));
			site_set_setting('site_email', trim((string)($_POST['site_email'] ?? $SITEEMAIL)));
			site_set_setting('admin_email', trim((string)($_POST['admin_email'] ?? $admin_email)));
			site_set_setting('maxusers', max(1, (int)($_POST['maxusers'] ?? $maxusers)));
			site_admin_saved('Основные настройки');
		}

		echo '<div class="mn_wrap"><div class="tp1_title"><b>Основные настройки</b></div><div class="tp1_body">';
		site_admin_form_open($admin_file, 'SiteGeneralAdmin');
		echo '<tr><td class="colhead" colspan="2">Идентичность сайта</td></tr>';
		site_admin_text_row('Название сайта', 'site_name', site_setting('site_name', (string)$SITENAME), 60, 'Показывается в заголовках страниц и уведомлениях.');
		site_admin_text_row('Краткое имя', 'website_name', site_setting('website_name', (string)$website_name), 30, 'Используется в служебных формах.');
		echo '<tr><td class="colhead" colspan="2">Контакты</td></tr>';
		site_admin_text_row('Email сайта', 'site_email', site_setting('site_email', (string)$SITEEMAIL), 60, 'Адрес отправителя и контактный адрес. SMTP-настроек в движке больше нет.');
		site_admin_text_row('Email администратора', 'admin_email', site_setting('admin_email', (string)$admin_email), 60, 'Контакт для административных форм.');
		echo '<tr><td class="colhead" colspan="2">Лимиты</td></tr>';
		site_admin_text_row('Максимум пользователей', 'maxusers', site_setting_int('maxusers', (int)$maxusers, 1, 10000000), 12, 'Проверяется при регистрации.');
		site_admin_form_close();
		echo '</div></div>';
	}
}

switch ($op) {
	case 'SiteGeneralAdmin':
		SiteGeneralAdmin();
		break;
}

?>
