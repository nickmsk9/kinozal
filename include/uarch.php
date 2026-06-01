<?php

if (!function_exists('uarch_h')) {
	function uarch_h($value)
	{
		return htmlspecialchars_uni((string)$value);
	}
}

function uarch_ensure_schema()
{
	sql_query("
		CREATE TABLE IF NOT EXISTS uarch_smiles (
			id int unsigned NOT NULL AUTO_INCREMENT,
			userid int unsigned NOT NULL DEFAULT '0',
			username varchar(40) NOT NULL DEFAULT '',
			userclass tinyint unsigned NOT NULL DEFAULT '0',
			image_url text NOT NULL,
			active enum('yes','no') NOT NULL DEFAULT 'yes',
			ip varchar(45) NOT NULL DEFAULT '',
			added datetime NOT NULL,
			PRIMARY KEY (id),
			KEY active_added (active, added),
			KEY userid (userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);
}

function uarch_valid_image_url($url)
{
	$url = trim((string)$url);

	if ($url === '' || mb_strlen($url, 'UTF-8') > 500) {
		return false;
	}

	return (bool)preg_match('#^(https?://|/).+\.(jpe?g|png|gif|webp)(\?.*)?$#i', $url);
}

function uarch_add_smile($url)
{
	global $CURUSER;

	uarch_ensure_schema();

	$url = trim((string)$url);
	if (!uarch_valid_image_url($url)) {
		return 'Укажите прямую ссылку на картинку JPG, PNG, GIF или WEBP.';
	}

	$userid = (int)($CURUSER['id'] ?? 0);
	if ($userid <= 0) {
		return 'Добавлять улыбки могут только пользователи сайта.';
	}

	$username = (string)($CURUSER['username'] ?? '');
	$userclass = (int)($CURUSER['class'] ?? 0);
	$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

	sql_query("
		INSERT INTO uarch_smiles (userid, username, userclass, image_url, active, ip, added)
		VALUES ($userid, " . sqlesc($username) . ", $userclass, " . sqlesc($url) . ", 'yes', " . sqlesc($ip) . ", NOW())
	") or sqlerr(__FILE__, __LINE__);

	return '';
}

function uarch_smiles($active_only = true, $limit = 60)
{
	uarch_ensure_schema();

	$limit = max(1, min(200, (int)$limit));
	$where = $active_only ? "WHERE s.active = 'yes'" : '';

	$res = sql_query("
		SELECT s.*, u.username AS real_username, u.class AS real_class, u.country, u.gender, u.donor, u.warned, u.enabled, u.birthday
		FROM uarch_smiles AS s
		LEFT JOIN users AS u ON u.id = s.userid
		$where
		ORDER BY s.added DESC, s.id DESC
		LIMIT $limit
	") or sqlerr(__FILE__, __LINE__);

	$rows = array();
	while ($row = mysqli_fetch_assoc($res)) {
		$row['display_username'] = (string)($row['real_username'] ?: $row['username']);
		$row['display_class'] = (int)($row['real_username'] !== null ? $row['real_class'] : $row['userclass']);
		$rows[] = $row;
	}

	return $rows;
}

function uarch_block_smile()
{
	$rows = uarch_smiles(true, 1);

	if (!empty($rows)) {
		return $rows[0];
	}

	return null;
}

function uarch_user_line(array $smile)
{
	$userid = (int)($smile['userid'] ?? 0);
	$username = (string)($smile['display_username'] ?? $smile['username'] ?? 'Пользователь');
	$userclass = (int)($smile['display_class'] ?? $smile['userclass'] ?? 0);
	$country = (int)($smile['country'] ?? 0);
	$html = '';

	if ($country > 0) {
		$html .= '<img src="/pic/emty.gif" class="i2 c' . $country . '" alt="">';
	}

	if ($userid > 0) {
		$html .= '<a href="/userdetails.php?id=' . $userid . '" class="u' . $userclass . '">' . uarch_h($username) . '</a>';
	} else {
		$html .= '<span class="u' . $userclass . '">' . uarch_h($username) . '</span>';
	}

	if (function_exists('get_user_icons')) {
		$html .= get_user_icons($smile);
	} elseif ((string)($smile['gender'] ?? '') === '2') {
		$html .= '<i class="i1 s_dv"></i>';
	}

	return $html;
}

function uarch_block_html()
{
	$smile = uarch_block_smile();

	if (!$smile) {
		return '<div class="bx2_0">'
			. '<ul class="men">'
			. '<li class="tp2 center"><a href="/uarch.php" class="sbab">Улыбка</a></li>'
			. '<li class="center pad5x5">Записей нет</li>'
			. '</ul>'
			. '</div>';
	}

	$image = (string)($smile['image_url'] ?? '');

	return '<div class="bx2_0">'
		. '<ul class="men">'
		. '<li class="tp2 center"><a href="/uarch.php" class="sbab">Улыбка</a> от ' . uarch_user_line($smile) . '</li>'
		. '<li class="center"><a href="/uarch.php"><img src="' . uarch_h($image) . '" width="175" alt=""></a></li>'
		. '</ul>'
		. '</div>';
}
function uarch_set_active($id, $active)
{
	uarch_ensure_schema();
	$id = (int)$id;
	$active = $active === 'yes' ? 'yes' : 'no';

	if ($id > 0) {
		sql_query("UPDATE uarch_smiles SET active = " . sqlesc($active) . " WHERE id = $id LIMIT 1") or sqlerr(__FILE__, __LINE__);
	}
}

function uarch_delete($id)
{
	uarch_ensure_schema();
	$id = (int)$id;

	if ($id > 0) {
		sql_query("DELETE FROM uarch_smiles WHERE id = $id LIMIT 1") or sqlerr(__FILE__, __LINE__);
	}
}

?>
