<?php

if (!defined('IN_TRACKER')) {
	die('Прямой вызов запрещён.');
}

function h($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function upload_kinds()
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

function upload_categories()
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

function upload_release_groups()
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

function upload_quality_options()
{
	return array(
		'' => 'Выберите качество',
		'WEB-DLRip' => 'WEB-DLRip',
		'WEB-DL (2160p)' => 'WEB-DL (2160p)',
		'WEB-DL (1080p)' => 'WEB-DL (1080p)',
		'WEB-DL (720p)' => 'WEB-DL (720p)',
		'WEBRip (1080p)' => 'WEBRip (1080p)',
		'WEBRip (720p)' => 'WEBRip (720p)',
		'BDRemux' => 'BDRemux',
		'Blu-Ray' => 'Blu-Ray',
		'Blu-Ray 3D' => 'Blu-Ray 3D',
		'BDRip (1080p)' => 'BDRip (1080p)',
		'BDRip (720p)' => 'BDRip (720p)',
		'HDRip (1080p)' => 'HDRip (1080p)',
		'HDRip (720p)' => 'HDRip (720p)',
		'DVDRip' => 'DVDRip',
		'HDRip' => 'HDRip',
		'DVD-9' => 'DVD-9',
		'DVD-5' => 'DVD-5',
		'HDTVRip (1080p)' => 'HDTVRip (1080p)',
		'HDTVRip (720p)' => 'HDTVRip (720p)',
		'HDTVRip' => 'HDTVRip',
		'TVRip' => 'TVRip',
		'SATRip' => 'SATRip',
		'DVB' => 'DVB',
		'DVDScr' => 'DVDScr',
		'TS' => 'TS',
		'CAMRip' => 'CAMRip',
	);
}

function upload_translation_options()
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

function upload_language_options()
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

function upload_subtitle_options()
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

function upload_default_data()
{
	static $data = null;
	if ($data !== null) {
		return $data;
	}

	$data = array(
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
		'templates' => upload_templates_default_data(),
		'generic' => array(
			'desc1' => '',
			'desc2' => '',
			'desc3' => '',
			'desc4' => '',
		),
	);

	return $data;
}

function upload_empty_advanced()
{
	return array(
		'desc1' => '',
		'desc2' => '',
		'desc3' => '',
		'desc4' => '',
	);
}

function upload_empty_design()
{
	return array(
		'related' => array(),
		'watch' => array(),
		'tabs' => array(),
		'screens' => '',
		'notes' => '',
		'imdb' => array('enabled' => 0, 'url' => '', 'rating' => ''),
		'kinopoisk' => array('enabled' => 0, 'url' => '', 'rating' => ''),
	);
}

function upload_templates_default_data()
{
	static $out = null;
	if ($out !== null) {
		return $out;
	}

	$out = array();
	foreach (array('music', 'game', 'audiobook', 'program', 'book', 'graphic') as $kind) {
		$out[$kind] = array(
			'fields' => array(),
			'advanced' => upload_empty_advanced(),
			'design' => upload_empty_design(),
		);
	}

	return $out;
}

function upload_release_specs()
{
	static $specs = null;
	if ($specs !== null) {
		return $specs;
	}

	$desc = 'Рекомендуем писать собственное описание, а не копировать его из сети - это положительно скажется на количестве сидов и пиров и значительно увеличит срок жизни раздачи. Помощь по созданию описаний к раздачам <a href="//forum.kinozal.tv/forumdisplay.php?f=244" target="_blank">здесь</a>';
	$size_desc = 'Устанавливается автоматически при выборе торрент-файла';

	$specs = array(
		'music' => array(
			'sections' => array(
				array('fields' => array(
					array('key' => 'artist', 'label' => 'Исполнитель', 'placeholder' => 'Имя исполнителя, Artist name'),
					array('key' => 'album', 'label' => 'Альбом', 'placeholder' => 'Название альбома, Album name'),
					array('key' => 'year', 'label' => 'Год выпуска', 'placeholder' => '2009'),
					array('key' => 'genre', 'label' => 'Жанр', 'placeholder' => 'Поп, рок, блюз'),
				)),
				array('fields' => array(
					array('key' => 'about', 'label' => 'О музыке', 'placeholder' => 'Краткое описание...', 'textarea' => 10, 'desc' => $desc),
				)),
				array('fields' => array(
					array('key' => 'audio', 'label' => 'Аудио', 'placeholder' => 'MP3, 192 Кбит/с'),
					array('key' => 'size', 'label' => 'Размер', 'placeholder' => '600 МБ', 'desc' => $size_desc),
					array('key' => 'duration', 'label' => 'Продолжительность', 'placeholder' => '01:15:14', 'desc' => 'Общая продолжительность в формате ЧЧ:ММ:СС'),
				)),
			),
			'design' => array(
				'related' => array(array('title' => 'Подобные раздачи', 'query' => 'Поисковое слово, название')),
				'watch' => array(array('title' => 'Инфо', 'url' => 'https://ссылка')),
				'tabs' => array(array('title' => 'Треклист', 'content' => '01. Перечень')),
			),
		),
		'game' => array(
			'sections' => array(
				array('fields' => array(
					array('key' => 'title', 'label' => 'Название', 'placeholder' => 'Название'),
					array('key' => 'original_title', 'label' => 'Оригинальное название', 'placeholder' => 'Name'),
					array('key' => 'year', 'label' => 'Год выпуска', 'placeholder' => '2009'),
					array('key' => 'genre', 'label' => 'Жанр', 'placeholder' => 'Action, shooter, racing, strategy'),
					array('key' => 'developer', 'label' => 'Разработчик', 'placeholder' => 'Наименование компании'),
					array('key' => 'released', 'label' => 'Выпущено', 'placeholder' => 'Наименование издательства'),
					array('key' => 'version', 'label' => 'Версия', 'placeholder' => '1.0'),
					array('key' => 'language', 'label' => 'Язык', 'placeholder' => 'Русский, английский'),
				)),
				array('fields' => array(
					array('key' => 'about', 'label' => 'Об игре', 'placeholder' => 'Краткое описание игры...', 'textarea' => 10, 'desc' => $desc),
				)),
				array('fields' => array(
					array('key' => 'requirements', 'label' => 'Минимальные системные требования', 'textarea' => 4),
					array('key' => 'os', 'label' => 'Операционная система', 'placeholder' => 'Windows 10/11 64-бит'),
					array('key' => 'cpu', 'label' => 'Процессор', 'placeholder' => 'Core i5-8400 / Ryzen 5 2600'),
					array('key' => 'memory', 'label' => 'Память', 'placeholder' => '8 ГБ'),
					array('key' => 'gpu', 'label' => 'Видеокарта', 'placeholder' => '4 ГБ, GeForce GTX 960 / Radeon R9 380, DirectX 11'),
					array('key' => 'sound', 'label' => 'Аудиокарта', 'placeholder' => 'Совместимая с ОС'),
					array('key' => 'space', 'label' => 'Свободное место', 'placeholder' => '20 ГБ'),
					array('key' => 'platform', 'label' => 'Платформа', 'placeholder' => 'Для мобильных, консольных и интерактивных игр'),
					array('key' => 'size', 'label' => 'Занимаемое место', 'placeholder' => 'Для мобильных, консольных и интерактивных игр'),
				)),
			),
			'design' => array(
				'related' => array(array('title' => 'Подобные раздачи', 'query' => 'Поисковое слово, название')),
				'watch' => array(array('title' => 'Полезная информация', 'url' => 'https://ссылка')),
				'tabs' => array(
					array('title' => 'Особенности', 'content' => '01. Перечень'),
					array('title' => 'Установка', 'content' => ''),
					array('title' => 'Русификация', 'content' => ''),
					array('title' => 'Скриншоты', 'content' => ''),
				),
			),
		),
		'audiobook' => array(
			'sections' => array(
				array('fields' => array(
					array('key' => 'author', 'label' => 'Автор', 'placeholder' => 'Имя Фамилия'),
					array('key' => 'title', 'label' => 'Название', 'placeholder' => 'Название книги'),
					array('key' => 'year', 'label' => 'Год выпуска', 'placeholder' => '2009'),
					array('key' => 'genre', 'label' => 'Жанр', 'placeholder' => 'Классика, радиоспектакль, фантастика'),
					array('key' => 'released', 'label' => 'Выпущено', 'placeholder' => 'Название издательства'),
					array('key' => 'reader', 'label' => 'Озвучивает', 'placeholder' => 'Имя Фамилия'),
				)),
				array('fields' => array(
					array('key' => 'about', 'label' => 'Описание', 'placeholder' => 'Краткая аннотация к книге...', 'textarea' => 10, 'desc' => $desc),
				)),
				array('fields' => array(
					array('key' => 'audio', 'label' => 'Аудио', 'placeholder' => 'MP3, 96 Кбит/с, стерео'),
					array('key' => 'size', 'label' => 'Размер', 'placeholder' => '635 МБ', 'desc' => $size_desc),
					array('key' => 'duration', 'label' => 'Продолжительность', 'placeholder' => '29:55:41'),
					array('key' => 'language', 'label' => 'Язык', 'placeholder' => 'Русский'),
				)),
			),
			'design' => array(
				'related' => array(
					array('title' => 'Цикл аудиокниг', 'query' => 'Поисковое слово, название'),
					array('title' => 'Подобные раздачи', 'query' => 'Поисковое слово, название'),
				),
				'watch' => array(array('title' => 'Послушать', 'url' => 'https://ссылка')),
				'tabs' => array(
					array('title' => 'Содержание', 'content' => '01. Перечень'),
					array('title' => 'Об издании', 'content' => ''),
					array('title' => 'Интересно', 'content' => ''),
					array('title' => 'Обложки', 'content' => ''),
				),
			),
		),
		'program' => array(
			'sections' => array(
				array('fields' => array(
					array('key' => 'original_title', 'label' => 'Оригинальное название', 'placeholder' => 'Program name'),
					array('key' => 'year', 'label' => 'Год выпуска', 'placeholder' => '2009'),
					array('key' => 'genre', 'label' => 'Жанр', 'placeholder' => 'Безопасность'),
					array('key' => 'developer', 'label' => 'Разработчик', 'placeholder' => 'Наименование компании'),
					array('key' => 'version', 'label' => 'Версия', 'placeholder' => '1.0'),
					array('key' => 'language', 'label' => 'Язык', 'placeholder' => 'Русский, английский'),
				)),
				array('fields' => array(
					array('key' => 'about', 'label' => 'О программе', 'placeholder' => 'Краткое описание программы...', 'textarea' => 10, 'desc' => $desc),
				)),
				array('fields' => array(
					array('key' => 'requirements', 'label' => 'Минимальные системные требования', 'textarea' => 4),
					array('key' => 'os', 'label' => 'Операционная система', 'placeholder' => 'Windows XP/Vista/7/8'),
					array('key' => 'cpu', 'label' => 'Процессор', 'placeholder' => 'Pentium 4 2 ГГц'),
					array('key' => 'memory', 'label' => 'Память', 'placeholder' => '512 МБ'),
					array('key' => 'gpu', 'label' => 'Видеокарта', 'placeholder' => '128 МБ, GeForce FX 5600 / Radeon 9600, 1024х768, Shader 2.0, DirectX 9.0c'),
					array('key' => 'sound', 'label' => 'Аудиокарта', 'placeholder' => 'Совместимая с ОС'),
					array('key' => 'space', 'label' => 'Свободное место', 'placeholder' => '1 ГБ'),
					array('key' => 'platform', 'label' => 'Платформа', 'placeholder' => 'Для мобильного и навигационного ПО'),
					array('key' => 'size', 'label' => 'Занимаемое место', 'placeholder' => 'Для мобильного и навигационного ПО'),
				)),
			),
			'design' => array(
				'related' => array(array('title' => 'Подобные раздачи', 'query' => 'Поисковое слово, название')),
				'watch' => array(
					array('title' => 'Полезная информация', 'url' => 'https://ссылка'),
					array('title' => 'Версия программы', 'url' => 'https://полноразмерный_скриншот'),
				),
				'tabs' => array(
					array('title' => 'Особенности', 'content' => '01. Перечень'),
					array('title' => 'Установка', 'content' => ''),
					array('title' => 'Русификация', 'content' => ''),
					array('title' => 'Скриншоты', 'content' => ''),
				),
			),
		),
		'book' => array(
			'sections' => array(
				array('fields' => array(
					array('key' => 'author', 'label' => 'Автор', 'placeholder' => 'Имя Фамилия'),
					array('key' => 'title', 'label' => 'Название', 'placeholder' => 'Название публикации'),
					array('key' => 'original_title', 'label' => 'Оригинальное название', 'placeholder' => 'Publication title'),
					array('key' => 'year', 'label' => 'Год выпуска', 'placeholder' => '2009'),
					array('key' => 'series', 'label' => 'Серия', 'placeholder' => ''),
					array('key' => 'genre', 'label' => 'Жанр', 'placeholder' => 'Периодика, раритеты, журнал'),
					array('key' => 'released', 'label' => 'Выпущено', 'placeholder' => 'Страна, город, название издательства'),
					array('key' => 'language', 'label' => 'Язык', 'placeholder' => 'Русский'),
				)),
				array('fields' => array(
					array('key' => 'about', 'label' => 'Описание', 'placeholder' => 'Описание публикации...', 'textarea' => 10, 'desc' => $desc),
				)),
				array('fields' => array(
					array('key' => 'format', 'label' => 'Формат', 'placeholder' => 'PDF'),
					array('key' => 'quality', 'label' => 'Качество', 'placeholder' => 'Отсканированные страницы'),
					array('key' => 'image_size', 'label' => 'Размеры изображений', 'placeholder' => 'от 2249х3350 до 2250х3350'),
					array('key' => 'paper_size', 'label' => 'Размеры листа', 'placeholder' => '204x292 мм, А4'),
					array('key' => 'resolution', 'label' => 'Разрешение', 'placeholder' => '72 пикс/дюйм, 300 пикс/дюйм'),
					array('key' => 'color_depth', 'label' => 'Глубина цвета', 'placeholder' => '8 бит, 24 бит'),
					array('key' => 'pages', 'label' => 'Количество страниц', 'placeholder' => '24'),
					array('key' => 'size', 'label' => 'Размер', 'placeholder' => '500 МБ', 'desc' => $size_desc),
				)),
			),
			'design' => array(
				'related' => array(array('title' => 'Подобные раздачи', 'query' => 'Поисковое слово, название')),
				'watch' => array(array('title' => 'Полезная информация', 'url' => 'https://ссылка')),
				'tabs' => array(
					array('title' => 'Содержание', 'content' => "01. Перечень\n[url=https://полноразмерный_скриншот]Страница-1[/url] | [url=https://полноразмерный_скриншот]Страница-2[/url]"),
					array('title' => 'Интересно', 'content' => ''),
					array('title' => 'Информация', 'content' => ''),
					array('title' => 'Скриншоты', 'content' => ''),
				),
			),
		),
		'graphic' => array(
			'sections' => array(
				array('fields' => array(
					array('key' => 'title', 'label' => 'Название', 'placeholder' => 'Название'),
					array('key' => 'original_title', 'label' => 'Оригинальное название', 'placeholder' => 'Title'),
					array('key' => 'year', 'label' => 'Год выпуска', 'placeholder' => '2009'),
					array('key' => 'genre', 'label' => 'Жанр', 'placeholder' => 'Фотографии'),
					array('key' => 'released', 'label' => 'Выпущено', 'placeholder' => 'Название издательства'),
					array('key' => 'compiler', 'label' => 'Составитель', 'placeholder' => ''),
				)),
				array('fields' => array(
					array('key' => 'about', 'label' => 'Описание', 'placeholder' => 'Описание раздачи...', 'textarea' => 10, 'desc' => $desc),
				)),
				array('fields' => array(
					array('key' => 'format', 'label' => 'Формат', 'placeholder' => 'JPEG'),
					array('key' => 'image_size', 'label' => 'Размеры изображений', 'placeholder' => '5000х4002, 6600х3004'),
					array('key' => 'resolution', 'label' => 'Разрешение', 'placeholder' => '72 пикс/дюйм, 96 пикс/дюйм'),
					array('key' => 'color_depth', 'label' => 'Глубина цвета', 'placeholder' => 'от 2 бит до 24 бит'),
					array('key' => 'count', 'label' => 'Количество', 'placeholder' => '920'),
					array('key' => 'size', 'label' => 'Размер', 'placeholder' => '887 МБ', 'desc' => $size_desc),
				)),
			),
			'design' => array(
				'related' => array(array('title' => 'Подобные раздачи', 'query' => 'Поисковое слово, название')),
				'watch' => array(array('title' => 'Полезная информация', 'url' => 'https://ссылка')),
				'tabs' => array(
					array('title' => 'Обзор', 'content' => "Сводные листы изображений:\n[url=https://сводный_лист]Лист-1[/url] | [url=https://сводный_лист]Лист-2[/url] | [url=https://сводный_лист]Лист-3[/url]"),
					array('title' => 'Скриншоты', 'content' => ''),
				),
			),
		),
	);

	foreach ($specs as $kind => $spec) {
		for ($i = 0; $i < 3; $i++) {
			$specs[$kind]['advanced'][$i] = upload_fields_to_advanced($spec['sections'][$i]['fields']);
		}
		$specs[$kind]['advanced'][3] = upload_design_to_advanced($spec['design']);
	}

	return $specs;
}

function upload_table_exists($table)
{
	static $cache = array();
	$table = trim((string)$table);
	if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
		return false;
	}
	if (!empty($cache[$table])) {
		return true;
	}

	$res = sql_query("SHOW TABLES LIKE " . sqlesc($table));
	$exists = $res && mysqli_num_rows($res) > 0;
	if ($exists) {
		$cache[$table] = true;
	}
	return $exists;
}

function upload_table_column_exists($table, $column)
{
	static $cache = array();
	$table = trim((string)$table);
	$column = trim((string)$column);
	if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
		return false;
	}
	$key = $table . '.' . $column;
	if (!empty($cache[$key])) {
		return true;
	}

	$res = sql_query("SHOW COLUMNS FROM `" . $table . "` LIKE " . sqlesc($column));
	$exists = $res && mysqli_num_rows($res) > 0;
	if ($exists) {
		$cache[$key] = true;
	}
	return $exists;
}

function upload_ensure_schema()
{
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;

	if (!defined('KZ_AUTO_MIGRATIONS') || KZ_AUTO_MIGRATIONS !== true) {
		return;
	}

	sql_query("
		CREATE TABLE IF NOT EXISTS torrent_details (
			tid int(10) unsigned NOT NULL,
			release_kind varchar(20) NOT NULL DEFAULT 'video',
			poster_url text NOT NULL,
			rgroup int(10) unsigned NOT NULL DEFAULT 0,
			rgroup_button varchar(255) NOT NULL DEFAULT '',
			torrent_file_updated_at datetime NULL DEFAULT NULL,
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

	if (!upload_table_column_exists('torrent_details', 'torrent_file_updated_at')) {
		sql_query("ALTER TABLE torrent_details ADD torrent_file_updated_at datetime NULL DEFAULT NULL AFTER rgroup_button") or sqlerr(__FILE__, __LINE__);
	}
}

function upload_load_details($tid)
{
	$tid = (int)$tid;
	$details = array(
		'exists' => false,
		'tid' => $tid,
		'release_kind' => 'video',
		'poster_url' => '',
		'rgroup' => 0,
		'rgroup_button' => '',
		'torrent_file_updated_at' => '',
		'form_mode' => 0,
		'section_modes' => '0,0,0,0',
		'data' => upload_default_data(),
	);

	if ($tid <= 0 || !upload_table_exists('torrent_details')) {
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
	$details['torrent_file_updated_at'] = (string)($row['torrent_file_updated_at'] ?? '');
	$details['form_mode'] = (int)$row['form_mode'];
	$details['section_modes'] = (string)$row['section_modes'];
	$details['data'] = array_replace_recursive(upload_default_data(), $data);

	return $details;
}

function upload_save_details($tid, $kind, $poster_url, $rgroup, $rgroup_button, array $data)
{
	upload_ensure_schema();

	$tid = (int)$tid;
	$kind = upload_normalize_kind($kind);
	$poster_url = trim((string)$poster_url);
	$rgroup = (int)$rgroup;
	$rgroup_button = trim((string)$rgroup_button);
	$form_mode = (int)($data['mode'] ?? 0);
	$section_modes = upload_normalize_section_modes($data['section_modes'] ?? array());
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

function upload_mark_torrent_file_updated($tid)
{
	upload_ensure_schema();
	sql_query("UPDATE torrent_details SET torrent_file_updated_at = NOW(), updated_at = NOW() WHERE tid = " . (int)$tid) or sqlerr(__FILE__, __LINE__);
}

function upload_normalize_kind($kind)
{
	$kind = (string)$kind;
	$kinds = upload_kinds();
	return isset($kinds[$kind]) ? $kind : 'video';
}

function upload_kind_by_category($category)
{
	$category = (int)$category;
	foreach (upload_categories() as $kind => $items) {
		if (isset($items[$category])) {
			return $kind;
		}
	}

	return 'video';
}

function upload_is_valid_category($kind, $category)
{
	$kind = upload_normalize_kind($kind);
	$category = (int)$category;
	$categories = upload_categories();
	return isset($categories[$kind][$category]);
}

function upload_normalize_section_modes($modes)
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

function upload_post_text($array, $key, $default = '')
{
	if (!is_array($array) || !array_key_exists($key, $array)) {
		return $default;
	}

	return trim((string)$array[$key]);
}

function upload_collect_pairs($titles, $values, $value_key)
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

function upload_collect_design(array $design, array $defaults = array())
{
	$out = array_replace_recursive(upload_empty_design(), $defaults);
	$out['related'] = upload_collect_pairs($design['related_title'] ?? array(), $design['related_query'] ?? array(), 'query');
	$out['watch'] = upload_collect_pairs($design['watch_title'] ?? array(), $design['watch_url'] ?? array(), 'url');
	$out['tabs'] = upload_collect_pairs($design['tab_title'] ?? array(), $design['tab_content'] ?? array(), 'content');
	$out['screens'] = upload_post_text($design, 'screens');
	$out['notes'] = upload_post_text($design, 'notes');
	$out['imdb'] = array(
		'enabled' => !empty($design['imdb_enabled']) ? 1 : 0,
		'url' => upload_post_text($design, 'imdb_url'),
		'rating' => upload_post_text($design, 'imdb_rating'),
	);
	$out['kinopoisk'] = array(
		'enabled' => !empty($design['kinopoisk_enabled']) ? 1 : 0,
		'url' => upload_post_text($design, 'kinopoisk_url'),
		'rating' => upload_post_text($design, 'kinopoisk_rating'),
	);
	$out = upload_autofill_external_ratings($out);

	return $out;
}

function upload_autofill_external_ratings(array $design)
{
	foreach (array('imdb', 'kinopoisk') as $key) {
		if (empty($design[$key]['enabled']) || trim((string)($design[$key]['url'] ?? '')) === '') {
			continue;
		}
		if (trim((string)($design[$key]['rating'] ?? '')) !== '') {
			continue;
		}

		$rating = $key === 'imdb'
			? upload_fetch_imdb_rating($design[$key]['url'], 2)
			: upload_fetch_kinopoisk_rating($design[$key]['url'], 2);
		if ($rating !== '') {
			$design[$key]['rating'] = $rating;
		}
	}

	return $design;
}

function upload_fetch_rating_url($url, $timeout = 2)
{
	$url = trim((string)$url);
	if ($url === '' || !preg_match('#^https?://#i', $url)) {
		return '';
	}
	$timeout = max(1, min(4, (int)$timeout));

	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => min(1, $timeout),
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
			CURLOPT_HTTPHEADER => array(
				'Accept: text/html,application/json,image/*,*/*',
				'Accept-Language: ru-RU,ru;q=0.9,en;q=0.8',
			),
		));
		$body = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);
		if (is_string($body) && $body !== '' && $code >= 200 && $code < 300) {
			return substr($body, 0, 1024 * 1024);
		}
	}

	$context = stream_context_create(array('http' => array(
		'timeout' => $timeout,
		'header' => "User-Agent: Mozilla/5.0\r\nAccept-Language: ru-RU,ru;q=0.9,en;q=0.8\r\n",
	)));
	$body = @file_get_contents($url, false, $context);
	return is_string($body) ? substr($body, 0, 1024 * 1024) : '';
}

function upload_rating_cache_path($service, $id)
{
	$id = preg_replace('/[^a-z0-9_-]+/i', '', (string)$id);
	if ($id === '') {
		return '';
	}
	return ROOT_PATH . 'cache/rating_' . $service . '_' . $id . '.json';
}

function upload_rating_cache_get($service, $id)
{
	$path = upload_rating_cache_path($service, $id);
	if ($path === '' || !is_file($path) || filemtime($path) < time() - 86400) {
		return '';
	}
	$data = json_decode((string)file_get_contents($path), true);
	if (!is_array($data)) {
		return '';
	}

	$legacy_key = $service === 'kinopoisk' ? 'kp' : $service;
	return trim((string)($data['rating'] ?? ($data[$legacy_key] ?? '')));
}

function upload_rating_cache_has_recent_miss($service, $id, $ttl = 3600)
{
	$path = upload_rating_cache_path($service, $id);
	if ($path === '' || !is_file($path) || filemtime($path) < time() - (int)$ttl) {
		return false;
	}

	$data = json_decode((string)file_get_contents($path), true);
	return is_array($data) && !empty($data['miss']);
}

function upload_rating_cache_set($service, $id, $rating)
{
	$path = upload_rating_cache_path($service, $id);
	$rating = upload_normalize_external_rating($rating);
	if ($path === '' || $rating === '') {
		return;
	}
	@file_put_contents($path, json_encode(array('rating' => $rating), JSON_UNESCAPED_UNICODE));
}

function upload_rating_cache_set_miss($service, $id)
{
	$path = upload_rating_cache_path($service, $id);
	if ($path === '') {
		return;
	}

	@file_put_contents($path, json_encode(array('miss' => 1), JSON_UNESCAPED_UNICODE));
}

function upload_normalize_external_rating($value)
{
	$value = str_replace(',', '.', trim((string)$value));
	if (!preg_match('/[0-9]+(?:\.[0-9]+)?/', $value, $m)) {
		return '';
	}
	$rating = (float)$m[0];
	if ($rating <= 0 || $rating > 10) {
		return '';
	}
	return number_format($rating, 1, '.', '');
}

function upload_fetch_imdb_rating($url, $timeout = 2)
{
	if (!preg_match('#imdb\.com/title/(tt[0-9]+)#i', (string)$url, $m)) {
		return '';
	}
	$id = strtolower($m[1]);
	$cached = upload_rating_cache_get('imdb', $id);
	if ($cached !== '') {
		return $cached;
	}
	if (upload_rating_cache_has_recent_miss('imdb', $id)) {
		return '';
	}

	$json_url = 'https://p.media-imdb.com/static-content/documents/v1/title/' . rawurlencode($id) . '/ratings%3Fjsonp=imdb.rating.run:imdb.api.title.ratings/data.json';
	$body = upload_fetch_rating_url($json_url, $timeout);
	if (substr($body, 0, 2) === "\x1f\x8b") {
		$decoded = @gzdecode($body);
		if (is_string($decoded)) {
			$body = $decoded;
		}
	}
	if (preg_match('/"rating"\s*:\s*([0-9]+(?:\.[0-9]+)?)/i', $body, $rm)
		|| preg_match('/"ratingValue"\s*:\s*"?([0-9]+(?:\.[0-9]+)?)/i', $body, $rm)) {
		$rating = upload_normalize_external_rating($rm[1]);
		upload_rating_cache_set('imdb', $id, $rating);
		return $rating;
	}

	upload_rating_cache_set_miss('imdb', $id);
	return '';
}

function upload_fetch_kinopoisk_rating($url, $timeout = 2)
{
	if (!preg_match('#kinopoisk\.ru/(?:film|series)/(?:[a-z0-9_-]+-)?([0-9]+)#i', (string)$url, $m)) {
		return '';
	}
	$id = $m[1];
	$cached = upload_rating_cache_get('kinopoisk', $id);
	if ($cached !== '') {
		return $cached;
	}
	if (upload_rating_cache_has_recent_miss('kinopoisk', $id)) {
		return '';
	}

	$xml = upload_fetch_rating_url('https://rating.kinopoisk.ru/' . rawurlencode($id) . '.xml', $timeout);
	if (substr($xml, 0, 2) === "\x1f\x8b") {
		$decoded = @gzdecode($xml);
		if (is_string($decoded)) {
			$xml = $decoded;
		}
	}
	if (preg_match('#<kp_rating\b[^>]*>\s*([0-9]+(?:[.,][0-9]+)?)\s*</kp_rating>#iu', $xml, $rm)) {
		$rating = upload_normalize_external_rating($rm[1]);
		upload_rating_cache_set('kinopoisk', $id, $rating);
		return $rating;
	}

	$gif = upload_fetch_rating_url('https://rating.kinopoisk.ru/' . rawurlencode($id) . '.gif', 1);
	$rating = upload_kinopoisk_rating_from_gif($gif);
	if ($rating !== '') {
		upload_rating_cache_set('kinopoisk', $id, $rating);
		return $rating;
	}

	$body = upload_fetch_rating_url('https://widgets.kinopoisk.ru/discovery/api/trailer?filmId=' . rawurlencode($id), 1);
	if (preg_match('/"ratingValue"\s*:\s*"?([0-9]+(?:[.,][0-9]+)?)/iu', $body, $rm)
		|| preg_match('/rating[^0-9]{0,80}([0-9]+[.,][0-9]+)/iu', $body, $rm)
		|| preg_match('/' . preg_quote($id, '/') . '.{0,500}?([0-9]+[.,][0-9]+)/u', $body, $rm)) {
		$rating = upload_normalize_external_rating($rm[1]);
		upload_rating_cache_set('kinopoisk', $id, $rating);
		return $rating;
	}

	upload_rating_cache_set_miss('kinopoisk', $id);
	return '';
}

function upload_kinopoisk_rating_from_gif($gif)
{
	if (!function_exists('imagecreatefromstring') || !is_string($gif) || $gif === '') {
		return '';
	}

	$im = @imagecreatefromstring($gif);
	if (!$im) {
		return '';
	}

	$min_x = 999;
	$min_y = 999;
	$max_x = -1;
	$max_y = -1;
	for ($y = 17; $y < 31; $y++) {
		for ($x = 0; $x < 36; $x++) {
			if (upload_kinopoisk_gif_dark_pixel($im, $x, $y)) {
				$min_x = min($min_x, $x);
				$min_y = min($min_y, $y);
				$max_x = max($max_x, $x);
				$max_y = max($max_y, $y);
			}
		}
	}

	if ($max_x < $min_x || $max_y < $min_y) {
		return '';
	}

	$columns = array();
	for ($x = $min_x; $x <= $max_x; $x++) {
		$has = false;
		for ($y = $min_y; $y <= $max_y; $y++) {
			if (upload_kinopoisk_gif_dark_pixel($im, $x, $y)) {
				$has = true;
				break;
			}
		}
		$columns[$x] = $has;
	}

	$segments = array();
	$start = null;
	foreach ($columns as $x => $has) {
		if ($has && $start === null) {
			$start = $x;
		} elseif (!$has && $start !== null) {
			$segments[] = array($start, $x - 1);
			$start = null;
		}
	}
	if ($start !== null) {
		$segments[] = array($start, $max_x);
	}

	$out = '';
	foreach ($segments as $segment) {
		$width = $segment[1] - $segment[0] + 1;
		if ($width <= 2) {
			if ($out !== '' && strpos($out, '.') === false) {
				$out .= '.';
			}
			continue;
		}
		if (strlen(str_replace('.', '', $out)) >= 2) {
			break;
		}

		$digit = upload_kinopoisk_ocr_digit($im, $segment[0], $segment[1], $min_y, $max_y);
		if ($digit === '') {
			return '';
		}
		$out .= $digit;
	}

	return upload_normalize_external_rating($out);
}

function upload_kinopoisk_gif_dark_pixel($im, $x, $y)
{
	$rgb = imagecolorat($im, $x, $y);
	$colors = imagecolorsforindex($im, $rgb);
	$r = (int)($colors['red'] ?? 255);
	$g = (int)($colors['green'] ?? 255);
	$b = (int)($colors['blue'] ?? 255);

	return $r < 140 && $g < 140 && $b < 140;
}

function upload_kinopoisk_ocr_digit($im, $x1, $x2, $y1, $y2)
{
	$lines = array();
	for ($y = $y1; $y <= $y2; $y++) {
		$line = '';
		for ($x = $x1; $x <= $x2; $x++) {
			$line .= upload_kinopoisk_gif_dark_pixel($im, $x, $y) ? '#' : ' ';
		}
		$lines[] = rtrim($line);
	}

	$pattern = implode("\n", $lines);
	$templates = array(
		'2' => " ####\n##  ##\n    ##\n    ##\n   ###\n   ##\n  ###\n ##",
		'6' => "  ####\n ##\n##\n######\n##  ##\n##  ###\n##  ###\n##  ##",
		'7' => "#######\n    ##\n   ###\n   ##\n  ###\n  ##\n ###\n ##",
		'8' => "  ####\n ##  ##\n ##  ###\n ##  ##\n  #####\n ##  ###\n###  ###\n ##  ##",
	);

	$best_digit = '';
	$best_score = 9999;
	foreach ($templates as $digit => $template) {
		$score = levenshtein($pattern, $template);
		if ($score < $best_score) {
			$best_score = $score;
			$best_digit = $digit;
		}
	}

	return $best_score <= 8 ? $best_digit : '';
}

function upload_collect_post($torrent_size = 0)
{
	$data = upload_default_data();
	$kind = upload_normalize_kind($_POST['kind'] ?? 'video');
	$mode = !empty($_POST['mode']) ? 1 : 0;
	$section_modes = upload_normalize_section_modes($_POST['section_mode'] ?? array());

	$data['mode'] = $mode;
	$data['section_modes'] = $section_modes;

	$advanced = is_array($_POST['advanced'] ?? null) ? $_POST['advanced'] : array();
	for ($i = 1; $i <= 4; $i++) {
		$data['advanced']['desc' . $i] = upload_post_text($advanced, 'desc' . $i);
	}

	$video = is_array($_POST['video'] ?? null) ? $_POST['video'] : array();
	foreach ($data['video'] as $key => $value) {
		$data['video'][$key] = upload_post_text($video, $key);
	}

	if ($data['video']['size'] === '' && $torrent_size > 0) {
		$data['video']['size'] = mksize($torrent_size);
	}

	$design = is_array($_POST['design'] ?? null) ? $_POST['design'] : array();
	$data['design'] = upload_collect_design($design, $data['design']);

	$generic = is_array($_POST['generic'] ?? null) ? $_POST['generic'] : array();
	for ($i = 1; $i <= 4; $i++) {
		$data['generic']['desc' . $i] = upload_post_text($generic, 'desc' . $i);
	}

	$posted_templates = is_array($_POST['templates'] ?? null) ? $_POST['templates'] : array();
	$specs = upload_release_specs();
	foreach ($specs as $template_kind => $spec) {
		$template_post = is_array($posted_templates[$template_kind] ?? null) ? $posted_templates[$template_kind] : array();
		$fields_post = is_array($template_post['fields'] ?? null) ? $template_post['fields'] : array();
		$advanced_post = is_array($template_post['advanced'] ?? null) ? $template_post['advanced'] : array();
		$design_post = is_array($template_post['design'] ?? null) ? $template_post['design'] : array();

		foreach (upload_release_field_keys($spec) as $field_key) {
			$data['templates'][$template_kind]['fields'][$field_key] = upload_post_text($fields_post, $field_key);
		}
		for ($i = 1; $i <= 4; $i++) {
			$data['templates'][$template_kind]['advanced']['desc' . $i] = upload_post_text($advanced_post, 'desc' . $i);
		}
		$data['templates'][$template_kind]['design'] = upload_collect_design($design_post, $spec['design']);
	}

	upload_apply_torrent_size($data, $kind, $torrent_size);

	return array($kind, $data);
}

function upload_release_field_keys(array $spec)
{
	$keys = array();
	foreach (($spec['sections'] ?? array()) as $section) {
		foreach (($section['fields'] ?? array()) as $field) {
			$key = (string)($field['key'] ?? '');
			if ($key !== '' && !in_array($key, $keys, true)) {
				$keys[] = $key;
			}
		}
	}

	return $keys;
}

function upload_apply_torrent_size(array &$data, $kind, $torrent_size)
{
	$torrent_size = (int)$torrent_size;
	if ($torrent_size <= 0) {
		return;
	}

	if ($kind === 'video') {
		if (trim((string)($data['video']['size'] ?? '')) === '') {
			$data['video']['size'] = mksize($torrent_size);
		}
		return;
	}

	if (isset($data['templates'][$kind]['fields']['size']) && trim((string)$data['templates'][$kind]['fields']['size']) === '') {
		$data['templates'][$kind]['fields']['size'] = mksize($torrent_size);
	}
}

function upload_line($label, $value)
{
	$value = trim((string)$value);
	if ($value === '') {
		return '';
	}

	return '[b]' . $label . ':[/b] ' . $value;
}

function upload_section_from_lines(array $lines)
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

function upload_fields_to_advanced(array $fields, array $values = array())
{
	$lines = array();
	foreach ($fields as $field) {
		$label = trim((string)($field['label'] ?? ''));
		$key = trim((string)($field['key'] ?? ''));
		if ($label === '' || $key === '') {
			continue;
		}
		$value = array_key_exists($key, $values)
			? trim((string)$values[$key])
			: trim((string)($field['placeholder'] ?? ''));
		$lines[] = '[b]' . $label . ':[/b]' . ($value !== '' ? ' ' . $value : '');
	}

	return implode("\n", $lines);
}

function upload_design_to_advanced(array $design)
{
	$parts = array();
	foreach (($design['related'] ?? array()) as $item) {
		$title = trim((string)($item['title'] ?? ''));
		$query = trim((string)($item['query'] ?? ''));
		if ($title !== '' && $query !== '') {
			$parts[] = '[searchm=' . $title . ']' . $query . '[/searchm]';
		}
	}
	foreach (($design['watch'] ?? array()) as $item) {
		$title = trim((string)($item['title'] ?? ''));
		$url = trim((string)($item['url'] ?? ''));
		if ($title !== '' && $url !== '') {
			$parts[] = '[linkm=' . $title . ']' . $url . '[/linkm]';
		}
	}
	foreach (($design['tabs'] ?? array()) as $tab) {
		$title = trim((string)($tab['title'] ?? ''));
		$content = trim((string)($tab['content'] ?? ''));
		if ($title !== '' && $content !== '') {
			$parts[] = '[pagesd=' . $title . "]\n" . $content . "\n[/pagesd]";
		}
	}

	return trim(implode("\n", $parts));
}

function upload_build_description(array $data, $kind, $torrent_name = '', $torrent_size = 0)
{
	$kind = upload_normalize_kind($kind);
	$section_modes = upload_normalize_section_modes($data['section_modes'] ?? array());
	$advanced = $data['advanced'] ?? array();

	if ($kind !== 'video') {
		$specs = upload_release_specs();
		$spec = $specs[$kind] ?? null;
		if (!$spec) {
			return '';
		}
		$template = array_replace_recursive(array(
			'fields' => array(),
			'advanced' => upload_empty_advanced(),
			'design' => upload_empty_design(),
		), $data['templates'][$kind] ?? array());
		$fields = $template['fields'] ?? array();
		$template_advanced = $template['advanced'] ?? array();
		$normal = array();
		for ($i = 0; $i < 3; $i++) {
			$normal[$i] = upload_fields_to_advanced($spec['sections'][$i]['fields'], $fields);
		}
		$normal[3] = upload_build_design_bbcode($template['design'] ?? array());

		$parts = array();
		for ($i = 0; $i < 4; $i++) {
			$value = !empty($section_modes[$i]) ? trim((string)($template_advanced['desc' . ($i + 1)] ?? '')) : trim((string)($normal[$i] ?? ''));
			if ($value === '' && !empty($section_modes[$i])) {
				$value = trim((string)($normal[$i] ?? ''));
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
	$normal[0] = upload_section_from_lines(array(
		upload_line('Название', $video['title'] ?? ''),
		upload_line('Оригинальное название', $video['original_title'] ?? ''),
		upload_line('Год выпуска', $video['year'] ?? ''),
		upload_line('Жанр', $video['genre'] ?? ''),
		upload_line('Выпущено', $video['released'] ?? ''),
		upload_line('Режиссер', $video['director'] ?? ''),
		upload_line('В ролях', $video['cast'] ?? ''),
	));
	$normal[1] = upload_section_from_lines(array(
		upload_line('О фильме', $video['about'] ?? ''),
	));
	$normal[2] = upload_section_from_lines(array(
		upload_line('Качество', $video['quality'] ?? ''),
		upload_line('Видео', $video['video'] ?? ''),
		upload_line('Аудио', $video['audio'] ?? ''),
		upload_line('Размер', $video['size'] ?? ''),
		upload_line('Продолжительность', $video['duration'] ?? ''),
		upload_line('Перевод', $video['translation'] ?? ''),
		upload_line('Язык', $video['language'] ?? ''),
		upload_line('Субтитры', $video['subtitles'] ?? ''),
	));
	$normal[3] = upload_build_design_bbcode($design);

	$parts = array();
	for ($i = 0; $i < 4; $i++) {
		$value = !empty($section_modes[$i]) ? trim((string)($advanced['desc' . ($i + 1)] ?? '')) : trim((string)($normal[$i] ?? ''));
		if ($value === '' && !empty($section_modes[$i])) {
			$value = trim((string)($normal[$i] ?? ''));
		}
		if ($value !== '') {
			$parts[] = $value;
		}
	}

	if (!$parts && trim((string)$torrent_name) !== '') {
		$parts[] = upload_line('Название', $torrent_name);
	}

	return trim(implode("\n\n", $parts));
}

function upload_build_design_bbcode(array $design)
{
	$parts = array();
	$related_lines = array();
	foreach (($design['related'] ?? array()) as $item) {
		$title = trim((string)($item['title'] ?? ''));
		$query = trim((string)($item['query'] ?? ''));
		if ($title !== '' && $query !== '') {
			$related_lines[] = '[searchm=' . $title . ']' . $query . '[/searchm]';
		}
	}
	if ($related_lines) {
		$parts[] = implode("\n", $related_lines);
	}

	$imdb = $design['imdb'] ?? array();
	if (!empty($imdb['enabled']) && trim((string)($imdb['url'] ?? '')) !== '') {
		$parts[] = upload_rating_line('IMDb', $imdb['url'], $imdb['rating'] ?? '');
	}

	$kinopoisk = $design['kinopoisk'] ?? array();
	if (!empty($kinopoisk['enabled']) && trim((string)($kinopoisk['url'] ?? '')) !== '') {
		$parts[] = upload_rating_line('КиноПоиск', $kinopoisk['url'], $kinopoisk['rating'] ?? '');
	}

	$watch_lines = array();
	foreach (($design['watch'] ?? array()) as $item) {
		$title = trim((string)($item['title'] ?? ''));
		$url = trim((string)($item['url'] ?? ''));
		if ($title !== '' && $url !== '') {
			$watch_lines[] = '[linkm=' . $title . ']' . $url . '[/linkm]';
		}
	}
	if ($watch_lines) {
		$parts[] = implode("\n", $watch_lines);
	}

	foreach (($design['tabs'] ?? array()) as $tab) {
		$title = trim((string)($tab['title'] ?? ''));
		$content = trim((string)($tab['content'] ?? ''));
		if ($title !== '' && $content !== '') {
			$parts[] = '[pagesd=' . $title . "]\n" . $content . "\n[/pagesd]";
		}
	}

	$screens = upload_screens_to_bbcode($design['screens'] ?? '');
	if ($screens !== '') {
		$parts[] = $screens;
	}

	$notes = trim((string)($design['notes'] ?? ''));
	if ($notes !== '') {
		$parts[] = $notes;
	}

	return trim(implode("\n\n", $parts));
}

function upload_build_design_section(array $design)
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
		$parts[] = upload_rating_line('IMDb', $imdb['url'], $imdb['rating'] ?? '');
	}

	$kinopoisk = $design['kinopoisk'] ?? array();
	if (!empty($kinopoisk['enabled']) && trim((string)($kinopoisk['url'] ?? '')) !== '') {
		$parts[] = upload_rating_line('КиноПоиск', $kinopoisk['url'], $kinopoisk['rating'] ?? '');
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

	$screens = upload_screens_to_bbcode($design['screens'] ?? '');
	if ($screens !== '') {
		$parts[] = $screens;
	}

	$notes = trim((string)($design['notes'] ?? ''));
	if ($notes !== '') {
		$parts[] = $notes;
	}

	return trim(implode("\n\n", $parts));
}

function upload_rating_line($title, $url, $rating)
{
	$url = trim((string)$url);
	$rating = trim((string)$rating);

	if ($rating !== '') {
		return '[b]' . $title . ':[/b] [url=' . $url . ']' . $rating . '[/url]';
	}

	return '[b]' . $title . ':[/b] [url=' . $url . ']' . $url . '[/url]';
}

function upload_screens_to_bbcode($value)
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

function upload_meta_description($descr)
{
	$text = trim(preg_replace('#\s+#u', ' ', strip_tags((string)$descr)));
	$text = preg_replace('#\[(?:/?[a-z0-9]+(?:=[^\]]+)?)\]#iu', '', $text);
	return mb_substr($text, 0, 250, 'UTF-8');
}

function upload_keywords(array $data, $kind, $name)
{
	if ($kind === 'video') {
		$video = $data['video'] ?? array();
		$parts = array($name, $video['original_title'] ?? '', $video['year'] ?? '', $video['genre'] ?? '');
		return trim(implode(', ', array_filter(array_map('trim', $parts))));
	}

	$fields = $data['templates'][$kind]['fields'] ?? array();
	$parts = array(
		$name,
		$fields['title'] ?? '',
		$fields['original_title'] ?? '',
		$fields['artist'] ?? '',
		$fields['album'] ?? '',
		$fields['author'] ?? '',
		$fields['year'] ?? '',
		$fields['genre'] ?? '',
	);
	return trim(implode(', ', array_filter(array_map('trim', $parts))));
}

function upload_release_name_part($value)
{
	$value = trim(preg_replace('#\s+#u', ' ', (string)$value));
	return trim($value, " \t\n\r\0\x0B/");
}

function upload_translation_short($translation)
{
	$translation = upload_release_name_part($translation);
	if ($translation === '') {
		return '';
	}

	if (preg_match('/^Дубл|^Р”СѓР±Р»/iu', $translation)) {
		return 'ДБ';
	}
	if (preg_match('/двух|РґРІСѓС…/iu', $translation)) {
		return 'ПД';
	}
	if (preg_match('/мног|РјРЅРѕРі/iu', $translation)) {
		return 'ПМ';
	}
	if (preg_match('/одног|РѕРґРЅРѕРі/iu', $translation)) {
		return 'ПО';
	}
	if (preg_match('/любител|Р›СЋР±РёС‚/iu', $translation)) {
		return 'ЛМ';
	}
	if (preg_match('/автор|РђРІС‚РѕСЂ/iu', $translation)) {
		return 'АВ';
	}
	if (preg_match('/оригинал|РћСЂРёРі/iu', $translation)) {
		return 'ОР';
	}

	return $translation;
}

function upload_generated_name(array $data, $kind)
{
	$kind = upload_normalize_kind($kind);
	if ($kind === 'video') {
		$video = $data['video'] ?? array();
		$fields = array(
			$video['title'] ?? '',
			$video['original_title'] ?? '',
			$video['year'] ?? '',
			upload_translation_short($video['translation'] ?? ''),
			$video['quality'] ?? '',
		);
	} else {
		$template = $data['templates'][$kind]['fields'] ?? array();
		$fields = array(
			$template['title'] ?? ($template['album'] ?? ''),
			$template['original_title'] ?? ($template['artist'] ?? ''),
			$template['year'] ?? '',
			$template['quality'] ?? ($template['audio'] ?? ''),
		);
	}

	$parts = array();
	foreach ($fields as $field) {
		$field = upload_release_name_part($field);
		if ($field !== '') {
			$parts[] = $field;
		}
	}

	return implode(' / ', $parts);
}

function upload_option_select($name, array $options, $selected, $class = 'w100p', $extra = '')
{
	$html = '<select name="' . h($name) . '" class="' . h($class) . '"' . ($extra ? ' ' . $extra : '') . '>';
	foreach ($options as $value => $label) {
		$html .= '<option value="' . h($value) . '"' . ((string)$value === (string)$selected ? ' selected="selected"' : '') . '>' . h($label) . '</option>';
	}
	$html .= '</select>';
	return $html;
}

function upload_input($name, $value, $placeholder = '')
{
	return '<input type="text" name="' . h($name) . '" value="' . h($value) . '" class="w100p up"' . ($placeholder !== '' ? ' placeholder="' . h($placeholder) . '"' : '') . '>';
}

function upload_textarea($name, $value, $rows = 6, $placeholder = '')
{
	return '<textarea name="' . h($name) . '" rows="' . (int)$rows . '" class="w100p up"' . ($placeholder !== '' ? ' placeholder="' . h($placeholder) . '"' : '') . '>' . h($value) . '</textarea>';
}

function upload_bbcode_editor($name, $value, $rows = 8)
{
	$buttons = array(
		'b' => 'B',
		'i' => 'I',
		'u' => 'U',
		's' => 'S',
		'quote' => 'Цитата',
		'url' => 'URL',
		'img' => 'IMG',
		'code' => 'Код',
		'center' => 'Центр',
	);
	$html = '<div class="upl-bbcode">';
	$html .= '<div class="upl-bbcode-toolbar">';
	foreach ($buttons as $tag => $label) {
		$html .= '<button type="button" class="upl-bbcode-button" data-tag="' . h($tag) . '" onclick="return Upl.insertBbcode(this, \'' . h($tag) . '\');">' . h($label) . '</button>';
	}
	$html .= '</div>';
	$html .= upload_textarea($name, $value, $rows);
	$html .= '</div>';
	return $html;
}

function upload_render_info_sidebar()
{
	global $CURUSER;

	$user = '<span class="u0">Гость</span>';
	if (!empty($CURUSER)) {
		$user = '<a href="/userdetails.php?id=' . (int)$CURUSER['id'] . '" class="u' . (int)$CURUSER['class'] . '">' . h($CURUSER['username']) . '</a>';
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

function upload_render_form($action, $submit_label, array $state, $is_edit = false)
{
	$name = (string)($state['name'] ?? '');
	$kind = upload_normalize_kind($state['kind'] ?? 'video');
	$category = (int)($state['category'] ?? 0);
	$details = $state['details'] ?? array();
	$data = array_replace_recursive(upload_default_data(), $details['data'] ?? array());
	$poster_url = (string)($details['poster_url'] ?? '');
	$rgroup = (int)($details['rgroup'] ?? 0);
	$rgroup_button = (string)($details['rgroup_button'] ?? '');
	$mode = (int)($data['mode'] ?? 0);
	$section_modes = upload_normalize_section_modes($data['section_modes'] ?? array());
	$allow_file = !empty($state['allow_file']);
	?>
	<form enctype="multipart/form-data" action="<?= h($action) ?>" method="post" name="upt" id="upt">
		<?php if ($is_edit) { ?>
			<input type="hidden" name="id" value="<?= (int)$state['id'] ?>">
			<?php if (!empty($state['returnto'])) { ?>
				<input type="hidden" name="returnto" value="<?= h($state['returnto']) ?>">
			<?php } ?>
		<?php } ?>
		<input type="hidden" name="kind" id="kind" value="<?= h($kind) ?>">
		<input type="hidden" name="type" id="type" value="<?= (int)$category ?>">
		<input type="hidden" name="mode" id="mode" value="<?= $mode ?>">
		<?php for ($i = 0; $i < 4; $i++) { ?>
			<input type="hidden" name="section_mode[<?= $i ?>]" id="section_mode_<?= $i ?>" value="<?= (int)$section_modes[$i] ?>">
		<?php } ?>

		<div class="bx1 upl">
			<ul class="men up">
				<li class="hdr">Название</li>
				<li>
					<input type="hidden" name="name" id="name" value="<?= h($name) ?>">
					<input type="text" id="generated_name" value="<?= h($name) ?>" class="w100p up" readonly="readonly">
					<div class="n">Название формируется автоматически из полей описания.</div>
				</li>
				<?php if (!$is_edit || $allow_file) { ?>
					<li class="hdr">Торрент-файл</li>
					<li><input type="file" name="file" size="80" class="w100p styled" accept=".torrent,application/x-bittorrent"></li>
				<?php } ?>
				<li class="hdr">Ссылка на постер</li>
				<li>
					<?= upload_input('imgl', $poster_url) ?>
					<div class="n">Ширина постера - 200 пикселей. Разместите постер на одном из <a href="//forum.kinozal.tv/showthread.php?t=78697" target="_blank" class="sba">хостингов изображений</a></div>
				</li>
			</ul>
		</div>

		<div class="bx1 upl">
			<ul class="men up">
				<li class="up_tmplt">Тип раздачи</li>
				<li><?php upload_render_kind_tabs($kind); ?></li>
			</ul>
		</div>

		<div class="bx1 upl">
			<ul class="men up">
				<li class="up_tmplt">Режим оформления</li>
				<li>
					<ul class="lis">
						<li id="mode_tab_0" class="bx1 up_tmpl<?= $mode ? ' sbab' : ' up_tmpls' ?>"><a href="#" onclick="Upl.changeMode(0); return false;">Обычный режим</a></li>
						<li id="mode_tab_1" class="bx1 up_tmpl<?= $mode ? ' up_tmpls' : ' sbab' ?>"><a href="#" onclick="Upl.changeMode(1); return false;">Расширенный режим</a></li>
					</ul>
					<div class="clr"></div>
				</li>
			</ul>
		</div>

		<div id="template_video"<?= $kind === 'video' ? '' : ' style="display:none;"' ?>>
			<?php upload_render_video_template($data, $section_modes); ?>
		</div>

		<?php foreach (upload_release_specs() as $template_kind => $spec) { ?>
			<div id="template_<?= h($template_kind) ?>"<?= $kind === $template_kind ? '' : ' style="display:none;"' ?>>
				<?php upload_render_release_template($template_kind, $data, $section_modes); ?>
			</div>
		<?php } ?>

		<div class="bx1 upl">
			<ul class="men up">
				<li class="hdr">Релиз-группа</li>
				<li>
					<select name="rgroup" class="w250 styled" onchange="document.forms['upt'].elements['rbut'].value = this.value == '0' ? '' : ('/pic/groupex/' + this.value + '.gif');">
						<option value="0">Выберите релиз-группу</option>
						<?php foreach (upload_release_groups() as $id => $label) { ?>
							<option value="<?= (int)$id ?>"<?= (int)$id === $rgroup ? ' selected="selected"' : '' ?>><?= h($label) ?></option>
						<?php } ?>
					</select>
				</li>
				<li class="hdr">Кнопка релиз-группы</li>
				<li>
					<input type="text" name="rbut" class="w100p up" value="<?= h($rgroup_button) ?>">
					<div class="n">Кнопка релиз-группы (88x31) или имя релиз-группы, если нет баннера. <a href="//forum.kinozal.tv/showthread.php?t=78697" target="_blank" class="sba">Список хостингов</a></div>
				</li>
			</ul>
		</div>

		<div class="bx1 upl">
			<ul class="men up">
				<li class="hdr">Раздел</li>
				<li><?php upload_render_category_selects($kind, $category); ?></li>
				<li class="hdr">Тип раздачи</li>
				<li><a href="//forum.kinozal.tv/showpost.php?p=2715225" class="sba" target="_blank">Правила включения золотых и серебряных раздач</a></li>
			</ul>
		</div>

		<?php if (!empty($state['service_controls'])) { ?>
			<div class="bx1 upl">
				<ul class="men up">
					<li class="hdr">Служебное</li>
					<li><?= $state['service_controls'] ?></li>
				</ul>
			</div>
		<?php } ?>

		<div class="bx1">
			<div class="u7">
				<input type="button" value="<?= h($submit_label) ?>" onclick="Upl.upload();" class="buttonS">
				<input type="button" value="Предварительный просмотр" onclick="Upl.test();" class="buttonS">
			</div>
		</div>
	</form>

	<?php upload_render_js($kind, $mode, $section_modes); ?>
	<?php
}

function upload_render_kind_tabs($current)
{
	echo '<ul class="lis">';
	foreach (upload_kinds() as $kind => $label) {
		echo '<li id="kind_tab_' . h($kind) . '" class="bx1 up_tmpl' . ($kind === $current ? ' up_tmpls' : ' sbab') . '"><a href="#" onclick="Upl.setTemplate(\'' . h($kind) . '\'); return false;">' . h($label) . '</a></li>';
	}
	echo '</ul><div class="clr"></div>';
}

function upload_render_category_selects($current_kind, $current_category)
{
	foreach (upload_categories() as $kind => $items) {
		echo '<select class="w250' . ($kind === $current_kind ? '' : ' up_hide') . '" id="type_' . h($kind) . '" onchange="Upl.syncType();">';
		if (count($items) > 1) {
			echo '<option value="0">Выберите раздел</option>';
		}
		foreach ($items as $id => $label) {
			echo '<option value="' . (int)$id . '"' . ((int)$id === (int)$current_category ? ' selected="selected"' : '') . '>' . h($label) . '</option>';
		}
		echo '</select>';
	}
}

function upload_render_video_template(array $data, array $section_modes)
{
	$video = $data['video'] ?? array();
	$advanced = $data['advanced'] ?? array();
	$design = $data['design'] ?? array();
	upload_render_section_start(0, 'Предварительное описание', $section_modes[0]);
	?>
	<table class="tables1 w100p upl-normal upl-section-0">
		<tr><td class="w175">Название:</td><td><?= upload_input('video[title]', $video['title'] ?? '', 'Название фильма') ?><div class="n">Для зарубежного видео, на русском языке</div></td></tr>
		<tr><td>Оригинальное название:</td><td><?= upload_input('video[original_title]', $video['original_title'] ?? '', 'Movie title') ?><div class="n">Название видео на языке оригинала</div></td></tr>
		<tr><td>Год выпуска:</td><td><?= upload_input('video[year]', $video['year'] ?? '', '2009') ?></td></tr>
		<tr><td>Жанр:</td><td><?= upload_input('video[genre]', $video['genre'] ?? '', 'Комедия, приключения') ?></td></tr>
		<tr><td>Выпущено:</td><td><?= upload_input('video[released]', $video['released'] ?? '', 'Страна, киностудия') ?></td></tr>
		<tr><td>Режиссер:</td><td><?= upload_input('video[director]', $video['director'] ?? '', 'Имя Фамилия') ?></td></tr>
		<tr><td>В ролях:</td><td><?= upload_textarea('video[cast]', $video['cast'] ?? '', 4, 'Имя Фамилия, Имя Фамилия') ?><div class="n">Список исполняющих роли через запятую</div></td></tr>
	</table>
	<div class="upl-advanced upl-section-0" style="display:none;"><?= upload_bbcode_editor('advanced[desc1]', $advanced['desc1'] ?? '', 8) ?></div>
	<?php
	upload_render_section_end();

	upload_render_section_start(1, 'Описание', $section_modes[1]);
	?>
	<table class="tables1 w100p upl-normal upl-section-1">
		<tr><td class="w175">О фильме:</td><td><?= upload_textarea('video[about]', $video['about'] ?? '', 10, 'Краткое описание фильма...') ?><div class="n">Рекомендуем писать собственное описание, а не копировать его из сети - это положительно скажется на количестве сидов и пиров.</div></td></tr>
	</table>
	<div class="upl-advanced upl-section-1" style="display:none;"><?= upload_bbcode_editor('advanced[desc2]', $advanced['desc2'] ?? '', 10) ?></div>
	<?php
	upload_render_section_end();

	upload_render_section_start(2, 'Технические данные', $section_modes[2]);
	?>
	<table class="tables1 w100p upl-normal upl-section-2">
		<tr><td class="w175">Качество:</td><td><?= upload_option_select('video[quality]', upload_quality_options(), $video['quality'] ?? '') ?><div class="n">Подробнее о качестве раздаваемого материала здесь</div></td></tr>
		<tr><td>Видео:</td><td><?= upload_input('video[video]', $video['video'] ?? '', 'MPEG-4 AVC, 9131 Кбит/с, 1920x1080') ?></td></tr>
		<tr><td>Аудио:</td><td><?= upload_input('video[audio]', $video['audio'] ?? '', 'Русский (AC3, 6 ch, 384 Кбит/с)') ?></td></tr>
		<tr><td>Размер:</td><td><?= upload_input('video[size]', $video['size'] ?? '', 'Установится автоматически после выбора торрент-файла') ?><div class="n">Если оставить поле пустым, размер будет взят из торрент-файла</div></td></tr>
		<tr><td>Продолжительность:</td><td><?= upload_input('video[duration]', $video['duration'] ?? '', '01:39:43') ?><div class="n">Точная продолжительность в формате ЧЧ:ММ:СС</div></td></tr>
		<tr><td>Перевод:</td><td><?= upload_option_select('video[translation]', upload_translation_options(), $video['translation'] ?? '') ?><div class="n">Для зарубежного видео. О видах перевода подробнее здесь</div></td></tr>
		<tr><td>Язык:</td><td><?= upload_option_select('video[language]', upload_language_options(), $video['language'] ?? '') ?><div class="n">Для отечественного видео</div></td></tr>
		<tr><td>Субтитры:</td><td><?= upload_option_select('video[subtitles]', upload_subtitle_options(), $video['subtitles'] ?? '') ?><div class="n">Укажите субтитры, если имеются</div></td></tr>
	</table>
	<div class="upl-advanced upl-section-2" style="display:none;"><?= upload_bbcode_editor('advanced[desc3]', $advanced['desc3'] ?? '', 8) ?></div>
	<?php
	upload_render_section_end();

	upload_render_section_start(3, 'Оформление, вкладки, примечания, скриншоты', $section_modes[3]);
	upload_render_design_fields($design);
	?>
	<div class="upl-advanced upl-section-3" style="display:none;"><?= upload_bbcode_editor('advanced[desc4]', $advanced['desc4'] ?? '', 31) ?></div>
	<?php
	upload_render_section_end();
}

function upload_render_section_start($index, $title, $advanced)
{
	?>
	<div class="bx1 upl">
		<ul class="men up">
			<li class="fhdr">
				<div class="sbab up_toggle" onclick="Upl.switchMode(<?= (int)$index ?>);">Сменить режим</div>
				<?= h($title) ?>
			</li>
		</ul>
		<div id="section_<?= (int)$index ?>" data-advanced="<?= $advanced ? 1 : 0 ?>">
	<?php
}

function upload_render_section_end()
{
	echo '</div></div>';
}

function upload_render_design_fields(array $design)
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
			<?php upload_render_pair_rows('design[related_title][]', 'design[related_query][]', $related, 'query', 2); ?>
		</table>
		<div class="pad0x0x5x0"><a href="#" class="sba" onclick="Upl.addPair('related_rows', 'design[related_title][]', 'design[related_query][]'); return false;">Добавить элемент</a></div>

		<ul class="men">
			<li class="tp2"><label><input type="checkbox" name="design[imdb_enabled]" value="1"<?= !empty($imdb['enabled']) ? ' checked="checked"' : '' ?>> Меню: рейтинг IMDb</label></li>
			<li>Укажите страницу фильма и его рейтинг на сайте <a href="https://www.imdb.com/" class="sba" target="_blank">IMDb</a>, если есть</li>
		</ul>
		<table class="tables1 w100p">
			<tr><td class="w50p b">Ссылка на фильм</td><td class="b">Цифры рейтинга</td></tr>
			<tr><td><input type="text" name="design[imdb_url]" class="w100p" value="<?= h($imdb['url'] ?? '') ?>" placeholder="https://www.imdb.com/title/tt00000/"></td><td><input type="text" name="design[imdb_rating]" class="w100p" value="<?= h($imdb['rating'] ?? '') ?>" placeholder="Цифры рейтинга"></td></tr>
		</table>

		<ul class="men">
			<li class="tp2"><label><input type="checkbox" name="design[kinopoisk_enabled]" value="1"<?= !empty($kinopoisk['enabled']) ? ' checked="checked"' : '' ?>> Меню: рейтинг КиноПоиск</label></li>
			<li>Укажите страницу фильма и его рейтинг на сайте <a href="https://www.kinopoisk.ru/" class="sba" target="_blank">КиноПоиск</a>, если есть</li>
		</ul>
		<table class="tables1 w100p">
			<tr><td class="w50p b">Ссылка на фильм</td><td class="b">Цифры рейтинга</td></tr>
			<tr><td><input type="text" name="design[kinopoisk_url]" class="w100p" value="<?= h($kinopoisk['url'] ?? '') ?>" placeholder="https://www.kinopoisk.ru/film/00000/"></td><td><input type="text" name="design[kinopoisk_rating]" class="w100p" value="<?= h($kinopoisk['rating'] ?? '') ?>" placeholder="Цифры рейтинга"></td></tr>
		</table>

		<ul class="men"><li class="tp2">Меню: ознакомление</li></ul>
		<table class="tables1 w100p" id="watch_rows">
			<tr><td class="w25p b">Заголовок ссылки</td><td class="b">Адрес ссылки</td><td class="w30"></td></tr>
			<?php upload_render_pair_rows('design[watch_title][]', 'design[watch_url][]', $watch, 'url', 2); ?>
		</table>
		<div class="pad0x0x5x0"><a href="#" class="sba" onclick="Upl.addPair('watch_rows', 'design[watch_title][]', 'design[watch_url][]'); return false;">Добавить элемент</a></div>

		<ul class="men">
			<li class="tp2">Дополнительные вкладки</li>
			<li class="n">Вы можете указать дополнительную информацию о раздаваемом материале. Допустимо использовать не больше шести вкладок</li>
		</ul>
		<?php upload_render_tab_panel('tab_rows', $tabs); ?>
		<div class="pad0x0x5x0"><a href="#" class="sba" onclick="Upl.addTab(); return false;">Добавить вкладку</a></div>

		<ul class="men"><li class="tp2">Скриншоты и примечания</li></ul>
		<table class="tables1 w100p">
			<tr><td class="w175">Скриншоты:</td><td><?= upload_textarea('design[screens]', $design['screens'] ?? '', 6, "Ссылки на изображения, по одной в строке") ?></td></tr>
			<tr><td>Примечания:</td><td><?= upload_textarea('design[notes]', $design['notes'] ?? '', 4) ?></td></tr>
		</table>
	</div>
	<?php
}

function upload_render_pair_rows($name1, $name2, array $rows, $value_key, $minimum)
{
	for ($i = 0; $i < max($minimum, count($rows)); $i++) {
		$row = $rows[$i] ?? array('title' => '', $value_key => '');
		echo '<tr><td><input type="text" name="' . h($name1) . '" class="w100p" value="' . h($row['title'] ?? '') . '"></td>';
		echo '<td><input type="text" name="' . h($name2) . '" class="w100p" value="' . h($row[$value_key] ?? '') . '"></td>';
		echo '<td class="center"><a href="#" class="sba" onclick="Upl.removeRow(this); return false;">×</a></td></tr>';
	}
}

function upload_render_tab_rows(array $rows, $title_name = 'design[tab_title][]', $content_name = 'design[tab_content][]')
{
	for ($i = 0; $i < max(1, count($rows)); $i++) {
		$row = $rows[$i] ?? array('title' => '', 'content' => '');
		echo '<tr><td><input type="text" name="' . h($title_name) . '" class="w100p" value="' . h($row['title'] ?? '') . '"></td>';
		echo '<td>' . upload_textarea($content_name, $row['content'] ?? '', 5) . '</td>';
		echo '<td class="center"><a href="#" class="sba" onclick="Upl.removeRow(this); return false;">×</a></td></tr>';
	}
}

function upload_render_tab_panel($id, array $rows, $title_name = 'design[tab_title][]', $content_name = 'design[tab_content][]')
{
	$count = max(1, count($rows));
	echo '<div class="up_bbtabs_body" id="' . h($id) . '" data-title-name="' . h($title_name) . '" data-content-name="' . h($content_name) . '">';
	echo '<ul class="up_bbtabs_tabs">';
	for ($i = 0; $i < $count; $i++) {
		$row = $rows[$i] ?? array('title' => '', 'content' => '');
		$title = trim((string)($row['title'] ?? ''));
		if ($title === '') {
			$title = $i === 0 ? 'Новая вкладка' : 'Вкладка ' . ($i + 1);
		}
		echo '<li class="up_bbtabs_tab' . ($i === 0 ? ' mn' : '') . '" data-index="' . (int)$i . '">';
		echo '<div class="up_bbtabs_tabtitle"><input class="up" type="text" name="' . h($title_name) . '" value="' . h($title) . '" maxlength="50" onchange="Upl.syncTabTitle(this);" onkeyup="Upl.syncTabTitle(this);"></div>';
		echo '<div class="up_bbtabs_del" title="Удалить вкладку" onclick="Upl.removeTab(this);"></div>';
		echo '</li>';
	}
	echo '<li class="up_bbtabs_add"><div class="up_bbtabs_plus" title="Добавить новую вкладку..." onclick="Upl.addTab(\'' . h($id) . '\', \'' . h($title_name) . '\', \'' . h($content_name) . '\');"></div></li>';
	echo '</ul>';
	for ($i = 0; $i < $count; $i++) {
		$row = $rows[$i] ?? array('title' => '', 'content' => '');
		echo '<div class="bx1 up_bbtabs_c" data-index="' . (int)$i . '"' . ($i === 0 ? '' : ' style="display:none;"') . '><div class="up_bbtabs_ctxt">';
		echo '<textarea name="' . h($content_name) . '" rows="12" class="up_bbtabs_txt">' . h($row['content'] ?? '') . '</textarea>';
		echo '</div></div>';
	}
	echo '<div class="up_clrl"></div></div>';
}

function upload_render_generic_template(array $data)
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
		echo '<div class="bx1"><ul class="men"><li class="tp2 b">' . h($labels[$i]) . '</li><li>' . upload_textarea('generic[desc' . $i . ']', $value, $i === 4 ? 14 : 8) . '</li></ul></div>';
	}
}

function upload_render_release_template($kind, array $data, array $section_modes)
{
	$specs = upload_release_specs();
	if (empty($specs[$kind])) {
		return;
	}
	$spec = $specs[$kind];
	$template = array_replace_recursive(array(
		'fields' => array(),
		'advanced' => upload_empty_advanced(),
		'design' => $spec['design'],
	), $data['templates'][$kind] ?? array());
	$fields = $template['fields'] ?? array();
	$advanced = $template['advanced'] ?? array();

	for ($i = 0; $i < 3; $i++) {
		upload_render_section_start($i, upload_section_title($i), $section_modes[$i]);
		echo '<table class="tables1 w100p upl-normal upl-section-' . (int)$i . '">';
		foreach (($spec['sections'][$i]['fields'] ?? array()) as $field) {
			$key = (string)$field['key'];
			$name = 'templates[' . $kind . '][fields][' . $key . ']';
			$value = $fields[$key] ?? '';
			$placeholder = $field['placeholder'] ?? '';
			$label = $field['label'] ?? '';
			$control = !empty($field['textarea'])
				? upload_textarea($name, $value, (int)$field['textarea'], $placeholder)
				: upload_input($name, $value, $placeholder);
			echo '<tr><td class="w175">' . h($label) . ':</td><td>' . $control;
			if (!empty($field['desc'])) {
				echo '<div class="n">' . $field['desc'] . '</div>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
		$default = upload_fields_to_advanced($spec['sections'][$i]['fields'], $fields);
		$value = (string)($advanced['desc' . ($i + 1)] ?? '');
		if ($value === '') {
			$value = $default;
		}
		echo '<div class="upl-advanced upl-section-' . (int)$i . '" style="display:none;">' . upload_bbcode_editor('templates[' . $kind . '][advanced][desc' . ($i + 1) . ']', $value, $i === 1 ? 10 : 8) . '</div>';
		upload_render_section_end();
	}

	upload_render_section_start(3, upload_section_title(3), $section_modes[3]);
	upload_render_release_design_fields($kind, array_replace_recursive($spec['design'], $template['design'] ?? array()));
	$default = upload_design_to_advanced(array_replace_recursive($spec['design'], $template['design'] ?? array()));
	$value = (string)($advanced['desc4'] ?? '');
	if ($value === '') {
		$value = $default;
	}
	echo '<div class="upl-advanced upl-section-3" style="display:none;">' . upload_bbcode_editor('templates[' . $kind . '][advanced][desc4]', $value, 20) . '</div>';
	upload_render_section_end();
}

function upload_section_title($index)
{
	$titles = array(
		'Предварительное описание',
		'Описание',
		'Технические данные',
		'Оформление, вкладки, примечания, скриншоты',
	);

	return $titles[(int)$index] ?? '';
}

function upload_render_release_design_fields($kind, array $design)
{
	$related = $design['related'] ?? array();
	$watch = $design['watch'] ?? array();
	$tabs = $design['tabs'] ?? array();
	$prefix = 'templates[' . $kind . '][design]';
	$related_id = 'related_rows_' . $kind;
	$watch_id = 'watch_rows_' . $kind;
	$tab_id = 'tab_rows_' . $kind;
	?>
	<div class="upl-normal upl-section-3">
		<ul class="men">
			<li class="tp2">Меню: поиск раздач</li>
			<li class="n">Добавьте ссылки на поиск других раздач, которые могут заинтересовать зрителей</li>
		</ul>
		<table class="tables1 w100p" id="<?= h($related_id) ?>">
			<tr><td class="w25p b">Заголовок ссылки</td><td class="b">Строка поиска</td><td class="w30"></td></tr>
			<?php upload_render_pair_rows($prefix . '[related_title][]', $prefix . '[related_query][]', $related, 'query', 1); ?>
		</table>
		<div class="pad0x0x5x0"><a href="#" class="sba" onclick="Upl.addPair('<?= h($related_id) ?>', '<?= h($prefix) ?>[related_title][]', '<?= h($prefix) ?>[related_query][]'); return false;">Добавить элемент</a></div>

		<ul class="men"><li class="tp2">Меню: ознакомление</li></ul>
		<table class="tables1 w100p" id="<?= h($watch_id) ?>">
			<tr><td class="w25p b">Заголовок ссылки</td><td class="b">Адрес ссылки</td><td class="w30"></td></tr>
			<?php upload_render_pair_rows($prefix . '[watch_title][]', $prefix . '[watch_url][]', $watch, 'url', 1); ?>
		</table>
		<div class="pad0x0x5x0"><a href="#" class="sba" onclick="Upl.addPair('<?= h($watch_id) ?>', '<?= h($prefix) ?>[watch_title][]', '<?= h($prefix) ?>[watch_url][]'); return false;">Добавить элемент</a></div>

		<ul class="men">
			<li class="tp2">Дополнительные вкладки</li>
			<li class="n">Вы можете указать дополнительную информацию о раздаваемом материале. Допустимо использовать не больше шести вкладок</li>
		</ul>
		<?php upload_render_tab_panel($tab_id, $tabs, $prefix . '[tab_title][]', $prefix . '[tab_content][]'); ?>
		<div class="pad0x0x5x0"><a href="#" class="sba" onclick="Upl.addTab('<?= h($tab_id) ?>', '<?= h($prefix) ?>[tab_title][]', '<?= h($prefix) ?>[tab_content][]'); return false;">Добавить вкладку</a></div>
	</div>
	<?php
}

function upload_render_js($kind, $mode, array $section_modes)
{
	?>
	<script type="text/javascript">
	var Upl = {
		insertBbcode: function(button, tag) {
			var editor = button;
			while (editor && (' ' + editor.className + ' ').indexOf(' upl-bbcode ') === -1) {
				editor = editor.parentNode;
			}
			if (!editor) return false;
			var textarea = editor.getElementsByTagName('textarea')[0];
			if (!textarea) return false;

			var start = typeof textarea.selectionStart === 'number' ? textarea.selectionStart : textarea.value.length;
			var end = typeof textarea.selectionEnd === 'number' ? textarea.selectionEnd : start;
			var selected = textarea.value.substring(start, end);
			var open = '[' + tag + ']';
			var close = '[/' + tag + ']';
			var replacement = open + selected + close;

			textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
			textarea.focus();
			if (typeof textarea.setSelectionRange === 'function') {
				if (selected === '') {
					textarea.setSelectionRange(start + open.length, start + open.length);
				} else {
					textarea.setSelectionRange(start, start + replacement.length);
				}
			}
			return false;
		},
		setTemplate: function(kind) {
			var kinds = ['video', 'music', 'game', 'audiobook', 'program', 'book', 'graphic'];
			document.getElementById('kind').value = kind;
			for (var i = 0; i < kinds.length; i++) {
				var k = kinds[i];
				var tab = document.getElementById('kind_tab_' + k);
				var sel = document.getElementById('type_' + k);
				if (tab) tab.className = 'bx1 up_tmpl' + (k === kind ? ' up_tmpls' : ' sbab');
				if (sel) sel.className = (k === kind ? 'w250' : 'w250 up_hide');
			}
			for (var j = 0; j < kinds.length; j++) {
				var tmpl = document.getElementById('template_' + kinds[j]);
				if (tmpl) tmpl.style.display = (kinds[j] === kind ? '' : 'none');
			}
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
			if (tab0) tab0.className = 'bx1 up_tmpl' + (mode ? ' sbab' : ' up_tmpls');
			if (tab1) tab1.className = 'bx1 up_tmpl' + (mode ? ' up_tmpls' : ' sbab');
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
		addTab: function(tableId, titleName, contentName) {
			tableId = tableId || 'tab_rows';
			titleName = titleName || 'design[tab_title][]';
			contentName = contentName || 'design[tab_content][]';
			var holder = document.getElementById(tableId);
			if (!holder) return false;
			if ((' ' + holder.className + ' ').indexOf(' up_bbtabs_body ') !== -1) {
				return this.addVisualTab(holder, titleName, contentName);
			}
			if (!holder.rows || holder.rows.length > 6) return false;
			var row = holder.insertRow(-1);
			row.innerHTML = '<td><input type="text" name="' + titleName + '" class="w100p"></td><td><textarea name="' + contentName + '" rows="5" class="w100p"></textarea></td><td class="center"><a href="#" class="sba" onclick="Upl.removeRow(this); return false;">X</a></td>';
			return false;
		},
		addVisualTab: function(body, titleName, contentName) {
			var tabs = body.getElementsByClassName('up_bbtabs_tab');
			if (tabs.length >= 6) return false;
			var list = body.getElementsByClassName('up_bbtabs_tabs')[0];
			if (!list) return false;
			var index = tabs.length;
			var add = list.getElementsByClassName('up_bbtabs_add')[0];
			var li = document.createElement('li');
			li.className = 'up_bbtabs_tab';
			li.innerHTML = '<div class="up_bbtabs_tabtitle"><input type="text" name="' + titleName + '" value="Вкладка ' + (index + 1) + '" class="up" onchange="Upl.syncTabTitle(this);" onkeyup="Upl.syncTabTitle(this);"></div><div class="up_bbtabs_del" title="Удалить вкладку" onclick="Upl.removeTab(this); return false;"></div>';
			if (add) list.insertBefore(li, add); else list.appendChild(li);
			var panel = document.createElement('div');
			panel.className = 'bx1 up_bbtabs_c';
			panel.innerHTML = '<div class="up_bbtabs_ctxt"><textarea name="' + contentName + '" rows="12" class="up_bbtabs_txt"></textarea></div>';
			var clear = body.getElementsByClassName('up_clrl')[0];
			if (clear) body.insertBefore(panel, clear); else body.appendChild(panel);
			this.refreshTabs(body);
			this.showTab(body.id, index);
			return false;
		},
		showTab: function(bodyId, index) {
			var body = document.getElementById(bodyId);
			if (!body) return false;
			var tabs = body.getElementsByClassName('up_bbtabs_tab');
			var panels = body.getElementsByClassName('up_bbtabs_c');
			for (var i = 0; i < tabs.length; i++) {
				tabs[i].className = 'up_bbtabs_tab' + (i === index ? ' mn' : '');
			}
			for (var j = 0; j < panels.length; j++) {
				panels[j].style.display = (j === index ? '' : 'none');
			}
			return false;
		},
		removeTab: function(link) {
			var tab = link;
			while (tab && tab.tagName !== 'LI') tab = tab.parentNode;
			if (!tab) return false;
			var body = tab;
			while (body && (' ' + body.className + ' ').indexOf(' up_bbtabs_body ') === -1) body = body.parentNode;
			if (!body) return false;
			var tabs = body.getElementsByClassName('up_bbtabs_tab');
			if (tabs.length <= 1) return false;
			var index = 0;
			for (var i = 0; i < tabs.length; i++) {
				if (tabs[i] === tab) {
					index = i;
					break;
				}
			}
			var panels = body.getElementsByClassName('up_bbtabs_c');
			if (panels[index]) panels[index].parentNode.removeChild(panels[index]);
			tab.parentNode.removeChild(tab);
			this.refreshTabs(body);
			this.showTab(body.id, Math.max(0, index - 1));
			return false;
		},
		refreshTabs: function(body) {
			var tabs = body.getElementsByClassName('up_bbtabs_tab');
			for (var i = 0; i < tabs.length; i++) {
				tabs[i].onclick = (function(bodyId, index) {
					return function(e) {
						e = e || window.event;
						var target = e.target || e.srcElement;
						if (target && target.className === 'up_bbtabs_del') return false;
						Upl.showTab(bodyId, index);
						return false;
					};
				})(body.id, i);
			}
		},
		syncTabTitle: function(input) {
			return true;
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
	Upl.setTemplate('<?= h($kind) ?>');
	<?php foreach ($section_modes as $index => $section_mode) { ?>
	Upl.setSectionMode(<?= (int)$index ?>, <?= (int)$section_mode ?>);
	<?php } ?>
	document.getElementById('mode').value = '<?= (int)$mode ?>';
	Upl.setModeTabs(<?= (int)$mode ?>);
	(function() {
		var bodies = document.getElementsByClassName('up_bbtabs_body');
		for (var i = 0; i < bodies.length; i++) {
			Upl.refreshTabs(bodies[i]);
			Upl.showTab(bodies[i].id, 0);
		}
	})();
	</script>
	<?php
}

function upload_render_online_block()
{
	echo page_online_box(array('/upload.php%', 'upload.php%'), 'пока никого');
}

function upload_render_details_panel(array $row, array $details, $descr_html, $owned, array $announces_urls = array())
{
	global $pic_base_url, $tracker_lang;

	$poster = trim((string)($details['poster_url'] ?? ''));
	$rbutton = trim((string)($details['rgroup_button'] ?? ''));
	$groups = upload_release_groups();
	$rgroup_id = (int)($details['rgroup'] ?? 0);
	$rgroup_title = $groups[$rgroup_id] ?? '';
	$id = (int)$row['id'];
	$edit_url = 'edit.php?id=' . $id;
	?>
	<div class="bx2">
		<div class="mn3_menu">
			<ul class="men">
				<?php if ($poster !== '') { ?>
					<li class="center"><img src="<?= h($poster) ?>" class="p200" alt=""></li>
				<?php } elseif (!empty($row['image1'])) { ?>
					<li class="center"><a href="torrents/images/<?= h($row['image1']) ?>"><img border="0" src="thumbnail.php?<?= h($row['image1']) ?>" alt=""></a></li>
				<?php } ?>
				<li class="tp2 center">Раздача</li>
				<li><span class="bulet"></span><a href="download.php?id=<?= $id ?>" class="sba">Скачать торрент</a></li>
					<li><span class="bulet"></span><a href="bookmark.php?torrent=<?= $id ?><?= !empty($CURUSER['hash4u']) ? '&amp;hash4u=' . h($CURUSER['hash4u']) : '' ?>" class="sba"><?= h($tracker_lang['bookmark'] ?? 'Закладка') ?></a></li>
				<?php if ($owned) { ?><li><span class="bulet"></span><a href="<?= h($edit_url) ?>" class="sba"><?= h($tracker_lang['edit'] ?? 'Редактировать') ?></a></li><?php } ?>
				<?php if ($rgroup_title !== '' || $rbutton !== '') { ?>
					<li class="tp2 center">Релиз-группа</li>
					<?php if ($rbutton !== '') { ?>
						<li class="center"><?= preg_match('#^(https?:)?//|^/#i', $rbutton) ? '<img src="' . h($rbutton) . '" class="p88x31n" alt="' . h($rgroup_title) . '">' : h($rbutton) ?></li>
					<?php } ?>
					<?php if ($rgroup_title !== '') { ?><li class="center b"><?= h($rgroup_title) ?></li><?php } ?>
				<?php } ?>
				<li class="tp2 center">Статистика</li>
				<li><dl><dt>Размер</dt><dd><?= h(mksize($row['size'])) ?></dd></dl></li>
				<li><dl><dt>Сиды</dt><dd><?= (int)$row['seeders'] ?></dd></dl></li>
				<li><dl><dt>Пиры</dt><dd><?= (int)$row['leechers'] ?></dd></dl></li>
				<li><dl><dt>Скачали</dt><dd><?= (int)$row['times_completed'] ?></dd></dl></li>
			</ul>
		</div>
		<div class="mn3_content">
			<ul class="men">
				<li class="tp2 b"><?= h($row['name']) ?></li>
				<li><b><?= h($row['cat_name'] ?? '') ?></b></li>
				<li class="pad5x5"><?= $descr_html ?></li>
			</ul>
		</div>
	</div>
	<?php
}

?>
