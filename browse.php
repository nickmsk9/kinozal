<?php

/*
// +--------------------------------------------------------------------------+
// | Project:    TBDevYSE - TBDev Yuna Scatari Edition                        |
// +--------------------------------------------------------------------------+
// | This file is part of TBDevYSE. TBDevYSE is based on TBDev,               |
// | originally by RedBeard of TorrentBits, extensively modified by           |
// | Gartenzwerg.                                                             |
// |                                                                          |
// | TBDevYSE is free software; you can redistribute it and/or modify         |
// | it under the terms of the GNU General Public License as published by     |
// | the Free Software Foundation; either version 2 of the License, or        |
// | (at your option) any later version.                                      |
// |                                                                          |
// | TBDevYSE is distributed in the hope that it will be useful,              |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with TBDevYSE; if not, write to the Free Software Foundation,      |
// | Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            |
// +--------------------------------------------------------------------------+
// |                                               Do not remove above lines! |
// +--------------------------------------------------------------------------+
*/

require_once("include/bittorrent.php");

dbconn(false);
parked();

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
    $currentYear = (int)date('Y');

    for ($year = $currentYear; $year >= 1950; $year--) {
        $isSelected = ((int)$selected === $year) ? ' selected="selected"' : '';
        $options .= '<option value="' . $year . '"' . $isSelected . '>' . $year . '</option>';
    }

    return $options;
}

$cats = genrelist();
$searchstr = isset($_GET['search']) ? (string)unesc($_GET["search"]) : '';
$cleansearchstr = htmlspecialchars_uni($searchstr);
if ($cleansearchstr === '') {
    unset($cleansearchstr);
}

if (isset($_GET['sort']) && isset($_GET['type'])) {
    switch ($_GET['sort']) {
        case '1': $column = "name"; break;
        case '2': $column = "numfiles"; break;
        case '3': $column = "comments"; break;
        case '4': $column = "added"; break;
        case '5': $column = "size"; break;
        case '6': $column = "times_completed"; break;
        case '7': $column = "seeders"; break;
        case '8': $column = "leechers"; break;
        case '9': $column = "owner"; break;
        case '10':
            $column = get_user_class() >= UC_MODERATOR ? "moderatedby" : "id";
            break;
        default:
            $column = "id";
    }

    switch ($_GET['type']) {
        case 'asc':
            $ascdesc = "ASC";
            $linkascdesc = "asc";
            break;
        default:
            $ascdesc = "DESC";
            $linkascdesc = "desc";
            break;
    }

    $orderby = "ORDER BY t." . $column . " " . $ascdesc;
    $pagerlink = "sort=" . intval($_GET['sort']) . "&type=" . $linkascdesc . "&";
} else {
    $orderby = "ORDER BY t.not_sticky DESC, t.id DESC";
    $pagerlink = "";
}

$addparam = "";
$wherea = array();
$wherecatina = array();
$incldead = 0;

if (isset($_GET['incldead'])) {
    if ($_GET["incldead"] == 1) {
        $addparam .= "incldead=1&amp;";
        if (!isset($CURUSER) || get_user_class() < UC_ADMINISTRATOR) {
            $wherea[] = "banned != 'yes'";
        }
    } elseif ($_GET["incldead"] == 2) {
        $addparam .= "incldead=2&amp;";
        $wherea[] = "visible = 'no'";
    } elseif ($_GET["incldead"] == 3) {
        $addparam .= "incldead=3&amp;";
        $wherea[] = "free = 'yes'";
        $wherea[] = "visible = 'yes'";
    } elseif ($_GET["incldead"] == 4) {
        $addparam .= "incldead=4&amp;";
        $wherea[] = "seeders = 0";
        $wherea[] = "visible = 'yes'";
    }
    $incldead = (int)$_GET['incldead'];
} else {
    $wherea[] = "visible = 'yes'";
}

$category = isset($_GET['cat']) ? (int)$_GET["cat"] : 0;
$all = isset($_GET['all']) ? $_GET["all"] : false;

if (!$all) {
    if (empty($_GET) && !empty($CURUSER['notifs'])) {
        $all = true;

        foreach ($cats as $cat) {
            $catid = (int)$cat['id'];
            $all = $all && $catid;

            if (strpos($CURUSER['notifs'], '[cat' . $catid . ']') !== false) {
                $wherecatina[] = $catid;
                $addparam .= 'c' . $catid . '=1&amp;';
            }
        }
    } elseif ($category) {
        if (!is_valid_id($category)) {
            stderr($tracker_lang['error'], 'Invalid category ID.');
        }

        $wherecatina[] = (int)$category;
        $addparam .= 'cat=' . (int)$category . '&amp;';
    } else {
        $all = true;

        foreach ($cats as $cat) {
            $catid = (int)$cat['id'];

            if (empty($_GET['cr' . $catid])) {
                $all = false;
            }

            if (isset($_GET['c' . $catid])) {
                $wherecatina[] = $catid;
                $addparam .= 'c' . $catid . '=1&amp;';
            }
        }
    }
}

if ($all) {
    $wherecatina = array();
    $addparam = "";
}

if (count($wherecatina) > 1) {
    $wherecatin = implode(",", $wherecatina);
} elseif (count($wherecatina) == 1) {
    $wherea[] = "category = $wherecatina[0]";
}

$wherebase = $wherea;

if (isset($cleansearchstr)) {
    $wherea[] = "t.name LIKE '%" . sqlwildcardesc($searchstr) . "%'";
    $addparam .= "search=" . urlencode($searchstr) . "&amp;";
}

$where = implode(" AND ", $wherea);
if (isset($wherecatin) && !empty($wherecatin)) {
    $where .= ($where ? " AND " : "") . "t.category IN (" . $wherecatin . ")";
}
if ($where !== "") {
    $where = "WHERE $where";
}

$res = sql_query("SELECT COUNT(*) FROM torrents AS t $where") or die(mysql_error());
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
    if ($addparam !== '') {
        if ($pagerlink !== '') {
            $lastChar = substr($addparam, -1);
            $addparam .= ($lastChar !== ';') ? '&' . $pagerlink : $pagerlink;
        }
    } else {
        $addparam = $pagerlink;
    }

    list($pagertop, $pagerbottom, $limit) = pager($torrentsperpage, $count, "browse.php?" . $addparam);
    $query = "SELECT t.id, t.moderated, t.moderatedby, t.category, (t.leechers + t.remote_leechers) AS leechers, (t.seeders + t.remote_seeders) AS seeders, t.multitracker, t.last_mt_update, t.free, t.name, t.info_hash, t.times_completed, t.size, t.added, t.comments, t.numfiles, t.filename, t.not_sticky, t.owner, IF(t.numratings < $minvotes, NULL, ROUND(t.ratingsum / t.numratings, 1)) AS rating, c.name AS cat_name, c.image AS cat_pic, u.username, u.class" . ($CURUSER ? ", EXISTS(SELECT * FROM readtorrents WHERE readtorrents.userid = " . sqlesc($CURUSER["id"]) . " AND readtorrents.torrentid = t.id) AS readtorrent" : ", 1 AS readtorrent") . " FROM torrents AS t LEFT JOIN categories AS c ON t.category = c.id LEFT JOIN users AS u ON t.owner = u.id $where $orderby $limit";
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

$selectedCat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$searchMode = isset($_GET['where']) ? (string)$_GET['where'] : 'name';
$sortField = isset($_GET['sort_view']) ? (string)$_GET['sort_view'] : 'added';
$formatSelected = isset($_GET['format']) ? (string)$_GET['format'] : '';
$filterSelected = isset($_GET['incldead']) ? (int)$_GET['incldead'] : 0;
?>
<div class="mn_wrap">
    <div class="bx2_0">
        <div class="pad5x5" style="background:#EEF7FF;">
            <form method="get" action="browse.php">
                <table class="tables2 w100p">
                    <tr>
                        <td colspan="3" style="color:#000000; padding-bottom:4px;">
                            <b>Поиск раздач</b> (
                            <a class="sba" href="faq.php">Как пользоваться поиском?</a>
                            )
                        </td>
                        <td class="w300" style="padding-bottom:4px;">
                            <b>Где именно</b>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <input type="text" id="searchinput" name="search" class="w100p" autocomplete="off" ondblclick="suggest(event.keyCode, this.value);" onkeyup="suggest(event.keyCode, this.value);" onkeypress="return noenter(event.keyCode);" value="<?= htmlspecialchars_uni($searchstr ?? ''); ?>" style="height:22px;">
                        </td>
                        <td class="nowrap">
                            <select name="where" class="w100">
                                <option value="name"<?= $searchMode === 'name' ? ' selected="selected"' : '' ?>>в названии</option>
                                <option value="descr"<?= $searchMode === 'descr' ? ' selected="selected"' : '' ?>>в описании</option>
                            </select>
                            <input class="buttonS" type="submit" value="Поиск раздач" style="margin-left:4px;">
                        </td>
                    </tr>
                    <tr>
                        <td class="small" style="padding-bottom:2px;">Выбор раздела</td>
                        <td class="small" style="padding-bottom:2px;">Выбор формата</td>
                        <td class="small" style="padding-bottom:2px;">Год выхода</td>
                        <td class="small" style="padding-bottom:2px;">Фильтр поиска</td>
                        <td class="small" style="padding-bottom:2px;">Сортировка результата</td>
                    </tr>
                    <tr>
                        <td>
                            <select name="cat" class="w190">
                                <option value="0">Поиск по разделам</option>
                                <?php foreach ($cats as $cat) { ?>
                                    <option value="<?= (int)$cat['id'] ?>"<?= $selectedCat === (int)$cat['id'] ? ' selected="selected"' : '' ?>><?= htmlspecialchars_uni($cat['name']) ?></option>
                                <?php } ?>
                            </select>
                        </td>
                        <td>
                            <select name="format" class="w190">
                                <option value=""<?= $formatSelected === '' ? ' selected="selected"' : '' ?>>Все форматы</option>
                                <option value="bdremux"<?= $formatSelected === 'bdremux' ? ' selected="selected"' : '' ?>>BDRemux</option>
                                <option value="bluray"<?= $formatSelected === 'bluray' ? ' selected="selected"' : '' ?>>Blu-Ray</option>
                                <option value="webdl"<?= $formatSelected === 'webdl' ? ' selected="selected"' : '' ?>>WEB-DL</option>
                                <option value="webrip"<?= $formatSelected === 'webrip' ? ' selected="selected"' : '' ?>>WEBRip</option>
                                <option value="dvdrip"<?= $formatSelected === 'dvdrip' ? ' selected="selected"' : '' ?>>DVDRip</option>
                            </select>
                        </td>
                        <td>
                            <select name="year" class="w90"><?= browse_year_options($selectedYear) ?></select>
                        </td>
                        <td>
                            <select name="incldead" class="w100">
                                <option value="0"<?= $filterSelected === 0 ? ' selected="selected"' : '' ?>>не выбран</option>
                                <option value="1"<?= $filterSelected === 1 ? ' selected="selected"' : '' ?>>включая мёртвые</option>
                                <option value="2"<?= $filterSelected === 2 ? ' selected="selected"' : '' ?>>только мёртвые</option>
                                <option value="3"<?= $filterSelected === 3 ? ' selected="selected"' : '' ?>>золотые</option>
                                <option value="4"<?= $filterSelected === 4 ? ' selected="selected"' : '' ?>>без сидов</option>
                            </select>
                        </td>
                        <td class="nowrap">
                            <select name="sort_view" class="w80">
                                <option value="added"<?= $sortField === 'added' ? ' selected="selected"' : '' ?>>Залит</option>
                                <option value="size"<?= $sortField === 'size' ? ' selected="selected"' : '' ?>>Размер</option>
                                <option value="seeders"<?= $sortField === 'seeders' ? ' selected="selected"' : '' ?>>Сидов</option>
                                <option value="comments"<?= $sortField === 'comments' ? ' selected="selected"' : '' ?>>Комм.</option>
                            </select>
                            <select name="type" class="w60">
                                <option value="desc"<?= (!isset($_GET['type']) || $_GET['type'] === 'desc') ? ' selected="selected"' : '' ?>>Убыв.</option>
                                <option value="asc"<?= (isset($_GET['type']) && $_GET['type'] === 'asc') ? ' selected="selected"' : '' ?>>Возр.</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" style="padding-top:4px;">
                            <span style="color:#000000;">► Найдено <?= number_format((int)$num_torrents, 0, '.', ' ') ?> раздач, в списке отображается только 5000. Пожалуйста, уточните параметры поиска.</span>
                        </td>
                    </tr>
                </table>
                <div id="suggcontainer" style="text-align:left; width:520px; display:none; margin:0 auto;">
                    <div id="suggestions" style="cursor:default; position:absolute; background-color:#FFFFFF; border:1px solid #777777;"></div>
                </div>
            </form>
        </div>
    </div>

    <div class="center" style="padding:6px 0 8px 0;">
        <img src="pic/pay_bn2.png" alt="Меценат" style="display:inline-block;">
    </div>

    <div class="bx2_0">
        <div class="pad5x5" style="padding-top:4px;">
            <table class="tables2 w100p" style="table-layout:fixed;">
                <tr style="background:#F1D29C;">
                    <td style="width:92px;"></td>
                    <td></td>
                    <td class="center nowrap" style="width:45px; font-weight:bold;">Комм.</td>
                    <td class="center nowrap" style="width:70px; font-weight:bold;">Размер</td>
                    <td class="center nowrap" style="width:40px; font-weight:bold;">Сидов</td>
                    <td class="center nowrap" style="width:40px; font-weight:bold;">Пиров</td>
                    <td class="center nowrap" style="width:116px; font-weight:bold;">Залит</td>
                    <td class="center nowrap" style="width:110px; font-weight:bold;">Раздает</td>
                </tr>
                <?php if ($num_torrents && isset($res)) { ?>
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
                        $uploader = isset($row['username']) ? get_user_class_color((int)$row['class'], htmlspecialchars_uni($row['username'])) : '<i>(unknown)</i>';
                        $freeColor = '';

                        if ($row['free'] === 'yes') {
                            $freeColor = '#D38A00';
                        } elseif ($row['free'] === 'silver') {
                            $freeColor = '#5A71B0';
                        }
                        ?>
                        <tr class="bov">
                            <td style="padding:3px 4px; vertical-align:middle;">
                                <?php if ($catPic !== '') { ?>
                                    <img src="pic/cats/<?= $catPic ?>" alt="<?= $catName ?>" style="display:block; max-width:88px;">
                                <?php } else { ?>
                                    <div style="width:88px; height:31px; background:#f5e0b1;"></div>
                                <?php } ?>
                            </td>
                            <td style="padding:3px 8px 3px 0; vertical-align:middle; line-height:1.15;">
                                <a href="details.php?id=<?= (int)$row['id'] ?>&amp;hit=1" style="font-weight:bold;<?= $freeColor !== '' ? ' color:' . $freeColor . ';' : '' ?>">
                                    <?= $title ?>
                                </a>
                            </td>
                            <td class="center" style="vertical-align:middle;"><?= $comments ?></td>
                            <td class="center nowrap" style="vertical-align:middle;"><?= $sizeText ?></td>
                            <td class="center" style="vertical-align:middle;"><span style="color:green; font-weight:bold;"><?= $seeders ?></span></td>
                            <td class="center" style="vertical-align:middle;"><span style="color:red; font-weight:bold;"><?= $leechers ?></span></td>
                            <td class="center nowrap" style="vertical-align:middle;"><?= $addedText ?></td>
                            <td class="center nowrap" style="vertical-align:middle;"><a href="userdetails.php?id=<?= (int)$row['owner'] ?>"><?= $uploader ?></a></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="8" class="center" style="padding:18px 8px;"><?= htmlspecialchars_uni($tracker_lang['nothing_found'] ?? 'Ничего не найдено') ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>

    <?php if ($num_torrents && isset($pagertop) && $pagertop) { ?>
        <div class="small" style="padding:6px 0 0 0;"><?= $pagertop ?></div>
    <?php } ?>
</div>

<script src="js/suggest.js" type="text/javascript"></script>
<?php
stdfoot();
?>
