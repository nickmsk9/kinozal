<?php

if (!defined('IN_TRACKER')) {
	die('Direct access denied.');
}

function kz_persons_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function kz_persons_ensure_schema()
{
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

function kz_persons_cp1251_urlencode($value)
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

function kz_persons_request_text($value)
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

function kz_persons_url($name, $pid = 0, array $extra = array())
{
	$url = '/persons.php?s=' . kz_persons_cp1251_urlencode($name);
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

function kz_persons_find($pid = 0, $name = '')
{
	kz_persons_ensure_schema();

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

function kz_persons_find_id_by_name($name)
{
	$person = kz_persons_find(0, $name);
	return $person ? (int)$person['id'] : 0;
}

function kz_persons_photos($person_id, $limit = 0)
{
	kz_persons_ensure_schema();

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

function kz_persons_save_photos($person_id, $photos_text)
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

function kz_persons_photo_text($person_id)
{
	$urls = array();
	foreach (kz_persons_photos($person_id) as $photo) {
		$urls[] = $photo['image_url'];
	}
	return implode("\n", $urls);
}

function kz_persons_can_edit($person = null)
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

function kz_persons_user_link($userid, $username, $class = 0, array $row = array())
{
	$userid = (int)$userid;
	if ($userid <= 0 || $username === '') {
		return '<i>unknown</i>';
	}
	$icons = function_exists('get_user_icons') ? get_user_icons(array_merge($row, array('id' => $userid, 'class' => $class))) : '';
	return '<a href="/userdetails.php?id=' . $userid . '" class="u' . (int)$class . '">' . kz_persons_h($username) . '</a>' . $icons;
}

function kz_persons_date($value)
{
	$value = (string)$value;
	if ($value === '' || $value === '0000-00-00') {
		return '';
	}
	$ts = strtotime($value);
	return $ts ? date('d.m.Y', $ts) : $value;
}

function kz_persons_text($text)
{
	$text = trim((string)$text);
	if ($text === '') {
		return '';
	}
	$text = htmlspecialchars_uni($text);
	$text = preg_replace('#(https?://[^\s<]+)#iu', '<a href="$1" target="_blank" class="sba">$1</a>', $text);
	return nl2br($text);
}

function kz_persons_links_html($links)
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
			$out[] = '<a href="' . kz_persons_h($url) . '" target="_blank" class="sbab">' . kz_persons_h($title) . '</a>';
		}
	}
	return implode('<br />', $out);
}

function kz_persons_torrent_like_conditions(array $person)
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

function kz_persons_torrent_count(array $person)
{
	$where = kz_persons_torrent_like_conditions($person);
	$res = sql_query("
		SELECT COUNT(*) AS c
		FROM torrents AS t
		LEFT JOIN torrent_details AS td ON td.tid = t.id
		WHERE t.visible = 'yes' AND t.banned = 'no' AND $where
	") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);
	return (int)($row['c'] ?? 0);
}

function kz_persons_torrents(array $person, $sort = 'date', $offset = 0, $limit = 50)
{
	$where = kz_persons_torrent_like_conditions($person);
	$order = $sort === 'top'
		? '(t.seeders + t.times_completed + t.comments + t.numratings) DESC, t.id DESC'
		: 't.added DESC, t.id DESC';
	$offset = max(0, (int)$offset);
	$limit = max(1, min(100, (int)$limit));

	$res = sql_query("
		SELECT t.id, t.name, t.comments, t.size, t.seeders, t.leechers, t.times_completed, t.added, t.image1,
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

function kz_persons_torrent_poster(array $torrent)
{
	if (!empty($torrent['poster_url'])) {
		return kz_persons_h($torrent['poster_url']);
	}
	if (!empty($torrent['image1'])) {
		return 'thumbnail.php?' . kz_persons_h($torrent['image1']);
	}
	return '/pic/default_avatar.gif';
}

function kz_persons_pager($base_url, $page, $pages)
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
		$html .= '<li' . $class . '><a href="' . kz_persons_h($base_url) . '&amp;page=' . $p . '">' . ($p + 1) . '</a></li>';
		$prev = $p;
	}
	if ($page + 1 < $pages) {
		$html .= '<li><a rel="next" href="' . kz_persons_h($base_url) . '&amp;page=' . ($page + 1) . '">Вперед</a></li>';
	}
	$html .= '</ul></div><div class="clr"></div>';
	return $html;
}

function kz_persons_http_json($url)
{
	$body = '';
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 4,
			CURLOPT_TIMEOUT => 8,
			CURLOPT_USERAGENT => 'kinozal-persons/1.0',
		));
		$body = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);
		if ($code < 200 || $code >= 300) {
			$body = '';
		}
	} else {
		$ctx = stream_context_create(array('http' => array('timeout' => 8, 'header' => "User-Agent: kinozal-persons/1.0\r\n")));
		$body = @file_get_contents($url, false, $ctx);
	}

	$data = json_decode((string)$body, true);
	return is_array($data) ? $data : array();
}

function kz_persons_wd_label(array $entities, $id)
{
	if ($id === '' || empty($entities[$id])) {
		return '';
	}
	$labels = $entities[$id]['labels'] ?? array();
	return (string)($labels['ru']['value'] ?? $labels['en']['value'] ?? '');
}

function kz_persons_wd_entity_id($claim)
{
	return (string)($claim['mainsnak']['datavalue']['value']['id'] ?? '');
}

function kz_persons_wd_labels(array $ids)
{
	$ids = array_values(array_unique(array_filter($ids)));
	if (!$ids) {
		return array();
	}

	$data = kz_persons_http_json('https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&props=labels&languages=ru|en&ids=' . rawurlencode(implode('|', $ids)));
	$labels = array();
	foreach (($data['entities'] ?? array()) as $id => $entity) {
		$labels[$id] = (string)($entity['labels']['ru']['value'] ?? $entity['labels']['en']['value'] ?? '');
	}
	return $labels;
}

function kz_persons_wd_date($claim)
{
	$time = (string)($claim['mainsnak']['datavalue']['value']['time'] ?? '');
	if ($time === '') {
		return '';
	}
	if (preg_match('/^\+?([0-9]{4})-([0-9]{2})-([0-9]{2})/', $time, $m)) {
		if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
			return '';
		}
		return $m[1] . '-' . $m[2] . '-' . $m[3];
	}
	return '';
}

function kz_persons_import_from_wikipedia($name, $lang = 'ru')
{
	$name = trim((string)$name);
	$lang = preg_match('/^[a-z]{2,3}$/i', (string)$lang) ? strtolower($lang) : 'ru';
	if ($name === '') {
		return array();
	}

	$api = 'https://' . $lang . '.wikipedia.org/w/api.php';
	$search = kz_persons_http_json($api . '?action=query&format=json&list=search&srlimit=1&utf8=1&srsearch=' . rawurlencode($name));
	$title = (string)($search['query']['search'][0]['title'] ?? $name);

	$page_data = kz_persons_http_json($api . '?action=query&format=json&prop=extracts|pageimages|pageprops|info&explaintext=1&exsectionformat=plain&piprop=original&inprop=url&utf8=1&titles=' . rawurlencode($title));
	$pages = $page_data['query']['pages'] ?? array();
	$page = $pages ? reset($pages) : array();

	$result = array(
		'name' => $title ?: $name,
		'biography' => trim((string)($page['extract'] ?? '')),
		'poster_url' => (string)($page['original']['source'] ?? ''),
		'source_url' => (string)($page['fullurl'] ?? ''),
	);

	$qid = (string)($page['pageprops']['wikibase_item'] ?? '');
	if ($qid !== '') {
		$wd = kz_persons_http_json('https://www.wikidata.org/wiki/Special:EntityData/' . rawurlencode($qid) . '.json');
		$entity = $wd['entities'][$qid] ?? array();
		$claims = $entity['claims'] ?? array();
		$labels = $entity['labels'] ?? array();
		$claim_ids = array();

		if (!empty($labels['ru']['value'])) {
			$result['name'] = $labels['ru']['value'];
		}
		if (!empty($labels['en']['value'])) {
			$result['original_name'] = $labels['en']['value'];
		}
		if (!empty($claims['P569'][0])) {
			$result['birth_date'] = kz_persons_wd_date($claims['P569'][0]);
		}
		foreach (array('P19', 'P21', 'P106') as $prop) {
			foreach (($claims[$prop] ?? array()) as $claim) {
				$id = kz_persons_wd_entity_id($claim);
				if ($id !== '') {
					$claim_ids[] = $id;
				}
			}
		}
		$claim_labels = kz_persons_wd_labels($claim_ids);
		if (!empty($claims['P19'][0])) {
			$result['birth_place'] = (string)($claim_labels[kz_persons_wd_entity_id($claims['P19'][0])] ?? '');
		}
		if (!empty($claims['P21'][0])) {
			$gender = (string)($claim_labels[kz_persons_wd_entity_id($claims['P21'][0])] ?? '');
			if (stripos($gender, 'female') !== false || stripos($gender, 'жен') !== false) {
				$result['gender'] = 2;
			} elseif (stripos($gender, 'male') !== false || stripos($gender, 'муж') !== false) {
				$result['gender'] = 1;
			}
		}
		if (!empty($claims['P106'])) {
			$career = array();
			foreach (array_slice($claims['P106'], 0, 8) as $claim) {
				$label = (string)($claim_labels[kz_persons_wd_entity_id($claim)] ?? '');
				if ($label !== '') {
					$career[] = $label;
				}
			}
			$result['career'] = implode(', ', array_unique($career));
		}
	}

	return $result;
}

function kz_persons_merge_import(array $existing, array $import, $overwrite = false)
{
	$fields = array('name', 'original_name', 'gender', 'poster_url', 'birth_date', 'birth_place', 'career', 'biography', 'source_url');
	$out = $existing;
	foreach ($fields as $field) {
		if (array_key_exists($field, $import) && ($overwrite || empty($out[$field]))) {
			$out[$field] = $import[$field];
		}
	}
	return $out;
}

?>
