<?php

if (!defined('ADMIN_FILE')) die('Illegal File Access');

require_once 'admin/site_settings_helpers.php';

if (!function_exists('SiteTorrentsAdmin')) {
	function SiteTorrentsAdmin()
	{
		global $admin_file, $max_torrent_size, $use_ttl, $ttl_days, $use_wait, $points_per_hour, $avatar_max_width, $avatar_max_height, $max_image_size;

		site_settings_ensure_schema();
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
			site_set_setting('max_torrent_size', max(1024, (int)($_POST['max_torrent_size'] ?? $max_torrent_size)));
			site_set_setting('use_ttl', !empty($_POST['use_ttl']) ? '1' : '0');
			site_set_setting('ttl_days', max(1, min(3650, (int)($_POST['ttl_days'] ?? $ttl_days))));
			site_set_setting('use_wait', !empty($_POST['use_wait']) ? '1' : '0');
			site_set_setting('points_per_hour', max(0, min(100000, (int)($_POST['points_per_hour'] ?? $points_per_hour))));
			site_set_setting('avatar_max_width', max(20, min(1000, (int)($_POST['avatar_max_width'] ?? $avatar_max_width))));
			site_set_setting('avatar_max_height', max(20, min(1000, (int)($_POST['avatar_max_height'] ?? $avatar_max_height))));
			site_set_setting('max_image_size', max(1024, min(20 * 1024 * 1024, (int)($_POST['max_image_size'] ?? $max_image_size))));
			site_admin_saved('Настройки торрентов и медиа');
		}

		echo '<div class="mn_wrap"><div class="tp1_title"><b>Торренты и медиа</b></div><div class="tp1_body">';
		site_admin_form_open($admin_file, 'SiteTorrentsAdmin');
		echo '<tr><td class="colhead" colspan="2">Торренты</td></tr>';
		site_admin_text_row('Максимальный .torrent, байт', 'max_torrent_size', site_setting_int('max_torrent_size', (int)$max_torrent_size, 1024, 1024 * 1024 * 1024), 12, 'Используется upload/takeupload для ограничения файла.');
		site_admin_bool_row('TTL раздач', 'use_ttl', site_setting_bool('use_ttl', !empty($use_ttl)), 'Автоочистка может удалять старые раздачи.');
		site_admin_text_row('TTL, дней', 'ttl_days', site_setting_int('ttl_days', (int)$ttl_days, 1, 3650), 8, 'От 1 до 3650 дней.');
		site_admin_bool_row('Ожидание для низкого рейтинга', 'use_wait', site_setting_bool('use_wait', !empty($use_wait)), 'Включает задержки скачивания по классу/рейтингу.');
		site_admin_text_row('Бонусов в час', 'points_per_hour', site_setting_int('points_per_hour', (int)$points_per_hour, 0, 100000), 8, 'Начисление за сидирование.');
		echo '<tr><td class="colhead" colspan="2">Медиа</td></tr>';
		site_admin_text_row('Ширина аватара, px', 'avatar_max_width', site_setting_int('avatar_max_width', (int)$avatar_max_width, 20, 1000), 8, 'От 20 до 1000 пикселей.');
		site_admin_text_row('Высота аватара, px', 'avatar_max_height', site_setting_int('avatar_max_height', (int)$avatar_max_height, 20, 1000), 8, 'От 20 до 1000 пикселей.');
		site_admin_text_row('Максимальный размер картинки, байт', 'max_image_size', site_setting_int('max_image_size', (int)$max_image_size, 1024, 20 * 1024 * 1024), 12, 'От 1 КБ до 20 МБ.');
		site_admin_form_close();
		echo '</div></div>';
	}
}

switch ($op) {
	case 'SiteTorrentsAdmin':
		SiteTorrentsAdmin();
		break;
}

?>
