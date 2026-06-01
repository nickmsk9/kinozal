<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

global $content;

require_once(dirname(__DIR__) . '/include/kz_test_torrents.php');
kz_test_torrents_ensure_schema();

$blocktitle = '';
$content = '';

if (!function_exists('kz_rel_h')) {
    function kz_rel_h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('kz_rel_cut')) {
    function kz_rel_cut($text, $limit)
    {
        $text = trim(strip_tags((string)$text));
        $text = preg_replace('/\s+/u', ' ', $text);

        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($text, 'UTF-8') > $limit
                ? rtrim(mb_substr($text, 0, $limit, 'UTF-8')) . '...'
                : $text;
        }

        return strlen($text) > $limit ? rtrim(substr($text, 0, $limit)) . '...' : $text;
    }
}

if (!function_exists('kz_rel_image_url')) {
    function kz_rel_image_url($image)
    {
        $image = trim(html_entity_decode((string)$image, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($image === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        if (strpos($image, '/') === 0 || strpos($image, 'thumbnail.php?') === 0) {
            return $image;
        }

        return 'thumbnail.php?' . $image;
    }
}

if (!function_exists('kz_rel_poster_src')) {
    function kz_rel_poster_src(array $row)
    {
        $poster = trim((string)($row['poster_url'] ?? ''));
        if ($poster !== '') {
            return $poster;
        }

        if (!empty($row['image1'])) {
            return 'thumbnail.php?' . $row['image1'];
        }

        return '/pic/default_avatar.gif';
    }
}

if (!function_exists('kz_rel_clean_label')) {
    function kz_rel_clean_label($label)
    {
        $label = trim((string)$label);
        $label = rtrim($label, " \t\n\r\0\x0B:");

        if ($label === 'Режиссёр') {
            return 'Режиссер';
        }

        if ($label === 'Описание') {
            return 'О фильме';
        }

        return $label;
    }
}

if (!function_exists('kz_rel_fields')) {
    function kz_rel_fields($torrentName, $descr)
    {
        $fields = array();
        $summary = array();
        $current = '';

        $allowed = array(
            'Название',
            'Оригинальное название',
            'Год выпуска',
            'Жанр',
            'Выпущено',
            'Режиссер',
            'В ролях',
            'О фильме',
            'Качество',
            'Видео',
            'Аудио',
            'Размер',
            'Продолжительность',
            'Перевод',
            'Язык',
            'Субтитры',
        );

        $text = str_replace(array("\r\n", "\r"), "\n", (string)$descr);
        $lines = preg_split('/\n+/u', $text);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $line = preg_replace('/^\[b\](.*?)\[\/b\]\s*:?\s*/iu', '$1: ', $line);
            $line = preg_replace('/<\s*b\s*>(.*?)<\s*\/\s*b\s*>\s*:?\s*/iu', '$1: ', $line);
            $line = trim(strip_tags($line));

            if (preg_match('/^([^:]{1,45}):\s*(.*)$/u', $line, $m)) {
                $label = kz_rel_clean_label($m[1]);
                $value = trim($m[2]);
                $value = preg_replace('/^\s*:\s*/u', '', $value);

                if ($value !== '' && in_array($label, $allowed, true)) {
                    $fields[$label] = isset($fields[$label]) ? $fields[$label] . ' ' . $value : $value;
                    $current = $label;
                    continue;
                }
            }

            if ($current === 'О фильме' && isset($fields['О фильме'])) {
                $fields['О фильме'] .= ' ' . $line;
                continue;
            }

            if (count($summary) < 3) {
                $summary[] = $line;
            }
        }

        $parts = preg_split('/\s*\/\s*/u', (string)$torrentName);
        $parts = array_values(array_filter(array_map('trim', $parts)));

        if (empty($fields['Название']) && isset($parts[0])) {
            $fields['Название'] = $parts[0];
        }

        if (empty($fields['Оригинальное название']) && isset($parts[1]) && !preg_match('/^(19|20)\d{2}/u', $parts[1])) {
            $fields['Оригинальное название'] = $parts[1];
        }

        $nameText = implode(' ', $parts);

        if (empty($fields['Год выпуска']) && preg_match('/(19|20)\d{2}([\-–](19|20)\d{2})?/u', $nameText, $m)) {
            $fields['Год выпуска'] = $m[0];
        }

        if (empty($fields['Качество']) && preg_match('/(WEB-DL|WEBRip|HDRip|BDRip|Blu-Ray|Remux|DVDRip|HDTV|CAMRip|TS|TC|SATRip)/iu', $nameText, $m)) {
            $fields['Качество'] = $m[0];
        }

        if (empty($fields['О фильме']) && !empty($summary)) {
            $fields['О фильме'] = implode(' ', $summary);
        }

        return $fields;
    }
}

if (!function_exists('kz_rel_link_items')) {
    function kz_rel_link_items($value, $type)
    {
        $items = explode(',', (string)$value);
        $links = array();
        $max = $type === 'person' ? 15 : 50;

        foreach ($items as $i => $item) {
            if ($i >= $max) {
                break;
            }

            $item = trim(str_replace('"', '', $item));
            $item = preg_replace('/^\s*:\s*/u', '', $item);

            if ($item === '') {
                continue;
            }

            if (strpos($item, '...') !== false) {
                $links[] = kz_rel_h($item);
                continue;
            }

            $url = $type === 'person'
                ? 'persons.php?s=' . urlencode($item)
                : 'top.php?j=' . urlencode($item);

            $links[] = '<a href="' . kz_rel_h($url) . '" class="sba" target="_blank">' . kz_rel_h($item) . '</a>';
        }

        return implode(', ', $links);
    }
}

if (!function_exists('kz_rel_field')) {
    function kz_rel_field($label, $value)
    {
        $value = trim((string)$value);

        if ($value === '') {
            return '';
        }

        if ($label === 'Жанр' || $label === 'Выпущено') {
            $value = kz_rel_link_items($value, 'tag');
        } elseif ($label === 'Режиссер' || $label === 'В ролях') {
            $value = kz_rel_link_items($value, 'person');
        } else {
            $value = kz_rel_h($value);
        }

        return '<b>' . kz_rel_h($label) . ':</b> ' . $value . '<br />';
    }
}

if (!function_exists('kz_rel_page_url')) {
    function kz_rel_page_url($page)
    {
        $params = $_GET;
        $params['relpage'] = max(0, (int)$page);

        return kz_rel_h($_SERVER['PHP_SELF'] . '?' . http_build_query($params));
    }
}

if (!function_exists('kz_rel_pager')) {
    function kz_rel_pager($page, $hasNext)
    {
        $page = (int)$page;

        if ($page <= 0 && !$hasNext) {
            return '';
        }

        $html = '<div class="paginator"><ul>';

        if ($page > 0) {
            $html .= '<li><a href="' . kz_rel_page_url($page - 1) . '">Назад</a></li>';
        }

        $start = max(0, $page - 2);
        $end = $hasNext ? $page + 2 : $page;

        for ($i = $start; $i <= $end; $i++) {
            $class = $i === $page ? ' class="current"' : '';
            $html .= '<li' . $class . '><a href="' . kz_rel_page_url($i) . '">' . ($i + 1) . '</a></li>';
        }

        if ($hasNext) {
            $html .= '<li><a rel="next" href="' . kz_rel_page_url($page + 1) . '">Вперед</a></li>';
        }

        return $html . '</ul></div>';
    }
}

$perPage = 10;
$page = isset($_GET['relpage']) ? max(0, (int)$_GET['relpage']) : 0;
$offset = $page * $perPage;
$limit = $perPage + 1;

$res = sql_query("
    SELECT
        t.id,
        t.name,
        t.descr,
        t.image1,
        t.image2,
        t.image3,
        t.image4,
        t.image5,
        td.poster_url,
        t.size,
        t.added,
        c.id AS catid,
        c.name AS catname,
        c.image AS catimage
    FROM torrents AS t
    LEFT JOIN categories AS c ON c.id = t.category
    LEFT JOIN torrent_details AS td ON td.tid = t.id
    WHERE t.visible = 'yes'
      AND (t.banned <> 'yes' OR t.banned IS NULL)
      AND (t.is_test <> 'yes' OR t.test_approved_at IS NOT NULL)
    ORDER BY t.added DESC, t.id DESC
    LIMIT " . (int)$offset . ", " . (int)$limit
) or sqlerr(__FILE__, __LINE__);

$rows = array();

while ($row = mysqli_fetch_assoc($res)) {
    $rows[] = $row;
}

if (empty($rows)) {
    $content .= '<div class="tp1_border"><div class="tp1_body">Нет раздач на трекере.</div></div>';
    return;
}

$hasNext = count($rows) > $perPage;

if ($hasNext) {
    array_pop($rows);
}

$content .= '<div class="tp1_border">';

foreach ($rows as $row) {
    $id = (int)$row['id'];
    $title = kz_rel_h($row['name']);
    $detailsUrl = 'details.php?id=' . $id . '&amp;hit=1';
    $fields = kz_rel_fields($row['name'], $row['descr']);

    if (empty($fields['Размер']) && !empty($row['size'])) {
        $fields['Размер'] = function_exists('mksize')
            ? mksize((float)$row['size'])
            : round(((float)$row['size'] / 1073741824), 2) . ' ГБ';
    }

    $catImage = '';

    if (!empty($row['catimage'])) {
        $catTitle = kz_rel_h($row['catname']);
        $catImage = '<a href="browse.php?cat=' . (int)$row['catid'] . '" title="' . $catTitle . '">'
            . '<img class="tp1_icat" src="pic/cat/' . kz_rel_h($row['catimage']) . '" alt="' . $catTitle . '" />'
            . '</a>';
    }

    $desc1 = $catImage;

    foreach (array('Название', 'Оригинальное название', 'Год выпуска', 'Жанр', 'Выпущено', 'Режиссер', 'В ролях') as $label) {
        if (!empty($fields[$label])) {
            $desc1 .= kz_rel_field($label, kz_rel_cut($fields[$label], $label === 'В ролях' ? 230 : 170));
        }
    }

    $desc2 = !empty($fields['О фильме'])
        ? '<b>О фильме:</b> ' . kz_rel_h(kz_rel_cut($fields['О фильме'], 430))
        : '';

    $desc3 = '';

    foreach (array('Качество', 'Видео', 'Аудио', 'Размер', 'Продолжительность', 'Перевод', 'Язык', 'Субтитры') as $label) {
        if (!empty($fields[$label])) {
            $limit = ($label === 'Аудио' || $label === 'Субтитры') ? 260 : 170;
            $desc3 .= kz_rel_field($label, kz_rel_cut($fields[$label], $limit));
        }
    }

    $content .= '
        <div class="tp1_title">
            <a href="' . $detailsUrl . '" title="' . $title . '">' . $title . '</a>
        </div>

        <div class="tp1_body">
            <a href="' . $detailsUrl . '" title="' . $title . '">
                <img class="tp1_img" src="' . kz_rel_h(kz_rel_poster_src($row)) . '" alt="' . $title . '" />
            </a>

            <div class="tp1_desc">
                <div class="tp1_desc1">' . $desc1 . '</div>
                <div class="tp1_desc2">' . $desc2 . '</div>
                <div class="tp1_desc3">' . $desc3 . '</div>

                <a href="download.php?id=' . $id . '" title="Скачать">
                    <img src="pic/dw2.png" class="tp1_but" title="Скачать" alt="Скачать" />
                </a>
            </div>

            <div class="clr"></div>
        </div>';
}

$content .= '</div>';
$content .= kz_rel_pager($page, $hasNext);
