<?php

require_once("include/bittorrent.php");

dbconn(false);
parked();

$hide_right_blocks = true;

function top_get_int($name, $default = 0)
{
    return isset($_GET[$name]) ? (int)$_GET[$name] : $default;
}

function top_h($value)
{
    return htmlspecialchars_uni((string)$value);
}

function top_selected($left, $right)
{
    return (string)$left === (string)$right ? ' selected="selected"' : '';
}

function top_year_ranges()
{
    return array(
        0 => 'все года',
        14 => '2024-2026',
        13 => '2021-2023',
        11 => '2018-2020',
        10 => '2015-2017',
        1 => '2012-2014',
        2 => '2009-2011',
        3 => '2006-2008',
        4 => '2001-2005',
        5 => '1996-2000',
        6 => '1992-1995',
        7 => '1982-1991',
        8 => '1972-1981',
        9 => '1951-1971',
    );
}

function top_categories()
{
    return array(
        0 => array('Избранные раздачи'),
        1 => array('Избранные фильмы', array(6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 24, 35, 37, 38, 39, 47, 48, 49, 50)),
        101 => array('|- Комедии', array(8)),
        102 => array('|- Фантастика, фэнтези', array(13, 14)),
        103 => array('|- Ужас, мистика', array(24)),
        104 => array('|- Боевик, военный', array(6)),
        105 => array('|- Триллер, детектив', array(15)),
        106 => array('|- Драма, мелодрама', array(17, 35)),
        107 => array('|- Наше кино', array(10)),
        108 => array('|- Детский, семейный', array(12)),
        110 => array('|- Приключения', array(11)),
        111 => array('|- Исторический', array(9)),
        112 => array('|- Документальный', array(18)),
        113 => array('|- Классика, театр, опера, балет', array(7, 38)),
        115 => array('|- Концерты', array(48)),
        116 => array('|- Спорт', array(37)),
        2 => array('Избранные мультфильмы', array(20, 21, 22)),
        21 => array('|- Русские', array(22)),
        22 => array('|- Буржуйские', array(21)),
        23 => array('|- Аниме', array(20)),
        3 => array('Избранные сериалы', array(45, 46)),
        31 => array('|- Русские', array(45)),
        32 => array('|- Буржуйские', array(46)),
        4 => array('Топ Музыки', array(3, 4, 5, 42)),
        41 => array('|- Русская', array(4)),
        42 => array('|- Буржуйская', array(3)),
        44 => array('|- Сборники', array(5)),
        43 => array('|- Классическая', array(42)),
        5 => array('Библиотека', array(41)),
        6 => array('Избранные аудиокниги', array(2)),
        7 => array('Избранные игры', array(23)),
        8 => array('Избранные программы', array(32, 40)),
    );
}

function top_year_condition($range)
{
    $ranges = array(
        14 => array(2024, 2026),
        13 => array(2021, 2023),
        11 => array(2018, 2020),
        10 => array(2015, 2017),
        1 => array(2012, 2014),
        2 => array(2009, 2011),
        3 => array(2006, 2008),
        4 => array(2001, 2005),
        5 => array(1996, 2000),
        6 => array(1992, 1995),
        7 => array(1982, 1991),
        8 => array(1972, 1981),
        9 => array(1951, 1971),
    );

    if (!isset($ranges[$range])) {
        return '';
    }

    list($from, $to) = $ranges[$range];
    $parts = array();
    for ($year = $from; $year <= $to; $year++) {
        $parts[] = "t.name LIKE '%" . $year . "%'";
    }

    return '(' . implode(' OR ', $parts) . ')';
}

function top_build_query($params)
{
    return http_build_query($params, '', '&amp;');
}

function top_poster_src(array $row)
{
    $poster = trim((string)($row['poster_url'] ?? ''));
    if ($poster !== '') {
        return top_h($poster);
    }

    $image = trim((string)($row['image1'] ?? ''));
    if ($image !== '') {
        return 'thumbnail.php?' . top_h($image);
    }

    return '/pic/default_avatar.gif';
}

$selectedTop = top_get_int('t', 0);
$selectedYear = top_get_int('d', 0);
$selectedFormat = top_get_int('f', 0);
$selectedUploaded = top_get_int('w', 0);
$selectedSort = top_get_int('s', 0);
$genre = isset($_GET['j']) ? trim((string)unesc($_GET['j'])) : '';
$categories = top_categories();

if (!isset($categories[$selectedTop])) {
    $selectedTop = 0;
}

$where = array("t.visible = 'yes'", "t.banned != 'yes'");
$pagerParams = array(
    't' => $selectedTop,
    'd' => $selectedYear,
    'f' => $selectedFormat,
    'c' => 0,
    'k' => top_get_int('k', 0),
    'j' => $genre,
    's' => $selectedSort,
    'w' => $selectedUploaded,
);

if (!empty($categories[$selectedTop][1])) {
    $where[] = 't.category IN (' . implode(',', array_map('intval', $categories[$selectedTop][1])) . ')';
}

$yearCondition = top_year_condition($selectedYear);
if ($yearCondition !== '') {
    $where[] = $yearCondition;
}

if ($genre !== '') {
    $genreSql = sqlwildcardesc($genre);
    $where[] = "(t.name LIKE '%$genreSql%' OR t.keywords LIKE '%$genreSql%' OR t.description LIKE '%$genreSql%')";
}

if ($selectedFormat === 2) {
    $where[] = "(t.name LIKE '%HD%' OR t.name LIKE '%BDRip%' OR t.name LIKE '%Blu-Ray%' OR t.name LIKE '%WEB-DL%')";
} elseif ($selectedFormat === 5) {
    $where[] = "(t.name LIKE '%4K%' OR t.name LIKE '%2160p%' OR t.name LIKE '%UHD%')";
} elseif ($selectedFormat === 4) {
    $where[] = "t.name LIKE '%3D%'";
} elseif ($selectedFormat === 3) {
    $where[] = "(t.name LIKE '%LossLess%' OR t.name LIKE '%FLAC%' OR t.name LIKE '%DTS%')";
}

if ($selectedUploaded === 1) {
    $where[] = "t.added >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
} elseif ($selectedUploaded === 2) {
    $where[] = "t.added >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($selectedUploaded === 3) {
    $where[] = "t.added >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
} elseif ($selectedUploaded === 6) {
    $where[] = "t.added >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
}

if ($selectedSort === 1) {
    $orderBy = 'ORDER BY peers DESC, t.seeders DESC, t.added DESC';
} elseif ($selectedSort === 2) {
    $orderBy = 'ORDER BY t.comments DESC, t.seeders DESC, t.added DESC';
} else {
    $orderBy = 'ORDER BY seeders DESC, peers DESC, t.added DESC';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$res = sql_query("SELECT COUNT(*) FROM torrents AS t $whereSql") or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_row($res);
$count = (int)($row[0] ?? 0);

$perPage = !empty($CURUSER['torrentsperpage']) ? (int)$CURUSER['torrentsperpage'] : 50;
if ($perPage < 1) {
    $perPage = 50;
}

list($pagertop, $pagerbottom, $limit) = pager($perPage, $count, 'top.php?' . top_build_query($pagerParams) . '&amp;');

$res = false;
if ($count > 0) {
    $res = sql_query("
		SELECT
			t.id,
			t.name,
			t.image1,
			td.poster_url,
			t.added,
			t.seeders + t.remote_seeders AS seeders,
			t.leechers + t.remote_leechers AS leechers,
			(t.seeders + t.remote_seeders + t.leechers + t.remote_leechers) AS peers,
			t.comments,
			t.times_completed,
			t.category,
			c.name AS cat_name
		FROM torrents AS t
		LEFT JOIN categories AS c ON t.category = c.id
		LEFT JOIN torrent_details AS td ON td.tid = t.id
		$whereSql
		$orderBy
		$limit
	") or sqlerr(__FILE__, __LINE__);
}

stdhead("Топ раздач");

?>
<style type="text/css">
    .top_poster_grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, 104px);
        gap: 6px;
        align-items: start;
        justify-content: start;
    }

    .top_poster_grid a {
        display: block;
        width: 104px;
        height: 152px;
        overflow: hidden;
        background: #f5e0bb;
    }

    .top_poster_grid img {
        display: block;
        width: 104px;
        height: 152px;
        border: 0;
        object-fit: cover;
    }

    .top_pager {
        padding: 3px 0 6px 0;
        overflow: hidden;
    }
</style>
<div class="bx2">
    <div style="padding:0 5px 7px 0;">
        <h1><span class="bulet"></span><a href="/top.php" class="sbab" title="Топ раздач">Топ раздач - Рейтинг лучших раздач трекера</a></h1>
    </div>

    <div class="mn1_menu">
        <form method="get" action="/top.php" name="br_top" id="br_top">
            <ul class="men">
                <li class="img"><a href="/top.php"><img src="/pic/p_top.jpg" height="75" class="block w200" alt="Топ раздач"></a></li>
                <li class="tp">Выбор топа по жанрам</li>
                <li class="img"><span class="w100p"><input type="text" name="j" value="<?= top_h($genre) ?>" class="w100p"></span></li>
                <li class="tp">Выбор топа по категориям</li>
                <li class="img">
                    <select class="w100p styled" name="t" size="15">
                        <?php foreach ($categories as $value => $category) { ?>
                            <option value="<?= (int)$value ?>" <?= top_selected($selectedTop, $value) ?>><?= top_h($category[0]) ?></option>
                        <?php } ?>
                    </select>
                </li>
                <li class="img">
                    <dl>
                        <dt>Год выпуска</dt>
                        <dd><span class="sw100"><select class="w100 styled" name="d">
                                    <?php foreach (top_year_ranges() as $value => $label) { ?>
                                        <option value="<?= (int)$value ?>" <?= top_selected($selectedYear, $value) ?>><?= top_h($label) ?></option>
                                    <?php } ?>
                                </select></span></dd>
                    </dl>
                    <dl>
                        <dt>Страна</dt>
                        <dd><span class="sw100"><select class="w100 styled" name="k">
                                    <option value="0">все страны</option>
                                </select></span></dd>
                    </dl>
                    <dl>
                        <dt>Формат</dt>
                        <dd><span class="sw100"><select class="w100 styled" name="f">
                                    <option value="0" <?= top_selected($selectedFormat, 0) ?>>все форматы</option>
                                    <option value="2" <?= top_selected($selectedFormat, 2) ?>>HD</option>
                                    <option value="5" <?= top_selected($selectedFormat, 5) ?>>4К</option>
                                    <option value="4" <?= top_selected($selectedFormat, 4) ?>>3D</option>
                                    <option value="3" <?= top_selected($selectedFormat, 3) ?>>LossLess</option>
                                </select></span></dd>
                    </dl>
                    <dl>
                        <dt>Залит</dt>
                        <dd><span class="sw100"><select class="w100 styled" name="w">
                                    <option value="0" <?= top_selected($selectedUploaded, 0) ?>>за все время</option>
                                    <option value="1" <?= top_selected($selectedUploaded, 1) ?>>за неделю</option>
                                    <option value="2" <?= top_selected($selectedUploaded, 2) ?>>за месяц</option>
                                    <option value="3" <?= top_selected($selectedUploaded, 3) ?>>за 3 месяца</option>
                                </select></span></dd>
                    </dl>
                    <dl>
                        <dt>Сортировать</dt>
                        <dd><span class="sw100"><select class="w100 styled" name="s">
                                    <option value="0" <?= top_selected($selectedSort, 0) ?>>по сидам</option>
                                    <option value="1" <?= top_selected($selectedSort, 1) ?>>по пирам</option>
                                    <option value="2" <?= top_selected($selectedSort, 2) ?>>по комм.</option>
                                </select></span></dd>
                    </dl>
                </li>
                <li class="img"><input type="submit" value="Перестроить топ" class="buttonS w200"></li>
                <li class="tp">Подборки для Вас</li>
                <li class="justify lnks_tobrs">Новогодний, Netflix, фильмы о спорте, лучший фильм Оскар, флора и фауна, мореокеан, ВОВ, Walt Disney Pictures, HBO, Marvel, Pixar, дорама, экранизация, студия Мельница, Ленфильм, Мосфильм, Союзмультфильм</li>
                <li class="tp">Информация</li>
                <li class="justify">
                    <p>Топ раздач - Автоматически обновляемый рейтинг лучших раздач. Надеемся, Вам будет интересна подборка популярных раздач.</p>
                </li>
            </ul>
        </form>
        <script type="text/javascript">
            $(".lnks_tobrs").each(function() {
                var str2_array = [];
                var str_array = $(this).html().split(",");
                for (var i = 0; i < str_array.length; i++) {
                    str_array[i] = str_array[i].trim().replace(/"/ig, "");
                    str2_array[i] = '<a href="/top.php?j=' + encodeURIComponent(str_array[i]) + '" class="sba">' + str_array[i] + '</a>';
                }
                $(this).html(str2_array.join(", "));
            });
        </script>
    </div>

    <div class="mn1_content">
        <div class="pad0x0x5x0">
            <ul class="lis">
                <?php
                $tabs = array(
                    0 => 'Топ раздач',
                    1 => 'Топ раздач недели',
                    2 => 'Топ раздач месяца',
                    6 => 'Топ раздач полгода',
                );
                foreach ($tabs as $period => $label) {
                    $params = $pagerParams;
                    $params['w'] = $period;
                    $class = $selectedUploaded === $period ? ' class="mn"' : '';
                    print('<li' . $class . '><a href="/top.php?' . top_build_query($params) . '" title="' . top_h($label) . '">' . top_h($label) . '</a></li>');
                }
                ?>
            </ul>
        </div>

        <?php if ($count > 0 && $pagertop) { ?>
            <div class="pad0x0x5x0"><?= $pagertop ?></div>
        <?php } ?>

        <div class="bx1 top_poster_grid">
            <?php if ($res) { ?>
                <?php while ($torrent = mysqli_fetch_assoc($res)) { ?>
                    <?php
                    $id = (int)$torrent['id'];
                    $title = top_h($torrent['name']);
                    $poster = top_poster_src($torrent);
                    $cat = !empty($torrent['cat_name']) ? ' / ' . top_h($torrent['cat_name']) : '';
                    $tip = $title . $cat . ' / сидов: ' . (int)$torrent['seeders'] . ' / пиров: ' . (int)$torrent['leechers'];
                    ?>
                    <a href="/details.php?id=<?= $id ?>&amp;hit=1" title="<?= $tip ?>"><img src="<?= $poster ?>" alt="<?= $title ?>"></a>
                <?php } ?>
            <?php } else { ?>
                <div style="padding:12px;">Раздачи не найдены.</div>
            <?php } ?>
        </div>

        <?php if ($count > 0 && $pagerbottom) { ?>
            <div class="top_pager"><?= $pagerbottom ?></div>
        <?php } ?>
    </div>

    <div class="clr"></div>
</div>

<?= page_online_box(array('/top.php%', 'top.php%'), 'никого нет на этой странице'); ?>

<div id="movie_video"></div>
<div class="clr"></div>
<?php

stdfoot();

?>
