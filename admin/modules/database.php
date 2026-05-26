<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

$dbname = $mysql_db;

function db_table_name($name)
{
	$name = (string)$name;

	if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
		return '';
	}

	return '`' . $name . '`';
}

function StatusDB()
{
	global $admin_file, $dbname;

	$type = (string)($_POST['type'] ?? '');
	$selected_tables = $_POST['datatable'] ?? [];

	if (!is_array($selected_tables)) {
		$selected_tables = [];
	}

	$result = sql_query('SHOW TABLES FROM `' . $dbname . '`') or sqlerr(__FILE__, __LINE__);

	$options = '';
	$all_tables = [];

	while ($row = mysqli_fetch_row($result)) {
		$table = (string)$row[0];
		$all_tables[] = $table;
		$options .= '<option value="' . htmlspecialchars_uni($table) . '" selected="selected">' . htmlspecialchars_uni($table) . '</option>';
	}

	echo '
	<div class="mn_wrap">
		<div class="tp1_title"><b>Обслуживание базы данных</b></div>
		<div class="tp1_body">
			<form method="post" action="' . $admin_file . '.php">
				<table class="tables2 w100p">
					<tr>
						<td class="top w350">
							<select name="datatable[]" size="10" multiple="multiple" class="w350">
								' . $options . '
							</select>
						</td>
						<td class="top">
							<table class="tables2 w100p">
								<tr>
									<td class="top w20">
										<input type="radio" name="type" value="Optimize" checked="checked" />
									</td>
									<td>
										<b>Оптимизация базы данных</b><br />
										<span class="small">
											Оптимизация уменьшает размер таблиц и может ускорить работу базы данных.
											Рекомендуется выполнять её периодически, например один раз в неделю.
										</span>
									</td>
								</tr>
								<tr>
									<td class="top w20">
										<input type="radio" name="type" value="Repair" />
									</td>
									<td>
										<b>Ремонт базы данных</b><br />
										<span class="small">
											Ремонт может помочь при повреждении таблиц после сбоя MySQL-сервера
											или некорректного завершения операций.
										</span>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="center">
							<input type="hidden" name="op" value="StatusDB" />
							<input type="submit" value="Выполнить действие" class="buttonS" />
						</td>
					</tr>
				</table>
			</form>
		</div>
	</div>';

	if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($type !== 'Optimize' && $type !== 'Repair')) {
		return;
	}

	$tables = [];

	foreach ($selected_tables as $table) {
		$table = (string)$table;

		if (in_array($table, $all_tables, true)) {
			$safe_table = db_table_name($table);

			if ($safe_table !== '') {
				$tables[] = $safe_table;
			}
		}
	}

	if (!$tables) {
		foreach ($all_tables as $table) {
			$safe_table = db_table_name($table);

			if ($safe_table !== '') {
				$tables[] = $safe_table;
			}
		}
	}

	if (!$tables) {
		echo '
		<div class="mn_wrap">
			<div class="tp1_title"><b>Ошибка</b></div>
			<div class="tp1_body center red">Не выбраны таблицы для обработки.</div>
		</div>';
		return;
	}

	$total_size = 0;
	$total_free = 0;
	$content = '';
	$i = 0;

	if ($type === 'Optimize') {
		$status = sql_query('SHOW TABLE STATUS FROM `' . $dbname . '`') or sqlerr(__FILE__, __LINE__);

		while ($row = mysqli_fetch_assoc($status)) {
			$table_name = (string)$row['Name'];

			if (!in_array(db_table_name($table_name), $tables, true)) {
				continue;
			}

			$i++;

			$total = (int)$row['Data_length'] + (int)$row['Index_length'];
			$free = !empty($row['Data_free']) ? (int)$row['Data_free'] : 0;

			$total_size += $total;
			$total_free += $free;

			$label = $free > 0
				? '<span class="green">Оптимизирована</span>'
				: '<span class="red">Не нуждается</span>';

			$content .= '
				<tr class="bov">
					<td class="center">' . $i . '</td>
					<td>' . htmlspecialchars_uni($table_name) . '</td>
					<td>' . mksize($total) . '</td>
					<td class="center">' . $label . '</td>
					<td class="center">' . mksize($free) . '</td>
				</tr>';
		}

		sql_query('OPTIMIZE TABLE ' . implode(', ', $tables)) or sqlerr(__FILE__, __LINE__);

		echo '
		<div class="mn_wrap">
			<div class="tp1_title"><b>Оптимизация базы данных: ' . htmlspecialchars_uni($dbname) . '</b></div>
			<div class="tp1_body">
				<div class="center">
					Общий размер базы данных: <b>' . mksize($total_size) . '</b><br />
					Общие накладные расходы: <b>' . mksize($total_free) . '</b>
				</div>
				<br />
				<table class="tables2 w100p">
					<tr>
						<td class="colhead center">№</td>
						<td class="colhead">Таблица</td>
						<td class="colhead">Размер</td>
						<td class="colhead center">Статус</td>
						<td class="colhead center">Накладные расходы</td>
					</tr>
					' . $content . '
				</table>
			</div>
		</div>';
	} elseif ($type === 'Repair') {
		$status = sql_query('SHOW TABLE STATUS FROM `' . $dbname . '`') or sqlerr(__FILE__, __LINE__);

		while ($row = mysqli_fetch_assoc($status)) {
			$table_name = (string)$row['Name'];

			if (!in_array(db_table_name($table_name), $tables, true)) {
				continue;
			}

			$i++;

			$total = (int)$row['Data_length'] + (int)$row['Index_length'];
			$total_size += $total;

			$repair = sql_query('REPAIR TABLE ' . db_table_name($table_name));
			$label = $repair
				? '<span class="green">OK</span>'
				: '<span class="red">Ошибка</span>';

			$content .= '
				<tr class="bov">
					<td class="center">' . $i . '</td>
					<td>' . htmlspecialchars_uni($table_name) . '</td>
					<td>' . mksize($total) . '</td>
					<td class="center">' . $label . '</td>
				</tr>';
		}

		echo '
		<div class="mn_wrap">
			<div class="tp1_title"><b>Ремонт базы данных: ' . htmlspecialchars_uni($dbname) . '</b></div>
			<div class="tp1_body">
				<div class="center">
					Общий размер базы данных: <b>' . mksize($total_size) . '</b>
				</div>
				<br />
				<table class="tables2 w100p">
					<tr>
						<td class="colhead center">№</td>
						<td class="colhead">Таблица</td>
						<td class="colhead">Размер</td>
						<td class="colhead center">Статус</td>
					</tr>
					' . $content . '
				</table>
			</div>
		</div>';
	}
}

switch ($op) {
	case 'StatusDB':
		StatusDB();
		break;
}

?>