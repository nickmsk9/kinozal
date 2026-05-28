<?php

require_once("include/bittorrent.php");
require_once("include/persons.php");

dbconn(false);
loggedinorreturn();
parked();
kz_persons_ensure_schema();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$person = $id > 0 ? kz_persons_find($id, '') : null;

if ($id > 0 && !$person) {
	stderr($tracker_lang['error'], 'Персона не найдена.');
}

if (!kz_persons_can_edit($person)) {
	stderr($tracker_lang['error'], $tracker_lang['access_denied']);
}

$empty = array(
	'id' => 0,
	'name' => kz_persons_request_text($_GET['s'] ?? ''),
	'original_name' => '',
	'type' => 11,
	'gender' => 0,
	'poster_url' => '',
	'birth_date' => '',
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
);
$person = $person ?: $empty;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = trim((string)($_POST['name'] ?? ''));
	if ($name === '') {
		stderr($tracker_lang['error'], 'Укажите имя персоны.');
	}

	$data = array();
	foreach (array('original_name','poster_url','birth_date','birth_text','birth_place','career','genre','height','spouse','biography','trivia','filmography','voice','producer','director','writer','awards','links','source_url') as $field) {
		$data[$field] = trim((string)($_POST[$field] ?? ''));
	}
	$data['type'] = max(0, (int)($_POST['type'] ?? 11));
	$data['gender'] = max(0, min(2, (int)($_POST['gender'] ?? 0)));
	$birth_date_sql = $data['birth_date'] !== '' && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $data['birth_date'])
		? sqlesc($data['birth_date'])
		: 'NULL';

	if ($id > 0) {
		sql_query("
			UPDATE persons SET
				name = " . sqlesc($name) . ",
				original_name = " . sqlesc($data['original_name']) . ",
				type = " . (int)$data['type'] . ",
				gender = " . (int)$data['gender'] . ",
				poster_url = " . sqlesc($data['poster_url']) . ",
				birth_date = $birth_date_sql,
				birth_text = " . sqlesc($data['birth_text']) . ",
				birth_place = " . sqlesc($data['birth_place']) . ",
				career = " . sqlesc($data['career']) . ",
				genre = " . sqlesc($data['genre']) . ",
				height = " . sqlesc($data['height']) . ",
				spouse = " . sqlesc($data['spouse']) . ",
				biography = " . sqlesc($data['biography']) . ",
				trivia = " . sqlesc($data['trivia']) . ",
				filmography = " . sqlesc($data['filmography']) . ",
				voice = " . sqlesc($data['voice']) . ",
				producer = " . sqlesc($data['producer']) . ",
				director = " . sqlesc($data['director']) . ",
				writer = " . sqlesc($data['writer']) . ",
				awards = " . sqlesc($data['awards']) . ",
				links = " . sqlesc($data['links']) . ",
				source_url = " . sqlesc($data['source_url']) . ",
				updated_by = " . (int)$CURUSER['id'] . ",
				updated_at = " . sqlesc(get_date_time()) . "
			WHERE id = $id
		") or sqlerr(__FILE__, __LINE__);
	} else {
		sql_query("
			INSERT INTO persons
				(name, original_name, type, gender, poster_url, birth_date, birth_text, birth_place, career, genre, height, spouse, biography, trivia, filmography, voice, producer, director, writer, awards, links, source_url, created_by, created_at, updated_by, updated_at)
			VALUES
				(" . sqlesc($name) . ", " . sqlesc($data['original_name']) . ", " . (int)$data['type'] . ", " . (int)$data['gender'] . ", " . sqlesc($data['poster_url']) . ", $birth_date_sql, " . sqlesc($data['birth_text']) . ", " . sqlesc($data['birth_place']) . ", " . sqlesc($data['career']) . ", " . sqlesc($data['genre']) . ", " . sqlesc($data['height']) . ", " . sqlesc($data['spouse']) . ", " . sqlesc($data['biography']) . ", " . sqlesc($data['trivia']) . ", " . sqlesc($data['filmography']) . ", " . sqlesc($data['voice']) . ", " . sqlesc($data['producer']) . ", " . sqlesc($data['director']) . ", " . sqlesc($data['writer']) . ", " . sqlesc($data['awards']) . ", " . sqlesc($data['links']) . ", " . sqlesc($data['source_url']) . ", " . (int)$CURUSER['id'] . ", " . sqlesc(get_date_time()) . ", " . (int)$CURUSER['id'] . ", " . sqlesc(get_date_time()) . ")
		") or sqlerr(__FILE__, __LINE__);
		global $link;
		$id = mysqli_insert_id($link);
	}

	kz_persons_save_photos($id, $_POST['photos'] ?? '');
	header('Location: ' . str_replace('&amp;', '&', kz_persons_url($name, $id)));
	exit;
}

$photos_text = $id > 0 ? kz_persons_photo_text($id) : '';
$hide_right_blocks = true;
stdhead(($id > 0 ? 'Редактировать персону' : 'Создать персону'));
?>
<div class="mn_wrap">
	<div style="padding:0 5px 7px 0;"><h1><span class="bulet"></span><a href="/personsearch.php" class="sbab">Персоны</a> :: <?= $id > 0 ? 'Редактировать' : 'Создать' ?></h1></div>
	<form method="post" action="/personedit.php">
	<input type="hidden" name="id" value="<?= (int)$id ?>">
	<div class="bx1_0"><table class="tables1 w100p">
		<tr><td class="w175">Имя:</td><td><input type="text" name="name" class="w98p" value="<?= kz_persons_h($person['name']) ?>"></td></tr>
		<tr><td>Оригинальное имя:</td><td><input type="text" name="original_name" class="w98p" value="<?= kz_persons_h($person['original_name']) ?>"></td></tr>
		<tr><td>Категория:</td><td><select name="type"><option value="11"<?= (int)$person['type'] === 11 ? ' selected' : '' ?>>Персоны</option><option value="1"<?= (int)$person['type'] === 1 ? ' selected' : '' ?>>Русская персона</option><option value="2"<?= (int)$person['type'] === 2 ? ' selected' : '' ?>>Иностранная персона</option><option value="12"<?= (int)$person['type'] === 12 ? ' selected' : '' ?>>Музыкальная группа</option><option value="3"<?= (int)$person['type'] === 3 ? ' selected' : '' ?>>Русская группа</option><option value="4"<?= (int)$person['type'] === 4 ? ' selected' : '' ?>>Иностранная группа</option></select></td></tr>
		<tr><td>Пол:</td><td><select name="gender"><option value="0"<?= (int)$person['gender'] === 0 ? ' selected' : '' ?>>Не указан</option><option value="1"<?= (int)$person['gender'] === 1 ? ' selected' : '' ?>>Мужской</option><option value="2"<?= (int)$person['gender'] === 2 ? ' selected' : '' ?>>Женский</option></select></td></tr>
		<tr><td>Постер:</td><td><input type="text" name="poster_url" class="w98p" value="<?= kz_persons_h($person['poster_url']) ?>"></td></tr>
		<tr><td>Фото:</td><td><textarea name="photos" rows="5" class="w98p" placeholder="Ссылки на фото, по одной в строке"><?= kz_persons_h($photos_text) ?></textarea></td></tr>
		<tr><td>Дата рождения:</td><td><input type="text" name="birth_date" value="<?= kz_persons_h($person['birth_date']) ?>" placeholder="YYYY-MM-DD"> <input type="text" name="birth_text" size="50" value="<?= kz_persons_h($person['birth_text']) ?>" placeholder="Текстом, если нужно"></td></tr>
		<tr><td>Место рождения:</td><td><input type="text" name="birth_place" class="w98p" value="<?= kz_persons_h($person['birth_place']) ?>"></td></tr>
		<tr><td>Карьера:</td><td><input type="text" name="career" class="w98p" value="<?= kz_persons_h($person['career']) ?>"></td></tr>
		<tr><td>Жанр:</td><td><input type="text" name="genre" class="w98p" value="<?= kz_persons_h($person['genre']) ?>"></td></tr>
		<tr><td>Рост:</td><td><input type="text" name="height" value="<?= kz_persons_h($person['height']) ?>"></td></tr>
		<tr><td>Супруг(а):</td><td><input type="text" name="spouse" class="w98p" value="<?= kz_persons_h($person['spouse']) ?>"></td></tr>
		<tr><td>Биография:</td><td><textarea name="biography" rows="12" class="w98p"><?= kz_persons_h($person['biography']) ?></textarea></td></tr>
		<tr><td>Знаете ли Вы:</td><td><textarea name="trivia" rows="6" class="w98p"><?= kz_persons_h($person['trivia']) ?></textarea></td></tr>
		<tr><td>Фильмография:</td><td><textarea name="filmography" rows="8" class="w98p"><?= kz_persons_h($person['filmography']) ?></textarea></td></tr>
		<tr><td>Озвучивание:</td><td><textarea name="voice" rows="5" class="w98p"><?= kz_persons_h($person['voice']) ?></textarea></td></tr>
		<tr><td>Продюсер:</td><td><textarea name="producer" rows="5" class="w98p"><?= kz_persons_h($person['producer']) ?></textarea></td></tr>
		<tr><td>Режиссер:</td><td><textarea name="director" rows="5" class="w98p"><?= kz_persons_h($person['director']) ?></textarea></td></tr>
		<tr><td>Сценарист:</td><td><textarea name="writer" rows="5" class="w98p"><?= kz_persons_h($person['writer']) ?></textarea></td></tr>
		<tr><td>Награды:</td><td><textarea name="awards" rows="6" class="w98p"><?= kz_persons_h($person['awards']) ?></textarea></td></tr>
		<tr><td>Ссылки:</td><td><textarea name="links" rows="5" class="w98p" placeholder="Название|https://example.com"><?= kz_persons_h($person['links']) ?></textarea></td></tr>
		<tr><td>Источник:</td><td><input type="text" name="source_url" class="w98p" value="<?= kz_persons_h($person['source_url']) ?>"></td></tr>
		<tr><td colspan="2" class="center"><input type="submit" value="Сохранить" class="buttonS"></td></tr>
	</table></div>
	</form>
</div>
<?php
stdfoot();

?>
