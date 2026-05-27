<?php

if (!defined('IN_TRACKER')) {
	die('Hacking attempt!');
}

function kz_h($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function kz_upload_kinds()
{
	return array(
		'video' => 'Видео',
		'music' => 'Музыка',
		'game' => 'Игра',
		'audiobook' => 'Аудиокнига',
		'program' => 'Программа',
		'book' => 'Библиотека',
		'graphic' => 'Графика',
	);
}

function kz_upload_categories()
{
	return array(
		'video' => array(
			45 => 'Сериал - Русский',
			46 => 'Сериал - Буржуйский',
			8 => 'Кино - Комедия',
			6 => 'Кино - Боевик / Военный',
			15 => 'Кино - Триллер / Детектив',
			17 => 'Кино - Драма',
			35 => 'Кино - Мелодрама',
			39 => 'Кино - Индийское',
			13 => 'Кино - Фантастика',
			14 => 'Кино - Фэнтези',
			24 => 'Кино - Ужас / Мистика',
			11 => 'Кино - Приключения',
			10 => 'Кино - Наше Кино',
			9 => 'Кино - Исторический',
			47 => 'Кино - Азиатский',
			18 => 'Кино - Документальный',
			37 => 'Кино - Спорт',
			12 => 'Кино - Детский / Семейный',
			7 => 'Кино - Классика',
			48 => 'Кино - Концерт',
			49 => 'Кино - Передачи / ТВ-шоу',
			50 => 'Кино - ТВ-шоу Мир',
			38 => 'Кино - Театр, Опера, Балет',
			16 => 'Кино - Эротика',
			21 => 'Мульт - Буржуйский',
			22 => 'Мульт - Русский',
			20 => 'Мульт - Аниме',
			1 => 'Другое - Видеоклипы',
		),
		'music' => array(
			3 => 'Музыка - Буржуйская',
			4 => 'Музыка - Русская',
			5 => 'Музыка - Сборники',
			42 => 'Музыка - Классическая',
		),
		'audiobook' => array(2 => 'Другое - АудиоКниги'),
		'game' => array(23 => 'Другое - Игры'),
		'program' => array(32 => 'Другое - Программы'),
		'book' => array(41 => 'Другое - Библиотека'),
		'graphic' => array(40 => 'Другое - Дизайн / Графика'),
	);
}

function kz_upload_release_groups()
{
	return array(
		923 => 'Занавес',
		944 => 'Сила слова',
		945 => 'HDLine',
		946 => 'LosslesS',
		947 => 'ParadiSe',
		948 => 'СамИздат',
		949 => 'Радио Кинозал.ТВ',
		950 => 'RuTracker',
		951 => 'ФЕНИКС',
		952 => 'HDClub',
		953 => 'LostFilm',
		954 => 'BigFANgroup',
		956 => 'HDTracker',
		958 => 'Puzkarapuz',
		959 => 'NovaFilm',
		1910 => 'AVCurtain',
		2037 => 'РиперАМ',
		2746 => 'ExKinoRay',
		2815 => 'IMA-Sound',
		2919 => 'RG GeneralFilm',
		2931 => 'СlubTorrent',
		2968 => 'NovaLan',
		3192 => 'Filmrus',
		3215 => 'IRONCLUB',
		3331 => 'MediaClub',
		3517 => 'RG HitWay',
		3613 => 'New-Team',
		3633 => 'KinoRay',
		3706 => 'ELEKTRI4KA',
		4442 => 'Files-x',
		4486 => 'BlueBird',
	);
}

function kz_upload_quality_options()
{
	return array(
		'' => 'Выберите качество',
		'WEB-DL (1080p)' => 'WEB-DL (1080p)',
		'WEB-DL (720p)' => 'WEB-DL (720p)',
		'BDRip (1080p)' => 'BDRip (1080p)',
		'BDRip (720p)' => 'BDRip (720p)',
		'DVDRip' => 'DVDRip',
		'HDRip' => 'HDRip',
		'HDTVRip' => 'HDTVRip',
		'DVB' => 'DVB',
		'DVDScr' => 'DVDScr',
		'TS' => 'TS',
		'CAMRip' => 'CAMRip',
	);
}

function kz_upload_translation_options()
{
	return array(
		'' => 'Выберите перевод',
		'Дублированный' => 'Дублированный',
		'Профессиональный многоголосый' => 'Профессиональный многоголосый',
		'Профессиональный двухголосый' => 'Профессиональный двухголосый',
		'Профессиональный одноголосый' => 'Профессиональный одноголосый',
		'Любительский многоголосый' => 'Любительский многоголосый',
		'Любительский двухголосый' => 'Любительский двухголосый',
		'Авторский' => 'Авторский',
		'Оригинал' => 'Оригинал',
		'Не требуется' => 'Не требуется',
	);
}

function kz_upload_language_options()
{
	return array(
		'' => 'Выберите язык',
		'Русский' => 'Русский',
		'Английский' => 'Английский',
		'Оригинальный' => 'Оригинальный',
		'Русский, английский' => 'Русский, английский',
		'Без диалогов' => 'Без диалогов',
	);
}

function kz_upload_subtitle_options()
{
	return array(
		'' => 'Выберите субтитры',
		'Нет' => 'Нет',
		'Русские' => 'Русские',
		'Английские' => 'Английские',
		'Русские, английские' => 'Русские, английские',
		'Вшитые' => 'Вшитые',
	);
}

function kz_upload_default_data()
{
	return array(
		'version' => 1,
		'mode' => 0,
		'section_modes' => array(0, 0, 0, 0),
		'advanced' => array(
			'desc1' => '',
			'desc2' => '',
			'desc3' => '',
			'desc4' => '',
		),
		'video' => array(
			'title' => '',
			'original_title' => '',
			'year' => '',
			'genre' => '',
			'released' => '',
			'director' => '',
			'cast' => '',
			'about' => '',
			'quality' => '',
			'video' => '',
			'audio' => '',
			'size' => '',
			'duration' => '',
			'translation' => '',
			'language' => '',
			'subtitles' => '',
		),
		'design' => array(
			'related' => array(
				array('title' => 'Все серии', 'query' => ''),
				array('title' => 'Подобные раздачи', 'query' => ''),
			),
			'imdb' => array('enabled' => 1, 'url' => '', 'rating' => ''),
			'kinopoisk' => array('enabled' => 1, 'url' => '', 'rating' => ''),
			'watch' => array(
				array('title' => 'Трейлер', 'url' => ''),
				array('title' => 'Скачать семпл', 'url' => ''),
			),
			'tabs' => array(
				array('title' => 'Содержание', 'content' => ''),
				array('title' => 'Интересно', 'content' => ''),
				array('title' => 'Релиз', 'content' => ''),
				array('title' => 'Скриншоты', 'content' => ''),
			),
			'screens' => '',
			'notes' => '',
		),
		'generic' => array(
			'desc1' => '',
			'desc2' => '',
			'desc3' => '',
			'desc4' => '',
		),
	);
}

function kz_upload_table_exists($table)
{
	$res = sql_query("SHOW TABLES LIKE " . sqlesc($table));
	return $res && mysqli_num_rows($res) > 0;
}

function kz_upload_ensure_schema()
{
	if (kz_upload_table_exists('torrent_details')) {
		return;
	}

	sql_query("
		CREATE TABLE IF NOT EXISTS torrent_details (
			tid int(10) unsigned NOT NULL,
			release_kind varchar(20) NOT NULL DEFAULT 'video',
			poster_url text NOT NULL,
			rgroup int(10) unsigned NOT NULL DEFAULT 0,
			rgroup_button varchar(255) NOT NULL DEFAULT '',
			form_mode tinyint(1) unsigned NOT NULL DEFAULT 0,
			section_modes varchar(20) NOT NULL DEFAULT '0,0,0,0',
			data mediumtext NOT NULL,
			created_at datetime NULL DEFAULT NULL,
			updated_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (tid),
			KEY release_kind (release_kind),
			KEY rgroup (rgroup)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);
}

function kz_upload_load_details($tid)
{
	$tid = (int)$tid;
	$details = array(
		'exists' => false,
		'tid' => $tid,
		'release_kind' => 'video',
		'poster_url' => '',
		'rgroup' => 0,
		'rgroup_button' => '',
		'form_mode' => 0,
		'section_modes' => '0,0,0,0',
		'data' => kz_upload_default_data(),
	);

	if ($tid <= 0 || !kz_upload_table_exists('torrent_details')) {
		return $details;
	}

	$res = sql_query("SELECT * FROM torrent_details WHERE tid = " . $tid . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);

	if (!$row) {
		return $details;
	}

	$data = json_decode($row['data'], true);
	if (!is_array($data)) {
		$data = array();
	}

	$details['release_kind'] = (string)$row['release_kind'];
	$details['exists'] = true;
	$details['poster_url'] = (string)$row['poster_url'];
	$details['rgroup'] = (int)$row['rgroup'];
	$details['rgroup_button'] = (string)$row['rgroup_button'];
	$details['form_mode'] = (int)$row['form_mode'];
	$details['section_modes'] = (string)$row['section_modes'];
	$details['data'] = array_replace_recursive(kz_upload_default_data(), $data);

	return $details;
}

function kz_upload_save_details($tid, $kind, $poster_url, $rgroup, $rgroup_button, array $data)
{
	kz_upload_ensure_schema();

	$tid = (int)$tid;
	$kind = kz_upload_normalize_kind($kind);
	$poster_url = trim((string)$poster_url);
	$rgroup = (int)$rgroup;
	$rgroup_button = trim((string)$rgroup_button);
	$form_mode = (int)($data['mode'] ?? 0);
	$section_modes = kz_upload_normalize_section_modes($data['section_modes'] ?? array());
	$data['section_modes'] = $section_modes;

	$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	sql_query("
		INSERT INTO torrent_details
			(tid, release_kind, poster_url, rgroup, rgroup_button, form_mode, section_modes, data, created_at, updated_at)
		VALUES
			(" . implode(', ', array(
				sqlesc($tid),
				sqlesc($kind),
				sqlesc($poster_url),
				sqlesc($rgroup),
				sqlesc($rgroup_button),
				sqlesc($form_mode),
				sqlesc(implode(',', $section_modes)),
				sqlesc($json),
				'NOW()',
				'NOW()',
			)) . ")
		ON DUPLICATE KEY UPDATE
			release_kind = VALUES(release_kind),
			poster_url = VALUES(poster_url),
			rgroup = VALUES(rgroup),
			rgroup_button = VALUES(rgroup_button),
			form_mode = VALUES(form_mode),
			section_modes = VALUES(section_modes),
			data = VALUES(data),
			updated_at = NOW()
	") or sqlerr(__FILE__, __LINE__);
}

function kz_upload_normalize_kind($kind)
{
	$kind = (string)$kind;
	$kinds = kz_upload_kinds();
	return isset($kinds[$kind]) ? $kind : 'video';
}

function kz_upload_kind_by_category($category)
{
	$category = (int)$category;
	foreach (kz_upload_categories() as $kind => $items) {
		if (isset($items[$category])) {
			return $kind;
		}
	}

	return 'video';
}

function kz_upload_is_valid_category($kind, $category)
{
	$kind = kz_upload_normalize_kind($kind);
	$category = (int)$category;
	$categories = kz_upload_categories();
	return isset($categories[$kind][$category]);
}

function kz_upload_normalize_section_modes($modes)
{
	$out = array(0, 0, 0, 0);
	if (!is_array($modes)) {
		$modes = explode(',', (string)$modes);
	}

	for ($i = 0; $i < 4; $i++) {
		$out[$i] = !empty($modes[$i]) ? 1 : 0;
	}

	return $out;
}

function kz_upload_post_text($array, $key, $default = '')
{
	if (!is_array($array) || !array_key_exists($key, $array)) {
		return $default;
	}

	return trim((string)$array[$key]);
}

function kz_upload_collect_pairs($titles, $values, $value_key)
{
	$pairs = array();
	$titles = is_array($titles) ? $titles : array();
	$values = is_array($values) ? $values : array();
	$count = max(count($titles), count($values));

	for ($i = 0; $i < $count; $i++) {
		$title = trim((string)($titles[$i] ?? ''));
		$value = trim((string)($values[$i] ?? ''));

		if ($title === '' && $value === '') {
			continue;
		}

		$pairs[] = array('title' => $title, $value_key => $value);
	}

	return $pairs;
}

function kz_upload_collect_post($torrent_size = 0)
{
	$data = kz_upload_default_data();
	$kind = kz_upload_normalize_kind($_POST['kind'] ?? 'video');
	$mode = !empty($_POST['mode']) ? 1 : 0;
	$section_modes = kz_upload_normalize_section_modes($_POST['section_mode'] ?? array());

	$data['mode'] = $mode;
	$data['section_modes'] = $section_modes;

	$advanced = is_array($_POST['advanced'] ?? null) ? $_POST['advanced'] : array();
	for ($i = 1; $i <= 4; $i++) {
		$data['advanced']['desc' . $i] = kz_upload_post_text($advanced, 'desc' . $i);
	}

	$video = is_array($_POST['video'] ?? null) ? $_POST['video'] : array();
	foreach ($data['video'] as $key => $value) {
		$data['video'][$key] = kz_upload_post_text($video, $key);
	}

	if ($data['video']['size'] === '' && $torrent_size > 0) {
		$data['video']['size'] = mksize($torrent_size);
	}

	$design = is_array($_POST['design'] ?? null) ? $_POST['design'] : array();
	$data['design']['related'] = kz_upload_collect_pairs($design['related_title'] ?? array(), $design['related_query'] ?? array(), 'query');
	$data['design']['watch'] = kz_upload_collect_pairs($design['watch_title'] ?? array(), $design['watch_url'] ?? array(), 'url');
	$data['design']['tabs'] = kz_upload_collect_pairs($design['tab_title'] ?? array(), $design['tab_content'] ?? array(), 'content');
	$data['design']['screens'] = kz_upload_post_text($design, 'screens');
	$data['design']['notes'] = kz_upload_post_text($design, 'notes');
	$data['design']['imdb'] = array(
		'enabled' => !empty($design['imdb_enabled']) ? 1 : 0,
		'url' => kz_upload_post_text($design, 'imdb_url'),
		'rating' => kz_upload_post_text($design, 'imdb_rating'),
	);
	$data['design']['kinopoisk'] = array(
		'enabled' => !empty($design['kinopoisk_enabled']) ? 1 : 0,
		'url' => kz_upload_post_text($design, 'kinopoisk_url'),
		'rating' => kz_upload_post_text($design, 'kinopoisk_rating'),
	);

	$generic = is_array($_POST['generic'] ?? null) ? $_POST['generic'] : array();
	for ($i = 1; $i <= 4; $i++) {
		$data['generic']['desc' . $i] = kz_upload_post_text($generic, 'desc' . $i);
	}

	return array($kind, $data);
}

function kz_upload_line($label, $value)
{
	$value = trim((string)$value);
	if ($value === '') {
		return '';
	}

	return '[b]' . $label . ':[/b] ' . $value;
}

function kz_upload_section_from_lines(array $lines)
{
	$out = array();
	foreach ($lines as $line) {
		$line = trim((string)$line);
		if ($line !== '') {
			$out[] = $line;
		}
	}

	return implode("\n", $out);
}

function kz_upload_build_description(array $data, $kind, $torrent_name = '', $torrent_size = 0)
{
	$kind = kz_upload_normalize_kind($kind);
	$section_modes = kz_upload_normalize_section_modes($data['section_modes'] ?? array());
	$advanced = $data['advanced'] ?? array();

	if ($kind !== 'video') {
		$generic = $data['generic'] ?? array();
		$parts = array();
		for ($i = 1; $i <= 4; $i++) {
			$value = trim((string)($generic['desc' . $i] ?? ''));
			if ($value === '') {
				$value = trim((string)($advanced['desc' . $i] ?? ''));
			}
			if ($value !== '') {
				$parts[] = $value;
			}
		}

		return trim(implode("\n\n", $parts));
	}

	$video = $data['video'] ?? array();
	$design = $data['design'] ?? array();

	$normal = array();
	$normal[0] = kz_upload_section_from_lines(array(
		kz_upload_line('Название', $video['title'] ?? ''),
		kz_upload_line('Оригинальное название', $video['original_title'] ?? ''),
		kz_upload_line('Год выпуска', $video['year'] ?? ''),
		kz_upload_line('Жанр', $video['genre'] ?? ''),
		kz_upload_line('Выпущено', $video['released'] ?? ''),
		kz_upload_line('Режиссер', $video['director'] ?? ''),
		kz_upload_line('В ролях', $video['cast'] ?? ''),
	));
	$normal[1] = kz_upload_section_from_lines(array(
		kz_upload_line('О фильме', $video['about'] ?? ''),
	));
	$normal[2] = kz_upload_section_from_lines(array(
		kz_upload_line('Качество', $video['quality'] ?? ''),
		kz_upload_line('Видео', $video['video'] ?? ''),
		kz_upload_line('Аудио', $video['audio'] ?? ''),
		kz_upload_line('Размер', $video['size'] ?? ''),
		kz_upload_line('Продолжительность', $video['duration'] ?? ''),
		kz_upload_line('Перевод', $video['translation'] ?? ''),
		kz_upload_line('Язык', $video['language'] ?? ''),
		kz_upload_line('Субтитры', $video['subtitles'] ?? ''),
	));
	$normal[3] = kz_upload_build_design_section($design);

	$parts = array();
	for ($i = 0; $i < 4; $i++) {
		$value = !empty($section_modes[$i]) ? trim((string)($advanced['desc' . ($i + 1)] ?? '')) : trim((string)($normal[$i] ?? ''));
		if ($value !== '') {
			$parts[] = $value;
		}
	}

	if (!$parts && trim((string)$torrent_name) !== '') {
		$parts[] = kz_upload_line('Название', $torrent_name);
	}

	return trim(implode("\n\n", $parts));
}

function kz_upload_build_design_section(array $design)
{
	global $DEFAULTBASEURL;

	$parts = array();
	$related_lines = array();
	foreach (($design['related'] ?? array()) as $item) {
		$title = trim((string)($item['title'] ?? ''));
		$query = trim((string)($item['query'] ?? ''));
		if ($title === '' || $query === '') {
			continue;
		}
		$related_lines[] = '[url=' . rtrim($DEFAULTBASEURL, '/') . '/browse.php?s=' . rawurlencode($query) . ']' . $title . '[/url]';
	}
	if ($related_lines) {
		$parts[] = "[b]Меню: поиск раздач[/b]\n" . implode("\n", $related_lines);
	}

	$imdb = $design['imdb'] ?? array();
	if (!empty($imdb['enabled']) && trim((string)($imdb['url'] ?? '')) !== '') {
		$parts[] = kz_upload_rating_line('IMDb', $imdb['url'], $imdb['rating'] ?? '');
	}

	$kinopoisk = $design['kinopoisk'] ?? array();
	if (!empty($kinopoisk['enabled']) && trim((string)($kinopoisk['url'] ?? '')) !== '') {
		$parts[] = kz_upload_rating_line('КиноПоиск', $kinopoisk['url'], $kinopoisk['rating'] ?? '');
	}

	$watch_lines = array();
	foreach (($design['watch'] ?? array()) as $item) {
		$title = trim((string)($item['title'] ?? ''));
		$url = trim((string)($item['url'] ?? ''));
		if ($title === '' || $url === '') {
			continue;
		}
		$watch_lines[] = '[url=' . $url . ']' . $title . '[/url]';
	}
	if ($watch_lines) {
		$parts[] = "[b]Меню: ознакомление[/b]\n" . implode("\n", $watch_lines);
	}

	foreach (($design['tabs'] ?? array()) as $tab) {
		$title = trim((string)($tab['title'] ?? ''));
		$content = trim((string)($tab['content'] ?? ''));
		if ($title === '' || $content === '') {
			continue;
		}
		$parts[] = '[hide=' . $title . ']' . $content . '[/hide]';
	}

	$screens = kz_upload_screens_to_bbcode($design['screens'] ?? '');
	if ($screens !== '') {
		$parts[] = $screens;
	}

	$notes = trim((string)($design['notes'] ?? ''));
	if ($notes !== '') {
		$parts[] = $notes;
	}

	return trim(implode("\n\n", $parts));
}

function kz_upload_rating_line($title, $url, $rating)
{
	$url = trim((string)$url);
	$rating = trim((string)$rating);

	if ($rating !== '') {
		return '[b]' . $title . ':[/b] [url=' . $url . ']' . $rating . '[/url]';
	}

	return '[b]' . $title . ':[/b] [url=' . $url . ']' . $url . '[/url]';
}

function kz_upload_screens_to_bbcode($value)
{
	$value = trim((string)$value);
	if ($value === '') {
		return '';
	}

	if (preg_match('#\[(img|url|hide|spoiler|b|i|u)[=\]]#i', $value)) {
		return $value;
	}

	$lines = preg_split('#\r\n|\r|\n#', $value);
	$out = array();
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '') {
			continue;
		}
		if (preg_match('#^(https?:)?//#i', $line)) {
			$out[] = '[img]' . $line . '[/img]';
		} else {
			$out[] = $line;
		}
	}

	return implode("\n", $out);
}

function kz_upload_meta_description($descr)
{
	$text = trim(preg_replace('#\s+#u', ' ', strip_tags((string)$descr)));
	$text = preg_replace('#\[(?:/?[a-z0-9]+(?:=[^\]]+)?)\]#iu', '', $text);
	return mb_substr($text, 0, 250, 'UTF-8');
}

function kz_upload_keywords(array $data, $kind, $name)
{
	if ($kind === 'video') {
		$video = $data['video'] ?? array();
		$parts = array($name, $video['original_title'] ?? '', $video['year'] ?? '', $video['genre'] ?? '');
		return trim(implode(', ', array_filter(array_map('trim', $parts))));
	}

	return trim((string)$name);
}

function kz_upload_option_select($name, array $options, $selected, $class = 'w100p', $extra = '')
{
	$html = '<select name="' . kz_h($name) . '" class="' . kz_h($class) . '"' . ($extra ? ' ' . $extra : '') . '>';
	foreach ($options as $value => $label) {
		$html .= '<option value="' . kz_h($value) . '"' . ((string)$value === (string)$selected ? ' selected="selected"' : '') . '>' . kz_h($label) . '</option>';
	}
	$html .= '</select>';
	return $html;
}

function kz_upload_input($name, $value, $placeholder = '')
{
	return '<input type="text" name="' . kz_h($name) . '" value="' . kz_h($value) . '" class="w100p"' . ($placeholder !== '' ? ' placeholder="' . kz_h($placeholder) . '"' : '') . '>';
}

function kz_upload_textarea($name, $value, $rows = 6, $placeholder = '')
{
	return '<textarea name="' . kz_h($name) . '" rows="' . (int)$rows . '" class="w100p"' . ($placeholder !== '' ? ' placeholder="' . kz_h($placeholder) . '"' : '') . '>' . kz_h($value) . '</textarea>';
}

function kz_upload_render_info_sidebar()
{
	global $CURUSER;

	$user = '<span class="u0">Гость</span>';
	if (!empty($CURUSER)) {
		$user = '<a href="/userdetails.php?id=' . (int)$CURUSER['id'] . '" class="u' . (int)$CURUSER['class'] . '">' . kz_h($CURUSER['username']) . '</a>';
	}
	?>
	<div class="mn3_menu">
		<ul class="men">
			<li class="img"><a href="/upload.php"><img src="/pic//bn/p_upload.jpg" height="75" class="block w200" alt=""></a></li>
			<li class="tp">Информация</li>
			<li class="justify">Вы пользователь <?= $user ?> и можете, если есть чем поделиться с народом, залить раздачу на трекер. Раздача будет размещена в тренировочном трекере; подходящие требованиям материалы могут быть перенесены в основной трекер.</li>
			<li></li>
			<li class="tp">Правила оформления раздач</li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=68207">Общие правила оформления</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showpost.php?p=2715219">Правила использования тегов</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showpost.php?p=2715216">Постер раздачи</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=68461">Правила раздела Видео</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=59050">Правила раздела Аудио</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=59059">Правила раздела Игры</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=151438">Правила раздела Программы</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=95897">Правила раздела Библиотеки</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=74474">Правила раздела АудиоКниги</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=81921">Правила раздела Графики</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showpost.php?p=2715231">Раздачи с допматериалами</a></li>
			<li></li>
			<li class="tp">Учебная информация</li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=68207">Помощник Кинооператора</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=63820">Как пользоваться поиском</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=144207">Полезная информация</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showpost.php?p=2715221">Название видеофайла</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=68468">Создание торрент-файла</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=84803">Качество фильма и перевода</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=68458">Создание инфо-файла</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=30869">Создание скриншотов</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=220279">Скриншоты для DVD</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=66343">Полноразмерные скриншоты</a></li>
			<li><span class="bulet"></span><a href="//forum.kinozal.tv/showthread.php?t=68460">Создание семпла</a></li>
			<li></li>
			<li class="tp">Скрипт заливки версия 1.0</li>
			<li>Скрипт заливки раздачи создан<br>на сайте creative.kinozal.tv. Разработчик: ФАНАТ</li>
		</ul>
	</div>
	<?php
}

function kz_upload_render_form($action, $submit_label, array $state, $is_edit = false)
{
	$name = (string)($state['name'] ?? '');
	$kind = kz_upload_normalize_kind($state['kind'] ?? 'video');
	$category = (int)($state['category'] ?? 0);
	$details = $state['details'] ?? array();
	$data = array_replace_recursive(kz_upload_default_data(), $details['data'] ?? array());
	$poster_url = (string)($details['poster_url'] ?? '');
	$rgroup = (int)($details['rgroup'] ?? 0);
	$rgroup_button = (string)($details['rgroup_button'] ?? '');
	$mode = (int)($data['mode'] ?? 0);
	$section_modes = kz_upload_normalize_section_modes($data['section_modes'] ?? array());
	$allow_file = !empty($state['allow_file']);
	?>
	<form enctype="multipart/form-data" action="<?= kz_h($action) ?>" method="post" name="upt" id="upt">
		<?php if ($is_edit) { ?>
			<input type="hidden" name="id" value="<?= (int)$state['id'] ?>">
			<?php if (!empty($state['returnto'])) { ?>
				<input type="hidden" name="returnto" value="<?= kz_h($state['returnto']) ?>">
			<?php } ?>
		<?php } ?>
		<input type="hidden" name="kind" id="kind" value="<?= kz_h($kind) ?>">
		<input type="hidden" name="type" id="type" value="<?= (int)$category ?>">
		<input type="hidden" name="mode" id="mode" value="<?= $mode ?>">
		<?php for ($i = 0; $i < 4; $i++) { ?>
			<input type="hidden" name="section_mode[<?= $i ?>]" id="section_mode_<?= $i ?>" value="<?= (int)$section_modes[$i] ?>">
		<?php } ?>

		<div class="bx1">
			<ul class="men">
				<li class="tp2 b">Название</li>
				<li><?= kz_upload_input('name', $name) ?></li>
				<?php if (!$is_edit || $allow_file) { ?>
					<li class="tp2 b">Торрент-файл</li>
					<li><input type="file" name="file" size="80" class="w100p" accept=".torrent,application/x-bittorrent"></li>
				<?php } ?>
				<li class="tp2 b">Ссылка на постер</li>
				<li>
					<?= kz_upload_input('imgl', $poster_url) ?>
					<div class="n">Ширина постера - 200 пикселей. Разместите постер на одном из <a href="//forum.kinozal.tv/showthread.php?t=78697" target="_blank" class="sba">хостингов изображений</a></div>
				</li>
			</ul>
		</div>

		<div class="bx1">
			<ul class="men">
				<li class="tp2 b">Тип раздачи</li>
				<li><?php kz_upload_render_kind_tabs($kind); ?></li>
			</ul>
		</div>

		<div class="bx1">
			<ul class="men">
				<li class="tp2 b">Режим оформления</li>
				<li>
					<ul class="lis">
						<li id="mode_tab_0"<?= $mode ? '' : ' class="tp"' ?>><a href="#" onclick="Upl.changeMode(0); return false;">Обычный режим</a></li>
						<li id="mode_tab_1"<?= $mode ? ' class="tp"' : '' ?>><a href="#" onclick="Upl.changeMode(1); return false;">Расширенный режим</a></li>
					</ul>
					<div class="clr"></div>
				</li>
			</ul>
		</div>

		<div id="template_video"<?= $kind === 'video' ? '' : ' style="display:none;"' ?>>
			<?php kz_upload_render_video_template($data, $section_modes); ?>
		</div>

		<div id="template_generic"<?= $kind === 'video' ? ' style="display:none;"' : '' ?>>
			<?php kz_upload_render_generic_template($data); ?>
		</div>

		<div class="bx1">
			<ul class="men">
				<li class="tp2 b">Релиз-группа</li>
				<li>
					<select name="rgroup" class="w250" onchange="document.forms['upt'].elements['rbut'].value = this.value == '0' ? '' : ('/pic/groupex/' + this.value + '.gif');">
						<option value="0">Выберите релиз-группу</option>
						<?php foreach (kz_upload_release_groups() as $id => $label) { ?>
							<option value="<?= (int)$id ?>"<?= (int)$id === $rgroup ? ' selected="selected"' : '' ?>><?= kz_h($label) ?></option>
						<?php } ?>
					</select>
				</li>
				<li class="tp2 b">Кнопка релиз-группы</li>
				<li>
					<input type="text" name="rbut" class="w100p" value="<?= kz_h($rgroup_button) ?>">
					<div class="n">Кнопка релиз-группы (88x31) или имя релиз-группы, если нет баннера. <a href="//forum.kinozal.tv/showthread.php?t=78697" target="_blank" class="sba">Список хостингов</a></div>
				</li>
			</ul>
		</div>

		<div class="bx1">
			<ul class="men">
				<li class="tp2 b">Раздел</li>
				<li><?php kz_upload_render_category_selects($kind, $category); ?></li>
				<li class="tp2 b">Тип раздачи</li>
				<li><a href="//forum.kinozal.tv/showpost.php?p=2715225" class="sba" target="_blank">Правила включения золотых и серебряных раздач</a></li>
			</ul>
		</div>

		<?php if (!empty($state['service_controls'])) { ?>
			<div class="bx1">
				<ul class="men">
					<li class="tp2 b">Служебное</li>
					<li><?= $state['service_controls'] ?></li>
				</ul>
			</div>
		<?php } ?>

		<div class="bx1">
			<div class="u7">
				<input type="button" value="<?= kz_h($submit_label) ?>" onclick="Upl.upload();" class="buttonS">
				<input type="button" value="Предварительный просмотр" onclick="Upl.test();" class="buttonS">
			</div>
		</div>
	</form>

	<?php kz_upload_render_js($kind, $mode, $section_modes); ?>
	<?php
}

function kz_upload_render_kind_tabs($current)
{
	echo '<ul class="lis">';
	foreach (kz_upload_kinds() as $kind => $label) {
		echo '<li id="kind_tab_' . kz_h($kind) . '"' . ($kind === $current ? ' class="tp"' : '') . '><a href="#" onclick="Upl.setTemplate(\'' . kz_h($kind) . '\'); return false;">' . kz_h($label) . '</a></li>';
	}
	echo '</ul><div class="clr"></div>';
}

function kz_upload_render_category_selects($current_kind, $current_category)
{
	foreach (kz_upload_categories() as $kind => $items) {
		echo '<select class="w250" id="type_' . kz_h($kind) . '"' . ($kind === $current_kind ? '' : ' style="display:none;"') . ' onchange="Upl.syncType();">';
		if (count($items) > 1) {
			echo '<option value="0">Выберите раздел</option>';
		}
		foreach ($items as $id => $label) {
			echo '<option value="' . (int)$id . '"' . ((int)$id === (int)$current_category ? ' selected="selected"' : '') . '>' . kz_h($label) . '</option>';
		}
		echo '</select>';
	}
}

function kz_upload_render_video_template(array $data, array $section_modes)
{
	$video = $data['video'] ?? array();
	$advanced = $data['advanced'] ?? array();
	$design = $data['design'] ?? array();
	kz_upload_render_section_start(0, 'Предварительное описание', $section_modes[0]);
	?>
	<table class="tables1 w100p upl-normal upl-section-0">
		<tr><td class="w175">Название:</td><td><?= kz_upload_input('video[title]', $video['title'] ?? '', 'Название фильма') ?><div class="n">Для зарубежного видео, на русском языке</div></td></tr>
		<tr><td>Оригинальное название:</td><td><?= kz_upload_input('video[original_title]', $video['original_title'] ?? '', 'Movie title') ?><div class="n">Название видео на языке оригинала</div></td></tr>
		<tr><td>Год выпуска:</td><td><?= kz_upload_input('video[year]', $video['year'] ?? '', '2009') ?></td></tr>
		<tr><td>Жанр:</td><td><?= kz_upload_input('video[genre]', $video['genre'] ?? '', 'Комедия, приключения') ?></td></tr>
		<tr><td>Выпущено:</td><td><?= kz_upload_input('video[released]', $video['released'] ?? '', 'Страна, киностудия') ?></td></tr>
		<tr><td>Режиссер:</td><td><?= kz_upload_input('video[director]', $video['director'] ?? '', 'Имя Фамилия') ?></td></tr>
		<tr><td>В ролях:</td><td><?= kz_upload_textarea('video[cast]', $video['cast'] ?? '', 4, 'Имя Фамилия, Имя Фамилия') ?><div class="n">Список исполняющих роли через запятую</div></td></tr>
	</table>
	<div class="upl-advanced upl-section-0" style="display:none;"><?= kz_upload_textarea('advanced[desc1]', $advanced['desc1'] ?? '', 8) ?></div>
	<?php
	kz_upload_render_section_end();

	kz_upload_render_section_start(1, 'Описание', $section_modes[1]);
	?>
	<table class="tables1 w100p upl-normal upl-section-1">
		<tr><td class="w175">О фильме:</td><td><?= kz_upload_textarea('video[about]', $video['about'] ?? '', 10, 'Краткое описание фильма...') ?><div class="n">Рекомендуем писать собственное описание, а не копировать его из сети - это положительно скажется на количестве сидов и пиров.</div></td></tr>
	</table>
	<div class="upl-advanced upl-section-1" style="display:none;"><?= kz_upload_textarea('advanced[desc2]', $advanced['desc2'] ?? '', 10) ?></div>
	<?php
	kz_upload_render_section_end();

	kz_upload_render_section_start(2, 'Технические данные', $section_modes[2]);
	?>
	<table class="tables1 w100p upl-normal upl-section-2">
		<tr><td class="w175">Качество:</td><td><?= kz_upload_option_select('video[quality]', kz_upload_quality_options(), $video['quality'] ?? '') ?><div class="n">Подробнее о качестве раздаваемого материала здесь</div></td></tr>
		<tr><td>Видео:</td><td><?= kz_upload_input('video[video]', $video['video'] ?? '', 'MPEG-4 AVC, 9131 Кбит/с, 1920x1080') ?></td></tr>
		<tr><td>Аудио:</td><td><?= kz_upload_input('video[audio]', $video['audio'] ?? '', 'Русский (AC3, 6 ch, 384 Кбит/с)') ?></td></tr>
		<tr><td>Размер:</td><td><?= kz_upload_input('video[size]', $video['size'] ?? '', 'Установится автоматически после выбора торрент-файла') ?><div class="n">Если оставить поле пустым, размер будет взят из торрент-файла</div></td></tr>
		<tr><td>Продолжительность:</td><td><?= kz_upload_input('video[duration]', $video['duration'] ?? '', '01:39:43') ?><div class="n">Точная продолжительность в формате ЧЧ:ММ:СС</div></td></tr>
		<tr><td>Перевод:</td><td><?= kz_upload_option_select('video[translation]', kz_upload_translation_options(), $video['translation'] ?? '') ?><div class="n">Для зарубежного видео. О видах перевода подробнее здесь</div></td></tr>
		<tr><td>Язык:</td><td><?= kz_upload_option_select('video[language]', kz_upload_language_options(), $video['language'] ?? '') ?><div class="n">Для отечественного видео</div></td></tr>
		<tr><td>Субтитры:</td><td><?= kz_upload_option_select('video[subtitles]', kz_upload_subtitle_options(), $video['subtitles'] ?? '') ?><div class="n">Укажите субтитры, если имеются</div></td></tr>
	</table>
	<div class="upl-advanced upl-section-2" style="display:none;"><?= kz_upload_textarea('advanced[desc3]', $advanced['desc3'] ?? '', 8) ?></div>
	<?php
	kz_upload_render_section_end();

	kz_upload_render_section_start(3, 'Оформление, вкладки, примечания, скриншоты', $section_modes[3]);
	kz_upload_render_design_fields($design);
	?>
	<div class="upl-advanced upl-section-3" style="display:none;"><?= kz_upload_textarea('advanced[desc4]', $advanced['desc4'] ?? '', 31) ?></div>
	<?php
	kz_upload_render_section_end();
}

function kz_upload_render_section_start($index, $title, $advanced)
{
	?>
	<div class="bx1">
		<ul class="men">
			<li class="tp2 b">
				<span class="floatright"><input type="button" value="Сменить режим" onclick="Upl.switchMode(<?= (int)$index ?>);" class="buttonS"></span>
				<?= kz_h($title) ?>
			</li>
		</ul>
		<div id="section_<?= (int)$index ?>" data-advanced="<?= $advanced ? 1 : 0 ?>">
	<?php
}

function kz_upload_render_section_end()
{
	echo '</div></div>';
}

function kz_upload_render_design_fields(array $design)
{
	$related = $design['related'] ?? array();
	$watch = $design['watch'] ?? array();
	$tabs = $design['tabs'] ?? array();
	$imdb = $design['imdb'] ?? array();
	$kinopoisk = $design['kinopoisk'] ?? array();
	?>
	<div class="upl-normal upl-section-3">
		<ul class="men">
			<li class="tp2">Меню: поиск раздач</li>
			<li class="n">Добавьте ссылки на поиск других раздач, которые могут заинтересовать зрителей</li>
		</ul>
		<table class="tables1 w100p" id="related_rows">
			<tr><td class="w25p b">Заголовок ссылки</td><td class="b">Строка поиска</td><td class="w30"></td></tr>
			<?php kz_upload_render_pair_rows('design[related_title][]', 'design[related_query][]', $related, 'query', 2); ?>
		</table>
		<div class="pad0x0x5x0"><a href="#" class="sba" onclick="Upl.addPair('related_rows', 'design[related_title][]', 'design[related_query][]'); return false;">Добавить элемент</a></div>

		<ul class="men">
			<li class="tp2"><label><input type="checkbox" name="design[imdb_enabled]" value="1"<?= !empty($imdb['enabled']) ? ' checked="checked"' : '' ?>> Меню: рейтинг IMDb</label></li>
			<li>Укажите страницу фильма и его рейтинг на сайте <a href="https://www.imdb.com/" class="sba" target="_blank">IMDb</a>, если есть</li>
		</ul>
		<table class="tables1 w100p">
			<tr><td class="w50p b">Ссылка на фильм</td><td class="b">Цифры рейтинга</td></tr>
			<tr><td><input type="text" name="design[imdb_url]" class="w100p" value="<?= kz_h($imdb['url'] ?? '') ?>" placeholder="https://www.imdb.com/title/tt00000/"></td><td><input type="text" name="design[imdb_rating]" class="w100p" value="<?= kz_h($imdb['rating'] ?? '') ?>" placeholder="Цифры рейтинга"></td></tr>
		</table>

		<ul class="men">
			<li class="tp2"><label><input type="checkbox" name="design[kinopoisk_enabled]" value="1"<?= !empty($kinopoisk['enabled']) ? ' checked="checked"' : '' ?>> Меню: рейтинг КиноПоиск</label></li>
			<li>Укажите страницу фильма и его рейтинг на сайте <a href="https://www.kinopoisk.ru/" class="sba" target="_blank">КиноПоиск</a>, если есть</li>
		</ul>
		<table class="tables1 w100p">
			<tr><td class="w50p b">Ссылка на фильм</td><td class="b">Цифры рейтинга</td></tr>
			<tr><td><input type="text" name="design[kinopoisk_url]" class="w100p" value="<?= kz_h($kinopoisk['url'] ?? '') ?>" placeholder="https://www.kinopoisk.ru/film/00000/"></td><td><input type="text" name="design[kinopoisk_rating]" class="w100p" value="<?= kz_h($kinopoisk['rating'] ?? '') ?>" placeholder="Цифры рейтинга"></td></tr>
		</table>

		<ul class="men"><li class="tp2">Меню: ознакомление</li></ul>
		<table class="tables1 w100p" id="watch_rows">
			<tr><td class="w25p b">Заголовок ссылки</td><td class="b">Адрес ссылки</td><td class="w30"></td></tr>
			<?php kz_upload_render_pair_rows('design[watch_title][]', 'design[watch_url][]', $watch, 'url', 2); ?>
		</table>
		<div class="pad0x0x5x0"><a href="#" class="sba" onclick="Upl.addPair('watch_rows', 'design[watch_title][]', 'design[watch_url][]'); return false;">Добавить элемент</a></div>

		<ul class="men">
			<li class="tp2">Дополнительные вкладки</li>
			<li class="n">Вы можете указать дополнительную информацию о раздаваемом материале. Допустимо использовать не больше шести вкладок</li>
		</ul>
		<table class="tables1 w100p" id="tab_rows">
			<tr><td class="w175 b">Название вкладки</td><td class="b">Содержимое</td><td class="w30"></td></tr>
			<?php kz_upload_render_tab_rows($tabs); ?>
		</table>
		<div class="pad0x0x5x0"><a href="#" class="sba" onclick="Upl.addTab(); return false;">Добавить вкладку</a></div>

		<ul class="men"><li class="tp2">Скриншоты и примечания</li></ul>
		<table class="tables1 w100p">
			<tr><td class="w175">Скриншоты:</td><td><?= kz_upload_textarea('design[screens]', $design['screens'] ?? '', 6, "Ссылки на изображения, по одной в строке") ?></td></tr>
			<tr><td>Примечания:</td><td><?= kz_upload_textarea('design[notes]', $design['notes'] ?? '', 4) ?></td></tr>
		</table>
	</div>
	<?php
}

function kz_upload_render_pair_rows($name1, $name2, array $rows, $value_key, $minimum)
{
	for ($i = 0; $i < max($minimum, count($rows)); $i++) {
		$row = $rows[$i] ?? array('title' => '', $value_key => '');
		echo '<tr><td><input type="text" name="' . kz_h($name1) . '" class="w100p" value="' . kz_h($row['title'] ?? '') . '"></td>';
		echo '<td><input type="text" name="' . kz_h($name2) . '" class="w100p" value="' . kz_h($row[$value_key] ?? '') . '"></td>';
		echo '<td class="center"><a href="#" class="sba" onclick="Upl.removeRow(this); return false;">×</a></td></tr>';
	}
}

function kz_upload_render_tab_rows(array $rows)
{
	for ($i = 0; $i < max(1, count($rows)); $i++) {
		$row = $rows[$i] ?? array('title' => '', 'content' => '');
		echo '<tr><td><input type="text" name="design[tab_title][]" class="w100p" value="' . kz_h($row['title'] ?? '') . '"></td>';
		echo '<td>' . kz_upload_textarea('design[tab_content][]', $row['content'] ?? '', 5) . '</td>';
		echo '<td class="center"><a href="#" class="sba" onclick="Upl.removeRow(this); return false;">×</a></td></tr>';
	}
}

function kz_upload_render_generic_template(array $data)
{
	$generic = $data['generic'] ?? array();
	$advanced = $data['advanced'] ?? array();
	$labels = array(
		1 => 'Предварительное описание',
		2 => 'Описание',
		3 => 'Технические данные',
		4 => 'Оформление, вкладки, примечания, скриншоты',
	);
	for ($i = 1; $i <= 4; $i++) {
		$value = (string)($generic['desc' . $i] ?? '');
		if ($value === '') {
			$value = (string)($advanced['desc' . $i] ?? '');
		}
		echo '<div class="bx1"><ul class="men"><li class="tp2 b">' . kz_h($labels[$i]) . '</li><li>' . kz_upload_textarea('generic[desc' . $i . ']', $value, $i === 4 ? 14 : 8) . '</li></ul></div>';
	}
}

function kz_upload_render_js($kind, $mode, array $section_modes)
{
	?>
	<script type="text/javascript">
	var Upl = {
		setTemplate: function(kind) {
			var kinds = ['video', 'music', 'game', 'audiobook', 'program', 'book', 'graphic'];
			document.getElementById('kind').value = kind;
			for (var i = 0; i < kinds.length; i++) {
				var k = kinds[i];
				var tab = document.getElementById('kind_tab_' + k);
				var sel = document.getElementById('type_' + k);
				if (tab) tab.className = (k === kind ? 'tp' : '');
				if (sel) sel.style.display = (k === kind ? '' : 'none');
			}
			document.getElementById('template_video').style.display = (kind === 'video' ? '' : 'none');
			document.getElementById('template_generic').style.display = (kind === 'video' ? 'none' : '');
			this.syncType();
		},
		syncType: function() {
			var kind = document.getElementById('kind').value;
			var sel = document.getElementById('type_' + kind);
			if (sel) document.getElementById('type').value = sel.value;
		},
		changeMode: function(mode) {
			document.getElementById('mode').value = mode ? '1' : '0';
			this.setModeTabs(mode);
			for (var i = 0; i < 4; i++) {
				this.setSectionMode(i, mode);
			}
		},
		setModeTabs: function(mode) {
			var tab0 = document.getElementById('mode_tab_0');
			var tab1 = document.getElementById('mode_tab_1');
			if (tab0) tab0.className = mode ? '' : 'tp';
			if (tab1) tab1.className = mode ? 'tp' : '';
		},
		switchMode: function(index) {
			var field = document.getElementById('section_mode_' + index);
			this.setSectionMode(index, field && field.value === '1' ? 0 : 1);
		},
		setSectionMode: function(index, mode) {
			var field = document.getElementById('section_mode_' + index);
			if (field) field.value = mode ? '1' : '0';
			var normal = document.getElementsByClassName('upl-normal upl-section-' + index);
			var advanced = document.getElementsByClassName('upl-advanced upl-section-' + index);
			for (var i = 0; i < normal.length; i++) normal[i].style.display = mode ? 'none' : '';
			for (var j = 0; j < advanced.length; j++) advanced[j].style.display = mode ? '' : 'none';
		},
		addPair: function(tableId, name1, name2) {
			var table = document.getElementById(tableId);
			if (!table) return false;
			var row = table.insertRow(-1);
			row.innerHTML = '<td><input type="text" name="' + name1 + '" class="w100p"></td><td><input type="text" name="' + name2 + '" class="w100p"></td><td class="center"><a href="#" class="sba" onclick="Upl.removeRow(this); return false;">×</a></td>';
			return false;
		},
		addTab: function() {
			var table = document.getElementById('tab_rows');
			if (!table || table.rows.length > 6) return false;
			var row = table.insertRow(-1);
			row.innerHTML = '<td><input type="text" name="design[tab_title][]" class="w100p"></td><td><textarea name="design[tab_content][]" rows="5" class="w100p"></textarea></td><td class="center"><a href="#" class="sba" onclick="Upl.removeRow(this); return false;">×</a></td>';
			return false;
		},
		removeRow: function(link) {
			var row = link;
			while (row && row.tagName !== 'TR') row = row.parentNode;
			if (row && row.parentNode.rows.length > 2) row.parentNode.removeChild(row);
			return false;
		},
		upload: function() {
			var form = document.getElementById('upt');
			this.syncType();
			form.action = form.action.replace(/[?&]preview=1/g, '');
			form.submit();
		},
		test: function() {
			var form = document.getElementById('upt');
			this.syncType();
			form.action = form.action.replace(/[?&]preview=1/g, '') + (form.action.indexOf('?') === -1 ? '?preview=1' : '&preview=1');
			form.submit();
		}
	};
	Upl.setTemplate('<?= kz_h($kind) ?>');
	<?php foreach ($section_modes as $index => $section_mode) { ?>
	Upl.setSectionMode(<?= (int)$index ?>, <?= (int)$section_mode ?>);
	<?php } ?>
	document.getElementById('mode').value = '<?= (int)$mode ?>';
	Upl.setModeTabs(<?= (int)$mode ?>);
	</script>
	<?php
}

function kz_upload_render_online_block()
{
	$users = array();
	$res = sql_query("SELECT id, username, class FROM users WHERE last_access >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) ORDER BY class DESC, username ASC LIMIT 20");
	if ($res) {
		while ($row = mysqli_fetch_assoc($res)) {
			$users[] = '<a href="/userdetails.php?id=' . (int)$row['id'] . '" class="u' . (int)$row['class'] . '">' . kz_h($row['username']) . '</a>';
		}
	}
	if (!$users) {
		$users[] = 'пока никого';
	}
	?>
	<div class="bx2_0">
		<ul class="men">
			<li class="tp2 center">Кто ОнЛайн здесь, на этой странице [ <a class="sba" href="/pay.php">помочь проекту</a> ]</li>
			<li><div class="pad5x5"><?= implode(', ', $users) ?></div></li>
		</ul>
	</div>
	<?php
}

function kz_upload_render_details_panel(array $row, array $details, $descr_html, $owned, array $announces_urls = array())
{
	global $pic_base_url, $tracker_lang;

	$poster = trim((string)($details['poster_url'] ?? ''));
	$rbutton = trim((string)($details['rgroup_button'] ?? ''));
	$groups = kz_upload_release_groups();
	$rgroup_id = (int)($details['rgroup'] ?? 0);
	$rgroup_title = $groups[$rgroup_id] ?? '';
	$id = (int)$row['id'];
	$edit_url = 'edit.php?id=' . $id;
	?>
	<div class="bx2">
		<div class="mn3_menu">
			<ul class="men">
				<?php if ($poster !== '') { ?>
					<li class="center"><img src="<?= kz_h($poster) ?>" class="p200" alt=""></li>
				<?php } elseif (!empty($row['image1'])) { ?>
					<li class="center"><a href="viewimage.php?pic=<?= kz_h($row['image1']) ?>"><img border="0" src="thumbnail.php?<?= kz_h($row['image1']) ?>" alt=""></a></li>
				<?php } ?>
				<li class="tp2 center">Раздача</li>
				<li><span class="bulet"></span><a href="download.php?id=<?= $id ?>" class="sba">Скачать торрент</a></li>
				<li><span class="bulet"></span><a href="bookmark.php?torrent=<?= $id ?>" class="sba"><?= kz_h($tracker_lang['bookmark'] ?? 'Закладка') ?></a></li>
				<?php if ($owned) { ?><li><span class="bulet"></span><a href="<?= kz_h($edit_url) ?>" class="sba"><?= kz_h($tracker_lang['edit'] ?? 'Редактировать') ?></a></li><?php } ?>
				<?php if ($rgroup_title !== '' || $rbutton !== '') { ?>
					<li class="tp2 center">Релиз-группа</li>
					<?php if ($rbutton !== '') { ?>
						<li class="center"><?= preg_match('#^(https?:)?//|^/#i', $rbutton) ? '<img src="' . kz_h($rbutton) . '" class="p88x31n" alt="' . kz_h($rgroup_title) . '">' : kz_h($rbutton) ?></li>
					<?php } ?>
					<?php if ($rgroup_title !== '') { ?><li class="center b"><?= kz_h($rgroup_title) ?></li><?php } ?>
				<?php } ?>
				<li class="tp2 center">Статистика</li>
				<li><dl><dt>Размер</dt><dd><?= kz_h(mksize($row['size'])) ?></dd></dl></li>
				<li><dl><dt>Сиды</dt><dd><?= (int)$row['seeders'] ?></dd></dl></li>
				<li><dl><dt>Пиры</dt><dd><?= (int)$row['leechers'] ?></dd></dl></li>
				<li><dl><dt>Скачали</dt><dd><?= (int)$row['times_completed'] ?></dd></dl></li>
			</ul>
		</div>
		<div class="mn3_content">
			<ul class="men">
				<li class="tp2 b"><?= kz_h($row['name']) ?></li>
				<li><b><?= kz_h($row['cat_name'] ?? '') ?></b></li>
				<li class="pad5x5"><?= $descr_html ?></li>
			</ul>
		</div>
	</div>
	<?php
}

?>
