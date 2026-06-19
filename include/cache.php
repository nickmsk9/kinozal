<?php

if (!defined('IN_TRACKER')) {
	die('Direct access denied.');
}

function tracker_cache_enabled()
{
	global $cache_enabled;
	return !empty($cache_enabled);
}

function tracker_cache_prefix()
{
	global $cache_prefix;
	$prefix = isset($cache_prefix) ? (string)$cache_prefix : 'kinozal';
	$prefix = preg_replace('/[^a-zA-Z0-9:_-]/', '_', $prefix);
	return trim($prefix, ':') . ':';
}

function tracker_cache_clean_key($key)
{
	return trim(preg_replace('/[^a-zA-Z0-9:_-]/', '_', (string)$key), ':');
}

function tracker_cache_group_from_key($key)
{
	$key = tracker_cache_clean_key($key);
	if ($key === '') {
		return '';
	}

	$parts = explode(':', $key, 2);
	$group = $parts[0];

	if ($group === '' || str_starts_with($group, '__')) {
		return '';
	}

	return $group;
}

function tracker_cache_group_version_key($group)
{
	$group = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$group);
	return tracker_cache_prefix() . '__version:' . $group;
}

function tracker_cache_group_version($group)
{
	global $tracker_cache_group_versions;

	$group = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$group);
	if ($group === '') {
		return 1;
	}

	if (!isset($tracker_cache_group_versions) || !is_array($tracker_cache_group_versions)) {
		$tracker_cache_group_versions = array();
	}

	$local_key = tracker_cache_prefix() . $group;
	if (isset($tracker_cache_group_versions[$local_key])) {
		return max(1, (int)$tracker_cache_group_versions[$local_key]);
	}

	$version = 1;
	$redis = tracker_cache_redis();

	if ($redis) {
		try {
			$value = $redis->get(tracker_cache_group_version_key($group));
			$version = max(1, (int)$value);
			if ($value === false || $value === null) {
				$redis->setnx(tracker_cache_group_version_key($group), 1);
			}
		} catch (Throwable $e) {
			$version = 1;
		}
	}

	$tracker_cache_group_versions[$local_key] = $version;
	return $version;
}

function tracker_cache_bump_group_version($group)
{
	global $tracker_cache_group_versions;

	$group = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$group);
	if ($group === '') {
		return false;
	}

	if (!isset($tracker_cache_group_versions) || !is_array($tracker_cache_group_versions)) {
		$tracker_cache_group_versions = array();
	}

	$local_key = tracker_cache_prefix() . $group;
	$version = isset($tracker_cache_group_versions[$local_key])
		? max(1, (int)$tracker_cache_group_versions[$local_key]) + 1
		: 2;

	$redis = tracker_cache_redis();
	if ($redis) {
		try {
			$version = max(1, (int)$redis->incr(tracker_cache_group_version_key($group)));
		} catch (Throwable $e) {
			$version = isset($tracker_cache_group_versions[$local_key])
				? max(1, (int)$tracker_cache_group_versions[$local_key]) + 1
				: 2;
		}
	}

	$tracker_cache_group_versions[$local_key] = $version;

	return (bool)$redis;
}

function tracker_cache_key($key)
{
	$key = tracker_cache_clean_key($key);
	$group = tracker_cache_group_from_key($key);

	if ($group === '') {
		return tracker_cache_prefix() . $key;
	}

	$rest = substr($key, strlen($group));

	return tracker_cache_prefix() . $group . ':v' . tracker_cache_group_version($group) . $rest;
}

function tracker_cache_pattern_key($pattern)
{
	$pattern = preg_replace('/[^a-zA-Z0-9:_*_-]/', '_', (string)$pattern);

	if (preg_match('/^([a-zA-Z0-9_-]+):(.*)$/', $pattern, $m)) {
		return tracker_cache_prefix() . $m[1] . ':v*:' . $m[2];
	}

	return tracker_cache_prefix() . $pattern;
}

function tracker_cache_pattern_group($pattern)
{
	$pattern = preg_replace('/[^a-zA-Z0-9:_*_-]/', '_', (string)$pattern);

	if (preg_match('/^([a-zA-Z0-9_-]+):\*$/', $pattern, $m)) {
		return $m[1];
	}

	return '';
}

function tracker_cache_delete_local_group($group)
{
	global $tracker_cache_local;

	if (!isset($tracker_cache_local) || !is_array($tracker_cache_local)) {
		return;
	}

	$group = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$group);
	$pattern = '/^' . preg_quote(tracker_cache_prefix() . $group . ':v', '/') . '\d+(?::|$)/';

	foreach (array_keys($tracker_cache_local) as $key) {
		if (preg_match($pattern, $key)) {
			unset($tracker_cache_local[$key]);
		}
	}
}

function tracker_cache_redis()
{
	static $redis = null;
	static $checked = false;

	if ($checked) {
		return $redis;
	}

	$checked = true;

	if (!tracker_cache_enabled() || !class_exists('Redis')) {
		return null;
	}

	global $cache_backend, $redis_host, $redis_port, $redis_timeout, $redis_database, $redis_password;
	if (isset($cache_backend) && (string)$cache_backend !== 'redis') {
		return null;
	}

	$host = isset($redis_host) ? (string)$redis_host : '127.0.0.1';
	$port = isset($redis_port) ? (int)$redis_port : 6379;
	$timeout = isset($redis_timeout) ? (float)$redis_timeout : 0.25;

	try {
		$client = new Redis();
		if (!$client->connect($host, $port, $timeout)) {
			return null;
		}

		if (!empty($redis_password) && !$client->auth((string)$redis_password)) {
			$client->close();
			return null;
		}

		if (isset($redis_database) && (int)$redis_database > 0) {
			$client->select((int)$redis_database);
		}

		$redis = $client;
	} catch (Throwable $e) {
		$redis = null;
	}

	return $redis;
}

function tracker_cache_get($key, $default = null)
{
	global $tracker_cache_local;
	if (!isset($tracker_cache_local) || !is_array($tracker_cache_local)) {
		$tracker_cache_local = array();
	}

	$cache_key = tracker_cache_key($key);
	if (array_key_exists($cache_key, $tracker_cache_local)) {
		return $tracker_cache_local[$cache_key];
	}

	$redis = tracker_cache_redis();
	if (!$redis) {
		return $default;
	}

	try {
		$payload = $redis->get($cache_key);
		if ($payload === false || $payload === null) {
			return $default;
		}

		$value = @unserialize($payload, array('allowed_classes' => false));
		if ($value === false && $payload !== serialize(false)) {
			return $default;
		}

		$tracker_cache_local[$cache_key] = $value;
		return $value;
	} catch (Throwable $e) {
		return $default;
	}
}

function tracker_cache_set($key, $value, $ttl = 300)
{
	global $tracker_cache_local;
	if (!isset($tracker_cache_local) || !is_array($tracker_cache_local)) {
		$tracker_cache_local = array();
	}

	$cache_key = tracker_cache_key($key);
	$tracker_cache_local[$cache_key] = $value;

	$redis = tracker_cache_redis();
	if (!$redis) {
		return false;
	}

	try {
		$payload = serialize($value);
		$ttl = (int)$ttl;
		if ($ttl > 0) {
			return (bool)$redis->setex($cache_key, $ttl, $payload);
		}
		return (bool)$redis->set($cache_key, $payload);
	} catch (Throwable $e) {
		return false;
	}
}

function tracker_cache_delete($key)
{
	global $tracker_cache_local;
	unset($tracker_cache_local[tracker_cache_key($key)]);

	$redis = tracker_cache_redis();
	if (!$redis) {
		return false;
	}

	try {
		return (bool)$redis->del(tracker_cache_key($key));
	} catch (Throwable $e) {
		return false;
	}
}

function tracker_cache_delete_pattern($pattern)
{
	global $tracker_cache_local;

	$group = tracker_cache_pattern_group($pattern);
	if ($group !== '') {
		tracker_cache_delete_local_group($group);
		return tracker_cache_bump_group_version($group);
	}

	$pattern_key = tracker_cache_pattern_key($pattern);
	$local_pattern = '/^' . str_replace('\\*', '.*', preg_quote($pattern_key, '/')) . '$/';
	if (isset($tracker_cache_local) && is_array($tracker_cache_local)) {
		foreach (array_keys($tracker_cache_local) as $key) {
			if (preg_match($local_pattern, $key)) {
				unset($tracker_cache_local[$key]);
			}
		}
	}

	$redis = tracker_cache_redis();
	if (!$redis) {
		return false;
	}

	try {
		$iterator = null;
		$keys = array();

		do {
			$scan = $redis->scan($iterator, $pattern_key, 250);
			if (is_array($scan) && $scan) {
				$keys = array_merge($keys, $scan);
				if (count($keys) >= 250) {
					foreach ($keys as $key) {
						unset($tracker_cache_local[$key]);
					}
					$redis->del($keys);
					$keys = array();
				}
			}
		} while ($iterator > 0);

		if ($keys) {
			foreach ($keys as $key) {
				unset($tracker_cache_local[$key]);
			}
			$redis->del($keys);
		}

		return true;
	} catch (Throwable $e) {
		return false;
	}
}

function tracker_cache_remember($key, $ttl, callable $callback)
{
	$miss = new stdClass();
	$value = tracker_cache_get($key, $miss);

	if ($value !== $miss) {
		return $value;
	}

	$value = $callback();
	tracker_cache_set($key, $value, $ttl);

	return $value;
}

function tracker_cache_invalidate_for_query($query)
{
	$sql = strtolower((string)$query);
	if (!preg_match('/^\s*(insert|update|delete|replace|truncate|alter|drop|create)\b/', $sql)) {
		return;
	}

	if (preg_match('/^\s*update\s+users\s+set\s+(.+?)\s+where\s+id\s*=/is', $sql, $match)) {
		$fields = array();
		foreach (preg_split('/\s*,\s*/', trim($match[1])) as $assignment) {
			if (preg_match('/^`?([a-z0-9_]+)`?\s*=/i', $assignment, $field_match)) {
				$fields[] = $field_match[1];
			}
		}
		if ($fields && !array_diff($fields, array('ip', 'last_access'))) {
			return;
		}
	}

	if (!preg_match('/\b(site_settings|orbital_blocks|categories|torrents|torrent_details|torrents_descr|torrent_trackers|ratings|comments|snatched|pay_transactions|pay_settings|cups|user_cups|user_status_assignments|countries|uarch_smiles|users|news|readtorrents)\b/', $sql)) {
		return;
	}

	$exact = array();
	$patterns = array();
	$has = function ($table) use ($sql) {
		return preg_match('/\b' . preg_quote($table, '/') . '\b/', $sql);
	};

	if ($has('site_settings')) {
		$exact[] = 'site_settings:all';
	}

	if ($has('orbital_blocks')) {
		$exact[] = 'blocks:active';
		$patterns[] = 'block:*';
		$patterns[] = 'index:*';
	}

	if ($has('categories')) {
		$patterns[] = 'categories:*';
		$patterns[] = 'browse:*';
		$patterns[] = 'details:*';
		$patterns[] = 'block:*';
		$patterns[] = 'index:*';
	}

	if ($has('torrents') || $has('torrent_details') || $has('torrents_descr') || $has('torrent_trackers') || $has('ratings')) {
		$patterns[] = 'browse:*';
		$patterns[] = 'details:*';
		$patterns[] = 'userdetails:*';
		$patterns[] = 'block:*';
		$patterns[] = 'index:*';
	}

	if ($has('readtorrents')) {
		$patterns[] = 'browse:*';
	}

	if ($has('comments')) {
		$patterns[] = 'details:*';
		$patterns[] = 'userdetails:*';
		$patterns[] = 'block:*';
		$patterns[] = 'index:*';
	}

	if ($has('snatched')) {
		$patterns[] = 'userdetails:*';
	}

	if ($has('pay_transactions') || $has('pay_settings')) {
		$patterns[] = 'pay:*';
		$patterns[] = 'block:*';
		$patterns[] = 'index:*';
	}

	if ($has('cups') || $has('user_cups') || $has('user_status_assignments') || $has('countries')) {
		$patterns[] = 'cups:*';
		$patterns[] = 'countries:*';
		$patterns[] = 'userdetails:*';
		$patterns[] = 'block:*';
		$patterns[] = 'index:*';
	}

	if ($has('uarch_smiles')) {
		$patterns[] = 'uarch:*';
		$patterns[] = 'block:*';
		$patterns[] = 'index:*';
	}

	if ($has('users')) {
		$patterns[] = 'userdetails:*';
		$patterns[] = 'pay:*';
		$patterns[] = 'cups:*';
		$patterns[] = 'uarch:*';
		$patterns[] = 'block:*';
		$patterns[] = 'index:*';
	}

	if ($has('news')) {
		$patterns[] = 'block:*';
		$patterns[] = 'index:*';
	}

	foreach (array_unique($exact) as $key) {
		tracker_cache_delete($key);
	}

	foreach (array_unique($patterns) as $pattern) {
		tracker_cache_delete_pattern($pattern);
	}
}

?>
