<?php

if (!defined('ADMIN_FILE')) die('Illegal File Access');

require_once 'admin/site_settings_helpers.php';

if (!function_exists('SiteAppearanceAdmin')) {
	function SiteAppearanceAdmin()
	{
		global $admin_file, $default_theme, $default_language, $use_lang, $use_blocks, $allow_block_hide;

		site_settings_ensure_schema();
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
			site_set_setting('default_theme', trim((string)($_POST['default_theme'] ?? $default_theme)));
			site_set_setting('use_lang', !empty($_POST['use_lang']) ? '1' : '0');
			site_set_setting('default_language', trim((string)($_POST['default_language'] ?? $default_language)));
			site_set_setting('use_blocks', !empty($_POST['use_blocks']) ? '1' : '0');
			site_set_setting('allow_block_hide', !empty($_POST['allow_block_hide']) ? '1' : '0');
			site_admin_saved('Настройки внешнего вида');
		}

		$themes = site_admin_dirs('themes');
		$languages = array();
		foreach (site_admin_dirs('languages') as $dir => $title) {
			$value = preg_replace('/^lang_/', '', $dir);
			$languages[$value] = $value;
		}
		if (!$themes) $themes[$default_theme] = $default_theme;
		if (!$languages) $languages[$default_language] = $default_language;

		echo '<div class="mn_wrap"><div class="tp1_title"><b>Внешний вид</b></div><div class="tp1_body">';
		site_admin_form_open($admin_file, 'SiteAppearanceAdmin');
		echo '<tr><td class="colhead" colspan="2">Тема и язык</td></tr>';
		site_admin_select_row('Тема по умолчанию', 'default_theme', site_setting('default_theme', (string)$default_theme), $themes, 'Для гостей и пользователей без выбранной темы.');
		site_admin_bool_row('Языковая система', 'use_lang', site_setting_bool('use_lang', !empty($use_lang)), 'Подключает файлы из каталога languages.');
		site_admin_select_row('Язык по умолчанию', 'default_language', site_setting('default_language', (string)$default_language), $languages, 'Используется если у пользователя язык не выбран.');
		echo '<tr><td class="colhead" colspan="2">Блоки</td></tr>';
		site_admin_bool_row('Система блоков', 'use_blocks', site_setting_bool('use_blocks', !empty($use_blocks)), 'Управляет выводом блоков сайта.');
		site_admin_bool_row('Сворачивание блоков', 'allow_block_hide', site_setting_bool('allow_block_hide', !empty($allow_block_hide)), 'Показывает пользователям кнопку свернуть/развернуть.');
		site_admin_form_close();
		echo '</div></div>';
	}
}

switch ($op) {
	case 'SiteAppearanceAdmin':
		SiteAppearanceAdmin();
		break;
}

?>
