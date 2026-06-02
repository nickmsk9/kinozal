<?php

if (!defined('IN_TRACKER')) {
	die('Прямой вызов запрещён.');
}

function radio_h($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function radio_defaults()
{
	return array(
		'current_song' => 'Нажмите play для начала воспроизведения',
		'next_song' => 'Игорь Крутой & ANNA ASTI - Я хочу быть (A-Traxx Remix)',
		'listeners' => '8',
		'kbps' => '128',
		'dj_user_id' => '',
		'dj_name' => 'ДиДжей не выбран',
		'public_url' => 'https://myradio24.com/kinozaltv',
		'stream_url_128' => 'https://myradio24.org/kinozaltv',
		'stream_url_320' => 'https://myradio24.org/kinozaltv_320',
		'playlist_url_128' => 'https://myradio24.org/kinozaltv.m3u',
		'playlist_url_320' => 'https://myradio24.org/kinozaltv_320.m3u',
		'order_url' => 'https://myradio24.com/ru/table/kinozaltv',
		'order_image' => '/pic/radio_ban.jpg',
		'order_full_url' => '/pic/radio_ban.jpg',
		'group_title' => 'Группа «Радио Кинозал.ТВ» приглашает',
		'group_url' => '/groupex.php?id=949',
		'announce_title' => 'Анонс эфира',
		'announce_text' => 'Следите за расписанием эфиров и музыкальными вечерами Радио Кинозал.ТВ.',
		'recruit_contact' => 'admin',
		'offline_mode' => '0',
		'chat_enabled' => '1',
		'rules_text' => radio_default_rules_text(),
	);
}

function radio_default_rules_text()
{
	return <<<TEXT
Определим понятие, кто такой ДиДжей.
Диджей - человек, инструментом которого является музыка, обладающий чувством ритма и стиля, а также приятным голосом. Он заряжает слушателей настроением, энергией, контролирует эмоции и создает атмосферу.
Классный ДиДжей - это талант, нашедший способ себя проявить.

Возможно, это есть и в тебе. Есть только один способ узнать это - попробовать!
Рассматриваются кандидаты, зарегистрированные в Кинозал.ТВ не менее 1-го месяца, достигшие 18-и летнего возраста и соблюдающие правила нашего сайта и зарекомендовавшие себя хорошим общением. А также, обладающие следующими техническими характеристиками:
1. Высокая скорость отдачи (минимум 0.5 Мбит/c), а также скорость закачки не менее 3 Мбит/с - http://www.speedtest.net/
2. Большой объем разнообразной музыки в битрейте от 128 кбит/с (желательно 320 кбит/с) (от 15 ГБ).
3. Наличие микрофона.
4. Наличие Skype обязательно.
5. Прекрасное владение русским языком (вещание ведётся на русском языке).
6. Наличие свободного времени.

Устав и правила ДиДжеев и Дикторов Радио Кинозал.ТВ

1. Администрация всегда права.
2. Если Администрация не права смотри пункт 1.
3. Предварительно заполнять расписание и указывать тему передачи.
4. Стараться как можно интереснее проводить свои эфиры и дарить положительные эмоции слушателям.
5. Скачивать или раздавать, во время всего эфира, запрещается.
6. Во время работы стола заказов старайтесь выполнять максимальное количество заявок. Песни с нецензурной речью ставить в эфир запрещено.
7. Уважаемые ДиДжеи, основывайте свои отношения между собой на взаимопомощи и взаимовыручке. Будьте предельно вежливы с пользователями.
8. Имеется-ли у вас опыт да/нет (если есть, то ссылка на демозапись).
9. Кинозал.ТВ ресурс международный, но язык общения русский. Относитесь к людям так, как вы хотите, что бы они отнеслись к вам.

Правила обязательны к исполнению ДиДжеями Радио Кинозал.ТВ.
По всем вопросам, касающихся работы группы Радио, вы всегда можете обратиться к ДиДжеям.

Заполните следующие пункты Анкеты:
1. Имя на трекере и ссылка на профиль.
2. Регистрация на форуме с тем же именем и почтовым ящиком, что и на трекере - ссылка на профиль.
3. ОС (операционная система).
4. Логин в Skype.
5. Почему Вы решили стать ДиДжеем на Радио Кинозал.ТВ?
6. Скорость интернета (скрин скорости должен прилагаться).
7. Наличие микрофона (есть/нет) (обязательно).
8. Немного о себе: ...

Отправьте Анкету в личном сообщении (ЛС): admin
TEXT;
}

function radio_ensure_schema()
{
	static $done = false;

	if ($done) {
		return;
	}

	$done = true;

	sql_query("
		CREATE TABLE IF NOT EXISTS radio_settings (
			setting_key VARCHAR(64) NOT NULL,
			setting_value MEDIUMTEXT NOT NULL,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (setting_key)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	sql_query("
		CREATE TABLE IF NOT EXISTS radio_chat (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			tab TINYINT UNSIGNED NOT NULL DEFAULT 11,
			userid INT UNSIGNED NOT NULL DEFAULT 0,
			username VARCHAR(40) NOT NULL DEFAULT '',
			userclass TINYINT UNSIGNED NOT NULL DEFAULT 0,
			text TEXT NOT NULL,
			ip VARCHAR(45) NOT NULL DEFAULT '',
			added DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY tab_added (tab, added),
			KEY userid (userid)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	$defaults = radio_defaults();

	if (!empty($defaults)) {
		$values = array();

		foreach ($defaults as $key => $value) {
			$values[] = '(' . sqlesc($key, true) . ', ' . sqlesc($value, true) . ', NOW())';
		}

		sql_query("
			INSERT IGNORE INTO radio_settings 
				(setting_key, setting_value, updated_at)
			VALUES
				" . implode(",\n", $values)
		) or sqlerr(__FILE__, __LINE__);
	}
}

function radio_settings()
{
	radio_ensure_schema();

	$settings = radio_defaults();
	$res = sql_query("SELECT setting_key, setting_value FROM radio_settings") or sqlerr(__FILE__, __LINE__);

	while ($row = mysqli_fetch_assoc($res)) {
		$settings[(string)$row['setting_key']] = (string)$row['setting_value'];
	}

	return $settings;
}

function radio_save_settings(array $values)
{
	radio_ensure_schema();

	foreach ($values as $key => $value) {
		$key = (string)$key;
		if (!array_key_exists($key, radio_defaults())) {
			continue;
		}

		sql_query("
			INSERT INTO radio_settings (setting_key, setting_value, updated_at)
			VALUES (" . sqlesc($key, true) . ", " . sqlesc((string)$value, true) . ", NOW())
			ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
		") or sqlerr(__FILE__, __LINE__);
	}
}

function radio_url($url, $fallback = '#')
{
	$url = trim((string)$url);

	if ($url === '') {
		return $fallback;
	}

	if (preg_match('#^(https?://|/)#i', $url)) {
		return $url;
	}

	return $fallback;
}

function radio_find_user($userid)
{
	$userid = (int)$userid;

	if ($userid <= 0) {
		return null;
	}

	$res = sql_query("SELECT id, username, class FROM users WHERE id = $userid LIMIT 1");

	if ($res && ($row = mysqli_fetch_assoc($res))) {
		return $row;
	}

	return null;
}

function radio_add_chat_message($tab, $text)
{
	global $CURUSER;

	radio_ensure_schema();

	if (empty($CURUSER) || !is_array($CURUSER)) {
		return 'Писать в чат могут только авторизованные пользователи.';
	}

	$tab = (int)$tab === 12 ? 12 : 11;
	$text = trim((string)$text);

	if (mb_strlen($text, 'UTF-8') < 5) {
		return 'Минимум 5 символов.';
	}

	if (mb_strlen($text, 'UTF-8') > 1000) {
		return 'Сообщение слишком длинное.';
	}

	$userid = (int)$CURUSER['id'];
	$username = (string)$CURUSER['username'];
	$userclass = (int)$CURUSER['class'];
	$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

	sql_query("
		INSERT INTO radio_chat (tab, userid, username, userclass, text, ip, added)
		VALUES ($tab, $userid, " . sqlesc($username, true) . ", $userclass, " . sqlesc($text, true) . ", " . sqlesc($ip, true) . ", NOW())
	") or sqlerr(__FILE__, __LINE__);

	return '';
}

function radio_chat_messages($tab, $limit = 80)
{
	radio_ensure_schema();

	$tab = (int)$tab === 12 ? 12 : 11;
	$limit = max(1, min(200, (int)$limit));
	$rows = array();

	$res = sql_query("
		SELECT id, tab, userid, username, userclass, text, added
		FROM radio_chat
		WHERE tab = $tab
		ORDER BY id DESC
		LIMIT $limit
	") or sqlerr(__FILE__, __LINE__);

	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;
	}

	return array_reverse($rows);
}

function radio_delete_chat_message($id)
{
	$id = (int)$id;

	if ($id > 0) {
		sql_query("DELETE FROM radio_chat WHERE id = $id LIMIT 1") or sqlerr(__FILE__, __LINE__);
	}
}

function radio_clear_chat()
{
	sql_query("TRUNCATE TABLE radio_chat") or sqlerr(__FILE__, __LINE__);
}
