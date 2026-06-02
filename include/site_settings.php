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
			$res = sql_query("SELECT setting_key, setting_value FROM site_settings");
			if ($res) {
				while ($row = mysqli_fetch_assoc($res)) {
					$site_settings_cache[(string)$row['setting_key']] = (string)$row['setting_value'];
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

	$SITE_ONLINE = site_setting_bool('site_online', !empty($SITE_ONLINE));
	$deny_signup = site_setting_bool('deny_signup', !empty($deny_signup)) ? 1 : 0;
	$use_captcha = !empty($use_captcha) && site_setting_bool('use_captcha', true) ? 1 : 0;
	$use_blocks = site_setting_bool('use_blocks', !empty($use_blocks)) ? 1 : 0;
	$allow_guests_details = site_setting_bool('allow_guests_details', !empty($allow_guests_details));
	$maxusers = site_setting_int('maxusers', (int)$maxusers, 1, 10000000);
	$max_torrent_size = site_setting_int('max_torrent_size', (int)$max_torrent_size, 1024, 1024 * 1024 * 1024);

	$site_name = trim(site_setting('site_name', (string)$SITENAME));
	if ($site_name !== '') {
		$SITENAME = $site_name;
	}

	$site_email = trim(site_setting('site_email', (string)$SITEEMAIL));
	if ($site_email !== '') {
		$SITEEMAIL = $site_email;
	}
}

?>
