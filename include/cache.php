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

function tracker_cache_key($key)
{
	$key = preg_replace('/[^a-zA-Z0-9:_-]/', '_', (string)$key);
	return tracker_cache_prefix() . $key;
}

function tracker_cache_pattern_key($pattern)
{
	$pattern = preg_replace('/[^a-zA-Z0-9:_*_-]/', '_', (string)$pattern);
	return tracker_cache_prefix() . $pattern;
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
		$keys = $redis->keys($pattern_key);
		if (!is_array($keys) || !$keys) {
			return true;
		}

		foreach ($keys as $key) {
			unset($tracker_cache_local[$key]);
		}

		return (bool)$redis->del($keys);
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

	if (preg_match('/\bsite_settings\b/', $sql)) {
		tracker_cache_delete('site_settings:all');
	}

	if (preg_match('/\borbital_blocks\b/', $sql)) {
		tracker_cache_delete('blocks:active');
		tracker_cache_delete_pattern('index:*');
	}

	if (preg_match('/\b(users|torrents|news|uarch_smiles|cups|user_cups|user_status_assignments|countries|pay_transactions|pay_settings)\b/', $sql)) {
		tracker_cache_delete_pattern('index:*');
	}
}

?>
