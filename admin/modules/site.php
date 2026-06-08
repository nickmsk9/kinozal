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

	function SiteSettingsAdmin()
	{
		global $admin_file, $SITE_ONLINE, $deny_signup, $use_captcha;

		site_settings_ensure_schema();

		$site_online = site_setting_bool('site_online', !empty($SITE_ONLINE));
		$signup_closed = site_setting_bool('deny_signup', !empty($deny_signup));
		$captcha = site_setting_bool('use_captcha', !empty($use_captcha));

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>Управление сайтом</b></div>';
		echo '<div class="tp1_body">';
		echo '<table class="tables2 w100p">';
		echo '<tr><td class="colhead center" colspan="4">Состояние</td></tr><tr>';
		site_admin_status_cell('Сайт', $site_online ? 'онлайн' : 'техработы', $site_online);
		site_admin_status_cell('Регистрация', $signup_closed ? 'закрыта' : 'открыта', !$signup_closed);
		site_admin_status_cell('Капча', $captcha ? 'включена' : 'выключена', $captcha);
		site_admin_status_cell('SMTP', 'удален', true);
		echo '</tr></table><br>';
		echo '<table class="w100p"><tr>';
		site_admin_card($admin_file, 'SiteGeneralAdmin', 'Основное', 'Название, контактные адреса, лимит пользователей.');
		site_admin_card($admin_file, 'SiteAccessAdmin', 'Доступ', 'Онлайн-режим, регистрация, гости и защита.');
		site_admin_card($admin_file, 'SiteCaptchaAdmin', 'Капча', 'Размер, сложность и поведение защитного кода.');
		echo '</tr><tr>';
		site_admin_card($admin_file, 'SiteAppearanceAdmin', 'Внешний вид', 'Тема, язык и блоки интерфейса.');
		site_admin_card($admin_file, 'SiteTorrentsAdmin', 'Торренты и медиа', 'Размеры файлов, TTL, бонусы, аватары.');
		site_admin_card($admin_file, 'SiteSystemAdmin', 'Система', 'Сессии, IP-баны и служебные переключатели.');
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
