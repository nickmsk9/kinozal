<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

require_once('include/persons.php');

if (!function_exists('PersonsAdmin')) {
	function persons_admin_default_row($name = '')
	{
		return array(
			'id' => 0,
			'name' => $name,
			'original_name' => '',
			'type' => 11,
			'gender' => 0,
			'poster_url' => '',
			'birth_date' => null,
			'birth_text' => '',
			'birth_place' => '',
			'career' => '',
			'genre' => '',
			'height' => '',
			'spouse' => '',
			'biography' => '',
			'trivia' => '',
			'filmography' => '',
			'voice' => '',
			'producer' => '',
			'director' => '',
			'writer' => '',
			'awards' => '',
			'links' => '',
			'source_url' => '',
			'created_by' => 0,
			'created_at' => '',
			'updated_by' => 0,
			'updated_at' => '',
		);
	}

	function persons_admin_birth_sql($birth_date)
	{
		$birth_date = trim((string)$birth_date);
		return preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $birth_date) ? sqlesc($birth_date) : 'NULL';
	}

	function persons_admin_save(array $person)
	{
		global $CURUSER, $link;

		$id = (int)($person['id'] ?? 0);
		$name = trim((string)($person['name'] ?? ''));
		if ($name === '') {
			return 0;
		}

		$birth_date_sql = persons_admin_birth_sql($person['birth_date'] ?? '');
		$fields = array('original_name','poster_url','birth_text','birth_place','career','genre','height','spouse','biography','trivia','filmography','voice','producer','director','writer','awards','links','source_url');

		if ($id > 0) {
			$set = array(
				'name = ' . sqlesc($name),
				'type = ' . (int)($person['type'] ?? 11),
				'gender = ' . (int)($person['gender'] ?? 0),
				'birth_date = ' . $birth_date_sql,
				'updated_by = ' . (int)$CURUSER['id'],
				'updated_at = ' . sqlesc(get_date_time()),
			);
			foreach ($fields as $field) {
				$set[] = $field . ' = ' . sqlesc((string)($person[$field] ?? ''));
			}
			sql_query("UPDATE persons SET " . implode(', ', $set) . " WHERE id = $id") or sqlerr(__FILE__, __LINE__);
			return $id;
		}

		sql_query("
			INSERT INTO persons
				(name, original_name, type, gender, poster_url, birth_date, birth_text, birth_place, career, genre, height, spouse, biography, trivia, filmography, voice, producer, director, writer, awards, links, source_url, created_by, created_at, updated_by, updated_at)
			VALUES
				(" . sqlesc($name) . ", " . sqlesc((string)($person['original_name'] ?? '')) . ", " . (int)($person['type'] ?? 11) . ", " . (int)($person['gender'] ?? 0) . ", " . sqlesc((string)($person['poster_url'] ?? '')) . ", $birth_date_sql, " . sqlesc((string)($person['birth_text'] ?? '')) . ", " . sqlesc((string)($person['birth_place'] ?? '')) . ", " . sqlesc((string)($person['career'] ?? '')) . ", " . sqlesc((string)($person['genre'] ?? '')) . ", " . sqlesc((string)($person['height'] ?? '')) . ", " . sqlesc((string)($person['spouse'] ?? '')) . ", " . sqlesc((string)($person['biography'] ?? '')) . ", " . sqlesc((string)($person['trivia'] ?? '')) . ", " . sqlesc((string)($person['filmography'] ?? '')) . ", " . sqlesc((string)($person['voice'] ?? '')) . ", " . sqlesc((string)($person['producer'] ?? '')) . ", " . sqlesc((string)($person['director'] ?? '')) . ", " . sqlesc((string)($person['writer'] ?? '')) . ", " . sqlesc((string)($person['awards'] ?? '')) . ", " . sqlesc((string)($person['links'] ?? '')) . ", " . sqlesc((string)($person['source_url'] ?? '')) . ", " . (int)$CURUSER['id'] . ", " . sqlesc(get_date_time()) . ", " . (int)$CURUSER['id'] . ", " . sqlesc(get_date_time()) . ")
		") or sqlerr(__FILE__, __LINE__);

		return (int)mysqli_insert_id($link);
	}

	function PersonsAdmin()
	{
		global $admin_file;

		kz_persons_ensure_schema();

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_person'])) {
			$name = kz_persons_request_text($_POST['person_name'] ?? '');
			$lang = preg_match('/^[a-z]{2,3}$/i', (string)($_POST['lang'] ?? 'ru')) ? strtolower((string)$_POST['lang']) : 'ru';
			$pid = (int)($_POST['person_id'] ?? 0);
			$overwrite = !empty($_POST['overwrite']);

			if ($name === '') {
				stdmsg('Персоны', 'Укажите имя персоны для импорта.');
			} else {
				$import = kz_persons_import_from_wikipedia($name, $lang);
				if (!$import) {
					stdmsg('Персоны', 'Wikipedia не вернула данные по этому запросу.');
				} else {
					$existing = $pid > 0 ? kz_persons_find($pid, '') : kz_persons_find(0, $name);
					$base = $existing ?: persons_admin_default_row($name);
					$merged = kz_persons_merge_import($base, $import, $overwrite);
					if (empty($merged['name'])) {
						$merged['name'] = $name;
					}
					$merged['id'] = (int)($base['id'] ?? 0);
					$saved_id = persons_admin_save($merged);
					if ($saved_id > 0) {
						stdmsg('Персоны', 'Информация загружена и сохранена: <a href="' . kz_persons_url($merged['name'], $saved_id) . '" class="sbab">открыть персону</a>.');
					}
				}
			}
		}

		echo '<div class="mn_wrap">';
		echo '<div class="tp1_title"><b>Персоны</b></div>';
		echo '<div class="tp1_body">';
		echo '<form method="post" action="' . htmlspecialchars_uni($admin_file) . '.php?op=PersonsAdmin">';
		echo '<input type="hidden" name="import_person" value="1">';
		echo '<table class="tables2 w100p">';
		echo '<tr><td class="w250">Имя для поиска в Wikipedia</td><td><input type="text" name="person_name" class="w98p" value="" placeholder="Райан Гослинг"></td></tr>';
		echo '<tr><td>Язык Wikipedia</td><td><select name="lang"><option value="ru">ru.wikipedia.org</option><option value="en">en.wikipedia.org</option></select></td></tr>';
		echo '<tr><td>ID существующей персоны</td><td><input type="text" name="person_id" size="8"> <span class="small">оставьте пустым, чтобы найти по имени или создать новую</span></td></tr>';
		echo '<tr><td>Перезаписывать заполненные поля</td><td><input type="checkbox" name="overwrite" value="1"></td></tr>';
		echo '<tr><td colspan="2" class="center"><input type="submit" class="buttonS" value="Загрузить и сохранить"></td></tr>';
		echo '</table>';
		echo '</form>';

		$res = sql_query("SELECT id, name, original_name, updated_at, created_at FROM persons ORDER BY updated_at DESC, id DESC LIMIT 30") or sqlerr(__FILE__, __LINE__);
		echo '<div class="tp1_title"><b>Последние персоны</b></div>';
		echo '<table class="tables2 w100p"><tr><td class="colhead">Персона</td><td class="colhead w150">Дата</td><td class="colhead w150">Действия</td></tr>';
		while ($row = mysqli_fetch_assoc($res)) {
			$date = $row['updated_at'] ?: $row['created_at'];
			echo '<tr><td><a href="' . kz_persons_url($row['name'], (int)$row['id']) . '" class="sbab">' . htmlspecialchars_uni($row['name']) . '</a>';
			if ($row['original_name'] !== '') {
				echo ' / ' . htmlspecialchars_uni($row['original_name']);
			}
			echo '</td><td>' . htmlspecialchars_uni((string)$date) . '</td><td><a href="/personedit.php?id=' . (int)$row['id'] . '" class="sba">Редактировать</a></td></tr>';
		}
		echo '</table>';
		echo '</div>';
		echo '</div>';
	}
}

switch ($op) {
	case 'PersonsAdmin':
		PersonsAdmin();
		break;
}

?>
