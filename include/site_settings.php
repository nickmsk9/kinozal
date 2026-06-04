<?php

if (!defined('IN_TRACKER') && !defined('ADMIN_FILE')) {
	die('Direct access denied.');
}

function site_settings_table_exists()
{
	$res = sql_query("SHOW TABLES LIKE 'site_settings'");
	return $res && mysqli_num_rows($res) > 0;
}

function site_settings_ensure_schema()
{
	sql_query("
		CREATE TABLE IF NOT EXISTS site_settings (
			setting_key varchar(80) NOT NULL,
			setting_value text NOT NULL,
			PRIMARY KEY (setting_key)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);
}

function site_setting($key, $default = '')
{
	global $site_settings_cache;

	$key = (string)$key;
	if (!isset($site_settings_cache) || !is_array($site_settings_cache)) {
		$cached = function_exists('tracker_cache_get')
			? tracker_cache_get('site_settings:all')
			: null;

		if (is_array($cached)) {
			$site_settings_cache = $cached;
		} else {
			$site_settings_cache = array();
			if (site_settings_table_exists()) {
				$res = sql_query("SELECT setting_key, setting_value FROM site_settings");
				if ($res) {
					while ($row = mysqli_fetch_assoc($res)) {
						$site_settings_cache[(string)$row['setting_key']] = (string)$row['setting_value'];
					}
				}
			}

			if (function_exists('tracker_cache_set')) {
				tracker_cache_set('site_settings:all', $site_settings_cache, 300);
			}
		}
	}

	return array_key_exists($key, $site_settings_cache) ? $site_settings_cache[$key] : $default;
}

function site_set_setting($key, $value)
{
	global $site_settings_cache;

	site_settings_ensure_schema();
	sql_query("
		INSERT INTO site_settings (setting_key, setting_value)
		VALUES (" . sqlesc((string)$key, true) . ", " . sqlesc((string)$value, true) . ")
		ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
	") or sqlerr(__FILE__, __LINE__);

	if (isset($site_settings_cache) && is_array($site_settings_cache)) {
		$site_settings_cache[(string)$key] = (string)$value;
	}

	if (function_exists('tracker_cache_delete')) {
		tracker_cache_delete('site_settings:all');
	}
}

function site_setting_bool($key, $default)
{
	$value = site_setting($key, $default ? '1' : '0');
	return in_array((string)$value, array('1', 'yes', 'true', 'on'), true);
}

function site_setting_int($key, $default, $min = 0, $max = null)
{
	$value = (int)site_setting($key, (string)$default);
	$value = max($min, $value);
	if ($max !== null) {
		$value = min((int)$max, $value);
	}
	return $value;
}

function site_settings_apply_runtime_overrides()
{
	global $SITE_ONLINE, $SITENAME, $SITEEMAIL, $maxusers, $max_torrent_size;
	global $deny_signup, $use_captcha, $use_blocks, $allow_guests_details;
	global $use_email_act, $use_ttl, $ttl_days, $points_per_hour, $points_per_cleanup, $autoclean_interval;
	global $use_wait, $use_lang, $use_ipbans, $use_sessions, $allow_block_hide;
	global $default_theme, $default_language, $avatar_max_width, $avatar_max_height, $max_image_size;
	global $admin_email, $website_name, $enable_adv_antidreg, $check_for_working_mta;

	$SITE_ONLINE = site_setting_bool('site_online', !empty($SITE_ONLINE));
	$deny_signup = site_setting_bool('deny_signup', !empty($deny_signup)) ? 1 : 0;
	$use_email_act = site_setting_bool('use_email_act', !empty($use_email_act)) ? 1 : 0;
	$use_captcha = !empty($use_captcha) && site_setting_bool('use_captcha', true) ? 1 : 0;
	$use_blocks = site_setting_bool('use_blocks', !empty($use_blocks)) ? 1 : 0;
	$allow_guests_details = site_setting_bool('allow_guests_details', !empty($allow_guests_details));
	$use_ttl = site_setting_bool('use_ttl', !empty($use_ttl)) ? 1 : 0;
	$use_wait = site_setting_bool('use_wait', !empty($use_wait)) ? 1 : 0;
	$use_lang = site_setting_bool('use_lang', !empty($use_lang)) ? 1 : 0;
	$use_ipbans = site_setting_bool('use_ipbans', !empty($use_ipbans)) ? 1 : 0;
	$use_sessions = site_setting_bool('use_sessions', !empty($use_sessions)) ? 1 : 0;
	$allow_block_hide = site_setting_bool('allow_block_hide', !empty($allow_block_hide));
	$enable_adv_antidreg = site_setting_bool('enable_adv_antidreg', !empty($enable_adv_antidreg));
	$check_for_working_mta = false;
	$maxusers = site_setting_int('maxusers', (int)$maxusers, 1, 10000000);
	$max_torrent_size = site_setting_int('max_torrent_size', (int)$max_torrent_size, 1024, 1024 * 1024 * 1024);
	$ttl_days = site_setting_int('ttl_days', (int)$ttl_days, 1, 3650);
	$points_per_hour = site_setting_int('points_per_hour', (int)$points_per_hour, 0, 100000);
	$points_per_cleanup = $points_per_hour * ($autoclean_interval / 3600);
	$avatar_max_width = site_setting_int('avatar_max_width', (int)$avatar_max_width, 20, 1000);
	$avatar_max_height = site_setting_int('avatar_max_height', (int)$avatar_max_height, 20, 1000);
	$max_image_size = site_setting_int('max_image_size', (int)$max_image_size, 1024, 20 * 1024 * 1024);

	$site_name = trim(site_setting('site_name', (string)$SITENAME));
	if ($site_name !== '') {
		$SITENAME = $site_name;
	}

	$site_email = trim(site_setting('site_email', (string)$SITEEMAIL));
	if ($site_email !== '') {
		$SITEEMAIL = $site_email;
	}

	$theme = trim(site_setting('default_theme', (string)$default_theme));
	if ($theme !== '') {
		$default_theme = $theme;
	}

	$language = trim(site_setting('default_language', (string)$default_language));
	if ($language !== '') {
		$default_language = $language;
	}

	$admin_mail = trim(site_setting('admin_email', (string)$admin_email));
	if ($admin_mail !== '') {
		$admin_email = $admin_mail;
	}

	$short_name = trim(site_setting('website_name', (string)$website_name));
	if ($short_name !== '') {
		$website_name = $short_name;
	}
}

?>
