<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

$dbname = $mysql_db;

function db_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function db_identifier($name)
{
	return '`' . str_replace('`', '``', (string)$name) . '`';
}

function db_all_tables($dbname)
{
	$tables = array();
	$res = sql_query('SHOW FULL TABLES FROM ' . db_identifier($dbname)) or sqlerr(__FILE__, __LINE__);

	while ($row = mysqli_fetch_row($res)) {
		$name = (string)($row[0] ?? '');
		if ($name === '') {
			continue;
		}
		$tables[$name] = array(
			'name' => $name,
			'type' => (string)($row[1] ?? 'BASE TABLE'),
		);
	}

	ksort($tables);
	return $tables;
}

function db_table_status($dbname)
{
	$rows = array();
	$res = sql_query('SHOW TABLE STATUS FROM ' . db_identifier($dbname)) or sqlerr(__FILE__, __LINE__);

	while ($row = mysqli_fetch_assoc($res)) {
		$name = (string)($row['Name'] ?? '');
		if ($name !== '') {
			$rows[$name] = $row;
		}
	}

	return $rows;
}

function db_selected_tables(array $all_tables)
{
	$posted = $_POST['datatable'] ?? array_keys($all_tables);
	if (!is_array($posted)) {
		$posted = array();
	}

	$selected = array();
	foreach ($posted as $table) {
		$table = (string)$table;
		if (isset($all_tables[$table])) {
			$selected[] = $table;
		}
	}

	return array_values(array_unique($selected));
}

function db_actions()
{
	return array(
		'Check' => array(
			'sql' => 'CHECK TABLE',
			'title' => 'Проверка таблиц',
			'hint' => 'Проверяет структуру и сообщает о повреждениях.',
		),
		'Analyze' => array(
			'sql' => 'ANALYZE TABLE',
			'title' => 'Анализ таблиц',
			'hint' => 'Обновляет статистику индексов для планировщика MySQL.',
		),
		'Optimize' => array(
			'sql' => 'OPTIMIZE TABLE',
			'title' => 'Оптимизация таблиц',
			'hint' => 'Перестраивает таблицы и возвращает свободное место там, где это поддерживается.',
		),
		'Repair' => array(
			'sql' => 'REPAIR TABLE',
			'title' => 'Ремонт таблиц',
			'hint' => 'Актуально в основном для MyISAM; InnoDB обычно вернет служебное сообщение.',
		),
	);
}

function db_overview($dbname, array $all_tables, array $status)
{
	$total_size = 0;
	$total_free = 0;
	$total_rows = 0;
	$engines = array();

	foreach ($all_tables as $table => $info) {
		$row = $status[$table] ?? array();
		$size = (int)($row['Data_length'] ?? 0) + (int)($row['Index_length'] ?? 0);
		$free = (int)($row['Data_free'] ?? 0);
		$total_size += $size;
		$total_free += $free;
		$total_rows += (int)($row['Rows'] ?? 0);

		$engine = (string)($row['Engine'] ?? ($info['type'] ?? ''));
		if ($engine !== '') {
			$engines[$engine] = ($engines[$engine] ?? 0) + 1;
		}
	}

	$db_time = array(
		'db_now' => '',
		'session_tz' => '',
		'global_tz' => '',
		'version' => '',
	);
	$res = sql_query('SELECT NOW() AS db_now, @@session.time_zone AS session_tz, @@global.time_zone AS global_tz, VERSION() AS version') or sqlerr(__FILE__, __LINE__);
	if ($row = mysqli_fetch_assoc($res)) {
		$db_time = $row;
	}

	echo '<div class="mn_wrap">';
	echo '<div class="tp1_title"><b>Сводка базы данных: ' . db_h($dbname) . '</b></div>';
	echo '<div class="tp1_body">';
	echo '<table class="tables2 w100p">';
	echo '<tr>';
	echo '<td class="center"><b>' . count($all_tables) . '</b><br><span class="small">таблиц/представлений</span></td>';
	echo '<td class="center"><b>' . number_format($total_rows, 0, '.', ' ') . '</b><br><span class="small">примерно строк</span></td>';
	echo '<td class="center"><b>' . mksize($total_size) . '</b><br><span class="small">данные и индексы</span></td>';
	echo '<td class="center"><b>' . mksize($total_free) . '</b><br><span class="small">свободно внутри таблиц</span></td>';
	echo '</tr><tr>';
	echo '<td colspan="2">PHP: <b>' . db_h(date('Y-m-d H:i:s')) . '</b> (' . db_h(defined('TRACKER_TIMEZONE') ? TRACKER_TIMEZONE : date_default_timezone_get()) . ')</td>';
	echo '<td colspan="2">MySQL: <b>' . db_h($db_time['db_now'] ?? '') . '</b> / session ' . db_h($db_time['session_tz'] ?? '') . ' / global ' . db_h($db_time['global_tz'] ?? '') . '</td>';
	echo '</tr><tr>';
	echo '<td colspan="4">MySQL: <b>' . db_h($db_time['version'] ?? '') . '</b>' . ($engines ? ' / engines: ' . db_h(implode(', ', array_keys($engines))) : '') . '</td>';
	echo '</tr>';
	echo '</table>';
	echo '</div></div>';
}

function db_render_form($admin_file, array $all_tables, array $status, array $selected, $current_action)
{
	$actions = db_actions();
	if (!isset($actions[$current_action])) {
		$current_action = 'Check';
	}

	echo '<div class="mn_wrap">';
	echo '<div class="tp1_title"><b>Обслуживание базы данных</b></div>';
	echo '<div class="tp1_body">';
	echo '<form method="post" action="' . db_h($admin_file) . '.php?op=StatusDB">';
	echo '<table class="tables2 w100p">';
	echo '<tr>';
	echo '<td class="top w350">';
	echo '<select name="datatable[]" size="16" multiple="multiple" class="w350">';
	foreach ($all_tables as $table => $info) {
		$is_selected = in_array($table, $selected, true) ? ' selected="selected"' : '';
		echo '<option value="' . db_h($table) . '"' . $is_selected . '>' . db_h($table) . '</option>';
	}
	echo '</select><br><span class="small">По умолчанию выбраны все таблицы. Для выборочного действия используйте Ctrl/Shift.</span>';
	echo '</td>';
	echo '<td class="top">';
	echo '<table class="tables2 w100p">';
	echo '<tr><td class="colhead" colspan="2">Действие</td></tr>';
	foreach ($actions as $key => $action) {
		$checked = $key === $current_action ? ' checked="checked"' : '';
		echo '<tr>';
		echo '<td class="top w20"><input type="radio" name="type" value="' . db_h($key) . '"' . $checked . '></td>';
		echo '<td><b>' . db_h($action['title']) . '</b><br><span class="small">' . db_h($action['hint']) . '</span></td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</td>';
	echo '</tr>';
	echo '<tr><td colspan="2" class="center">';
	echo '<input type="hidden" name="op" value="StatusDB">';
	echo '<input type="submit" value="Выполнить действие" class="buttonS">';
	echo '</td></tr>';
	echo '</table>';
	echo '</form>';
	echo '</div></div>';

	echo '<div class="mn_wrap">';
	echo '<div class="tp1_title"><b>Таблицы</b></div>';
	echo '<div class="tp1_body">';
	echo '<table class="tables2 w100p">';
	echo '<tr><td class="colhead">Таблица</td><td class="colhead center">Тип</td><td class="colhead center">Engine</td><td class="colhead center">Строк</td><td class="colhead center">Размер</td><td class="colhead center">Свободно</td><td class="colhead">Обновлена</td></tr>';
	foreach ($all_tables as $table => $info) {
		$row = $status[$table] ?? array();
		$size = (int)($row['Data_length'] ?? 0) + (int)($row['Index_length'] ?? 0);
		$free = (int)($row['Data_free'] ?? 0);
		echo '<tr class="bov">';
		echo '<td>' . db_h($table) . '</td>';
		echo '<td class="center">' . db_h($info['type'] ?? '') . '</td>';
		echo '<td class="center">' . db_h($row['Engine'] ?? '') . '</td>';
		echo '<td class="center">' . number_format((int)($row['Rows'] ?? 0), 0, '.', ' ') . '</td>';
		echo '<td class="center">' . mksize($size) . '</td>';
		echo '<td class="center">' . ($free > 0 ? '<span class="red">' . mksize($free) . '</span>' : '0 kB') . '</td>';
		echo '<td>' . db_h($row['Update_time'] ?? $row['Create_time'] ?? '') . '</td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</div></div>';
}

function db_render_action_result($dbname, $action_key, array $selected)
{
	$actions = db_actions();
	if (!isset($actions[$action_key]) || !$selected) {
		return;
	}

	$sql = $actions[$action_key]['sql'] . ' ' . implode(', ', array_map('db_identifier', $selected));
	$res = sql_query($sql) or sqlerr(__FILE__, __LINE__);

	echo '<div class="mn_wrap">';
	echo '<div class="tp1_title"><b>' . db_h($actions[$action_key]['title']) . ': ' . db_h($dbname) . '</b></div>';
	echo '<div class="tp1_body">';
	echo '<table class="tables2 w100p">';
	echo '<tr><td class="colhead">Таблица</td><td class="colhead center">Операция</td><td class="colhead center">Тип</td><td class="colhead">Сообщение</td></tr>';
	while ($row = mysqli_fetch_assoc($res)) {
		$msg_type = strtolower((string)($row['Msg_type'] ?? ''));
		$ok = in_array($msg_type, array('status', 'note'), true)
			&& stripos((string)($row['Msg_text'] ?? ''), 'error') === false;
		$class = $ok ? 'green' : ($msg_type === 'warning' ? 'red' : '');
		echo '<tr class="bov">';
		echo '<td>' . db_h($row['Table'] ?? '') . '</td>';
		echo '<td class="center">' . db_h($row['Op'] ?? '') . '</td>';
		echo '<td class="center">' . ($class !== '' ? '<span class="' . $class . '">' . db_h($row['Msg_type'] ?? '') . '</span>' : db_h($row['Msg_type'] ?? '')) . '</td>';
		echo '<td>' . db_h($row['Msg_text'] ?? '') . '</td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</div></div>';
}

function StatusDB()
{
	global $admin_file, $dbname;

	$all_tables = db_all_tables($dbname);
	$status = db_table_status($dbname);
	$actions = db_actions();
	$type = (string)($_POST['type'] ?? 'Check');
	if (!isset($actions[$type])) {
		$type = 'Check';
	}

	$selected_tables = db_selected_tables($all_tables);

	db_overview($dbname, $all_tables, $status);
	db_render_form($admin_file, $all_tables, $status, $selected_tables, $type);

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		return;
	}

	if (!$selected_tables) {
		echo '<div class="mn_wrap"><div class="tp1_title"><b>Ошибка</b></div><div class="tp1_body center red">Не выбраны таблицы для обработки.</div></div>';
		return;
	}

	db_render_action_result($dbname, $type, $selected_tables);
}

switch ($op) {
	case 'StatusDB':
		StatusDB();
		break;
}

?>
