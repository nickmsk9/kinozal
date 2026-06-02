<?

if (!defined('IN_TRACKER')) {
	die('Прямой вызов запрещён.');
}

require_once ROOT_PATH . 'include/captcha/Gregwar/Captcha/CaptchaBuilderInterface.php';
require_once ROOT_PATH . 'include/captcha/Gregwar/Captcha/PhraseBuilderInterface.php';
require_once ROOT_PATH . 'include/captcha/Gregwar/Captcha/PhraseBuilder.php';
require_once ROOT_PATH . 'include/captcha/Gregwar/Captcha/ImageFileHandler.php';
require_once ROOT_PATH . 'include/captcha/Gregwar/Captcha/CaptchaBuilder.php';

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

function tracker_captcha_int_setting($key, $default, $min, $max)
{
	if (function_exists('site_setting_int')) {
		return site_setting_int($key, $default, $min, $max);
	}

	return max($min, min($max, (int)$default));
}

function tracker_captcha_bool_setting($key, $default)
{
	if (function_exists('site_setting_bool')) {
		return site_setting_bool($key, $default);
	}

	return (bool)$default;
}

function tracker_captcha_settings()
{
	return array(
		'length' => tracker_captcha_int_setting('captcha_length', 5, 4, 8),
		'width' => tracker_captcha_int_setting('captcha_width', 180, 120, 320),
		'height' => tracker_captcha_int_setting('captcha_height', 56, 36, 120),
		'front_lines' => tracker_captcha_int_setting('captcha_front_lines', 2, 0, 12),
		'behind_lines' => tracker_captcha_int_setting('captcha_behind_lines', 4, 0, 20),
		'max_angle' => tracker_captcha_int_setting('captcha_max_angle', 12, 0, 35),
		'max_offset' => tracker_captcha_int_setting('captcha_max_offset', 6, 0, 20),
		'distortion' => tracker_captcha_bool_setting('captcha_distortion', true),
	);
}

function tracker_captcha_session_gc()
{
	if (empty($_SESSION['tracker_captcha']) || !is_array($_SESSION['tracker_captcha'])) {
		$_SESSION['tracker_captcha'] = array();
	} else {
		$expires = time() - 900;
		foreach ($_SESSION['tracker_captcha'] as $id => $row) {
			if (empty($row['created']) || (int)$row['created'] < $expires) {
				unset($_SESSION['tracker_captcha'][$id]);
			}
		}
	}

	$expires = time() - 900;
	$dir = tracker_captcha_cache_dir();
	if (is_dir($dir)) {
		foreach (glob($dir . '*.php') ?: array() as $file) {
			if (@filemtime($file) < $expires) {
				@unlink($file);
			}
		}
	}
}

function tracker_captcha_cache_dir()
{
	return ROOT_PATH . 'cache' . DIRECTORY_SEPARATOR . 'captcha' . DIRECTORY_SEPARATOR;
}

function tracker_captcha_new_id()
{
	if (function_exists('random_bytes')) {
		return bin2hex(random_bytes(16));
	}

	return md5(uniqid('', true) . mt_rand());
}

function tracker_captcha_cache_file($id)
{
	$id = preg_replace('/[^a-f0-9]/i', '', (string)$id);
	return $id === '' ? '' : tracker_captcha_cache_dir() . $id . '.php';
}

function tracker_captcha_store($id, $phrase)
{
	$_SESSION['tracker_captcha'][$id] = array(
		'phrase' => $phrase,
		'created' => time(),
	);

	$dir = tracker_captcha_cache_dir();
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}

	$file = tracker_captcha_cache_file($id);
	if ($file !== '' && is_dir($dir)) {
		$data = "<?php\nreturn " . var_export(array(
			'phrase' => (string)$phrase,
			'created' => time(),
		), true) . ";\n";
		@file_put_contents($file, $data, LOCK_EX);
	}
}

function tracker_captcha_load($id)
{
	$id = trim((string)$id);

	if ($id !== '' && !empty($_SESSION['tracker_captcha'][$id]['phrase'])) {
		return (string)$_SESSION['tracker_captcha'][$id]['phrase'];
	}

	$file = tracker_captcha_cache_file($id);
	if ($file === '' || !is_file($file) || @filemtime($file) < time() - 900) {
		return '';
	}

	$row = include $file;
	if (!is_array($row) || empty($row['phrase']) || empty($row['created']) || (int)$row['created'] < time() - 900) {
		@unlink($file);
		return '';
	}

	$_SESSION['tracker_captcha'][$id] = array(
		'phrase' => (string)$row['phrase'],
		'created' => (int)$row['created'],
	);

	return (string)$row['phrase'];
}

function tracker_captcha_forget($id)
{
	unset($_SESSION['tracker_captcha'][$id]);

	$file = tracker_captcha_cache_file($id);
	if ($file !== '' && is_file($file)) {
		@unlink($file);
	}
}

function create_captcha()
{
	tracker_captcha_session_gc();

	$settings = tracker_captcha_settings();
	$builder = new PhraseBuilder($settings['length'], 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
	$phrase = $builder->build();
	$id = tracker_captcha_new_id();

	tracker_captcha_store($id, $phrase);

	return $id;
}

function tracker_captcha_validate($id, $answer)
{
	tracker_captcha_session_gc();

	$id = trim((string)$id);
	$answer = trim((string)$answer);

	if ($id === '' || $answer === '') {
		return false;
	}

	$phrase = tracker_captcha_load($id);
	tracker_captcha_forget($id);

	return $phrase !== '' && PhraseBuilder::comparePhrases($phrase, $answer);
}

function tracker_captcha_build_image($id)
{
	tracker_captcha_session_gc();

	$id = trim((string)$id);
	$phrase = tracker_captcha_load($id);
	if ($phrase === '') {
		$phrase = 'ERROR';
	}

	$settings = tracker_captcha_settings();
	$builder = new CaptchaBuilder($phrase);
	$builder
		->setImageType('png')
		->setDistortion($settings['distortion'])
		->setMaxFrontLines($settings['front_lines'])
		->setMaxBehindLines($settings['behind_lines'])
		->setMaxAngle($settings['max_angle'])
		->setMaxOffset($settings['max_offset'])
		->build($settings['width'], $settings['height']);

	return $builder;
}

function tracker_captcha_image_url($id)
{
	return 'captcha.php?id=' . urlencode((string)$id) . '&amp;t=' . time();
}

?>
