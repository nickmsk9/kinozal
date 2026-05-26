<?php

require_once("include/bittorrent.php");

dbconn(false);
loggedinorreturn();

$hide_right_blocks = true;

$title = "Новинки кино - Анонс новых работ отечественного и зарубежного кинематографа";
stdhead($title);

$movie_categories = range(1, 26);
$where_categories = implode(',', array_map('intval', $movie_categories));

$res = sql_query("
	SELECT id, name, image1
	FROM torrents
	WHERE visible = 'yes'
	  AND category IN ($where_categories)
	  AND image1 <> ''
	ORDER BY added DESC
	LIMIT 60
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
					<a class="sbab" href="novinki.php">Новинки кино - Анонс новых работ отечественного и зарубежного кинематографа</a>
				</td>
			</tr>
			<tr valign="top">
				<td style="padding:4px;">
					<div class="floatleft" style="width:200px; margin-right:8px;">
						<div style="padding-bottom:4px;">
							<img src="pic/p_novinki.jpg" alt="Новинки кино" title="Новинки кино" border="0">
						</div>
						<div style="background:#F1D29C; color:#000000; font-weight:bold; padding:3px 6px;">Новинки кино Кинозал.ТВ</div>
						<div style="padding:6px 4px 10px 4px; text-align:justify;">
							Новинки кино - Раздел анонсирует новые работы отечественного и зарубежного кинематографа. Предлагаем Вам ознакомиться с новинками фильмов и мультфильмов. Качество представленных видеоматериалов сомнительно. Претензии к качеству видео и звука не принимаются.
						</div>
						<div style="background:#F1D29C; color:#000000; font-weight:bold; padding:3px 6px;">Информация</div>
						<div style="padding:6px 4px 4px 4px; text-align:justify;">
							В разделе представлены раздачи новинок фильмов и мультфильмов в форматах: CAMRip, Telesync (TS, SuperTS), Telecine (TC), Workprint (WP), Screener (SCR, DVDScr, VHSScr), WEBRip; звук CAMRip, TS. После добавления на трекер видеофайлов с лучшим качеством раздачи будут заменены.
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
									$poster_src = "torrents/images/" . htmlspecialchars_uni($poster['image1']);
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

	<div class="bx2_0">
		<?= kz_page_online_box($page_patterns, "никого нет на этой странице"); ?>
	</div>
</div>
<?php
stdfoot();
?>
