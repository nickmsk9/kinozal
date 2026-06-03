<?php

if (!defined('IN_TRACKER')) {
    die('Прямой вызов запрещён.');
}

function torrenttable($res, $variant = 'index')
{
    global $pic_base_url, $CURUSER, $use_wait, $use_ttl, $ttl_days, $tracker_lang;

    $rows = array();

    if (!$res) {
        return $rows;
    }

    $is_logged = !empty($CURUSER) && is_array($CURUSER);
    $user_class = $is_logged && isset($CURUSER['class']) ? (int)$CURUSER['class'] : 0;
    $user_id = $is_logged && isset($CURUSER['id']) ? (int)$CURUSER['id'] : 0;
    $is_moderator = function_exists('get_user_class') && get_user_class() >= UC_MODERATOR;

    $is_index = ($variant === 'index');
    $is_mytorrents = ($variant === 'mytorrents');
    $is_bookmarks = ($variant === 'bookmarks');
    $has_ttl = !empty($use_ttl);

    $e = function ($value) {
        return htmlspecialchars_uni((string)$value);
    };

    $lang = function ($key, $default = '') use ($tracker_lang) {
        return isset($tracker_lang[$key]) ? $tracker_lang[$key] : $default;
    };

    $img = function ($file, $title = '', $attrs = '') use ($pic_base_url, $e) {
        $title = (string)$title;
        $title_attr = $title !== ''
            ? ' alt="' . $e($title) . '" title="' . $e($title) . '"'
            : ' alt=""';

        return '<img src="' . $e($pic_base_url . '/' . $file) . '"' . $title_attr . ($attrs !== '' ? ' ' . $attrs : '') . ' />';
    };

    $wait = 0;

    if (!empty($use_wait) && $is_logged && $user_class < UC_VIP) {
        $uploaded = isset($CURUSER['uploaded']) ? (float)$CURUSER['uploaded'] : 0;
        $downloaded = isset($CURUSER['downloaded']) ? (float)$CURUSER['downloaded'] : 0;

        $gigs = $uploaded / 1073741824;
        $ratio = $downloaded > 0 ? ($uploaded / $downloaded) : 0;

        if ($ratio < 0.5 || $gigs < 5) {
            $wait = 48;
        } elseif ($ratio < 0.65 || $gigs < 6.5) {
            $wait = 24;
        } elseif ($ratio < 0.8 || $gigs < 8) {
            $wait = 12;
        } elseif ($ratio < 0.95 || $gigs < 9.5) {
            $wait = 6;
        }
    }

    $script = $is_mytorrents ? 'mytorrents.php' : ($is_bookmarks ? 'bookmarks.php' : 'browse.php');

    $get = $_GET;
    unset($get['sort'], $get['type']);

    $oldlink = http_build_query($get, '', '&amp;');
    $oldlink = $oldlink !== '' ? $oldlink . '&amp;' : '';

    $current_sort = isset($_GET['sort']) ? (int)$_GET['sort'] : 0;
    $current_type = isset($_GET['type']) && $_GET['type'] === 'desc' ? 'desc' : 'asc';

    $sort_defaults = array(
        1 => 'asc',
        2 => 'desc',
        3 => 'desc',
        4 => 'desc',
        5 => 'desc',
        7 => 'desc',
        8 => 'desc',
        9 => 'desc',
        10 => 'desc',
    );

    $sort_link = function ($sort, $title) use ($script, $oldlink, $sort_defaults, $current_sort, $current_type, $e) {
        $type = isset($sort_defaults[$sort]) ? $sort_defaults[$sort] : 'desc';

        if ($current_sort === (int)$sort) {
            $type = $current_type === 'desc' ? 'asc' : 'desc';
        }

        return '<a href="' . $e($script . '?' . $oldlink . 'sort=' . (int)$sort . '&type=' . $type) . '" class="altlink_white">' . $e($title) . '</a>';
    };

    $details_url = function ($id, $extra = '') use ($variant, $is_index, $is_bookmarks, $e) {
        $url = 'details.php?';

        if ($variant === 'mytorrents') {
            $return_to = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $url .= 'returnto=' . urlencode($return_to) . '&amp;';
        }

        $url .= 'id=' . (int)$id;

        if ($is_index || $is_bookmarks) {
            $url .= '&amp;hit=1';
        }

        return $e($url . $extra);
    };

    $cols = 7;
    $cols += $wait > 0 ? 1 : 0;
    $cols += $is_mytorrents ? 1 : 0;
    $cols += $has_ttl ? 1 : 0;
    $cols += ($is_index || $is_bookmarks) ? 1 : 0;
    $cols += ($is_moderator && $is_index) ? 2 : 0;
    $cols += $is_bookmarks ? 1 : 0;

    $out = array();

    if ($is_moderator && $is_index) {
        $out[] = '<form method="post" action="deltorrent.php?mode=delete">';
    } elseif ($is_bookmarks) {
        $out[] = '<form method="post" action="takedelbookmark.php">';
    }

    $out[] = '<tr>';
    $out[] = '<td class="colhead center">' . $e($lang('type', 'Тип')) . '</td>';
    $out[] = '<td class="colhead left">' . $sort_link(1, $lang('name', 'Название')) . ' / ' . $sort_link(4, $lang('added', 'Добавлено')) . '</td>';

    if ($wait > 0) {
        $out[] = '<td class="colhead center">' . $e($lang('wait', 'Ожидание')) . '</td>';
    }

    if ($is_mytorrents) {
        $out[] = '<td class="colhead center">' . $e($lang('visible', 'Видим')) . '</td>';
    }

    $out[] = '<td class="colhead center">' . $sort_link(2, $lang('files', 'Файлы')) . '</td>';
    $out[] = '<td class="colhead center">' . $sort_link(3, $lang('comments', 'Комментарии')) . '</td>';

    if ($has_ttl) {
        $out[] = '<td class="colhead center">' . $e($lang('ttl', 'TTL')) . '</td>';
    }

    $out[] = '<td class="colhead center">' . $sort_link(5, $lang('size', 'Размер')) . '</td>';
    $out[] = '<td class="colhead center">' . $sort_link(7, $lang('seeds', 'Сиды')) . ' | ' . $sort_link(8, $lang('leechers', 'Личи')) . '</td>';

    if ($is_index || $is_bookmarks) {
        $out[] = '<td class="colhead center">' . $sort_link(9, $lang('uploadeder', 'Залил')) . '</td>';
    }

    if ($is_moderator && $is_index) {
        $out[] = '<td class="colhead center">' . $sort_link(10, 'Проверен') . '</td>';
        $out[] = '<td class="colhead center">' . $e($lang('delete', 'Удалить')) . '</td>';
    }

    if ($is_bookmarks) {
        $out[] = '<td class="colhead center">' . $e($lang('delete', 'Удалить')) . '</td>';
    }

    $out[] = '</tr>';
    $out[] = '<tbody id="highlighted">';

    while ($row = mysqli_fetch_assoc($res)) {
        $rows[] = $row;

        $id = isset($row['id']) ? (int)$row['id'] : 0;

        if ($id <= 0) {
            continue;
        }

        $is_sticky = isset($row['not_sticky']) && $row['not_sticky'] === 'no';
        $owner_id = isset($row['owner']) ? (int)$row['owner'] : 0;
        $owned = ($is_logged && $user_id === $owner_id) || $is_moderator;

        $added = isset($row['added']) ? (string)$row['added'] : '';
        $numfiles = isset($row['numfiles']) ? (int)$row['numfiles'] : 0;
        $comments = isset($row['comments']) ? (int)$row['comments'] : 0;
        $seeders = isset($row['seeders']) ? (int)$row['seeders'] : 0;
        $leechers = isset($row['leechers']) ? (int)$row['leechers'] : 0;

        $out[] = '<tr' . ($is_sticky ? ' class="highlight"' : '') . '>';

        $out[] = '<td class="center" style="padding:0;">';

        if (!empty($row['cat_name'])) {
            $cat_id = isset($row['category']) ? (int)$row['category'] : 0;

            $out[] = '<a href="browse.php?cat=' . $cat_id . '">';

            if (!empty($row['cat_pic'])) {
                $out[] = '<img src="' . $e($pic_base_url . '/cat/' . $row['cat_pic']) . '" alt="' . $e($row['cat_name']) . '" />';
            } else {
                $out[] = $e($row['cat_name']);
            }

            $out[] = '</a>';
        } else {
            $out[] = '-';
        }

        $out[] = '</td>';

        $name_html = array();

        if ($is_sticky) {
            $name_html[] = '<b>Важный:</b> ';
        }

        $name_html[] = '<a href="' . $details_url($id) . '"><b>' . $e(isset($row['name']) ? $row['name'] : '') . '</b></a>';

        if (isset($row['free']) && $row['free'] === 'yes') {
            $name_html[] = $img('freedownload.gif', $lang('golden', 'Золотая раздача'));
        } elseif (isset($row['free']) && $row['free'] === 'silver') {
            $name_html[] = $img('silverdownload.gif', $lang('silver', 'Серебряная раздача'));
        }

        if (!$is_bookmarks && $is_logged) {
            $name_html[] = '<a href="bookmark.php?torrent=' . $id . '">' . $img('bookmark.gif', $lang('bookmark_this', 'Добавить в закладки')) . '</a>';
        }

        $name_html[] = '<a href="download.php?id=' . $id . '">' . $img('download.gif', $lang('download', 'Скачать')) . '</a>';

        if (isset($row['multitracker']) && $row['multitracker'] === 'yes' && function_exists('magnet')) {
            $hash = isset($row['info_hash']) ? $row['info_hash'] : '';
            $filename = isset($row['filename']) ? $row['filename'] : '';
            $size_for_magnet = isset($row['size']) ? (int)$row['size'] : 0;

            $name_html[] = '<a href="' . $e(magnet(true, $hash, $filename, $size_for_magnet)) . '">' . $img('magnet.png', $lang('magnet', 'Magnet')) . '</a>';

            $last_update = !empty($row['last_mt_update']) ? strtotime($row['last_mt_update']) : 0;
            $allow_update = $last_update < (TIMENOW - 3600);

            $external_title = $lang(
                $allow_update ? 'external_torrent_update' : 'external_torrent',
                $allow_update ? 'Обновить внешний торрент' : 'Внешний торрент'
            );

            $multi_image = $img('multitracker.png', $external_title);

            $name_html[] = $allow_update
                ? '<a href="update_multi.php?id=' . $id . '">' . $multi_image . '</a>'
                : $multi_image;
        }

        if ($owned) {
            $name_html[] = '<a href="edit.php?id=' . $id . '">' . $img('pen.gif', $lang('edit', 'Редактировать')) . '</a>';
        }

        if ($is_index && isset($row['readtorrent']) && (int)$row['readtorrent'] === 0) {
            $name_html[] = '<span class="red small"><b>[новый]</b></span>';
        }

        if ($added !== '') {
            $name_html[] = '<br /><i>' . $e($added) . '</i>';
        }

        $out[] = '<td class="left">' . implode("\n", $name_html) . '</td>';

        if ($wait > 0) {
            $added_time = $added !== '' ? strtotime($added) : 0;
            $elapsed = $added_time > 0 ? floor((gmtime() - $added_time) / 3600) : $wait;

            if ($elapsed < $wait) {
                $out[] = '<td class="center nowrap"><span class="red"><b>' . number_format(max(0, $wait - $elapsed)) . ' ч.</b></span></td>';
            } else {
                $out[] = '<td class="center nowrap">' . $e($lang('no', 'Нет')) . '</td>';
            }
        }

        if ($is_mytorrents) {
            if (isset($row['visible']) && $row['visible'] === 'no') {
                $out[] = '<td class="center"><span class="red"><b>' . $e($lang('no', 'Нет')) . '</b></span></td>';
            } else {
                $out[] = '<td class="center"><span class="green">' . $e($lang('yes', 'Да')) . '</span></td>';
            }
        }

        if (isset($row['type']) && $row['type'] === 'single') {
            $out[] = '<td class="right">' . $numfiles . '</td>';
        } else {
            $file_extra = $is_index ? '&amp;filelist=1' : '&amp;filelist=1#filelist';
            $out[] = '<td class="right"><b><a href="' . $details_url($id, $file_extra) . '">' . $numfiles . '</a></b></td>';
        }

        if ($comments <= 0) {
            $out[] = '<td class="right">0</td>';
        } else {
            $comm_extra = $is_index ? '&amp;tocomm=1' : '&amp;page=0#startcomments';
            $out[] = '<td class="right"><b><a href="' . $details_url($id, $comm_extra) . '">' . $comments . '</a></b></td>';
        }

        if ($has_ttl) {
            if ($added !== '' && function_exists('sql_timestamp_to_unix_timestamp')) {
                $added_unix = sql_timestamp_to_unix_timestamp($added);
            } else {
                $added_unix = $added !== '' ? strtotime($added) : 0;
            }

            $ttl = max(0, ((int)$ttl_days * 24) - floor((gmtime() - (int)$added_unix) / 3600));

            $out[] = '<td class="center nowrap">' . (int)$ttl . ' ' . ($ttl === 1 ? 'час' : 'часов') . '</td>';
        }

        $size = isset($row['size']) ? (float)$row['size'] : 0;
        $out[] = '<td class="center nowrap">' . str_replace(' ', '<br />', mksize($size)) . '</td>';

        $sl = array();

        if ($seeders > 0) {
            if ($is_index) {
                $ratio = $leechers > 0 ? ($seeders / $leechers) : 1;
                $color = function_exists('get_slr_color') ? get_slr_color($ratio) : 'green';

                $sl[] = '<b><a href="' . $details_url($id, '&amp;toseeders=1') . '"><font color="' . $e($color) . '">' . $seeders . '</font></a></b>';
            } else {
                $link_class = function_exists('linkcolor') ? linkcolor($seeders) : 'green';

                $sl[] = '<b><a class="' . $e($link_class) . '" href="details.php?id=' . $id . '&amp;dllist=1#seeders">' . $seeders . '</a></b>';
            }
        } else {
            $link_class = function_exists('linkcolor') ? linkcolor(0) : 'red';
            $sl[] = '<span class="' . $e($link_class) . '">0</span>';
        }

        $sl[] = '|';

        if ($leechers > 0) {
            if ($is_index) {
                $sl[] = '<b><a href="' . $details_url($id, '&amp;todlers=1') . '">' . number_format($leechers) . '</a></b>';
            } else {
                $link_class = function_exists('linkcolor') ? linkcolor($leechers) : 'red';

                $sl[] = '<b><a class="' . $e($link_class) . '" href="details.php?id=' . $id . '&amp;dllist=1#leechers">' . $leechers . '</a></b>';
            }
        } else {
            $sl[] = '0';
        }

        $out[] = '<td class="center nowrap">' . implode(' ', $sl) . '</td>';

        if ($is_index || $is_bookmarks) {
            if (!empty($row['username'])) {
                $owner_class = isset($row['class']) ? (int)$row['class'] : 0;
                $username = htmlspecialchars_uni($row['username']);

                $owner_html = '<a href="userdetails.php?id=' . $owner_id . '"><b>' . get_user_class_color($owner_class, $username) . '</b></a>';
            } else {
                $owner_html = '<i>неизвестно</i>';
            }

            $out[] = '<td class="center">' . $owner_html . '</td>';
        }

        if ($is_moderator && $is_index) {
            if (isset($row['moderated']) && $row['moderated'] === 'no') {
                $moderated_html = '<span class="red"><b>Нет</b></span>';
            } else {
                $moderatedby = isset($row['moderatedby']) ? (int)$row['moderatedby'] : 0;
                $moderated_html = '<span class="green"><b>Да</b></span>';

                if ($moderatedby > 0) {
                    $moderated_html = '<a href="userdetails.php?id=' . $moderatedby . '">' . $moderated_html . '</a>';
                }
            }

            $out[] = '<td class="center">' . $moderated_html . '</td>';
            $out[] = '<td class="center"><input type="checkbox" name="delete[]" value="' . $id . '" /></td>';
        }

        if ($is_bookmarks) {
            $bookmark_id = isset($row['bookmarkid']) ? (int)$row['bookmarkid'] : 0;
            $out[] = '<td class="center"><input type="checkbox" name="delbookmark[]" value="' . $bookmark_id . '" /></td>';
        }

        $out[] = '</tr>';
    }

    $out[] = '</tbody>';

    if ($is_index && $is_logged) {
        $out[] = '<tr><td class="colhead center" colspan="' . $cols . '"><a href="markread.php" class="altlink_white">Все торренты прочитаны</a></td></tr>';
    }

    if ($is_index && $is_moderator) {
        $out[] = '<tr><td class="right" colspan="' . $cols . '"><input type="submit" class="buttonS" value="Удалить выбранные" /></td></tr>';
    }

    if ($is_bookmarks) {
        $out[] = '<tr><td class="right" colspan="' . $cols . '"><input type="submit" class="buttonS" value="' . $e($lang('delete', 'Удалить')) . '" /></td></tr>';
    }

    if (($is_index && $is_moderator) || $is_bookmarks) {
        $out[] = '</form>';
    }

    print implode("\n", $out) . "\n";

    return $rows;
}