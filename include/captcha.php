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
		return;
	}

	$expires = time() - 900;
	foreach ($_SESSION['tracker_captcha'] as $id => $row) {
		if (empty($row['created']) || (int)$row['created'] < $expires) {
			unset($_SESSION['tracker_captcha'][$id]);
		}
	}
}

function tracker_captcha_new_id()
{
	if (function_exists('random_bytes')) {
		return bin2hex(random_bytes(16));
	}

	return md5(uniqid('', true) . mt_rand());
}

function create_captcha()
{
	tracker_captcha_session_gc();

	$settings = tracker_captcha_settings();
	$builder = new PhraseBuilder($settings['length'], 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
	$phrase = $builder->build();
	$id = tracker_captcha_new_id();

	$_SESSION['tracker_captcha'][$id] = array(
		'phrase' => $phrase,
		'created' => time(),
	);

	return $id;
}

function tracker_captcha_validate($id, $answer)
{
	tracker_captcha_session_gc();

	$id = trim((string)$id);
	$answer = trim((string)$answer);

	if ($id === '' || $answer === '' || empty($_SESSION['tracker_captcha'][$id]['phrase'])) {
		return false;
	}

	$phrase = (string)$_SESSION['tracker_captcha'][$id]['phrase'];
	unset($_SESSION['tracker_captcha'][$id]);

	return PhraseBuilder::comparePhrases($phrase, $answer);
}

function tracker_captcha_build_image($id)
{
	tracker_captcha_session_gc();

	$id = trim((string)$id);
	$phrase = empty($_SESSION['tracker_captcha'][$id]['phrase'])
		? 'ERROR'
		: (string)$_SESSION['tracker_captcha'][$id]['phrase'];

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
