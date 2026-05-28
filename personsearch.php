<?php

require_once("include/bittorrent.php");
require_once("include/persons.php");

dbconn(false);
kz_persons_ensure_schema();

function ps_get($key, $default = '')
{
	return kz_persons_request_text($_GET[$key] ?? $default);
}

function ps_selected($a, $b)
{
	return (string)$a === (string)$b ? ' selected' : '';
}

function ps_letters($param)
{
	$letters = preg_split('//u', 'АБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЭЮЯ', -1, PREG_SPLIT_NO_EMPTY);
	foreach ($letters as $letter) {
		echo '<span><a href="/personsearch.php?' . $param . '=' . rawurlencode($letter) . '">' . kz_persons_h($letter) . '</a></span>';
	}
	echo '<a href="/personsearch.php">...</a><div class="clr"></div>';
}

function ps_menu($s, $type, $gender, $day, $month, $year)
{
	global $CURUSER;
	?>
	<div class="mn1_menu"><form method="get" action="/personsearch.php" name="br_top" id="br_top">
	<ul class="men w200">
		<li class="img"><a href="/personsearch.php"><img src="/pic/bn/p_personsearch.jpg" height="75" class="block w200" alt=""></a></li>
		<li class="tp">Поиск персон</li>
		<li class="img"><dl><dt>Имя</dt><dd><input type="text" class="p_srch" name="s" value="<?= kz_persons_h($s) ?>" placeholder="Имя Фамилия"></dd></dl></li>
		<li class="img"><dl><dt>Категория</dt><dd><span class="sw120"><select class="p_srch styled" name="t">
			<option value="0"<?= ps_selected($type, 0) ?>>Все</option>
			<option value="11"<?= ps_selected($type, 11) ?>>Персоны</option>
			<option value="1"<?= ps_selected($type, 1) ?>>|- Русская</option>
			<option value="2"<?= ps_selected($type, 2) ?>>|- Иностранная</option>
			<option value="12"<?= ps_selected($type, 12) ?>>Музыкальные группы</option>
			<option value="3"<?= ps_selected($type, 3) ?>>|- Русская</option>
			<option value="4"<?= ps_selected($type, 4) ?>>|- Иностранная</option>
		</select></span></dd></dl></li>
		<li class="img"><dl><dt>Пол</dt><dd><span class="sw120"><select class="p_srch styled" name="p">
			<option value="0"<?= ps_selected($gender, 0) ?>>Пол персоны</option>
			<option value="1"<?= ps_selected($gender, 1) ?>>Мужской</option>
			<option value="2"<?= ps_selected($gender, 2) ?>>Женский</option>
		</select></span></dd></dl></li>
		<li class="img">Дата рождения<table id="bday"><tr><td id="d"><select name="d" class="w100p styled"><option value="0">День</option><?php for ($i = 1; $i <= 31; $i++) { ?><option value="<?= $i ?>"<?= ps_selected($day, $i) ?>><?= $i ?></option><?php } ?></select></td><td id="m"><select name="m" class="w100p styled"><option value="0">Месяц</option><?php $months = array(1=>'января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'); foreach ($months as $i => $month_name) { ?><option value="<?= $i ?>"<?= ps_selected($month, $i) ?>><?= $month_name ?></option><?php } ?></select></td><td><input type="text" class="w100p" name="g" value="<?= kz_persons_h($year) ?>" placeholder="Год"></td></tr></table></li>
		<li class="img"><input type="submit" value="Поиск персон" class="w100p buttonS"></li>
		<?php if ($CURUSER) { ?><li><span class="bulet"></span><a href="/personedit.php">Создать персону</a></li><?php } ?>
		<li class="tp"><h2>Выбор персон</h2></li>
		<li><span class="bulet"></span><a href="/personsearch.php">День рождения</a></li>
		<li><span class="bulet"></span><a href="/personsearch.php?a=newp">Новые персоны</a></li>
		<li><span class="bulet"></span><a href="/personsearch.php?a=updp">Недавно измененные</a></li>
		<li class="tp"><h2>Заглавная буква имени</h2></li>
		<li class="letters"><?php ps_letters('nam'); ?></li>
		<li class="tp"><h2>Заглавная буква фамилии</h2></li>
		<li class="letters"><?php ps_letters('fam'); ?></li>
		<li class="tp"><h2>Информация</h2></li>
		<li class="justify">Раздел Персоны - Биографии знаменитых мастеров кино, известных личностей мира искусства, заслуженных деятелей театра и звезд шоу-бизнеса, их творческие достижения и награды.</li>
	</ul></form></div>
	<?php
}

function ps_person_grid(array $persons, $title)
{
	echo '<div class="bx2_0"><div class="pad5x5 b"><span class="bulet"></span>' . kz_persons_h($title) . '</div>';
	if (!$persons) {
		echo '<div class="pad10x10">Персоны не найдены.</div></div>';
		return;
	}
	echo '<table class="tables3 w100p">';
	$col = 0;
	foreach ($persons as $person) {
		if ($col === 0) {
			echo '<tr>';
		}
		$poster = trim((string)$person['poster_url']);
		if ($poster === '') {
			$poster = '/pic/default_avatar.gif';
		}
		echo '<td class="center top" width="12%"><a href="' . kz_persons_url($person['name'], (int)$person['id']) . '" title="' . kz_persons_h($person['name']) . '"><img src="' . kz_persons_h($poster) . '" width="120" alt=""><br><b>' . kz_persons_h($person['name']) . '</b></a></td>';
		$col++;
		if ($col >= 8) {
			echo '</tr>';
			$col = 0;
		}
	}
	if ($col !== 0) {
		while ($col < 8) {
			echo '<td></td>';
			$col++;
		}
		echo '</tr>';
	}
	echo '</table></div>';
}

$s = ps_get('s');
$type = (int)ps_get('t', 0);
$gender = (int)ps_get('p', 0);
$day = (int)ps_get('d', 0);
$month = (int)ps_get('m', 0);
$year = ps_get('g');
$mode = ps_get('a');
$nam = ps_get('nam');
$fam = ps_get('fam');

$where = array('1=1');
$title = '';
if ($s !== '') {
	$where[] = '(name LIKE ' . sqlesc('%' . $s . '%', true) . ' OR original_name LIKE ' . sqlesc('%' . $s . '%', true) . ')';
	$title = 'Результаты поиска: ' . $s;
}
if ($type > 0) {
	$where[] = 'type = ' . $type;
}
if ($gender > 0) {
	$where[] = 'gender = ' . $gender;
}
if ($day > 0) {
	$where[] = 'DAY(birth_date) = ' . $day;
}
if ($month > 0) {
	$where[] = 'MONTH(birth_date) = ' . $month;
}
if ($year !== '' && preg_match('/^[0-9]{3,4}$/', $year)) {
	$where[] = 'YEAR(birth_date) = ' . (int)$year;
}
if ($nam !== '') {
	$where[] = 'name LIKE ' . sqlesc($nam . '%', true);
	$title = 'Персоны на букву ' . $nam;
}
if ($fam !== '') {
	$where[] = 'SUBSTRING_INDEX(name, " ", -1) LIKE ' . sqlesc($fam . '%', true);
	$title = 'Фамилии на букву ' . $fam;
}

$order = 'name ASC';
if ($mode === 'newp') {
	$order = 'created_at DESC, id DESC';
	$title = 'Новые персоны';
} elseif ($mode === 'updp') {
	$order = 'updated_at DESC, id DESC';
	$title = 'Недавно измененные';
}

if ($title === '') {
	$today = getdate();
	$where[] = 'MONTH(birth_date) = ' . (int)$today['mon'] . ' AND DAY(birth_date) = ' . (int)$today['mday'];
	$title = 'Сегодня День рождения у персон';
}

$res = sql_query("SELECT * FROM persons WHERE " . implode(' AND ', $where) . " ORDER BY $order LIMIT 96") or sqlerr(__FILE__, __LINE__);
$persons = array();
while ($row = mysqli_fetch_assoc($res)) {
	$persons[] = $row;
}

$hide_right_blocks = true;
stdhead('Персоны');
?>
<div class="mn_wrap">
	<div style="padding:0 5px 7px 0;"><h1><span class="bulet"></span><a href="/personsearch.php" class="sbab">Персоны - Биографии и творчество деятелей кино и искусства</a></h1></div>
	<?php ps_menu($s, $type, $gender, $day, $month, $year); ?>
	<div class="mn1_content">
		<?php ps_person_grid($persons, $title . ' ' . count($persons)); ?>
	</div>
	<div class="clear"></div>
</div>
<?php
stdfoot();

?>
