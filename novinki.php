<?php

require_once("include/bittorrent.php");

dbconn(false);
//loggedinorreturn();

$hide_right_blocks = true;

$title = "Новинки раздач - новые материалы Кинозал.ТВ";
stdhead($title);

function novinki_poster_src(array $row)
{
    $poster = trim((string)($row['poster_url'] ?? ''));
    if ($poster !== '') {
        return htmlspecialchars_uni($poster);
    }

    $image = trim((string)($row['image1'] ?? ''));
    if ($image !== '') {
        return 'thumbnail.php?' . htmlspecialchars_uni($image);
    }

    return '/pic/default_avatar.gif';
}

$res = sql_query("
	SELECT t.id, t.name, t.image1, t.category, t.added, td.poster_url
	FROM torrents AS t
	LEFT JOIN torrent_details AS td ON td.tid = t.id
	WHERE t.visible = 'yes'
	  AND t.banned = 'no'
	ORDER BY t.added DESC
	LIMIT 120
") or sqlerr(__FILE__, __LINE__);

$posters = array();
while ($row = mysqli_fetch_assoc($res)) {
    $posters[] = $row;
}

$page_patterns = array('/novinki.php%', 'novinki.php%');
?>
<div class="mn_wrap">
    <div class="bx2_0">
        <table class="main" width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td style="background:#F1D29C; padding:3px 6px;">
                    <a class="sbab" href="novinki.php">Новинки раздач - новые материалы Кинозал.ТВ</a>
                </td>
            </tr>
            <tr valign="top">
                <td style="padding:4px;">
                    <div class="floatleft" style="width:200px; margin-right:8px;">
                        <div style="padding-bottom:4px;">
                            <img src="pic/p_novinki.jpg" alt="Новинки кино" title="Новинки кино" border="0">
                        </div>
                        <div style="background:#F1D29C; color:#000000; font-weight:bold; padding:3px 6px;">Новинки Кинозал.ТВ</div>
                        <div style="padding:6px 4px 10px 4px; text-align:justify;">
                            Здесь собираются новые раздачи трекера: фильмы, сериалы, мультфильмы, музыка, программы и другие свежие материалы.
                        </div>
                        <div style="background:#F1D29C; color:#000000; font-weight:bold; padding:3px 6px;">Информация</div>
                        <div style="padding:6px 4px 4px 4px; text-align:justify;">
                            Раздачи отсортированы по дате добавления. Если у материала нет постера, показывается иконка его категории.
                        </div>
                    </div>

                    <div style="overflow:hidden;">
                        <?php if ($posters) { ?>
                            <table class="main" width="100%" border="0" cellspacing="4" cellpadding="0">
                                <tr valign="top">
                                    <?php
                                    $columns = 12;
                                    $index = 0;
                                    foreach ($posters as $poster) {
                                        if ($index > 0 && $index % $columns == 0) {
                                            print("</tr>\n<tr valign=\"top\">");
                                        }

                                        $poster_title = htmlspecialchars_uni($poster['name']);
                                        $poster_src = novinki_poster_src($poster);
                                        $poster_id = (int)$poster['id'];

                                        print("<td style=\"padding:0; width:104px; vertical-align:top;\">");
                                        print("<a href=\"details.php?id=$poster_id&amp;hit=1\" title=\"$poster_title\"><img src=\"$poster_src\" alt=\"$poster_title\" title=\"$poster_title\" border=\"0\" style=\"display:block; width:104px;\"></a>");
                                        print("</td>\n");
                                        $index++;
                                    }

                                    if ($index > 0) {
                                        $rest = $columns - ($index % $columns);
                                        if ($rest > 0 && $rest < $columns) {
                                            for ($i = 0; $i < $rest; $i++) {
                                                print("<td style=\"width:104px;\">&nbsp;</td>");
                                            }
                                        }
                                    }
                                    ?>
                                </tr>
                            </table>
                        <?php } else { ?>
                            <div style="padding:10px;">Нет доступных новинок.</div>
                        <?php } ?>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <?= page_online_box($page_patterns, "никого нет на этой странице"); ?>
</div>
<?php
stdfoot();
?>
