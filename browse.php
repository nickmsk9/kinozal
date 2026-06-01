<?php


require_once("include/bittorrent.php");
require_once("include/test_torrents.php");
require_once("include/multitracker.php");

dbconn(false);
parked();
test_torrents_ensure_schema();
multitracker_ensure_schema();

function browse_fmt_added($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return 'неизвестно';
    }

    $ts = strtotime($datetime);
    if (!$ts) {
        return htmlspecialchars_uni($datetime);
    }

    if (date('Y-m-d', $ts) === date('Y-m-d')) {
        return 'сегодня в ' . date('H:i', $ts);
    }

    return date('d.m.y в H:i', $ts);
}

function browse_year_options($selected = 0) {
    $options = '<option value="0">все года</option>';
    $currentYear = max((int)date('Y'), 2026);

    for ($year = $currentYear; $year >= 1900; $year--) {
        $isSelected = ((int)$selected === $year) ? ' selected="selected"' : '';
        $options .= '<option value="' . $year . '"' . $isSelected . '>' . $year . '</option>';
    }

    return $options;
}

function browse_selected($current, $value) {
    return ((string)$current === (string)$value) ? ' selected="selected"' : '';
}

function browse_add_category_options($cats, $selected) {
    $groups = array(
        1001 => 'Все сериалы',
        1002 => 'Все фильмы',
        1003 => 'Все мульты',
        1006 => 'Шоу, концерты, спорт',
        1004 => 'Вся музыка',
    );

    $html = '<option value="0">Поиск по разделам</option>';
    foreach ($groups as $value => $label) {
        $html .= '<option value="' . (int)$value . '" class="green"' . browse_selected($selected, $value) . '>' . htmlspecialchars_uni($label) . '</option>';
    }

    foreach ($cats as $cat) {
        $html .= '<option value="' . (int)$cat['id'] . '"' . browse_selected($selected, $cat['id']) . '>' . htmlspecialchars_uni($cat['name']) . '</option>';
    }

    return $html;
}

function browse_format_groups() {
    return array(
        array('value' => 101, 'label' => 'Форматы видео', 'disabled' => true),
        array('value' => 3, 'label' => '|- Рипы HD (1080|720)'),
        array('value' => 3001, 'label' => '|-- 1080'),
        array('value' => 3002, 'label' => '|-- 720'),
        array('value' => 1, 'label' => '|- Рипы DVD и BD (HD)'),
        array('value' => 4, 'label' => '|- HD Blu-Ray и Remux'),
        array('value' => 2, 'label' => '|- DVD-5 и DVD-9'),
        array('value' => 5, 'label' => '|- Рипы TV'),
        array('value' => 6, 'label' => '|- 3D'),
        array('value' => 7, 'label' => '|- 4K'),
        array('value' => 201, 'label' => 'Форматы аудио', 'disabled' => true),
        array('value' => 51, 'label' => '|- Lossless'),
        array('value' => 52, 'label' => '|- MP3 и AAC'),
        array('value' => 301, 'label' => 'Форматы игр и программ', 'disabled' => true),
        array('value' => 61, 'label' => '|- Компьютер'),
        array('value' => 62, 'label' => '|- Приставка'),
        array('value' => 63, 'label' => '|- Мобильные устройства'),
        array('value' => 64, 'label' => '|- Навигация'),
    );
}

function browse_format_options($selected) {
    $html = '<option value="0"' . browse_selected($selected, 0) . '>Все форматы</option>';
    foreach (browse_format_groups() as $format) {
        $disabled = !empty($format['disabled']) ? ' disabled="disabled"' : '';
        $html .= '<option value="' . (int)$format['value'] . '"' . $disabled . browse_selected($selected, $format['value']) . '>' . htmlspecialchars_uni($format['label']) . '</option>';
    }

    return $html;
}

function browse_format_rules() {
    return array(
        1 => array('DVDRip', 'BDRip', 'HDRip', 'WEB-DL', 'WEBRip'),
        2 => array('DVD-5', 'DVD-9'),
        3 => array('1080p', '720p', '1080', '720', 'HDTVRip', 'BDRip', 'WEB-DL', 'HDRip'),
        4 => array('Blu-Ray', 'BluRay', 'BDRemux', 'Remux', 'BDMV'),
        5 => array('TVRip', 'HDTVRip', 'SATRip', 'DVB', 'IPTV'),
        6 => array('3D'),
        7 => array('4K', '2160p', 'UHD'),
        51 => array('Lossless', 'FLAC', 'APE', 'WavPack'),
        52 => array('MP3', 'AAC', 'M4A'),
        61 => array('PC', 'Windows', 'MacOS', 'Linux', 'Компьютер'),
        62 => array('PlayStation', 'PS4', 'PS5', 'Xbox', 'Nintendo', 'Switch', 'Приставка'),
        63 => array('Android', 'iOS', 'iPadOS', 'Mobile', 'Мобильные'),
        64 => array('Навигация', 'GPS', 'Navitel', 'Garmin', 'iGO'),
        3001 => array('1080p', '1080', '1920x1080'),
        3002 => array('720p', '720', '1280x720'),
    );
}

$cats = genrelist();
$searchstr = isset($_GET['s']) ? (string)unesc($_GET['s']) : (isset($_GET['search']) ? (string)unesc($_GET["search"]) : '');
$cleansearchstr = htmlspecialchars_uni($searchstr);
if ($cleansearchstr === '') {
    unset($cleansearchstr);
}

$searchMode = isset($_GET['g']) ? (int)$_GET['g'] : 0;
$category = isset($_GET['c']) ? (int)$_GET['c'] : (isset($_GET['cat']) ? (int)$_GET['cat'] : 0);
$formatSelected = isset($_GET['v']) ? (int)$_GET['v'] : 0;
$selectedYear = isset($_GET['d']) ? (int)$_GET['d'] : (isset($_GET['year']) ? (int)$_GET['year'] : 0);
$filterSelected = isset($_GET['w']) ? (int)$_GET['w'] : (isset($_GET['incldead']) ? (int)$_GET['incldead'] : 0);
$sortField = isset($_GET['t']) ? (int)$_GET['t'] : 0;
$sortDirection = isset($_GET['f']) ? (int)$_GET['f'] : 0;

$sortColumns = array(
    0 => 't.added',
    1 => 'seeders',
    2 => 'leechers',
    3 => 't.size',
    4 => 't.comments',
    5 => 't.times_completed',
    6 => 't.added',
);
$orderby = 'ORDER BY t.not_sticky DESC, ' . ($sortColumns[$sortField] ?? 't.added') . ' ' . ($sortDirection === 1 ? 'ASC' : 'DESC') . ', t.id DESC';

$addparam = "";
$wherea = array();
$wherecatina = array();
$joins = array();
$wherea[] = "t.visible = 'yes'";
$wherea[] = "t.banned != 'yes'";
$wherea[] = "t.is_test = 'no'";

if ($category > 0) {
    $categoryGroups = array(
        1001 => array(45, 46),
        1002 => array(6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 24, 35, 37, 38, 39, 47, 48, 49, 50),
        1003 => array(20, 21, 22),
        1006 => array(37, 48, 49, 50),
        1004 => array(3, 4, 5, 42),
    );

    if (isset($categoryGroups[$category])) {
        $wherea[] = "t.category IN (" . implode(',', array_map('intval', $categoryGroups[$category])) . ")";
    } elseif (is_valid_id($category)) {
        $wherea[] = "t.category = " . (int)$category;
    }
}

$wherebase = $wherea;

if (isset($cleansearchstr)) {
    $escapedSearch = sqlwildcardesc($searchstr);
    if ($searchMode === 2) {
        $wherea[] = "t.keywords LIKE '%" . $escapedSearch . "%'";
    } elseif ($searchMode === 1 || $searchMode === 3) {
        $wherea[] = "(t.name LIKE '%" . $escapedSearch . "%' OR t.description LIKE '%" . $escapedSearch . "%' OR t.keywords LIKE '%" . $escapedSearch . "%')";
    } else {
        $wherea[] = "t.name LIKE '%" . $escapedSearch . "%'";
    }
}

if ($selectedYear >= 1900) {
    $wherea[] = "t.name LIKE '%" . (int)$selectedYear . "%'";
}

if ($formatSelected > 0) {
    $formatRules = browse_format_rules();
    if (isset($formatRules[$formatSelected])) {
        $joins['torrent_details'] = 'LEFT JOIN torrent_details AS td ON td.tid = t.id';
        $parts = array();
        foreach ($formatRules[$formatSelected] as $formatWord) {
            $like = "'%" . sqlwildcardesc($formatWord) . "%'";
            $parts[] = "t.name LIKE $like";
            $parts[] = "t.keywords LIKE $like";
            $parts[] = "t.description LIKE $like";
            $parts[] = "t.descr LIKE $like";
        }
        $regexp = implode('|', array_map(function ($formatWord) {
            return preg_quote($formatWord, '/');
        }, $formatRules[$formatSelected]));
        $parts[] = "(td.tid IS NOT NULL AND JSON_VALID(td.data) AND JSON_UNQUOTE(JSON_EXTRACT(td.data, '$.video.quality')) REGEXP " . sqlesc($regexp) . ")";
        $parts[] = "(td.tid IS NOT NULL AND JSON_VALID(td.data) AND JSON_UNQUOTE(JSON_EXTRACT(td.data, '$.video.video')) REGEXP " . sqlesc($regexp) . ")";
        $wherea[] = '(' . implode(' OR ', $parts) . ')';
    }
}

if ($filterSelected === 1) {
    $wherea[] = "DATE(t.added) = CURDATE()";
} elseif ($filterSelected === 2) {
    $wherea[] = "DATE(t.added) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($filterSelected === 3) {
    $wherea[] = "t.added >= DATE_SUB(NOW(), INTERVAL 3 DAY)";
} elseif ($filterSelected === 4) {
    $wherea[] = "t.added >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
} elseif ($filterSelected === 5) {
    $wherea[] = "t.added >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($filterSelected === 6) {
    $wherea[] = "t.size < 1395864371";
} elseif ($filterSelected === 7) {
    $wherea[] = "t.size >= 1395864371 AND t.size < 2362232013";
} elseif ($filterSelected === 8) {
    $wherea[] = "t.size >= 2362232013 AND t.size < 4294967296";
} elseif ($filterSelected === 9) {
    $wherea[] = "t.size >= 4294967296 AND t.size < 10200547328";
} elseif ($filterSelected === 10) {
    $wherea[] = "t.size >= 10200547328";
} elseif ($filterSelected === 11) {
    $wherea[] = "t.free = 'yes'";
} elseif ($filterSelected === 12) {
    $wherea[] = "t.free = 'silver'";
}

$where = implode(" AND ", $wherea);
if (isset($wherecatin) && !empty($wherecatin)) {
    $where .= ($where ? " AND " : "") . "t.category IN (" . $wherecatin . ")";
}
if ($where !== "") {
    $where = "WHERE $where";
}
$joinSql = $joins ? "\n" . implode("\n", $joins) . "\n" : '';

$res = sql_query("SELECT COUNT(*) FROM torrents AS t $joinSql $where") or die(mysql_error());
$row = mysqli_fetch_array($res);
$count = (int)$row[0];
$num_torrents = $count;

if (!$count && isset($cleansearchstr)) {
    $wherea = $wherebase;
    $searcha = explode(" ", $cleansearchstr);
    $sc = 0;

    foreach ($searcha as $searchss) {
        if (strlen($searchss) <= 1) {
            continue;
        }
        $sc++;
        if ($sc > 5) {
            break;
        }
    }

    if ($sc) {
        $where = implode(" AND ", $wherea);
        if ($where !== "") {
            $where = "WHERE $where";
        }
        $res = sql_query("SELECT COUNT(*) FROM torrents AS t $where");
        $row = mysqli_fetch_array($res);
        $count = (int)$row[0];
    }
}

$torrentsperpage = !empty($CURUSER["torrentsperpage"]) ? (int)$CURUSER["torrentsperpage"] : 25;

if ($count) {
    $pagerParams = array(
        's' => $searchstr,
        'g' => $searchMode,
        'c' => $category,
        'v' => $formatSelected,
        'd' => $selectedYear,
        'w' => $filterSelected,
        't' => $sortField,
        'f' => $sortDirection,
    );

    foreach ($pagerParams as $paramKey => $paramValue) {
        if ($paramValue === '' || (string)$paramValue === '0') {
            unset($pagerParams[$paramKey]);
        }
    }

    $addparam = http_build_query($pagerParams, '', '&amp;');
    $addparam = $addparam !== '' ? $addparam . '&amp;' : '';
    list($pagertop, $pagerbottom, $limit) = pager($torrentsperpage, $count, "browse.php?" . $addparam);
    $query = "SELECT t.id, t.moderated, t.moderatedby, t.category, (t.leechers + t.remote_leechers) AS leechers, (t.seeders + t.remote_seeders) AS seeders, t.multitracker, t.last_mt_update, t.free, t.name, t.info_hash, t.times_completed, t.size, t.added, t.comments, t.numfiles, t.filename, t.not_sticky, t.owner, IF(t.numratings < $minvotes, NULL, ROUND(t.ratingsum / t.numratings, 1)) AS rating, c.name AS cat_name, c.image AS cat_pic, u.username, u.class" . ($CURUSER ? ", EXISTS(SELECT * FROM readtorrents WHERE readtorrents.userid = " . sqlesc($CURUSER["id"]) . " AND readtorrents.torrentid = t.id) AS readtorrent" : ", 1 AS readtorrent") . " FROM torrents AS t $joinSql LEFT JOIN categories AS c ON t.category = c.id LEFT JOIN users AS u ON t.owner = u.id $where $orderby $limit";
    $res = sql_query($query) or die(mysql_error());
} else {
    unset($res);
}

$hide_right_blocks = true;
if (isset($cleansearchstr)) {
    stdhead($tracker_lang['search_results_for'] . " \"$cleansearchstr\"");
} else {
    stdhead($tracker_lang['browse']);
}

?>
<form method="get" action="browse.php">
    <div class="bx1_0" style="padding:3px 38px 3px 5px;">
        <table class="tables1">
            <tr>
                <td colspan="3">Поиск раздач ( <a href="faq.php" class="sba">Как пользоваться поиском?</a> )</td>
                <td>Где именно</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="3"><input type="text" name="s" value="<?= htmlspecialchars_uni($searchstr); ?>" class="w98p"></td>
                <td>
                    <span class="sw100">
                        <select name="g" class="w100 styled">
                            <option value="0"<?= browse_selected($searchMode, 0) ?>>В названии</option>
                            <option value="1"<?= browse_selected($searchMode, 1) ?>>Персона</option>
                            <option value="2"<?= browse_selected($searchMode, 2) ?>>Жанры</option>
                            <option value="3"<?= browse_selected($searchMode, 3) ?>>Формула</option>
                        </select>
                    </span>
                </td>
                <td class="center"><input type="submit" value="Поиск раздач" class="buttonS w98p"></td>
            </tr>
            <tr>
                <td>Выбор раздела</td>
                <td>Выбор формата</td>
                <td>Год выхода</td>
                <td>Фильтр поиска</td>
                <td>Сортировка результата</td>
            </tr>
            <tr>
                <td>
                    <span class="sw190">
                        <select name="c" class="w190 styled">
                            <?= browse_add_category_options($cats, $category) ?>
                        </select>
                    </span>
                </td>
                <td>
                    <span class="sw190">
                        <select name="v" class="w190 styled">
                            <?= browse_format_options($formatSelected) ?>
                        </select>
                    </span>
                </td>
                <td>
                    <span class="sw90">
                        <select name="d" class="w90 styled"><?= browse_year_options($selectedYear) ?></select>
                    </span>
                </td>
                <td>
                    <span class="sw100">
                        <select name="w" class="w100 styled">
                            <option value="0"<?= browse_selected($filterSelected, 0) ?>>не выбран</option>
                            <option value="1"<?= browse_selected($filterSelected, 1) ?>>сегодня</option>
                            <option value="2"<?= browse_selected($filterSelected, 2) ?>>вчера</option>
                            <option value="3"<?= browse_selected($filterSelected, 3) ?>>за 3 дня</option>
                            <option value="4"<?= browse_selected($filterSelected, 4) ?>>за неделю</option>
                            <option value="5"<?= browse_selected($filterSelected, 5) ?>>за месяц</option>
                            <option value="6"<?= browse_selected($filterSelected, 6) ?>>менее 1.3ГБ</option>
                            <option value="7"<?= browse_selected($filterSelected, 7) ?>>1.3ГБ - 2.2ГБ</option>
                            <option value="8"<?= browse_selected($filterSelected, 8) ?>>2.2ГБ - 4.0ГБ</option>
                            <option value="9"<?= browse_selected($filterSelected, 9) ?>>4.0ГБ - 9.5ГБ</option>
                            <option value="10"<?= browse_selected($filterSelected, 10) ?>>9.5ГБ и выше</option>
                            <option value="11"<?= browse_selected($filterSelected, 11) ?>>золото</option>
                            <option value="12"<?= browse_selected($filterSelected, 12) ?>>серебро</option>
                        </select>
                    </span>
                </td>
                <td class="nw">
                    <span class="sw70">
                        <select name="t" class="styled">
                            <option value="0"<?= browse_selected($sortField, 0) ?>>Залит</option>
                            <option value="1"<?= browse_selected($sortField, 1) ?>>Сидам</option>
                            <option value="2"<?= browse_selected($sortField, 2) ?>>Пирам</option>
                            <option value="3"<?= browse_selected($sortField, 3) ?>>Размер</option>
                            <option value="4"<?= browse_selected($sortField, 4) ?>>Коммент.</option>
                            <option value="5"<?= browse_selected($sortField, 5) ?>>Скачали</option>
                            <option value="6"<?= browse_selected($sortField, 6) ?>>Посл.комм.</option>
                        </select>
                    </span>
                    <span class="sw70">
                        <select name="f" class="styled">
                            <option value="0"<?= browse_selected($sortDirection, 0) ?>>Убыв.</option>
                            <option value="1"<?= browse_selected($sortDirection, 1) ?>>Возр.</option>
                        </select>
                    </span>
                </td>
            </tr>
            <tr>
                <td colspan="5"><span class="bulet"></span>Найдено <?= number_format((int)$num_torrents, 0, '.', ' ') ?> раздач, в списке отображается только 5000. Пожалуйста, уточните параметры поиска.</td>
            </tr>
        </table>
    </div>
</form>

<div class="pad0x0x5x0 center">
    <img src="pic/pay_bn2.png" style="display:inline-block;" alt="">
</div>

<div class="bx2_0">
    <table class="t_peer w100p">
        <tr class="mn">
            <td class="z w90"></td>
            <td class="w90p"></td>
            <td class="z">Комм.</td>
            <td class="z">Размер</td>
            <td class="z">Сидов</td>
            <td class="z">Пиров</td>
            <td class="z">Залит</td>
            <td class="zl">Раздает</td>
        </tr>
        <?php if ($num_torrents && isset($res)) { ?>
            <?php $rowIndex = 0; ?>
            <?php while ($row = mysqli_fetch_assoc($res)) { ?>
                <?php
                $title = htmlspecialchars_uni($row['name']);
                $catPic = !empty($row['cat_pic']) ? htmlspecialchars_uni($row['cat_pic']) : '';
                $catName = !empty($row['cat_name']) ? htmlspecialchars_uni($row['cat_name']) : '';
                $comments = (int)$row['comments'];
                $sizeText = mksize((int)$row['size']);
                $seeders = (int)$row['seeders'];
                $leechers = (int)$row['leechers'];
                $addedText = browse_fmt_added($row['added']);
                $linkClass = 'r0';
                if ($row['free'] === 'yes') {
                    $linkClass = 'r1';
                } elseif ($row['free'] === 'silver') {
                    $linkClass = 'r2';
                }
                ?>
                <tr class="<?= $rowIndex === 0 ? 'first bg' : 'bg' ?>">
                    <td class="bt">
                        <?php if ($catPic !== '') { ?>
                            <img src="pic/cat/<?= $catPic ?>" class="p90x32 pointer" onclick="cat(<?= (int)$row['category'] ?>);" alt="<?= $catName ?>">
                        <?php } ?>
                    </td>
                    <td class="nam"><a href="details.php?id=<?= (int)$row['id'] ?>&amp;hit=1" class="<?= $linkClass ?>"><?= $title ?></a></td>
                    <td class="s"><?= $comments ?></td>
                    <td class="s"><?= $sizeText ?></td>
                    <td class="sl_s"><?= $seeders ?></td>
                    <td class="sl_p"><?= $leechers ?></td>
                    <td class="s"><?= $addedText ?></td>
                    <td class="sl">
                        <?php if (!empty($row['username'])) { ?>
                            <a href="userdetails.php?id=<?= (int)$row['owner'] ?>" class="u<?= (int)$row['class'] ?>"><?= htmlspecialchars_uni($row['username']) ?></a>
                        <?php } else { ?>
                            <i>(unknown)</i>
                        <?php } ?>
                    </td>
                </tr>
                <?php $rowIndex++; ?>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="8" class="center" style="padding:18px 8px;"><?= htmlspecialchars_uni($tracker_lang['nothing_found'] ?? 'Ничего не найдено') ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

<?php if ($num_torrents && isset($pagertop) && $pagertop) { ?>
    <div class="small" style="padding:6px 0 0 0;"><?= $pagertop ?></div>
<?php } ?>
<?php
stdfoot();
?>
