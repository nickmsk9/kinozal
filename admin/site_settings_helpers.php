<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

if (!function_exists('site_admin_h')) {
	function site_admin_h($value)
	{
		return htmlspecialchars_uni((string)$value);
	}

	function site_admin_bool_row($label, $name, $current, $hint = '')
	{
		echo '<tr><td class="rowhead w250">' . site_admin_h($label) . '</td><td>';
		echo '<label><input type="checkbox" name="' . site_admin_h($name) . '" value="1"' . ($current ? ' checked' : '') . '> включено</label>';
		if ($hint !== '') {
			echo '<br><span class="small">' . site_admin_h($hint) . '</span>';
		}
		echo '</td></tr>';
	}

	function site_admin_text_row($label, $name, $value, $size = 60, $hint = '')
	{
		echo '<tr><td class="rowhead w250">' . site_admin_h($label) . '</td><td>';
		echo '<input type="text" name="' . site_admin_h($name) . '" size="' . (int)$size . '" value="' . site_admin_h($value) . '">';
		if ($hint !== '') {
			echo '<br><span class="small">' . site_admin_h($hint) . '</span>';
		}
		echo '</td></tr>';
	}

	function site_admin_select_row($label, $name, $current, $options, $hint = '')
	{
		echo '<tr><td class="rowhead w250">' . site_admin_h($label) . '</td><td>';
		echo '<select name="' . site_admin_h($name) . '">';
		foreach ($options as $value => $title) {
			$selected = ((string)$value === (string)$current) ? ' selected' : '';
			echo '<option value="' . site_admin_h($value) . '"' . $selected . '>' . site_admin_h($title) . '</option>';
		}
		echo '</select>';
		if ($hint !== '') {
			echo '<br><span class="small">' . site_admin_h($hint) . '</span>';
		}
		echo '</td></tr>';
	}

	function site_admin_status_cell($label, $value, $good = true)
	{
		$color = $good ? '#2d7a42' : '#a33d2f';
		echo '<td class="center" style="padding:10px;">';
		echo '<div class="small">' . site_admin_h($label) . '</div>';
		echo '<b style="color:' . $color . ';">' . site_admin_h($value) . '</b>';
		echo '</td>';
	}

	function site_admin_dirs($path)
	{
		$items = array();
		if (is_dir($path)) {
			$files = scandir($path);
			if ($files !== false) {
				foreach ($files as $file) {
					if ($file !== '.' && $file !== '..' && is_dir($path . '/' . $file)) {
						$items[$file] = $file;
					}
				}
			}
		}
		return $items;
	}

	function site_admin_saved($title)
	{
		site_settings_apply_runtime_overrides();
		stdmsg($title, 'Настройки сохранены.');
	}

	function site_admin_form_open($admin_file, $op)
	{
		echo '<form method="post" action="' . site_admin_h($admin_file) . '.php?op=' . site_admin_h($op) . '">';
		echo '<input type="hidden" name="save_settings" value="1">';
		echo '<table class="tables2 w100p">';
	}

	function site_admin_form_close()
	{
		echo '<tr><td colspan="2" class="center"><input type="submit" class="buttonS" value="Сохранить настройки"></td></tr>';
		echo '</table></form>';
	}
}

?>
