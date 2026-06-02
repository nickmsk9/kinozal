<?php

if (!defined('IN_TRACKER')) {
	die('Direct access denied.');
}

function tracker_flag_h($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tracker_country_flag_html($country_id, $flagpic = '', $alt = '')
{
	$country_id = (int)$country_id;
	$flagpic = basename((string)$flagpic);
	$alt = tracker_flag_h($alt);

	if ($flagpic !== '' && is_file(ROOT_PATH . 'pic' . DIRECTORY_SEPARATOR . 'flag' . DIRECTORY_SEPARATOR . $flagpic)) {
		return '<img src="/pic/flag/' . tracker_flag_h($flagpic) . '" width="16" height="11" alt="' . $alt . '" style="vertical-align:middle; margin-right:3px;">';
	}

	if ($country_id <= 0) {
		return '';
	}

	$sprite = ROOT_PATH . 'pic' . DIRECTORY_SEPARATOR . 'emty.gif';
	if (!is_file($sprite)) {
		$sprite = ROOT_PATH . 'pic' . DIRECTORY_SEPARATOR . 'empty.gif';
	}
	if (!is_file($sprite)) {
		return '';
	}

	$flag_width = 11;
	$flag_height = 9;
	$flags_count = 0;
	$size = @getimagesize($sprite);
	if (is_array($size) && !empty($size[0])) {
		$flags_count = max(1, (int)floor((int)$size[0] / $flag_width));
	}

	if ($flags_count > 0 && $country_id > $flags_count) {
		return '';
	}

	$offset = -1 * ($country_id - 1) * $flag_width;
	$src = is_file(ROOT_PATH . 'pic' . DIRECTORY_SEPARATOR . 'emty.gif') ? '/pic/emty.gif' : '/pic/empty.gif';

	return '<span title="' . $alt . '" style="display:inline-block; width:' . $flag_width . 'px; height:' . $flag_height . 'px; overflow:hidden; vertical-align:middle; margin-right:3px; background:url(' . $src . ') ' . $offset . 'px 0 no-repeat;"></span>';
}

?>
