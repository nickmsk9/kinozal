<?php

/*
// +--------------------------------------------------------------------------+
// | Project:    TBDevYSE - TBDev Yuna Scatari Edition                        |
// +--------------------------------------------------------------------------+
// |                                               Do not remove above lines! |
// +--------------------------------------------------------------------------+
*/

require_once("include/bittorrent.php");
require_once("include/kz_upload.php");
require_once("include/persons.php");
require_once("include/kz_multitracker.php");

dbconn(false);
kz_upload_ensure_schema();
kz_mt_ensure_schema();

if (!$allow_guests_details) {
	loggedinorreturn();
}

function details_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function details_plain($value)
{
	$value = html_entity_decode((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	$value = preg_replace('#\[(/?)(b|i|u|s|url|img|quote|hide|spoiler|color|size|font|family|center|left|right|justify|code|php)[^\]]*\]#iu', '', $value);
	$value = strip_tags($value);
	return trim(preg_replace('#\s+#u', ' ', $value));
}

function details_data(array $details)
{
	$data = isset($details['data']) && is_array($details['data']) ? $details['data'] : array();
	$video = isset($data['video']) && is_array($data['video']) ? $data['video'] : array();
	$design = isset($data['design']) && is_array($data['design']) ? $data['design'] : array();
	return array($video, $design);
}

function details_upload_details_from_row($tid, array $row)
{
	$details = array(
		'exists' => false,
		'tid' => (int)$tid,
		'release_kind' => 'video',
		'poster_url' => '',
		'rgroup' => 0,
		'rgroup_button' => '',
		'torrent_file_updated_at' => '',
		'form_mode' => 0,
		'section_modes' => '0,0,0,0',
		'data' => kz_upload_default_data(),
	);

	if (empty($row['tdet_exists'])) {
		return $details;
	}

	$data = json_decode((string)($row['tdet_data'] ?? ''), true);
	if (!is_array($data)) {
		$data = array();
	}

	$details['exists'] = true;
	$details['release_kind'] = (string)($row['tdet_release_kind'] ?? 'video');
	$details['poster_url'] = (string)($row['tdet_poster_url'] ?? '');
	$details['rgroup'] = (int)($row['tdet_rgroup'] ?? 0);
	$details['rgroup_button'] = (string)($row['tdet_rgroup_button'] ?? '');
	$details['torrent_file_updated_at'] = (string)($row['tdet_torrent_file_updated_at'] ?? '');
	$details['form_mode'] = (int)($row['tdet_form_mode'] ?? 0);
	$details['section_modes'] = (string)($row['tdet_section_modes'] ?? '0,0,0,0');
	$details['data'] = array_replace_recursive(kz_upload_default_data(), $data);

	return $details;
}

function details_guess_title($name)
{
	$name = preg_replace('#\s*/\s*[0-9]{4}\s*/.*$#u', '', (string)$name);
	$name = preg_replace('#\s*/\s*(WEB|BD|DVD|HD|CAM|HDRip|WEBRip|BDRip|DVDRip).*$#iu', '', $name);
	return trim($name);
}

function details_split($value, $limit = 30)
{
	$parts = preg_split('/\s*,\s*/u', (string)$value, -1, PREG_SPLIT_NO_EMPTY);
	$out = array();

	foreach ($parts as $part) {
		$part = trim($part, " \t\n\r\0\x0B\"'");
		if ($part !== '') {
			$out[] = $part;
		}
		if (count($out) >= $limit) {
			break;
		}
	}

	return $out;
}

function details_term_links($value, $kind)
{
	$items = details_split($value, $kind === 'person' ? 20 : 30);
	if (!$items) {
		return '';
	}

	$links = array();
	$person_ids = $kind === 'person' ? details_person_ids_by_names($items) : array();
	foreach ($items as $item) {
		if ($kind === 'person') {
			$url = kz_persons_url($item, $person_ids[$item] ?? 0);
		} else {
			$url = '/top.php?j=' . rawurlencode($item);
		}
		$links[] = '<a href="' . $url . '" class="sba">' . details_h($item) . '</a>';
	}

	return implode(', ', $links);
}

function details_person_ids_by_names(array $names)
{
	static $known = array();
	static $schema_checked = false;

	$lookup = array();
	foreach ($names as $name) {
		$name = trim((string)$name);
		if ($name === '') {
			continue;
		}
		if (!array_key_exists($name, $known)) {
			$lookup[$name] = true;
		}
	}

	if ($lookup) {
		if (!$schema_checked) {
			kz_persons_ensure_schema();
			$schema_checked = true;
		}

		$values = array();
		foreach (array_keys($lookup) as $name) {
			$values[] = sqlesc($name);
			$known[$name] = 0;
		}

		$res = sql_query("
			SELECT id, name, original_name
			FROM persons
			WHERE name IN (" . implode(',', $values) . ")
			   OR original_name IN (" . implode(',', $values) . ")
			ORDER BY id ASC
		") or sqlerr(__FILE__, __LINE__);

		while ($row = mysqli_fetch_assoc($res)) {
			foreach (array_keys($lookup) as $name) {
				if ($known[$name] <= 0 && ($row['name'] === $name || $row['original_name'] === $name)) {
					$known[$name] = (int)$row['id'];
				}
			}
		}

		foreach (array_keys($lookup) as $name) {
			if ($known[$name] > 0) {
				continue;
			}

			$q = sqlesc('%' . $name . '%', true);
			$res = sql_query("
				SELECT id
				FROM persons
				WHERE name LIKE $q OR original_name LIKE $q
				ORDER BY id ASC
				LIMIT 1
			") or sqlerr(__FILE__, __LINE__);
			$row = mysqli_fetch_assoc($res);
			$known[$name] = $row ? (int)$row['id'] : 0;
		}
	}

	$result = array();
	foreach ($names as $name) {
		$result[$name] = $known[$name] ?? 0;
	}

	return $result;
}

function details_line($title, $value, $kind = '')
{
	$value = trim((string)$value);
	if ($value === '') {
		return '';
	}

	if ($kind === 'genre' || $kind === 'released') {
		$value = details_term_links($value, 'genre');
	} elseif ($kind === 'person') {
		$value = details_term_links($value, 'person');
	} else {
		$value = details_h($value);
	}

	return '<b>' . details_h($title) . ':</b> ' . $value . '<br />';
}

function details_tab_content(array $design, $name)
{
	$name = mb_strtolower((string)$name, 'UTF-8');
	foreach (($design['tabs'] ?? array()) as $tab) {
		$title = mb_strtolower(trim((string)($tab['title'] ?? '')), 'UTF-8');
		$content = trim((string)($tab['content'] ?? ''));
		if ($content !== '' && ($title === $name || mb_strpos($title, $name, 0, 'UTF-8') !== false)) {
			return nl2br(details_h($content));
		}
	}

	return '';
}

function details_torrent_updated_notice(array $details)
{
	$updated = trim((string)($details['torrent_file_updated_at'] ?? ''));
	if ($updated === '' || substr($updated, 0, 10) !== date('Y-m-d')) {
		return '';
	}

	$time = date('H:i', strtotime($updated));
	return '<div class="bx1 justify"><b>Торрент-файл обновлен сегодня в ' . details_h($time) . '</b> Чтобы переподключиться к раздаче, скачайте заново торрент-файл и перехешируйте задание в клиенте. Возможные причины обновления: добавление серии, альбома, выпуска, обновление версии, улучшение качества раздачи.</div>';
}

function details_release_group_block(array $details)
{
	$groups = function_exists('kz_upload_release_groups') ? kz_upload_release_groups() : array();
	$rgroup_id = (int)($details['rgroup'] ?? 0);
	$rgroup_title = $groups[$rgroup_id] ?? '';
	$rbutton = trim((string)($details['rgroup_button'] ?? ''));

	if ($rgroup_title === '' && $rbutton === '') {
		return '';
	}

	if ($rbutton === '' && $rgroup_id > 0) {
		$rbutton = '/pic/rgroup/rg_serial.gif';
	}

	$html = '<li class="tp">Релиз-группа</li><li>';
	if ($rbutton !== '') {
		if (preg_match('#^(https?:)?//|^/#i', $rbutton)) {
			$html .= '<img src="' . details_h($rbutton) . '" align="right" alt="' . details_h($rgroup_title) . '">';
		} else {
			$html .= '<span class="floatright b">' . details_h($rbutton) . '</span>';
		}
	}
	if ($rgroup_title !== '') {
		$html .= '<span class="b">' . details_h($rgroup_title) . '</span>';
	}
	return $html . '<div class="clear"></div></li>';
}

function details_poster(array $row, array $details)
{
	$poster = trim((string)($details['poster_url'] ?? ''));
	if ($poster !== '') {
		return details_h($poster);
	}
	if (!empty($row['image1'])) {
		return 'thumbnail.php?' . details_h($row['image1']);
	}
	return '/pic/default_avatar.gif';
}

function details_user_link($userid, $username, $class = 0, $user = array())
{
	$userid = (int)$userid;
	if ($userid <= 0 || $username === '') {
		return '<i>unknown</i>';
	}

	$user = array_merge($user, array('id' => $userid, 'class' => $class, 'username' => $username));
	$icons = function_exists('get_user_icons') ? get_user_icons($user) : '';

	return '<a href="/userdetails.php?id=' . $userid . '" class="u' . (int)$class . '">' . details_h($username) . '</a>' . $icons;
}

function details_owner(array $row)
{
	$owner = (int)$row['owner'];
	if (!empty($row['owner_username'])) {
		return array(
			'id' => $owner,
			'username' => (string)$row['owner_username'],
			'class' => (int)($row['owner_class'] ?? 0),
			'donor' => $row['owner_donor'] ?? 'no',
			'gender' => $row['owner_gender'] ?? '1',
			'birthday' => $row['owner_birthday'] ?? '',
			'warned' => $row['owner_warned'] ?? 'no',
			'enabled' => $row['owner_enabled'] ?? 'yes',
			'uploaded' => $row['owner_uploaded'] ?? 0,
			'downloaded' => $row['owner_downloaded'] ?? 0,
			'country' => $row['owner_country'] ?? 0,
			'manual_status_keys' => $row['owner_manual_status_keys'] ?? '',
		);
	}

	return array(
		'id' => $owner,
		'username' => (string)($row['username'] ?? ''),
		'class' => (int)($row['owner_class'] ?? 0),
		'donor' => 'no',
		'gender' => '1',
		'birthday' => '',
		'warned' => 'no',
		'enabled' => 'yes',
		'uploaded' => 0,
		'downloaded' => 0,
	);
}

function details_rating_value(array $row)
{
	$num = max(0, (int)($row['numratings'] ?? 0));
	if ($num <= 0) {
		return 0.0;
	}

	return round(((float)$row['ratingsum']) / $num, 1);
}

function details_starbar($id, $rating, $user_rating = 0)
{
	$rating = max(0, min(10, (float)$rating));
	$width = round($rating * 20, 1);
	$class = $user_rating > 0 ? ' class="user"' : '';
	$html = '<div class="starbar"><div class="outer">';
	$html .= '<div style="width:' . $width . 'px" id="starbar"' . $class . '></div>';

	for ($i = 10; $i >= 1; $i--) {
		$html .= '<a onclick="vote(' . (int)$id . ',' . $i . '); return false;" href="#" class="s' . $i . '" title="' . $i . '"></a>';
	}

	$html .= '</div></div>';
	return $html;
}

function details_rating_cache_path($key)
{
	$dir = ROOT_PATH . 'cache';
	if (!is_dir($dir)) {
		@mkdir($dir, 0775, true);
	}

	if (!is_dir($dir) || !is_writable($dir)) {
		return '';
	}

	return $dir . DIRECTORY_SEPARATOR . 'rating_' . preg_replace('/[^a-z0-9_]/i', '_', $key) . '.json';
}

function details_http_get($url)
{
	$url = trim((string)$url);
	if ($url === '' || !preg_match('#^https?://#i', $url)) {
		return '';
	}

	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 3,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124 Safari/537.36',
			CURLOPT_HTTPHEADER => array('Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7'),
		));
		$body = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);

		return ($code >= 200 && $code < 300 && is_string($body)) ? $body : '';
	}

	$context = stream_context_create(array(
		'http' => array(
			'timeout' => 5,
			'header' => "User-Agent: Mozilla/5.0\r\nAccept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7\r\n",
		),
	));
	$body = @file_get_contents($url, false, $context);

	return is_string($body) ? $body : '';
}

function details_parse_rating_number($value)
{
	$value = str_replace(',', '.', trim((string)$value));
	if ($value === '' || !preg_match('/^[0-9]+(?:\.[0-9]+)?$/', $value)) {
		return '';
	}

	$rating = (float)$value;
	if ($rating <= 0 || $rating > 10) {
		return '';
	}

	return number_format($rating, 1);
}

function details_kinopoisk_id($url)
{
	if (preg_match('#kinopoisk\.ru/(?:film|series)/([0-9]+)#i', (string)$url, $m)) {
		return $m[1];
	}
	if (preg_match('#(?:^|[?&])kp=([0-9]+)#i', (string)$url, $m)) {
		return $m[1];
	}
	return '';
}

function details_kinopoisk_ratings($kp_url)
{
	$kp_id = details_kinopoisk_id($kp_url);
	if ($kp_id === '') {
		return array('kp' => '', 'imdb' => '');
	}

	$cache = details_rating_cache_path('kinopoisk_' . $kp_id);
	if ($cache !== '' && is_file($cache) && filemtime($cache) > time() - 43200) {
		$data = json_decode((string)@file_get_contents($cache), true);
		if (is_array($data)) {
			return array(
				'kp' => details_parse_rating_number($data['kp'] ?? ''),
				'imdb' => details_parse_rating_number($data['imdb'] ?? ''),
			);
		}
	}

	$body = details_http_get('https://rating.kinopoisk.ru/' . rawurlencode($kp_id) . '.xml');
	$ratings = array('kp' => '', 'imdb' => '');

	if ($body !== '') {
		if (preg_match('#<kp_rating[^>]*>([^<]+)</kp_rating>#i', $body, $m)) {
			$ratings['kp'] = details_parse_rating_number($m[1]);
		}
		if (preg_match('#<imdb_rating[^>]*>([^<]+)</imdb_rating>#i', $body, $m)) {
			$ratings['imdb'] = details_parse_rating_number($m[1]);
		}
	}

	if ($cache !== '' && ($ratings['kp'] !== '' || $ratings['imdb'] !== '')) {
		@file_put_contents($cache, json_encode($ratings, JSON_UNESCAPED_UNICODE));
	}

	return $ratings;
}

function details_imdb_id($url)
{
	if (preg_match('#/title/(tt[0-9]+)#i', (string)$url, $m)) {
		return $m[1];
	}
	return '';
}

function details_imdb_rating($imdb_url)
{
	$imdb_id = details_imdb_id($imdb_url);
	if ($imdb_id === '') {
		return '';
	}

	$cache = details_rating_cache_path('imdb_' . $imdb_id);
	if ($cache !== '' && is_file($cache) && filemtime($cache) > time() - 43200) {
		$data = json_decode((string)@file_get_contents($cache), true);
		return details_parse_rating_number($data['rating'] ?? '');
	}

	$body = details_http_get('https://www.imdb.com/title/' . rawurlencode($imdb_id) . '/');
	$rating = '';
	if ($body !== '' && preg_match('#"aggregateRating"\s*:\s*\{.*?"ratingValue"\s*:\s*"?([0-9.]+)"?#is', $body, $m)) {
		$rating = details_parse_rating_number($m[1]);
	}

	if ($cache !== '' && $rating !== '') {
		@file_put_contents($cache, json_encode(array('rating' => $rating), JSON_UNESCAPED_UNICODE));
	}

	return $rating;
}

function details_external_ratings(array $design)
{
	$imdb_manual = details_parse_rating_number($design['imdb']['rating'] ?? '');
	$kp_manual = details_parse_rating_number($design['kinopoisk']['rating'] ?? '');
	$kp_remote = details_kinopoisk_ratings($design['kinopoisk']['url'] ?? '');
	$imdb_remote = $kp_remote['imdb'] !== '' ? $kp_remote['imdb'] : details_imdb_rating($design['imdb']['url'] ?? '');

	return array(
		'imdb' => $imdb_remote !== '' ? $imdb_remote : $imdb_manual,
		'kinopoisk' => $kp_remote['kp'] !== '' ? $kp_remote['kp'] : $kp_manual,
	);
}

function details_query_terms(array $row, array $video, $mode)
{
	$seed = array($video['title'] ?? '', $video['original_title'] ?? '', $row['name'] ?? '');

	if ($mode === 'genre') {
		$seed = array($video['genre'] ?? '', $row['keywords'] ?? '');
	} elseif ($mode === 'person') {
		$seed = array($video['director'] ?? '', $video['cast'] ?? '');
	}

	$terms = array();
	foreach ($seed as $text) {
		foreach (preg_split('/[^\p{L}\p{N}]+/u', details_plain($text), -1, PREG_SPLIT_NO_EMPTY) as $word) {
			$word = trim($word);
			if (mb_strlen($word, 'UTF-8') >= 4 && !preg_match('/^[0-9]{4}$/', $word)) {
				$terms[mb_strtolower($word, 'UTF-8')] = $word;
			}
		}
	}

	return array_slice(array_values($terms), 0, 8);
}

function details_related_fetch_rows(array $where)
{
	static $loaded = array();

	$key = implode("\n", $where);
	if (array_key_exists($key, $loaded)) {
		return $loaded[$key];
	}

	$res = sql_query("
		SELECT t.id, t.name, t.comments, t.size, t.seeders, t.leechers, t.ratingsum, t.numratings,
		       u.id AS owner_id, u.username, u.class, u.donor, u.gender, u.birthday, u.warned, u.enabled, u.uploaded, u.downloaded,
		       ums.manual_status_keys
		FROM torrents AS t
		LEFT JOIN users AS u ON u.id = t.owner
		LEFT JOIN (
			SELECT userid, GROUP_CONCAT(status_key) AS manual_status_keys
			FROM user_status_assignments
			GROUP BY userid
		) AS ums ON ums.userid = u.id
		WHERE " . implode(' AND ', $where) . "
		ORDER BY (t.seeders + t.times_completed + t.comments) DESC, t.id DESC
		LIMIT 5
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($item = mysqli_fetch_assoc($res)) {
		$rows[] = $item;
	}

	$loaded[$key] = $rows;
	return $rows;
}

function details_related_rows(array $row, array $video, $mode)
{
	$id = (int)$row['id'];
	$where = array("t.id <> $id", "t.visible = 'yes'", "t.banned = 'no'");

	if ($mode === 'owner') {
		$where[] = 't.owner = ' . (int)$row['owner'];
	} else {
		if (!empty($row['category'])) {
			$where[] = 't.category = ' . (int)$row['category'];
		}

		$terms = details_query_terms($row, $video, $mode);
		$likes = array();
		foreach ($terms as $term) {
			$q = sqlesc('%' . $term . '%', true);
			$likes[] = "(t.name LIKE $q OR t.keywords LIKE $q OR t.description LIKE $q OR t.descr LIKE $q)";
		}
		if ($likes) {
			$where[] = '(' . implode(' OR ', $likes) . ')';
		}
	}

	$rows = details_related_fetch_rows($where);

	if (!$rows && $mode !== 'owner') {
		$fallback_where = array("t.id <> $id", "t.visible = 'yes'", "t.banned = 'no'");
		if (!empty($row['category'])) {
			$fallback_where[] = 't.category = ' . (int)$row['category'];
		}

		$rows = details_related_fetch_rows($fallback_where);
	}

	return $rows;
}

function details_related_table($title, array $rows, $count_url = '')
{
	$html = '<div class="bx2_0"><table class="tables3 w100p">';
	$html .= '<tr class="mn"><td class="w90p">' . details_h($title);
	if ($count_url !== '') {
		$html .= ' <a href="' . details_h($count_url) . '" class="sba">найти еще</a>';
	}
	$html .= '</td><td class="s">Комм.</td><td class="s">Размер</td><td class="s">Сидов</td><td class="s">Пиров</td><td class="sbl">Раздает</td></tr>';

	if (!$rows) {
		$html .= '<tr class="first"><td colspan="6">Подходящих раздач пока нет.</td></tr>';
	} else {
		foreach ($rows as $item) {
			$rating = ((int)$item['numratings'] > 0) ? round((float)$item['ratingsum'] / (int)$item['numratings']) : 0;
			$class = 'r' . max(0, min(10, (int)$rating));
			$user = array(
				'id' => (int)$item['owner_id'],
				'username' => (string)$item['username'],
				'class' => (int)$item['class'],
				'donor' => $item['donor'] ?? 'no',
				'gender' => $item['gender'] ?? '1',
				'birthday' => $item['birthday'] ?? '',
				'warned' => $item['warned'] ?? 'no',
				'enabled' => $item['enabled'] ?? 'yes',
				'uploaded' => $item['uploaded'] ?? 0,
				'downloaded' => $item['downloaded'] ?? 0,
				'manual_status_keys' => $item['manual_status_keys'] ?? '',
			);

			$html .= "<tr class='first'>";
			$html .= "<td><a class='" . $class . "' href='/details.php?id=" . (int)$item['id'] . "'>" . details_h($item['name']) . "</a></td>";
			$html .= "<td class='s'>" . (int)$item['comments'] . "</td>";
			$html .= "<td class='s'>" . details_h(mksize($item['size'])) . "</td>";
			$html .= "<td class='s green b'>" . (int)$item['seeders'] . "</td>";
			$html .= "<td class='s red b'>" . (int)$item['leechers'] . "</td>";
			$html .= "<td class='sbl'>" . details_user_link((int)$item['owner_id'], (string)$item['username'], (int)$item['class'], $user) . "</td>";
			$html .= '</tr>';
		}
	}

	$html .= '</table></div>';
	return $html;
}

function details_file_list($id)
{
	$id = (int)$id;
	$res = sql_query("SELECT filename, size FROM files WHERE torrent = $id ORDER BY id ASC") or sqlerr(__FILE__, __LINE__);
	$rows = array();

	while ($file = mysqli_fetch_assoc($res)) {
		$rows[] = '<tr><td>' . details_h($file['filename']) . '</td><td class="sbr">' . details_h(mksize($file['size'])) . '</td></tr>';
	}

	if (!$rows) {
		return '';
	}

	return '<table class="tables3 w100p"><tr class="mn"><td>Файл</td><td class="sbr">Размер</td></tr>' . implode('', $rows) . '</table>';
}

function details_screens_html(array $row, array $design)
{
	$out = array();
	$screens = trim((string)($design['screens'] ?? ''));

	if ($screens !== '') {
		foreach (preg_split('#\r\n|\r|\n#', $screens) as $line) {
			$line = trim($line);
			if ($line !== '' && preg_match('#^(https?:)?//#i', $line)) {
				$out[] = '<a href="' . details_h($line) . '" rel="lightbox"><img src="' . details_h($line) . '" class="p200" alt=""></a>';
			}
		}
	}

	for ($i = 2; $i <= 5; $i++) {
		if (!empty($row['image' . $i])) {
			$img = details_h($row['image' . $i]);
			$out[] = '<a href="torrents/images/' . $img . '" rel="lightbox"><img border="0" src="screenshot.php?' . $img . '" alt=""></a>';
		}
	}

	return $out ? implode(' ', $out) : 'Скриншоты не добавлены.';
}

function details_comment_format($text)
{
	$text = html_entity_decode((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	$text = preg_replace('#\[(color|size|font|family|left|right|center|justify|hide|spoiler|code|php)[^\]]*\](.*?)\[/\1\]#isu', '$2', $text);
	$text = htmlspecialchars_uni($text);

	$text = preg_replace('#\[b\](.*?)\[/b\]#isu', '<b>$1</b>', $text);
	$text = preg_replace('#\[i\](.*?)\[/i\]#isu', '<i>$1</i>', $text);
	$text = preg_replace('#\[u\](.*?)\[/u\]#isu', '<u>$1</u>', $text);
	$text = preg_replace('#\[url\](https?://[^\s\[]+)\[/url\]#isu', '<a href="$1" class="sba" target="_blank">$1</a>', $text);
	$text = preg_replace('#\[url=(https?://[^\]\s]+)\](.*?)\[/url\]#isu', '<a href="$1" class="sba" target="_blank">$2</a>', $text);
	$text = preg_replace('#\[img\](https?://[^\s\[]+)\[/img\]#isu', '<img src="$1" class="p200" alt="">', $text);
	$text = preg_replace('#\[quote=([^\]]+)\](.*?)\[/quote\]#isu', '<fieldset class="ft_cmt"><legend><span class="f_um b">$1</span></legend>$2</fieldset>', $text);
	$text = preg_replace('#\[quote\](.*?)\[/quote\]#isu', '<fieldset class="ft_cmt"><legend><span class="f_um b">Цитата</span></legend>$1</fieldset>', $text);

	$text = preg_replace_callback('#(?<![">])(https?://[^\s<]+)#iu', function ($m) {
		$url = rtrim($m[1], '.,!?');
		$tail = substr($m[1], strlen($url));
		return '<a href="' . details_h($url) . '" class="sba" target="_blank">' . details_h($url) . '</a>' . details_h($tail);
	}, $text);

	return nl2br($text);
}

function details_paginator($base_url, $page, $pages)
{
	$pages = max(1, (int)$pages);
	$page = max(0, min((int)$page, $pages - 1));
	if ($pages <= 1) {
		return '';
	}

	$html = '<div class="paginator" style="margin: 0px; float: right;"><ul>';
	$window = array_unique(array_merge(range(0, min(4, $pages - 1)), range(max(0, $page - 1), min($pages - 1, $page + 1)), array($pages - 1)));
	sort($window);
	$prev = -1;

	foreach ($window as $p) {
		if ($prev >= 0 && $p > $prev + 1) {
			$html .= '<li class="dots">...</li>';
		}
		$class = $p === $page ? ' class="current"' : '';
		$html .= '<li' . $class . '><a href="' . details_h($base_url) . 'page=' . $p . '#startcomments">' . ($p + 1) . '</a></li>';
		$prev = $p;
	}

	if ($page + 1 < $pages) {
		$html .= '<li><a rel="next" href="' . details_h($base_url) . 'page=' . ($page + 1) . '#startcomments">Вперед</a></li>';
	}

	$html .= '</ul></div><div class="clr"></div>';
	return $html;
}

function details_comments_html($torrentid, $comment_count, $page = 0)
{
	global $CURUSER;

	$perpage = 20;
	$pages = max(1, (int)ceil(max(0, (int)$comment_count) / $perpage));
	$page = max(0, min((int)$page, $pages - 1));
	$offset = $page * $perpage;
	$pager = details_paginator('/details.php?id=' . (int)$torrentid . '&amp;', $page, $pages);

	$res = sql_query("
		SELECT c.id, c.ip, c.text, c.user, c.added, c.editedby, c.editedat,
		       u.username, u.class, u.avatar, u.country, u.donor, u.gender, u.birthday, u.warned, u.enabled, u.uploaded, u.downloaded,
		       ums.manual_status_keys,
		       e.username AS editedbyname
		FROM comments AS c
		LEFT JOIN users AS u ON u.id = c.user
		LEFT JOIN users AS e ON e.id = c.editedby
		LEFT JOIN (
			SELECT userid, GROUP_CONCAT(status_key) AS manual_status_keys
			FROM user_status_assignments
			GROUP BY userid
		) AS ums ON ums.userid = u.id
		WHERE c.torrent = " . (int)$torrentid . "
		ORDER BY c.id DESC
		LIMIT $offset, $perpage
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}

	$html = '<div class="bx2_0" id="startcomments">';
	$html .= '<div class="pad5x5"><span class="bulet"></span><b>Комментарии ( <a onclick="$(\'#cmtcomm\').toggle(); return false;" href="#" class="sba" id="cmfoc">Комментировать</a> )</b>' . $pager . '</div>';

	if ($CURUSER) {
		$html .= '<form id="cmt" method="post" action="/comment.php?action=add" onsubmit="return cmt_submit();">';
		$html .= '<div class="pad10x10" id="cmtcomm">';
		$html .= '<div class="cmet_e_but"><ul>';
		foreach (array('b', 'i', 'u', 'quote', 'url', 'img') as $button) {
			$html .= '<li><input class="buttonS" type="button" value="' . $button . '" onclick="InsertCode(\'text\',\'' . $button . '\')"></li>';
		}
		$html .= '</ul><div class="clr"></div></div>';
		$html .= '<div class="cmet_e_inp"><textarea id="text" name="text" cols="70" rows="5" class="w98p"></textarea></div>';
		$html .= '<input type="hidden" name="tid" value="' . (int)$torrentid . '">';
		$html .= '<input type="submit" value="Добавить Комментарий" class="buts">';
		$html .= '</div></form>';
	}

	foreach ($rows as $row) {
		$avatar = trim((string)($row['avatar'] ?? ''));
		$avatar_html = '';
		if ($avatar !== '' && (!$CURUSER || ($CURUSER['avatars'] ?? 'yes') === 'yes')) {
			$avatar_html = '<img class="cmet_ava" src="' . details_h($avatar) . '" alt="">';
		}

		$country = (int)($row['country'] ?? 0);
		$flag = $country > 0 ? "<img src='/pic/emty.gif' class='i2 c$country'/>" : '';
		$user = details_user_link((int)$row['user'], (string)($row['username'] ?? ''), (int)($row['class'] ?? 0), $row);
		$username_js = json_encode((string)($row['username'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$reply = $CURUSER ? ' | <a class="sba" onclick="return c_replay(' . (int)$row['id'] . ',' . details_h($username_js) . ');" href="#">Ответить</a>' : '';
		$edit = ($CURUSER && ((int)$row['user'] === (int)$CURUSER['id'] || get_user_class() >= UC_MODERATOR))
			? ' | <a class="sba" href="/comment.php?action=edit&amp;cid=' . (int)$row['id'] . '">Изменить</a>'
			: '';
		$delete = (get_user_class() >= UC_MODERATOR)
			? ' | <a class="sba" href="/comment.php?action=delete&amp;cid=' . (int)$row['id'] . '">Удалить</a>'
			: '';

		$text = details_comment_format($row['text']);
		if (!empty($row['editedby'])) {
			$text .= '<br><span class="small">Изменено: ' . details_h($row['editedat']) . ' пользователем ' . details_h($row['editedbyname']) . '</span>';
		}

		$html .= '<div class="mn2 cmet_bx">' . $avatar_html . '<div class="cmet_sbx"><dl class="mn"><dt>' . $flag . $user . '</dt><dd>' . details_h($row['added']) . $reply . $edit . $delete . '</dd></dl>';
		$html .= '<div class="tx" id="cm' . (int)$row['id'] . '">' . $text . '</div></div><div class="clr"></div></div>';
	}

	if (!$rows) {
		$html .= '<div class="mn2 cmet_bx"><div class="cmet_sbx"><div class="tx">Комментариев пока нет.</div></div><div class="clr"></div></div>';
	}

	$html .= '<div class="pad5x5 mn2">' . $pager . '</div>';
	$html .= '</div>';
	$html .= '<script type="text/javascript">
function InsertCode(field, tag) {
	var el = document.getElementById(field);
	if (!el) return false;
	var start = el.selectionStart || 0;
	var end = el.selectionEnd || 0;
	var value = el.value;
	var selected = value.substring(start, end);
	var open = "[" + tag + "]";
	var close = "[/" + tag + "]";
	el.value = value.substring(0, start) + open + selected + close + value.substring(end);
	el.focus();
	return false;
}
function c_replay(id, username) {
	var text = $("#cm" + id).text().replace(/\s+/g, " ").trim();
	var el = document.getElementById("text");
	if (!el) return false;
	el.value += (el.value ? "\n\n" : "") + "[quote=" + username + "]" + text + "[/quote]\n";
	$("#cmtcomm").show();
	el.focus();
	return false;
}
function cmt_submit() {
	var el = document.getElementById("text");
	return el && el.value.replace(/\s+/g, "") !== "";
}
</script>';

	return $html;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!is_valid_id($id)) {
	stderr($tracker_lang['error'], $tracker_lang['invalid_id']);
}

$has_torrent_details = kz_upload_table_exists('torrent_details');
$details_select = '';
$details_join = '';
if ($has_torrent_details) {
	$details_select = ",
	       tdet.tid AS tdet_exists, tdet.release_kind AS tdet_release_kind, tdet.poster_url AS tdet_poster_url,
	       tdet.rgroup AS tdet_rgroup, tdet.rgroup_button AS tdet_rgroup_button,
	       tdet.torrent_file_updated_at AS tdet_torrent_file_updated_at, tdet.form_mode AS tdet_form_mode,
	       tdet.section_modes AS tdet_section_modes, tdet.data AS tdet_data";
	$details_join = "LEFT JOIN torrent_details AS tdet ON tdet.tid = t.id";
}

$res = sql_query("
	SELECT t.*, td.descr_hash, td.descr_parsed, c.name AS cat_name, c.image AS cat_pic,
	       u.username AS owner_username, u.class AS owner_class, u.donor AS owner_donor, u.gender AS owner_gender,
	       u.birthday AS owner_birthday, u.warned AS owner_warned, u.enabled AS owner_enabled,
	       u.uploaded AS owner_uploaded, u.downloaded AS owner_downloaded, u.country AS owner_country,
	       ums.manual_status_keys AS owner_manual_status_keys
	       $details_select
	FROM torrents AS t
	LEFT JOIN categories AS c ON c.id = t.category
	LEFT JOIN users AS u ON u.id = t.owner
	LEFT JOIN torrents_descr AS td ON td.tid = t.id
	$details_join
	LEFT JOIN (
		SELECT userid, GROUP_CONCAT(status_key) AS manual_status_keys
		FROM user_status_assignments
		GROUP BY userid
	) AS ums ON ums.userid = u.id
	WHERE t.id = $id
	LIMIT 1
") or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_assoc($res);

if (!$row) {
	stderr($tracker_lang['error'], $tracker_lang['no_torrent_with_such_id']);
}

kz_mt_sync_local_tracker($id, (int)$row['seeders'], (int)$row['leechers'], (int)$row['times_completed']);
$total_seeders = (int)$row['seeders'] + (int)($row['remote_seeders'] ?? 0);
$total_leechers = (int)$row['leechers'] + (int)($row['remote_leechers'] ?? 0);

$moderator = get_user_class() >= UC_MODERATOR;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mt_force_update'])) {
	if (!$moderator) {
		stderr($tracker_lang['error'], $tracker_lang['access_denied'] ?? 'Доступ запрещен');
	}
	$mt_result = kz_mt_update_torrent_trackers($id);
	header('Location: details.php?id=' . $id . '&mtupdated=1&mts=' . (int)$mt_result['success'] . '&mte=' . (int)$mt_result['errors']);
	exit;
}
if ($row['banned'] === 'yes' && !$moderator) {
	stderr($tracker_lang['error'], $tracker_lang['no_torrent_with_such_id']);
}

if ($CURUSER) {
	sql_query("INSERT IGNORE INTO readtorrents (userid, torrentid) VALUES (" . (int)$CURUSER['id'] . ", $id)");
}

if (isset($_GET['hit'])) {
	sql_query("UPDATE torrents SET views = views + 1 WHERE id = $id");
	header("Location: details.php?id=$id");
	exit;
}

$owned = $moderator || ($CURUSER && (int)$CURUSER['id'] === (int)$row['owner']);
$torrent_details = details_upload_details_from_row($id, $row);
list($video, $design) = details_data($torrent_details);
$owner = details_owner($row);
$rating = details_rating_value($row);
$user_rating = 0;

if ($CURUSER) {
	$rated = mysqli_fetch_assoc(sql_query("SELECT rating FROM ratings WHERE torrent = $id AND user = " . (int)$CURUSER['id'] . " ORDER BY id DESC LIMIT 1"));
	$user_rating = $rated ? (int)$rated['rating'] : 0;
}

$title = trim((string)($video['title'] ?? ''));
if ($title === '') {
	$title = details_guess_title($row['name']);
}
$original = trim((string)($video['original_title'] ?? ''));
$about = trim((string)($video['about'] ?? ''));
if ($about === '' && !empty($row['descr'])) {
	$about = details_plain($row['descr']);
}

$poster = details_poster($row, $torrent_details);
$cat_img = !empty($row['cat_pic']) ? '<img src="/pic/cat/' . details_h($row['cat_pic']) . '" class="cat_img_r" alt="">' : '';
$free = (string)($row['free'] ?? 'no');
$download_note = '';
if (!empty($_GET['mtupdated'])) {
	$download_note = '<b class="green">Мультитрекер обновлен.</b> Успешно: ' . (int)($_GET['mts'] ?? 0) . ', ошибок: ' . (int)($_GET['mte'] ?? 0) . '.';
}

if ($download_note === '' && $free === 'yes') {
	$download_note = '<b class="r1">Золотая раздача</b> Объем скачанного не учитывается, а отданное засчитывается полностью. На золотых раздачах появляется дополнительная возможность поднять свой рейтинг.';
} elseif ($download_note === '' && $free === 'silver') {
	$download_note = '<b class="r2">Серебряная раздача</b> Объем скачанного учитывается только на 50%, а отданное засчитывается полностью. На серебряных раздачах появляется дополнительная возможность поднять свой рейтинг.';
}

$similar = details_related_rows($row, $video, 'similar');
$by_genre = details_related_rows($row, $video, 'genre');
$by_person = details_related_rows($row, $video, 'person');
$by_owner = details_related_rows($row, $video, 'owner');
$external_ratings = details_external_ratings($design);
$comment_page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
$book_hash = $CURUSER ? '&amp;hash4u=' . details_h($CURUSER['hash4u'] ?? ($CURUSER['logout_hash'] ?? '')) : '';

$tech_tab = details_line('Качество', $video['quality'] ?? '') .
	details_line('Видео', $video['video'] ?? '') .
	details_line('Аудио', $video['audio'] ?? '') .
	details_line('Размер', $video['size'] ?? '') .
	details_line('Продолжительность', $video['duration'] ?? '') .
	details_line('Перевод', $video['translation'] ?? '') .
	details_line('Язык', $video['language'] ?? '') .
	details_line('Субтитры', $video['subtitles'] ?? '');
if ($tech_tab === '') {
	$tech_tab = details_line('Размер', mksize($row['size'])) . details_line('Файлов', $row['numfiles']);
}

$release_tab = details_tab_content($design, 'релиз');
if ($release_tab === '') {
	$release_tab = nl2br(details_h(trim((string)($design['notes'] ?? ''))));
}
if ($release_tab === '') {
	$release_tab = 'Описание релиза пока не добавлено.';
}

$search_title = $original !== '' ? $original : $title;
$title_class = $free === 'yes' ? 'r1' : ($free === 'silver' ? 'r2' : 'r0');
$hide_right_blocks = true;
stdhead($tracker_lang['torrent_details'] . ' "' . htmlspecialchars_decode($row['name']) . '"');
?>
<div class="mn_wrap">
	<div style="padding:0 5px 7px 0;"><h1><a href="/details.php?id=<?= $id ?>" class="<?= $title_class ?>"><?= details_h($row['name']) ?></a></h1></div>
	<div class="mn1_menu"><ul class="men w200">
		<li class="img"><a href="/details.php?id=<?= $id ?>" title="<?= details_h($row['name']) ?>"><img src="<?= $poster ?>" class="p200" alt=""></a></li>
		<li class="tp">Меню раздачи</li>
		<li><span class="bulet"></span><a href="/browse.php?s=<?= rawurlencode($search_title) ?>" target="_blank">Подобные раздачи</a></li>
		<li><span class="bulet"></span><a href="/bookmarks.php?torrent=<?= $id . $book_hash ?>" onclick="return mess_out('Добавить раздачу в закладки ?')">Добавить в закладки</a></li>
		<?php if ($owned) { ?><li><span class="bulet"></span><a href="/edit.php?id=<?= $id ?>">Редактировать</a></li><?php } ?>
		<li class="tp">Участники</li>
		<li><span class="bulet"></span><a href="#"><?= $total_seeders ? 'Раздают' : 'Раздают' ?><span class="floatright"><?= $total_seeders ?></span></a></li>
		<li><span class="bulet"></span><a href="#">Скачивают<span class="floatright"><?= $total_leechers ?></span></a></li>
		<li><span class="bulet"></span><a href="#">Скачали<span class="floatright"><?= (int)$row['times_completed'] ?></span></a></li>
		<li><span class="bulet"></span><a href="#tabs">Список файлов<span class="floatright"><?= (int)$row['numfiles'] ?></span></a></li>
		<li><span class="bulet"></span><a href="#startcomments">Комментариев<span class="floatright"><?= (int)$row['comments'] ?></span></a></li>
		<li class="tp">Залил раздачу</li>
		<li><span class="bulet"></span><?= details_user_link((int)$owner['id'], (string)$owner['username'], (int)$owner['class'], $owner) ?></li>
		<?php
		$watch_rows = array();
		if (!empty($design['watch']) && is_array($design['watch'])) {
			foreach ($design['watch'] as $item) {
				$wtitle = trim((string)($item['title'] ?? ''));
				$wurl = trim((string)($item['url'] ?? ''));
				if ($wtitle !== '' && $wurl !== '') {
					$watch_rows[] = '<li><span class="bulet"></span><a href="' . details_h($wurl) . '" target="_blank">' . details_h($wtitle) . '</a></li>';
				}
			}
		}
		if ($watch_rows) {
			echo '<li class="tp">Ознакомление</li>' . implode('', $watch_rows);
		}
		?>
		<?= details_release_group_block($torrent_details) ?>
		<li class="tp">Голосование</li>
		<?php if (!empty($design['imdb']['url'])) { ?><li><span class="bulet"></span><a href="<?= details_h($design['imdb']['url']) ?>" target="_blank">IMDb<span class="floatright"><?= details_h($external_ratings['imdb']) ?></span></a></li><?php } ?>
		<?php if (!empty($design['kinopoisk']['url'])) { ?><li><span class="bulet"></span><a href="<?= details_h($design['kinopoisk']['url']) ?>" target="_blank">Кинопоиск<span class="floatright"><?= details_h($external_ratings['kinopoisk']) ?></span></a></li><?php } ?>
		<li class="img"><?= details_starbar($id, $rating, $user_rating) ?></li>
		<li class="b"><span class="bulet"></span><span itemscope itemtype="https://schema.org/Product"><meta content="<?= details_h($row['name']) ?>" itemprop="name">Оценка<span class="floatright" itemtype="https://schema.org/AggregateRating" itemscope itemprop="aggregateRating"><span id="rating_value" itemprop="ratingValue"><?= number_format($rating, 1) ?></span> из <span itemprop="bestRating">10</span><meta itemprop="ratingCount" content="<?= (int)$row['numratings'] ?>"></span></span></li>
		<li class="b"><span class="bulet"></span>Голосов<span class="floatright" id="votes_count"><?= (int)$row['numratings'] ?></span></li>
		<li><div class="justify" id="ratio_get">Просим Вас оценивать материал после ознакомления с ним. <?php if ($CURUSER) { ?>Ваши оценки вы можете просмотреть <a href="/uservotes.php?id=<?= (int)$CURUSER['id'] ?>" class="sba">здесь</a><?php } ?></div></li>
		<li class="tp">Опубликовать ссылку</li>
		<li><div class="share b"><a class="vkontakte" href="https://vk.com/share.php?url=<?= rawurlencode($DEFAULTBASEURL . '/details.php?id=' . $id) ?>" title="Опубликовать ссылку во ВКонтакте" onclick="window.open(this.href, 'Опубликовать ссылку во Вконтакте', 'width=800,height=300'); return false"></a><a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($DEFAULTBASEURL . '/details.php?id=' . $id) ?>" title="Опубликовать ссылку в Facebook" onclick="window.open(this.href, 'Опубликовать ссылку в Facebook', 'width=640,height=436,toolbar=0,status=0'); return false"></a><a class="googleplus" href="https://plus.google.com/share?url=<?= rawurlencode($DEFAULTBASEURL . '/details.php?id=' . $id) ?>" title="Опубликовать ссылку в Google Plus" onclick="window.open(this.href, 'Опубликовать ссылку в Google Plus', 'width=800,height=300'); return false"></a><a class="twitter" href="https://twitter.com/intent/tweet?text=<?= rawurlencode($row['name'] . ' ' . $DEFAULTBASEURL . '/details.php?id=' . $id) ?>" title="Опубликовать ссылку в Twitter" onclick="window.open(this.href, 'Опубликовать ссылку в Twitter', 'width=800,height=300'); return false" target="_blank"></a></div><div class="clear"></div></li>
		<li class="tp">Характеристика</li>
		<li>Вес<span class="floatright green n"><?= details_h(mksize($row['size'])) ?> (<?= number_format((float)$row['size'], 0, '.', ',') ?>)</span></li>
		<li>Залит<span class="floatright green n"><?= details_h($row['added']) ?></span></li>
	</ul></div>
	<div class="mn1_content">
		<table class="w100p" style="margin: 0 0 5px 0;"><tr><td style="width: 210px" class="nw"><a href="/download.php?id=<?= $id ?>" title="Скачать <?= details_h($row['name']) ?>"><img src="/pic/dwn_torrent.gif" height="25" class="block w200" alt=""></a><td><?= $download_note ?></table>
		<?= details_torrent_updated_notice($torrent_details) ?>
		<div class="bx1 justify"><h2><?= $cat_img ?>
			<?= details_line('Название', $title) ?>
			<?= details_line('Оригинальное название', $original) ?>
			<?= details_line('Год выпуска', $video['year'] ?? '') ?>
			<?= details_line('Жанр', $video['genre'] ?? '', 'genre') ?>
			<?= details_line('Выпущено', $video['released'] ?? '', 'released') ?>
			<?= details_line('Режиссер', $video['director'] ?? '', 'person') ?>
			<?= details_line('В ролях', $video['cast'] ?? '', 'person') ?>
		</h2></div>
		<?php if ($about !== '') { ?><div class="bx1 justify"><p><b>О фильме:</b> <?= nl2br(details_h($about)) ?></p></div><?php } ?>
		<?= kz_mt_render_details_block($id) ?>
		<div class="bx1"><div class="pad0x0x5x0"><ul class="lis"><li id="tbch100" class="mn"><a onclick="showtab(100); return false;" href="#">Техданные</a></li><li id="tbch0"><a onclick="showtab(0); return false;" href="#">Релиз</a></li><li id="tbch1"><a onclick="showtab(1); return false;" href="#">Скриншоты</a></li></ul></div><div class="clr"></div><div class="justify mn2 pad5x5" id="tabs"></div></div>
		<div class="bx1"><div class="pad0x0x5x0"><ul class="lis"><li id="tbch2100" class="mn"><a onclick="showtab2(100); return false;" href="#">Подобные</a></li><li id="tbch2101"><a onclick="showtab2(101); return false;" href="#">Топ по жанрам</a></li><li id="tbch2102"><a onclick="showtab2(102); return false;" href="#">Топ по персонам</a></li><li id="tbch2103"><a onclick="showtab2(103); return false;" href="#">Топ раздающего</a></li></ul></div><div class="clr"></div><div class="justify mn2" id="tabs2"></div></div>
		<?= details_comments_html($id, (int)$row['comments'], $comment_page) ?>
	</div><div class="clear"></div>
</div>
<script type="text/javascript">
var tabsData = {
	100: <?= json_encode($tech_tab, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
	0: <?= json_encode($release_tab, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
	1: <?= json_encode(details_screens_html($row, $design), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
var tabs2Data = {
	100: <?= json_encode(details_related_table('Подобные раздачи', $similar, '/browse.php?s=' . rawurlencode($search_title) . '&c=' . (int)$row['category']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
	101: <?= json_encode(details_related_table('Топ по жанрам', $by_genre), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
	102: <?= json_encode(details_related_table('Топ по персонам', $by_person), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
	103: <?= json_encode(details_related_table('Топ раздающего', $by_owner), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
function showtab(id) {
	$('#tabs').html(tabsData[id] || '');
	$('#tbch100,#tbch0,#tbch1').removeClass('mn');
	$('#tbch' + id).addClass('mn');
}
function showtab2(id) {
	$('#tabs2').html(tabs2Data[id] || '');
	$('#tbch2100,#tbch2101,#tbch2102,#tbch2103').removeClass('mn');
	$('#tbch2' + id).addClass('mn');
}
function vote(id, rating) {
	$.post('/takerate.php', {id: id, rating: rating}, function (html) {
		$('#ratio_get').html(html);
		window.setTimeout(function () { window.location.reload(); }, 700);
	});
}
showtab(100);
showtab2(100);
</script>
<?php
stdfoot();

?>
