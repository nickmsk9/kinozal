<?php

if (!defined('BLOCK_FILE')) {
    header("Location: ../index.php");
    exit;
}

if (!function_exists('kz_release_block_extract_fields')) {
    function kz_release_block_extract_fields($torrentName, $descr)
    {
        $text = (string)$descr;
        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        $lines = preg_split('/\n+/u', $text);

        $fields = array();
        $summary = '';
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
            'Субтитры',
        );

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^([^:]{1,40}):\s*(.*)$/u', $line, $matches)) {
                $label = trim($matches[1]);
                $value = trim($matches[2]);

                if (in_array($label, $allowed, true) && $value !== '') {
                    $fields[$label] = $value;
                    continue;
                }
            }

            if ($summary === '') {
                $summary = $line;
            }
        }

        $nameParts = preg_split('/\s*\/\s*/u', (string)$torrentName);
        $cleanParts = array_values(array_filter(array_map('trim', $nameParts), static function ($part) {
            return $part !== '';
        }));

        if (empty($fields['Название']) && isset($cleanParts[0])) {
            $fields['Название'] = $cleanParts[0];
        }

        if (empty($fields['Оригинальное название']) && isset($cleanParts[1]) && !preg_match('/^\d{4}$/', $cleanParts[1])) {
            $fields['Оригинальное название'] = $cleanParts[1];
        }

        if (empty($fields['Год выпуска'])) {
            foreach ($cleanParts as $part) {
                if (preg_match('/^(19|20)\d{2}$/', $part)) {
                    $fields['Год выпуска'] = $part;
                    break;
                }
            }
        }

        if ($summary !== '' && empty($fields['О фильме'])) {
            $fields['О фильме'] = $summary;
        }

        return $fields;
    }
}

if (!function_exists('kz_release_block_render_pager')) {
    function kz_release_block_render_pager($count, $perPage)
    {
        $pages = (int)ceil($count / $perPage);
        if ($pages <= 1) {
            return '';
        }

        $page = isset($_GET['relpage']) ? (int)$_GET['relpage'] : 0;
        if ($page < 0) {
            $page = 0;
        }
        if ($page >= $pages) {
            $page = $pages - 1;
        }

        $params = $_GET;
        unset($params['relpage']);

        $baseHref = $_SERVER['PHP_SELF'];
        $queryPrefix = '';
        if (!empty($params)) {
            $queryPrefix = http_build_query($params) . '&';
        }

        $html = '<table class="main" border="0" cellspacing="0" cellpadding="0"><tr>';

        if ($page > 0) {
            $html .= '<td class="pager"><a href="' . $baseHref . '?' . $queryPrefix . 'relpage=' . ($page - 1) . '" style="text-decoration: none;"><b>Назад</b></a></td><td class="pagebr">&nbsp;</td>';
        }

        for ($i = 0; $i < $pages; $i++) {
            if ($i === $page) {
                $html .= '<td class="highlight"><b>' . ($i + 1) . '</b></td><td class="pagebr">&nbsp;</td>';
            } else {
                $html .= '<td class="pager"><a href="' . $baseHref . '?' . $queryPrefix . 'relpage=' . $i . '" style="text-decoration: none;"><b>' . ($i + 1) . '</b></a></td><td class="pagebr">&nbsp;</td>';
            }
        }

        if ($page < ($pages - 1)) {
            $html .= '<td class="pager"><a href="' . $baseHref . '?' . $queryPrefix . 'relpage=' . ($page + 1) . '" style="text-decoration: none;"><b>Вперед</b></a></td>';
        }

        $html .= '</tr></table>';

        return $html;
    }
}

global $content, $pic_base_url;

$perPage = 10;
$page = isset($_GET['relpage']) ? (int)$_GET['relpage'] : 0;
if ($page < 0) {
    $page = 0;
}

$offset = $page * $perPage;
$limit = $perPage + 1;

$blocktitle = '';
$content = '';

$res = sql_query("
    SELECT
        t.id,
        t.name,
        t.descr,
        t.image1,
        t.size,
        t.added,
        c.id AS catid,
        c.name AS catname,
        c.image AS catimage
    FROM torrents AS t
    LEFT JOIN categories AS c ON c.id = t.category
    WHERE t.visible = 'yes' AND t.banned != 'yes'
    ORDER BY t.added DESC, t.id DESC
    LIMIT $offset, $limit
") or sqlerr(__FILE__, __LINE__);

$rows = array();
while ($row = mysqli_fetch_assoc($res)) {
    $rows[] = $row;
}

if (!$rows) {
    $content .= '<div style="padding: 8px;">Нет раздач на трекере.</div>';
    return;
}

$hasNext = count($rows) > $perPage;
if ($hasNext) {
    array_pop($rows);
}

$content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0">';

foreach ($rows as $row) {
    $torrentId = (int)$row['id'];
    $torrentName = htmlspecialchars_uni($row['name']);
    $fields = kz_release_block_extract_fields($row['name'], $row['descr']);
    $poster = !empty($row['image1']) ? 'torrents/images/' . htmlspecialchars_uni($row['image1']) : 'pic/none.jpg';
    $catImage = !empty($row['catimage']) ? '<a href="browse.php?cat=' . (int)$row['catid'] . '"><img src="pic/cat/' . htmlspecialchars_uni($row['catimage']) . '" alt="' . htmlspecialchars_uni($row['catname']) . '" title="' . htmlspecialchars_uni($row['catname']) . '" border="0"></a>' : '';
    $downloadButton = '<a href="download.php?id=' . $torrentId . '"><img src="pic/dw2.png" onerror="this.onerror=null;this.src=\'' . $pic_base_url . '/download.gif\';" alt="Скачать" title="Скачать раздачу" border="0"></a>';

    $metaOrder = array(
        'Название',
        'Оригинальное название',
        'Год выпуска',
        'Жанр',
        'Выпущено',
        'Режиссер',
        'В ролях',
    );

    $techOrder = array(
        'Качество',
        'Видео',
        'Аудио',
        'Размер',
        'Продолжительность',
        'Перевод',
        'Субтитры',
    );

    $content .= '
    <tr>
        <td style="padding-bottom:6px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="main">
                <tr>
                    <td class="embedded" style="background:#F1D29C; font-weight:bold; padding:3px 6px;"><a href="details.php?id=' . $torrentId . '&amp;hit=1" class="sbab" title="' . $torrentName . '">' . $torrentName . '</a></td>
                </tr>
                <tr>
                    <td style="border-left:1px solid #F1D29C; border-right:1px solid #F1D29C; border-bottom:1px solid #F1D29C; padding:4px;">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr valign="top">
                                <td width="205" style="padding-right:8px;">
                                    <a href="details.php?id=' . $torrentId . '&amp;hit=1" title="' . $torrentName . '"><img src="' . $poster . '" alt="' . $torrentName . '" border="0" style="display:block; width:200px;"></a>
                                </td>
                                <td>
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                        <tr valign="top">
                                            <td>';

    foreach ($metaOrder as $label) {
        if (!empty($fields[$label])) {
            $content .= '<div><b>' . $label . ':</b> ' . htmlspecialchars_uni($fields[$label]) . '</div>';
        }
    }

    if (!empty($fields['О фильме'])) {
        $content .= '<div style="padding-top:8px;"><b>О фильме:</b> ' . htmlspecialchars_uni($fields['О фильме']) . '</div>';
    }

    $content .= '</td>
                                            <td width="92" align="right" style="padding-left:8px;">' . $catImage . '</td>
                                        </tr>
                                    </table>
                                    <div style="padding-top:10px;">';

    foreach ($techOrder as $label) {
        if (!empty($fields[$label])) {
            $content .= '<div><b>' . $label . ':</b> ' . htmlspecialchars_uni($fields[$label]) . '</div>';
        }
    }

    $content .= '</div>
                                    <div align="right" style="padding-top:8px;">' . $downloadButton . '</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>';
}

$content .= '</table>';

$pagerLinks = array();
if ($page > 0) {
    $pagerLinks[] = '<td class="pager"><a href="' . htmlspecialchars_uni($_SERVER['PHP_SELF']) . '?relpage=' . ($page - 1) . '" style="text-decoration:none;"><b>Назад</b></a></td>';
}
if ($hasNext) {
    $pagerLinks[] = '<td class="pager"><a href="' . htmlspecialchars_uni($_SERVER['PHP_SELF']) . '?relpage=' . ($page + 1) . '" style="text-decoration:none;"><b>Вперед</b></a></td>';
}
if ($pagerLinks) {
    $content .= '<table class="main" border="0" cellspacing="0" cellpadding="0"><tr>' . implode('<td class="pagebr">&nbsp;</td>', $pagerLinks) . '</tr></table>';
}

?>
