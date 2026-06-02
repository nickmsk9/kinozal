<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

BuildMenu("" . $admin_file . ".php?op=SiteSettingsAdmin", "Настройки сайта", "site.svg");
BuildMenu("" . $admin_file . ".php?op=SiteSettingsAdmin#captcha-settings", "Настройки каптчи", "captcha.svg");

?>
