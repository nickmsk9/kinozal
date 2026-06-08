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
		if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $birth_date, $m)) {
			return 'NULL';
		}
		if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
			return 'NULL';
		}
		return sqlesc($birth_date);
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
			if (array_key_exists('photos', $person)) {
				persons_save_photos($id, $person['photos']);
			}
			return $id;
		}

		sql_query("
			INSERT INTO persons
				(name, original_name, type, gender, poster_url, birth_date, birth_text, birth_place, career, genre, height, spouse, biography, trivia, filmography, voice, producer, director, writer, awards, links, source_url, created_by, created_at, updated_by, updated_at)
			VALUES
				(" . sqlesc($name) . ", " . sqlesc((string)($person['original_name'] ?? '')) . ", " . (int)($person['type'] ?? 11) . ", " . (int)($person['gender'] ?? 0) . ", " . sqlesc((string)($person['poster_url'] ?? '')) . ", $birth_date_sql, " . sqlesc((string)($person['birth_text'] ?? '')) . ", " . sqlesc((string)($person['birth_place'] ?? '')) . ", " . sqlesc((string)($person['career'] ?? '')) . ", " . sqlesc((string)($person['genre'] ?? '')) . ", " . sqlesc((string)($person['height'] ?? '')) . ", " . sqlesc((string)($person['spouse'] ?? '')) . ", " . sqlesc((string)($person['biography'] ?? '')) . ", " . sqlesc((string)($person['trivia'] ?? '')) . ", " . sqlesc((string)($person['filmography'] ?? '')) . ", " . sqlesc((string)($person['voice'] ?? '')) . ", " . sqlesc((string)($person['producer'] ?? '')) . ", " . sqlesc((string)($person['director'] ?? '')) . ", " . sqlesc((string)($person['writer'] ?? '')) . ", " . sqlesc((string)($person['awards'] ?? '')) . ", " . sqlesc((string)($person['links'] ?? '')) . ", " . sqlesc((string)($person['source_url'] ?? '')) . ", " . (int)$CURUSER['id'] . ", " . sqlesc(get_date_time()) . ", " . (int)$CURUSER['id'] . ", " . sqlesc(get_date_time()) . ")
		") or sqlerr(__FILE__, __LINE__);

		$id = (int)mysqli_insert_id($link);
		if ($id > 0 && array_key_exists('photos', $person)) {
			persons_save_photos($id, $person['photos']);
		}
		return $id;
	}

	function persons_admin_split_names($text)
	{
		$text = html_entity_decode(strip_tags((string)$text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$text = preg_replace('#\s+#u', ' ', $text);
		$parts = preg_split('/\s*(?:,|;|\/|\s+и\s+|\s+&\s+)\s*/u', $text, -1, PREG_SPLIT_NO_EMPTY);
		$out = array();
		foreach ($parts as $part) {
			$part = trim($part, " \t\n\r\0\x0B.,;:()[]{}\"'");
			$len = function_exists('mb_strlen') ? mb_strlen($part, 'UTF-8') : strlen($part);
			if ($part === '' || $len < 5 || $len > 80) {
				continue;
			}
			if (preg_match('/[0-9@<>\/\\\\]/u', $part)) {
				continue;
			}
			if (!preg_match('/^[\p{L}][\p{L}\s\.\'-]+$/u', $part)) {
				continue;
			}
			$out[] = $part;
		}
		return $out;
	}

	function persons_admin_extract_from_text($text)
	{
		$text = html_entity_decode((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$found = array();
		$patterns = array(
			'Режиссер' => 'director',
			'Режиссёр' => 'director',
			'В ролях' => 'actor',
		);
		foreach ($patterns as $label => $role) {
			if (preg_match_all('/' . preg_quote($label, '/') . '\s*:\s*([^\r\n<\[]+)/iu', $text, $matches)) {
				foreach ($matches[1] as $value) {
					foreach (persons_admin_split_names($value) as $name) {
						$found[$name] = $role;
					}
				}
			}
		}
		return $found;
	}

	function persons_admin_extract_from_torrent(array $row)
	{
		$found = array();
		$data = json_decode((string)($row['data'] ?? ''), true);
		if (is_array($data)) {
			$video = isset($data['video']) && is_array($data['video']) ? $data['video'] : array();
			foreach (persons_admin_split_names($video['director'] ?? '') as $name) {
				$found[$name] = 'director';
			}
			foreach (persons_admin_split_names($video['cast'] ?? '') as $name) {
				$found[$name] = 'actor';
			}
		}
		foreach (persons_admin_extract_from_text((string)($row['descr'] ?? '')) as $name => $role) {
			if (!isset($found[$name])) {
				$found[$name] = $role;
			}
		}
		return $found;
	}

	function persons_admin_autoparse($limit, $fill_wikipedia)
	{
		$limit = max(1, min(500, (int)$limit));
		$fill_wikipedia = (bool)$fill_wikipedia;
		$res = sql_query("
			SELECT t.id, t.name, t.descr, td.data
			FROM torrents AS t
			LEFT JOIN torrent_details AS td ON td.tid = t.id
			WHERE t.visible = 'yes' AND t.banned = 'no'
			ORDER BY t.id DESC
			LIMIT $limit
		") or sqlerr(__FILE__, __LINE__);

		$created = 0;
		$existing = 0;
		$updated = 0;
		$seen = array();
		while ($row = mysqli_fetch_assoc($res)) {
			foreach (persons_admin_extract_from_torrent($row) as $name => $role) {
				$key = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
				if (isset($seen[$key])) {
					continue;
				}
				$seen[$key] = true;
				$found_person = persons_find(0, $name);
				if ($found_person && !$fill_wikipedia) {
					$existing++;
					continue;
				}

				$person = $found_person ?: persons_admin_default_row($name);
				if ($found_person) {
					$person['photos'] = persons_photo_text((int)$found_person['id']);
				}
				$before = $person;
				if (empty($person['career'])) {
					$person['career'] = $role === 'director' ? 'режиссер' : 'актер';
				}
				if ($fill_wikipedia) {
					$import = persons_import_from_wikipedia($name, 'ru');
					if ($import) {
						$person = persons_merge_import($person, $import, false);
						$person['name'] = $name;
					}
				}
				if ($found_person && $person == $before) {
					$existing++;
					continue;
				}
				if (persons_admin_save($person) > 0) {
					if ($found_person) {
						$updated++;
					} else {
						$created++;
					}
				}
			}
		}

		return array('created' => $created, 'updated' => $updated, 'existing' => $existing, 'found' => count($seen));
	}

	function PersonsAdmin()
	{
		global $admin_file;

		persons_ensure_schema();

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_person'])) {
			$name = persons_request_text($_POST['person_name'] ?? '');
			$lang = preg_match('/^[a-z]{2,3}$/i', (string)($_POST['lang'] ?? 'ru')) ? strtolower((string)$_POST['lang']) : 'ru';
			$pid = (int)($_POST['person_id'] ?? 0);
			$overwrite = !empty($_POST['overwrite']);

			if ($name === '') {
				stdmsg('Персоны', 'Укажите имя персоны для импорта.');
			} else {
				$import = persons_import_from_wikipedia($name, $lang);
				if (!$import) {
					stdmsg('Персоны', 'Wikipedia не вернула данные по этому запросу.');
				} else {
					$existing = $pid > 0 ? persons_find($pid, '') : persons_find(0, $name);
					$base = $existing ?: persons_admin_default_row($name);
					if ($existing) {
						$base['photos'] = persons_photo_text((int)$existing['id']);
					}
					$merged = persons_merge_import($base, $import, $overwrite);
					if (empty($merged['name'])) {
						$merged['name'] = $name;
					}
					$merged['id'] = (int)($base['id'] ?? 0);
					$saved_id = persons_admin_save($merged);
					if ($saved_id > 0) {
						stdmsg('Персоны', 'Информация загружена и сохранена: <a href="' . persons_url($merged['name'], $saved_id) . '" class="sbab">открыть персону</a>.');
					}
				}
			}
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['autoparse_persons'])) {
			$result = persons_admin_autoparse((int)($_POST['autoparse_limit'] ?? 100), !empty($_POST['fill_wikipedia']));
			stdmsg('Персоны', 'Автопарсинг завершен. Найдено: ' . (int)$result['found'] . ', создано: ' . (int)$result['created'] . ', дополнено: ' . (int)$result['updated'] . ', пропущено: ' . (int)$result['existing'] . '.');
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

		echo '<div class="tp1_title"><b>Автопарсинг персон из раздач</b></div>';
		echo '<form method="post" action="' . htmlspecialchars_uni($admin_file) . '.php?op=PersonsAdmin">';
		echo '<input type="hidden" name="autoparse_persons" value="1">';
		echo '<table class="tables2 w100p">';
		echo '<tr><td class="w250">Сколько последних раздач обработать</td><td><input type="text" name="autoparse_limit" value="100" size="6"></td></tr>';
		echo '<tr><td>Заполнять карточки из Wikipedia</td><td><input type="checkbox" name="fill_wikipedia" value="1"> <span class="small">добавляет данные новым и дополняет уже существующие карточки, не затирая заполненные поля</span></td></tr>';
		echo '<tr><td colspan="2" class="center"><input type="submit" class="buttonS" value="Найти и добавить персон"></td></tr>';
		echo '</table>';
		echo '</form>';

		$res = sql_query("SELECT id, name, original_name, updated_at, created_at FROM persons ORDER BY updated_at DESC, id DESC LIMIT 30") or sqlerr(__FILE__, __LINE__);
		echo '<div class="tp1_title"><b>Последние персоны</b></div>';
		echo '<table class="tables2 w100p"><tr><td class="colhead">Персона</td><td class="colhead w150">Дата</td><td class="colhead w150">Действия</td></tr>';
		while ($row = mysqli_fetch_assoc($res)) {
			$date = $row['updated_at'] ?: $row['created_at'];
			echo '<tr><td><a href="' . persons_url($row['name'], (int)$row['id']) . '" class="sbab">' . htmlspecialchars_uni($row['name']) . '</a>';
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
