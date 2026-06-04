<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

BuildMenu("" . $admin_file . ".php?op=SiteSettingsAdmin", "Управление сайтом", "site.svg");
BuildMenu("" . $admin_file . ".php?op=SiteGeneralAdmin", "Основное сайта", "site.svg");
BuildMenu("" . $admin_file . ".php?op=SiteAccessAdmin", "Доступ сайта", "system.svg");
BuildMenu("" . $admin_file . ".php?op=SiteCaptchaAdmin", "Капча", "captcha.svg");
BuildMenu("" . $admin_file . ".php?op=SiteAppearanceAdmin", "Внешний вид", "stylesheet.png");
BuildMenu("" . $admin_file . ".php?op=SiteTorrentsAdmin", "Торренты и медиа", "db.png");
BuildMenu("" . $admin_file . ".php?op=SiteSystemAdmin", "Система сайта", "system.png");

?>
