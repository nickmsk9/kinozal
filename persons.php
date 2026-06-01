<?php

require_once("include/bittorrent.php");
require_once("include/persons.php");

dbconn(false);
persons_ensure_schema();

$pid = (int)($_GET['pid'] ?? 0);
$name = persons_request_text($_GET['s'] ?? '');
$action = (string)($_GET['a'] ?? '');
$page = max(0, (int)($_GET['page'] ?? 0));

$person = persons_find($pid, $name);
if (!$person) {
	$person = array(
		'id' => 0,
		'name' => $name !== '' ? $name : 'Персона',
		'original_name' => '',
		'type' => 11,
		'gender' => 0,
		'poster_url' => '/pic/default_avatar.gif',
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
		'created_by' => 0,
		'created_at' => '',
		'updated_by' => 0,
		'updated_at' => '',
	);
}

$pid = (int)$person['id'];
$name = (string)$person['name'];
$count = persons_torrent_count($person);
$base_url = persons_url($name, $pid);
$poster = trim((string)$person['poster_url']);
if ($poster === '') {
	$poster = '/pic/default_avatar.gif';
}
$photos = persons_photos($pid, 4);
$can_edit = persons_can_edit($person);
$hash = $CURUSER ? persons_h($CURUSER['hash4u'] ?? ($CURUSER['logout_hash'] ?? '')) : '';

$creator = null;
if (!empty($person['created_by'])) {
	$r = sql_query("SELECT id, username, class, donor, gender, birthday, warned, enabled, uploaded, downloaded FROM users WHERE id = " . (int)$person['created_by'] . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$creator = mysqli_fetch_assoc($r);
}
$editor = null;
if (!empty($person['updated_by'])) {
	$r = sql_query("SELECT id, username, class, donor, gender, birthday, warned, enabled, uploaded, downloaded FROM users WHERE id = " . (int)$person['updated_by'] . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$editor = mysqli_fetch_assoc($r);
}

$hide_right_blocks = true;
stdhead('Персоны :: ' . $name);

function persons_tabs($person, $action, $count)
{
	$name = (string)$person['name'];
	$pid = (int)$person['id'];
	echo '<div class="pad0x0x5x0"><ul class="lis">';
	echo '<li' . ($action === '' ? ' class="mn"' : '') . '><a href="' . persons_url($name, $pid) . '">Информация</a>';
	echo '<li' . ($action === 'torr' ? ' class="mn"' : '') . '><a href="' . persons_url($name, $pid, array('a' => 'torr')) . '">Раздачи персоны</a>';
	echo '<li' . ($action === 'torrtop' ? ' class="mn"' : '') . '><a href="' . persons_url($name, $pid, array('a' => 'torrtop')) . '">Топ раздач персоны</a>';
	echo '</ul><span class="floatright b" style="line-height:20px">С участием персоны <span class="u9">' . (int)$count . '</span> раздач</span></div>';
}

function persons_torrent_table($person, $page, $top = false)
{
	$count = persons_torrent_count($person);
	$perpage = 50;
	$pages = max(1, (int)ceil($count / $perpage));
	$page = max(0, min((int)$page, $pages - 1));
	$rows = persons_torrents($person, $top ? 'top' : 'date', $page * $perpage, $perpage);
	$base = persons_url($person['name'], (int)$person['id'], array('a' => $top ? 'torrtop' : 'torr'));

	echo '<div class="bx2_0"><table class="t_peer w100p">';
	echo '<tr class="mn"><td class="z w90"></td><td></td><td class="z">Комм.</td><td class="z">Размер</td><td class="z">Скач.</td><td class="z">Сидов</td><td class="z">Пиров</td><td class="z">Залит</td></tr>';
	if (!$rows) {
		echo '<tr class="first bg"><td colspan="8" class="center">Раздачи с этой персоной пока не найдены.</td></tr>';
	}
	foreach ($rows as $i => $row) {
		$tr = $i === 0 ? " class='first bg'" : " class='bg'";
		$cat = !empty($row['cat_pic']) ? '<img src="/pic/cat/' . persons_h($row['cat_pic']) . '" class="p90x32" alt="">' : '';
		echo "<tr$tr><td class=\"bt\">$cat</td><td class=\"nam\"><a href=\"/details.php?id=" . (int)$row['id'] . "\" class=\"r1\">" . persons_h($row['name']) . "</a>";
		echo "<td class='s'>" . (int)$row['comments'] . "</td>";
		echo "<td class='s'>" . persons_h(mksize($row['size'])) . "</td>";
		echo "<td class='s'>" . (int)$row['times_completed'] . "</td>";
		echo "<td class='sl_s'>" . (int)$row['seeders'] . "</td>";
		echo "<td class='sl_p'>" . (int)$row['leechers'] . "</td>";
		echo "<td class='s'>" . persons_h(date('d.m.Y в H:i', strtotime($row['added']))) . "</td></tr>";
	}
	echo '</table></div>';
	echo persons_pager($base, $page, $pages);
}

function persons_torrent_top_posters($person)
{
	$rows = persons_torrents($person, 'top', 0, 60);
	echo '<div class="bx1 stable">';
	if (!$rows) {
		echo 'Раздачи с этой персоной пока не найдены.';
	}
	foreach ($rows as $row) {
		$poster = persons_torrent_poster($row);
		echo '<a href="/details.php?id=' . (int)$row['id'] . '" title="' . persons_h($row['name']) . '" target="_blank"><img src="' . $poster . '" alt="" height="180"></a> ';
	}
	echo '</div>';
}
?>
<div class="mn_wrap">
	<div style="padding:0 5px 7px 0;"><h1><span class="bulet"></span><a href="/personsearch.php" class="sbab">Персоны</a> :: <a href="<?= $base_url ?>" class="sbab prsns"><?= persons_h($name) ?></a><?php if ($action === 'torr') { ?> :: <a href="<?= persons_url($name, $pid, array('a' => 'torr')) ?>" class="sbab">Раздачи персоны</a><?php } elseif ($action === 'torrtop') { ?> :: <a href="<?= persons_url($name, $pid, array('a' => 'torrtop')) ?>" class="sbab">Топ раздач персоны</a><?php } ?></h1></div>
	<div class="mn1_menu"><ul class="men w200">
		<li class="img"><a href="<?= $base_url ?>" title="<?= persons_h($name) ?>"><img src="<?= persons_h($poster) ?>" class="p200" alt=""></a></li>
		<li class="tp">Меню персоны</li>
		<?php if ($pid > 0 && $hash !== '') { ?><li><span class="bulet"></span><a href="/bookmarks.php?type=4&amp;add=<?= $pid ?>&amp;hash4u=<?= $hash ?>" onclick="return mess_out('Добавить персону в закладки?')">Добавить в закладки</a></li><?php } ?>
		<?php if ($can_edit) { ?><li><span class="bulet"></span><a href="/personedit.php<?= $pid > 0 ? '?id=' . $pid : '?s=' . rawurlencode($name) ?>">Редактировать</a></li><?php } ?>
		<li class="tp">Опубликовать ссылку</li>
		<li><div class="share b"><a class="vkontakte" href="https://vk.com/share.php?url=<?= rawurlencode($DEFAULTBASEURL . '/persons.php?pid=' . $pid) ?>" title="Опубликовать ссылку во ВКонтакте" onclick="window.open(this.href, 'Опубликовать ссылку во Вконтакте', 'width=800,height=300'); return false"></a><a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($DEFAULTBASEURL . '/persons.php?pid=' . $pid) ?>" title="Опубликовать ссылку в Facebook" onclick="window.open(this.href, 'Опубликовать ссылку в Facebook', 'width=640,height=436,toolbar=0,status=0'); return false"></a><a class="twitter" href="https://twitter.com/intent/tweet?text=<?= rawurlencode($name . ' ' . $DEFAULTBASEURL . '/persons.php?pid=' . $pid) ?>" title="Опубликовать ссылку в Twitter" onclick="window.open(this.href, 'Опубликовать ссылку в Twitter', 'width=800,height=300'); return false" target="_blank"></a></div><div class="clear"></div></li>
		<?php if ($photos) { ?><li class="tp">Фотографии <span class="floatright"><?= count(persons_photos($pid)) ?></span></li><?php foreach ($photos as $photo) { ?><li class="img"><img src="<?= persons_h(trim($photo['image_url'])) ?>" class="p200" alt=""></li><?php }} ?>
		<?php if ($creator) { ?><li class="tp">Создал<span class="floatright"><?= persons_h(persons_date($person['created_at'])) ?></span></li><li><span class="bulet"></span><?= persons_user_link((int)$creator['id'], $creator['username'], (int)$creator['class'], $creator) ?></li><?php } ?>
		<?php if ($editor) { ?><li class="tp">Ред.<span class="floatright"><?= persons_h(persons_date($person['updated_at'])) ?></span></li><li><span class="bulet"></span><?= persons_user_link((int)$editor['id'], $editor['username'], (int)$editor['class'], $editor) ?></li><?php } ?>
		<li class="tp">Информация</li>
		<li class="justify">Если Вы нашли ошибку в информации о персоне, просим Вас сообщить автору персоны или модератору.</li>
	</ul></div>
	<div class="mn1_content">
		<?php persons_tabs($person, $action, $count); ?>
		<?php if ($action === 'torr') { ?>
			<?php persons_torrent_table($person, $page, false); ?>
		<?php } elseif ($action === 'torrtop') { ?>
			<?php persons_torrent_top_posters($person); ?>
		<?php } else { ?>
			<div class="bx1"><div class="b"><span class="bulet"></span>Краткая биография</div><div class="pad10x10">
				<?php if ($person['original_name'] !== '') { ?><b>Имя:</b> <?= persons_h($person['original_name']) ?><br /><?php } ?>
				<?php if ($person['birth_date'] || $person['birth_text']) { ?><b>Дата рождения:</b> <?= persons_h($person['birth_text'] ?: persons_date($person['birth_date'])) ?><br /><?php } ?>
				<?php if ($person['birth_place'] !== '') { ?><b>Место рождения:</b> <?= persons_h($person['birth_place']) ?><br /><?php } ?>
				<?php if ($person['career'] !== '') { ?><b>Карьера:</b> <?= persons_h($person['career']) ?><br /><?php } ?>
				<?php if ($person['genre'] !== '') { ?><b>Жанр:</b> <?= persons_h($person['genre']) ?><br /><?php } ?>
				<?php if ($person['height'] !== '') { ?><b>Рост:</b> <?= persons_h($person['height']) ?><br /><?php } ?>
				<?php if ($person['spouse'] !== '') { ?><b>Супруг(а):</b> <?= persons_h($person['spouse']) ?><?php } ?>
				<?php if ($pid <= 0) { ?>Информации о персоне пока нет. <?php if ($CURUSER) { ?><a href="/personedit.php?s=<?= rawurlencode($name) ?>" class="sba">Создать страницу</a><?php } ?><?php } ?>
			</div></div>
			<?php
			$sections = array(
				'Биография' => $person['biography'],
				'Знаете ли Вы, что...' => $person['trivia'],
				'Фильмография' => $person['filmography'],
				'Озвучивание' => $person['voice'],
				'Продюсер' => $person['producer'],
				'Режиссер' => $person['director'],
				'Сценарист' => $person['writer'],
				'Награды и премии' => $person['awards'],
			);
			foreach ($sections as $title => $text) {
				if (trim((string)$text) !== '') {
					echo '<div class="bx1"><div class="b"><span class="bulet"></span>' . persons_h($title) . '</div><div class="pad10x10">' . persons_text($text) . '</div></div>';
				}
			}
			$links_html = persons_links_html($person['links']);
			if ($links_html !== '') {
				echo '<div class="bx1"><div class="b"><span class="bulet"></span>Ссылки</div><div class="pad10x10">' . $links_html . '</div></div>';
			}
			?>
		<?php } ?>
	</div>
	<div class="clear"></div>
</div>
<?php
stdfoot();

?>
