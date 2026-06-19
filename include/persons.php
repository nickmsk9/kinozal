<?php

if (!defined('IN_TRACKER')) {
	die('Direct access denied.');
}

function persons_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function persons_ensure_schema()
{
	static $checked = false;

	if ($checked) {
		return;
	}
	$checked = true;

	if (!defined('KZ_AUTO_MIGRATIONS') || KZ_AUTO_MIGRATIONS !== true) {
		return;
	}

	sql_query("
		CREATE TABLE IF NOT EXISTS persons (
			id int(10) unsigned NOT NULL auto_increment,
			name varchar(160) NOT NULL default '',
			original_name varchar(160) NOT NULL default '',
			type tinyint(3) unsigned NOT NULL default '11',
			gender tinyint(1) unsigned NOT NULL default '0',
			poster_url text NOT NULL,
			birth_date date NULL DEFAULT NULL,
			birth_text varchar(120) NOT NULL default '',
			birth_place varchar(255) NOT NULL default '',
			career varchar(255) NOT NULL default '',
			genre varchar(255) NOT NULL default '',
			height varchar(40) NOT NULL default '',
			spouse varchar(255) NOT NULL default '',
			biography mediumtext NOT NULL,
			trivia mediumtext NOT NULL,
			filmography mediumtext NOT NULL,
			voice mediumtext NOT NULL,
			producer mediumtext NOT NULL,
			director mediumtext NOT NULL,
			writer mediumtext NOT NULL,
			awards mediumtext NOT NULL,
			links mediumtext NOT NULL,
			source_url varchar(255) NOT NULL default '',
			created_by int(10) unsigned NOT NULL default '0',
			created_at datetime NULL DEFAULT NULL,
			updated_by int(10) unsigned NOT NULL default '0',
			updated_at datetime NULL DEFAULT NULL,
			PRIMARY KEY (id),
			KEY name (name),
			KEY original_name (original_name),
			KEY birth_date (birth_date),
			KEY type (type),
			KEY updated_at (updated_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS person_photos (
			id int(10) unsigned NOT NULL auto_increment,
			person_id int(10) unsigned NOT NULL default '0',
			image_url text NOT NULL,
			sort int(10) unsigned NOT NULL default '0',
			PRIMARY KEY (id),
			KEY person_id (person_id),
			KEY sort (sort)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);
}

function persons_cp1251_urlencode($value)
{
	$value = (string)$value;
	if (function_exists('iconv')) {
		$converted = @iconv('UTF-8', 'Windows-1251//TRANSLIT', $value);
		if ($converted !== false) {
			return str_replace('%20', '+', rawurlencode($converted));
		}
	}
	return str_replace('%20', '+', rawurlencode($value));
}

function persons_request_text($value)
{
	$value = trim((string)$value);
	if ($value === '') {
		return '';
	}

	if (function_exists('mb_check_encoding') && function_exists('iconv') && !mb_check_encoding($value, 'UTF-8')) {
		$converted = @iconv('Windows-1251', 'UTF-8//IGNORE', $value);
		if ($converted !== false) {
			return trim($converted);
		}
	}

	return $value;
}

function persons_url($name, $pid = 0, array $extra = array())
{
	$url = '/persons.php?s=' . persons_cp1251_urlencode($name);
	if ((int)$pid > 0) {
		$url .= '&amp;pid=' . (int)$pid;
	}
	foreach ($extra as $key => $value) {
		if ($value !== '' && $value !== null) {
			$url .= '&amp;' . rawurlencode((string)$key) . '=' . rawurlencode((string)$value);
		}
	}
	return $url;
}

function persons_find($pid = 0, $name = '')
{
	persons_ensure_schema();

	$pid = (int)$pid;
	if ($pid > 0) {
		$res = sql_query("SELECT * FROM persons WHERE id = $pid LIMIT 1") or sqlerr(__FILE__, __LINE__);
		$row = mysqli_fetch_assoc($res);
		if ($row) {
			return $row;
		}
	}

	$name = trim((string)$name);
	if ($name !== '') {
		$res = sql_query("SELECT * FROM persons WHERE name = " . sqlesc($name) . " OR original_name = " . sqlesc($name) . " ORDER BY id ASC LIMIT 1") or sqlerr(__FILE__, __LINE__);
		$row = mysqli_fetch_assoc($res);
		if ($row) {
			return $row;
		}

		$res = sql_query("SELECT * FROM persons WHERE name LIKE " . sqlesc('%' . $name . '%', true) . " OR original_name LIKE " . sqlesc('%' . $name . '%', true) . " ORDER BY id ASC LIMIT 1") or sqlerr(__FILE__, __LINE__);
		$row = mysqli_fetch_assoc($res);
		if ($row) {
			return $row;
		}
	}

	return null;
}

function persons_find_id_by_name($name)
{
	$person = persons_find(0, $name);
	return $person ? (int)$person['id'] : 0;
}

function persons_photos($person_id, $limit = 0)
{
	persons_ensure_schema();

	$person_id = (int)$person_id;
	if ($person_id <= 0) {
		return array();
	}

	$sql_limit = $limit > 0 ? ' LIMIT ' . (int)$limit : '';
	$res = sql_query("SELECT * FROM person_photos WHERE person_id = $person_id ORDER BY sort ASC, id ASC$sql_limit") or sqlerr(__FILE__, __LINE__);
	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function persons_save_photos($person_id, $photos_text)
{
	$person_id = (int)$person_id;
	sql_query("DELETE FROM person_photos WHERE person_id = $person_id") or sqlerr(__FILE__, __LINE__);

	$sort = 0;
	foreach (preg_split('#\r\n|\r|\n#', (string)$photos_text) as $line) {
		$url = trim($line);
		if ($url === '' || !preg_match('#^https?://#i', $url)) {
			continue;
		}
		sql_query("INSERT INTO person_photos (person_id, image_url, sort) VALUES ($person_id, " . sqlesc($url) . ", $sort)") or sqlerr(__FILE__, __LINE__);
		$sort++;
	}
}

function persons_photo_text($person_id)
{
	$urls = array();
	foreach (persons_photos($person_id) as $photo) {
		$urls[] = $photo['image_url'];
	}
	return implode("\n", $urls);
}

function persons_can_edit($person = null)
{
	global $CURUSER;
	if (!$CURUSER) {
		return false;
	}
	if (get_user_class() >= UC_MODERATOR) {
		return true;
	}
	if (!$person || empty($person['id'])) {
		return true;
	}
	return (int)$person['created_by'] === (int)$CURUSER['id'];
}

function persons_user_link($userid, $username, $class = 0, array $row = array())
{
	$userid = (int)$userid;
	if ($userid <= 0 || $username === '') {
		return '<i>unknown</i>';
	}
	$icons = function_exists('get_user_icons') ? get_user_icons(array_merge($row, array('id' => $userid, 'class' => $class))) : '';
	return '<a href="/userdetails.php?id=' . $userid . '" class="u' . (int)$class . '">' . persons_h($username) . '</a>' . $icons;
}

function persons_date($value)
{
	$value = (string)$value;
	if ($value === '' || $value === '0000-00-00') {
		return '';
	}
	$ts = strtotime($value);
	return $ts ? date('d.m.Y', $ts) : $value;
}

function persons_text($text)
{
	$text = trim((string)$text);
	if ($text === '') {
		return '';
	}
	$text = htmlspecialchars_uni($text);
	$text = preg_replace('#(https?://[^\s<]+)#iu', '<a href="$1" target="_blank" class="sba">$1</a>', $text);
	return nl2br($text);
}

function persons_links_html($links)
{
	$out = array();
	foreach (preg_split('#\r\n|\r|\n#', (string)$links) as $line) {
		$line = trim($line);
		if ($line === '') {
			continue;
		}
		$title = $line;
		$url = $line;
		if (strpos($line, '|') !== false) {
			list($title, $url) = array_map('trim', explode('|', $line, 2));
		}
		if (preg_match('#^https?://#i', $url)) {
			$out[] = '<a href="' . persons_h($url) . '" target="_blank" class="sbab">' . persons_h($title) . '</a>';
		}
	}
	return implode('<br />', $out);
}

function persons_torrent_like_conditions(array $person)
{
	$terms = array(trim((string)($person['name'] ?? '')));
	if (!empty($person['original_name'])) {
		$terms[] = trim((string)$person['original_name']);
	}

	$likes = array();
	foreach (array_unique(array_filter($terms)) as $term) {
		$q = sqlesc('%' . $term . '%', true);
		$likes[] = "(t.name LIKE $q OR t.keywords LIKE $q OR t.description LIKE $q OR t.descr LIKE $q OR td.data LIKE $q)";
	}

	return $likes ? '(' . implode(' OR ', $likes) . ')' : '1=0';
}

function persons_torrent_count(array $person)
{
	static $cache = array();

	$where = persons_torrent_like_conditions($person);
	$cache_key = md5($where);
	if (array_key_exists($cache_key, $cache)) {
		return $cache[$cache_key];
	}

	$res = sql_query("
		SELECT COUNT(*) AS c
		FROM torrents AS t
		LEFT JOIN torrent_details AS td ON td.tid = t.id
		WHERE t.visible = 'yes' AND t.banned = 'no' AND $where
	") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);
	$cache[$cache_key] = (int)($row['c'] ?? 0);
	return $cache[$cache_key];
}

function persons_torrents(array $person, $sort = 'date', $offset = 0, $limit = 50)
{
	$where = persons_torrent_like_conditions($person);
	$order = $sort === 'top'
		? '(t.seeders + t.remote_seeders + t.times_completed + t.comments + t.numratings) DESC, t.id DESC'
		: 't.added DESC, t.id DESC';
	$offset = max(0, (int)$offset);
	$limit = max(1, min(100, (int)$limit));

	$res = sql_query("
		SELECT t.id, t.name, t.comments, t.size, (t.seeders + t.remote_seeders) AS seeders, (t.leechers + t.remote_leechers) AS leechers, t.times_completed, t.added, t.image1,
		       c.image AS cat_pic, td.poster_url
		FROM torrents AS t
		LEFT JOIN categories AS c ON c.id = t.category
		LEFT JOIN torrent_details AS td ON td.tid = t.id
		WHERE t.visible = 'yes' AND t.banned = 'no' AND $where
		ORDER BY $order
		LIMIT $offset, $limit
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function persons_torrent_poster(array $torrent)
{
	if (!empty($torrent['poster_url'])) {
		return persons_h($torrent['poster_url']);
	}
	if (!empty($torrent['image1'])) {
		return 'thumbnail.php?' . persons_h($torrent['image1']);
	}
	return '/pic/default_avatar.gif';
}

function persons_pager($base_url, $page, $pages)
{
	$pages = max(1, (int)$pages);
	$page = max(0, min((int)$page, $pages - 1));
	if ($pages <= 1) {
		return '';
	}
	$html = '<div class="paginator"><ul>';
	$items = array_unique(array_merge(range(0, min(4, $pages - 1)), range(max(0, $page - 1), min($pages - 1, $page + 1)), array($pages - 1)));
	sort($items);
	$prev = -1;
	foreach ($items as $p) {
		if ($prev >= 0 && $p > $prev + 1) {
			$html .= '<li class="dots">...</li>';
		}
		$class = $p === $page ? ' class="current"' : '';
		$html .= '<li' . $class . '><a href="' . persons_h($base_url) . '&amp;page=' . $p . '">' . ($p + 1) . '</a></li>';
		$prev = $p;
	}
	if ($page + 1 < $pages) {
		$html .= '<li><a rel="next" href="' . persons_h($base_url) . '&amp;page=' . ($page + 1) . '">Вперед</a></li>';
	}
	$html .= '</ul></div><div class="clr"></div>';
	return $html;
}

function persons_http_json($url)
{
	static $cache = array();
	static $last_request_at = 0.0;

	$key = md5((string)$url);
	if (array_key_exists($key, $cache)) {
		return $cache[$key];
	}

	$body = '';
	for ($attempt = 0; $attempt < 3; $attempt++) {
		$elapsed = microtime(true) - $last_request_at;
		if ($elapsed < 0.18) {
			usleep((int)((0.18 - $elapsed) * 1000000));
		}
		$last_request_at = microtime(true);
		$code = 0;

		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_CONNECTTIMEOUT => 4,
				CURLOPT_TIMEOUT => 12,
				CURLOPT_USERAGENT => 'kinozal.lv-persons/2.0 (https://kinozal.lv/)',
			));
			$body = curl_exec($ch);
			$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
			curl_close($ch);
		} else {
			$ctx = stream_context_create(array('http' => array(
				'timeout' => 12,
				'ignore_errors' => true,
				'header' => "User-Agent: kinozal.lv-persons/2.0 (https://kinozal.lv/)\r\n",
			)));
			$body = @file_get_contents($url, false, $ctx);
			foreach ($http_response_header ?? array() as $header) {
				if (preg_match('#^HTTP/\S+\s+(\d{3})#i', $header, $m)) {
					$code = (int)$m[1];
				}
			}
		}

		if ($code >= 200 && $code < 300) {
			break;
		}
		$body = '';
		if (!in_array($code, array(429, 502, 503, 504), true)) {
			break;
		}
		sleep($attempt + 1);
	}

	$data = json_decode((string)$body, true);
	$data = is_array($data) ? $data : array();
	if ($data) {
		$cache[$key] = $data;
	}
	return $data;
}

function persons_wd_label(array $entities, $id)
{
	if ($id === '' || empty($entities[$id])) {
		return '';
	}
	$labels = $entities[$id]['labels'] ?? array();
	return (string)($labels['ru']['value'] ?? $labels['en']['value'] ?? '');
}

function persons_wd_entity_id($claim)
{
	return (string)($claim['mainsnak']['datavalue']['value']['id'] ?? '');
}

function persons_wd_claim_value($claim)
{
	return $claim['mainsnak']['datavalue']['value'] ?? null;
}

function persons_wd_labels(array $ids)
{
	$ids = array_values(array_unique(array_filter($ids)));
	if (!$ids) {
		return array();
	}

	$labels = array();
	foreach (array_chunk($ids, 45) as $chunk) {
		$data = persons_http_json('https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&props=labels&languages=ru|en&ids=' . rawurlencode(implode('|', $chunk)));
		foreach (($data['entities'] ?? array()) as $id => $entity) {
			$labels[$id] = (string)($entity['labels']['ru']['value'] ?? $entity['labels']['en']['value'] ?? '');
		}
	}
	return $labels;
}

function persons_wd_date_info($claim)
{
	$value = persons_wd_claim_value($claim);
	$time = (string)($value['time'] ?? '');
	$precision = (int)($value['precision'] ?? 0);
	if ($time === '') {
		return array('date' => '', 'text' => '');
	}
	if (preg_match('/^\+?([0-9]{4})-([0-9]{2})-([0-9]{2})/', $time, $m)) {
		if ($precision >= 11 && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
			return array('date' => $m[1] . '-' . $m[2] . '-' . $m[3], 'text' => '');
		}
		if ($precision === 10 && (int)$m[2] >= 1 && (int)$m[2] <= 12) {
			return array('date' => '', 'text' => $m[1] . '-' . $m[2]);
		}
		return array('date' => '', 'text' => $m[1]);
	}
	return array('date' => '', 'text' => '');
}

function persons_wd_date($claim)
{
	$info = persons_wd_date_info($claim);
	return $info['date'];
}

function persons_wd_text_value($claim)
{
	$value = persons_wd_claim_value($claim);
	if (is_array($value)) {
		return trim((string)($value['text'] ?? ''));
	}
	return trim((string)$value);
}

function persons_wd_quantity($claim)
{
	$value = persons_wd_claim_value($claim);
	if (!is_array($value) || !isset($value['amount'])) {
		return '';
	}

	$amount = (float)$value['amount'];
	$unit = (string)($value['unit'] ?? '');
	if (substr($unit, -6) === 'Q11573') {
		$amount *= 100;
	} elseif (substr($unit, -7) !== 'Q174728') {
		return '';
	}
	return rtrim(rtrim(number_format($amount, 1, '.', ''), '0'), '.') . ' см';
}

function persons_wikipedia_html_text($html)
{
	if (!class_exists('DOMDocument')) {
		$text = html_entity_decode(strip_tags((string)$html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		return trim(preg_replace('/[ \t]+/u', ' ', $text));
	}

	$dom = new DOMDocument();
	$old = libxml_use_internal_errors(true);
	$dom->loadHTML('<?xml encoding="utf-8" ?><div>' . (string)$html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
	libxml_clear_errors();
	libxml_use_internal_errors($old);
	$xpath = new DOMXPath($dom);
	foreach ($xpath->query(
		'//sup|//style|//script|//h1|//h2|//h3|//h4|//h5|//h6'
		. '|//*[contains(concat(" ", normalize-space(@class), " "), " navbox ")]'
		. '|//*[contains(concat(" ", normalize-space(@class), " "), " reflist ")]'
		. '|//*[contains(concat(" ", normalize-space(@class), " "), " references ")]'
		. '|//*[contains(concat(" ", normalize-space(@class), " "), " mw-editsection ")]'
	) as $node) {
		$node->parentNode->removeChild($node);
	}

	$lines = array();
	foreach ($xpath->query('//p|//li|//tr') as $node) {
		if ($node->parentNode && in_array(strtolower($node->parentNode->nodeName), array('li', 'tr'), true)) {
			continue;
		}
		if (strtolower($node->nodeName) === 'tr') {
			$cells = array();
			foreach ($xpath->query('./th|./td', $node) as $cell) {
				$value = preg_replace('/\s+/u', ' ', trim($cell->textContent));
				if ($value !== '') {
					$cells[] = $value;
				}
			}
			$line = implode(' | ', $cells);
		} else {
			$line = preg_replace('/\s+/u', ' ', trim($node->textContent));
		}
		$line = preg_replace('/\[\s*\d+\s*\]/u', '', $line);
		if ($line !== '' && !in_array($line, $lines, true)) {
			$lines[] = $line;
		}
	}
	return trim(implode("\n", $lines));
}

function persons_wikipedia_sections($api, $title)
{
	$data = persons_http_json($api . '?action=parse&format=json&formatversion=2&prop=sections&page=' . rawurlencode($title));
	$sections = array();
	foreach (($data['parse']['sections'] ?? array()) as $section) {
		$line = trim(html_entity_decode(strip_tags((string)($section['line'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
		$index = (string)($section['index'] ?? '');
		if ($line === '' || $index === '') {
			continue;
		}
		$sections[] = array('index' => $index, 'line' => $line, 'level' => (int)($section['level'] ?? 2));
	}
	return $sections;
}

function persons_wikipedia_section_text($api, $title, array $sections, array $patterns)
{
	$parts = array();
	$used = array();
	foreach ($sections as $section) {
		foreach ($patterns as $pattern) {
			if (!preg_match($pattern, $section['line'])) {
				continue;
			}
			if (isset($used[$section['index']])) {
				continue 2;
			}
			$used[$section['index']] = true;
			$data = persons_http_json($api . '?action=parse&format=json&formatversion=2&prop=text&page=' . rawurlencode($title) . '&section=' . rawurlencode($section['index']));
			$text = persons_wikipedia_html_text((string)($data['parse']['text'] ?? ''));
			if ($text !== '') {
				$parts[] = $text;
			}
			continue 2;
		}
	}
	return trim(implode("\n\n", array_unique($parts)));
}

function persons_normalize_filmography($text)
{
	$types = array(
		'ф', 'с', 'тф', 'кор', 'ки', 'мф', 'мс', 'док',
		'film', 'tv', 'short', 'video game', 'voice',
	);
	$lines = array();
	foreach (preg_split('#\r\n|\r|\n#', trim((string)$text)) as $line) {
		$columns = array_map('trim', explode('|', $line));
		if (count($columns) < 2) {
			if (trim($line) !== '') {
				$lines[] = trim($line);
			}
			continue;
		}

		$first = function_exists('mb_strtolower') ? mb_strtolower($columns[0], 'UTF-8') : strtolower($columns[0]);
		$second = function_exists('mb_strtolower') ? mb_strtolower($columns[1], 'UTF-8') : strtolower($columns[1]);
		if (in_array($first, $types, true)) {
			array_shift($columns);
		} elseif (in_array($second, $types, true)) {
			array_splice($columns, 1, 1);
		}
		$lines[] = implode(' | ', array_values(array_filter($columns, static function ($value) {
			return $value !== '';
		})));
	}
	return trim(implode("\n", $lines));
}

function persons_wikimedia_photos($category, $poster_url = '')
{
	$photos = array();
	if ($poster_url !== '') {
		$photos[] = $poster_url;
	}
	$category = trim((string)$category);
	if ($category === '') {
		return implode("\n", $photos);
	}

	$url = 'https://commons.wikimedia.org/w/api.php?action=query&format=json&generator=categorymembers'
		. '&gcmtitle=' . rawurlencode('Category:' . $category) . '&gcmtype=file&gcmlimit=20'
		. '&prop=imageinfo&iiprop=url';
	$data = persons_http_json($url);
	foreach (($data['query']['pages'] ?? array()) as $page) {
		$image_url = (string)($page['imageinfo'][0]['url'] ?? '');
		if ($image_url === '' || !preg_match('/\.(?:jpe?g|png|webp)(?:\?|$)/i', $image_url)) {
			continue;
		}
		$photos[] = $image_url;
		if (count(array_unique($photos)) >= 10) {
			break;
		}
	}
	return implode("\n", array_values(array_unique($photos)));
}

function persons_wikipedia_page_photos($api, $title, $poster_url = '', $alternate_title = '')
{
	$photos = array();
	if ($poster_url !== '') {
		$photos[] = $poster_url;
	}

	$url = $api . '?action=query&format=json&formatversion=2&generator=images'
		. '&titles=' . rawurlencode($title) . '&gimlimit=50&prop=imageinfo&iiprop=url|size';
	$data = persons_http_json($url);
	$names = trim($title . ' ' . $alternate_title);
	$name_words = preg_split('/[^\p{L}\p{N}]+/u', function_exists('mb_strtolower') ? mb_strtolower($names, 'UTF-8') : strtolower($names), -1, PREG_SPLIT_NO_EMPTY);
	foreach (($data['query']['pages'] ?? array()) as $page) {
		$image = $page['imageinfo'][0] ?? array();
		$image_url = (string)($image['url'] ?? '');
		$image_title = function_exists('mb_strtolower') ? mb_strtolower((string)($page['title'] ?? ''), 'UTF-8') : strtolower((string)($page['title'] ?? ''));
		if ($image_url === '' || !preg_match('/\.(?:jpe?g|png|webp)(?:\?|$)/i', $image_url)) {
			continue;
		}
		if ((int)($image['width'] ?? 0) < 300 || (int)($image['height'] ?? 0) < 300) {
			continue;
		}
		$matches_name = false;
		foreach ($name_words as $word) {
			if ((function_exists('mb_strlen') ? mb_strlen($word, 'UTF-8') : strlen($word)) >= 4 && strpos($image_title, $word) !== false) {
				$matches_name = true;
				break;
			}
		}
		if (!$matches_name) {
			continue;
		}
		$photos[] = $image_url;
		if (count(array_unique($photos)) >= 10) {
			break;
		}
	}

	if (count(array_unique($photos)) < 4) {
		$search_title = trim($alternate_title) !== '' ? trim($alternate_title) : $title;
		$url = 'https://commons.wikimedia.org/w/api.php?action=query&format=json&formatversion=2'
			. '&generator=search&gsrnamespace=6&gsrlimit=30&gsrsearch=' . rawurlencode($search_title)
			. '&prop=imageinfo&iiprop=url|size';
		$data = persons_http_json($url);
		foreach (($data['query']['pages'] ?? array()) as $page) {
			$image = $page['imageinfo'][0] ?? array();
			$image_url = (string)($image['url'] ?? '');
			$image_title = function_exists('mb_strtolower') ? mb_strtolower((string)($page['title'] ?? ''), 'UTF-8') : strtolower((string)($page['title'] ?? ''));
			if ($image_url === '' || !preg_match('/\.(?:jpe?g|png|webp)(?:\?|$)/i', $image_url)) {
				continue;
			}
			if ((int)($image['width'] ?? 0) < 300 || (int)($image['height'] ?? 0) < 300) {
				continue;
			}
			$matches_name = false;
			foreach ($name_words as $word) {
				if ((function_exists('mb_strlen') ? mb_strlen($word, 'UTF-8') : strlen($word)) >= 4 && strpos($image_title, $word) !== false) {
					$matches_name = true;
					break;
				}
			}
			if (!$matches_name) {
				continue;
			}
			$photos[] = $image_url;
			if (count(array_unique($photos)) >= 10) {
				break;
			}
		}
	}
	return implode("\n", array_values(array_unique($photos)));
}

function persons_wd_is_human(array $claims)
{
	foreach (($claims['P31'] ?? array()) as $claim) {
		if (persons_wd_entity_id($claim) === 'Q5') {
			return true;
		}
	}
	return false;
}

function persons_import_from_wikipedia($name, $lang = 'ru')
{
	$name = trim((string)$name);
	$lang = preg_match('/^[a-z]{2,3}$/i', (string)$lang) ? strtolower($lang) : 'ru';
	if ($name === '') {
		return array();
	}

	$api = 'https://' . $lang . '.wikipedia.org/w/api.php';
	$search = persons_http_json($api . '?action=query&format=json&formatversion=2&list=search&srlimit=5&srnamespace=0&utf8=1&srsearch=' . rawurlencode($name));
	$page = array();
	$entity = array();
	$qid = '';
	foreach (($search['query']['search'] ?? array()) as $candidate) {
		$page_data = persons_http_json($api . '?action=query&format=json&formatversion=2&prop=extracts|pageimages|pageprops|info&exintro=1&explaintext=1&piprop=original&inprop=url&utf8=1&pageids=' . (int)($candidate['pageid'] ?? 0));
		$candidate_page = $page_data['query']['pages'][0] ?? array();
		$candidate_qid = (string)($candidate_page['pageprops']['wikibase_item'] ?? '');
		if ($candidate_qid === '') {
			continue;
		}
		$wd = persons_http_json('https://www.wikidata.org/wiki/Special:EntityData/' . rawurlencode($candidate_qid) . '.json');
		$candidate_entity = $wd['entities'][$candidate_qid] ?? array();
		if (!persons_wd_is_human($candidate_entity['claims'] ?? array())) {
			continue;
		}
		$page = $candidate_page;
		$entity = $candidate_entity;
		$qid = $candidate_qid;
		break;
	}
	if (!$page || !$entity || $qid === '') {
		return array();
	}
	$title = (string)($page['title'] ?? $name);

	$result = array(
		'name' => $title ?: $name,
		'type' => 11,
		'biography' => trim((string)($page['extract'] ?? '')),
		'poster_url' => (string)($page['original']['source'] ?? ''),
		'source_url' => (string)($page['fullurl'] ?? ''),
	);

	$claims = $entity['claims'] ?? array();
	$labels = $entity['labels'] ?? array();
	$claim_ids = array();
	foreach (array('P19' => 1, 'P106' => 12, 'P136' => 12, 'P26' => 8, 'P166' => 20) as $prop => $limit) {
		foreach (array_slice($claims[$prop] ?? array(), 0, $limit) as $claim) {
			$id = persons_wd_entity_id($claim);
			if ($id !== '') {
				$claim_ids[] = $id;
			}
		}
	}
	$claim_labels = persons_wd_labels($claim_ids);

	if (!empty($labels['ru']['value'])) {
		$result['name'] = $labels['ru']['value'];
	}
	if (!empty($claims['P1559'][0])) {
		$result['original_name'] = persons_wd_text_value($claims['P1559'][0]);
	}
	if (empty($result['original_name']) && !empty($claims['P1477'][0])) {
		$result['original_name'] = persons_wd_text_value($claims['P1477'][0]);
	}
	if (empty($result['original_name']) && !empty($labels['en']['value'])) {
		$result['original_name'] = $labels['en']['value'];
	}
	if (!empty($claims['P569'][0])) {
		$birth = persons_wd_date_info($claims['P569'][0]);
		$result['birth_date'] = $birth['date'];
		$result['birth_text'] = $birth['text'];
	}
	if (!empty($claims['P19'][0])) {
		$result['birth_place'] = (string)($claim_labels[persons_wd_entity_id($claims['P19'][0])] ?? '');
	}
	if (!empty($claims['P21'][0])) {
		$gender_id = persons_wd_entity_id($claims['P21'][0]);
		if ($gender_id === 'Q6581072') {
			$result['gender'] = 2;
		} elseif ($gender_id === 'Q6581097') {
			$result['gender'] = 1;
		}
	}

	$list_fields = array('career' => 'P106', 'genre' => 'P136', 'spouse' => 'P26', 'awards' => 'P166');
	foreach ($list_fields as $field => $prop) {
		$values = array();
		foreach ($claims[$prop] ?? array() as $claim) {
			$label = (string)($claim_labels[persons_wd_entity_id($claim)] ?? '');
			if ($label !== '') {
				$values[] = $label;
			}
		}
		if ($values) {
			$result[$field] = $field === 'awards' ? implode("\n", array_unique($values)) : implode(', ', array_unique($values));
		}
	}
	if (!empty($claims['P2048'][0])) {
		$result['height'] = persons_wd_quantity($claims['P2048'][0]);
	}

	$commons_category = !empty($claims['P373'][0]) ? persons_wd_text_value($claims['P373'][0]) : '';
	if ($commons_category === '') {
		$commons_title = (string)($entity['sitelinks']['commonswiki']['title'] ?? '');
		if (stripos($commons_title, 'Category:') === 0) {
			$commons_category = trim(substr($commons_title, 9));
		}
	}
	$commons_photos = persons_wikimedia_photos($commons_category, $result['poster_url']);
	$page_photos = persons_wikipedia_page_photos($api, $title, $result['poster_url'], $result['original_name'] ?? '');
	$result['photos'] = implode("\n", array_unique(array_merge(
		preg_split('#\r\n|\r|\n#', $commons_photos, -1, PREG_SPLIT_NO_EMPTY),
		preg_split('#\r\n|\r|\n#', $page_photos, -1, PREG_SPLIT_NO_EMPTY)
	)));

	$links = array();
	if ($result['source_url'] !== '') {
		$links[] = 'Wikipedia (' . $lang . ')|' . $result['source_url'];
	}
	$links[] = 'Wikidata|https://www.wikidata.org/wiki/' . $qid;
	if (!empty($claims['P345'][0])) {
		$imdb_id = persons_wd_text_value($claims['P345'][0]);
		if ($imdb_id !== '') {
			$links[] = 'IMDb|https://www.imdb.com/name/' . rawurlencode($imdb_id) . '/';
		}
	}
	if (!empty($claims['P856'][0])) {
		$official_url = persons_wd_text_value($claims['P856'][0]);
		if (preg_match('#^https?://#i', $official_url)) {
			$links[] = 'Официальный сайт|' . $official_url;
		}
	}
	if ($commons_category !== '') {
		$links[] = 'Wikimedia Commons|https://commons.wikimedia.org/wiki/Category:' . rawurlencode(str_replace(' ', '_', $commons_category));
	}
	$result['links'] = implode("\n", array_unique($links));

	$sections = persons_wikipedia_sections($api, $title);
	$section_map = array(
		'trivia' => array('/^(?:Знаете ли вы|Интересные факты|Факты|Trivia)$/iu'),
		'filmography' => array('/^(?:Избранная\s+)?(?:Фильмография|Filmography|Selected filmography|Selected works|Acting credits(?: and accolades)?|Credits)$/iu'),
		'voice' => array('/(?:Озвучивание|Дубляж|Voice acting|Voice roles)/iu'),
		'producer' => array('/(?:Продюсерские работы|Продюсер|Producer credits|Producer)/iu'),
		'director' => array('/(?:Режисс[её]рские работы|Режисс[её]р|Directing|Director credits)/iu'),
		'writer' => array('/(?:Сценарные работы|Сценарист|Screenwriting|Writer credits)/iu'),
		'awards_sections' => array('/^(?:Награды(?: и номинации)?|Awards(?: and nominations)?)$/iu'),
	);
	foreach ($section_map as $field => $patterns) {
		$text = persons_wikipedia_section_text($api, $title, $sections, $patterns);
		if ($text === '') {
			continue;
		}
		if ($field === 'awards_sections') {
			$result['awards'] = trim(implode("\n\n", array_filter(array($result['awards'] ?? '', $text))));
		} elseif ($field === 'filmography') {
			$result[$field] = persons_normalize_filmography($text);
		} else {
			$result[$field] = $text;
		}
	}

	return $result;
}

function persons_merge_import(array $existing, array $import, $overwrite = false)
{
	$fields = array(
		'name', 'original_name', 'type', 'gender', 'poster_url', 'birth_date', 'birth_text',
		'birth_place', 'career', 'genre', 'height', 'spouse', 'biography', 'trivia',
		'filmography', 'voice', 'producer', 'director', 'writer', 'awards', 'links', 'source_url'
	);
	$out = $existing;
	foreach ($fields as $field) {
		if (array_key_exists($field, $import) && trim((string)$import[$field]) !== '' && ($overwrite || empty($out[$field]))) {
			$out[$field] = $import[$field];
		}
	}
	if (array_key_exists('photos', $import)) {
		$old_photos = preg_split('#\r\n|\r|\n#', (string)($out['photos'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
		$new_photos = preg_split('#\r\n|\r|\n#', (string)$import['photos'], -1, PREG_SPLIT_NO_EMPTY);
		$out['photos'] = implode("\n", $overwrite ? array_unique($new_photos) : array_unique(array_merge($old_photos, $new_photos)));
	}
	return $out;
}

?>
