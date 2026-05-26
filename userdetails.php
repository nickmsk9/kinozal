<?

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

require "include/bittorrent.php";

dbconn(false);

loggedinorreturn();

function bark($msg) {
	global $tracker_lang;
	stdhead($tracker_lang['error']);
	stdmsg($tracker_lang['error'], $msg);
	stdfoot();
	exit;
}

function maketable($res)
{
  global $tracker_lang, $use_ttl, $ttl_days;
  $ret = "<table class=main border=1 cellspacing=0 cellpadding=5>" .
    "<tr><td class=colhead align=left>".$tracker_lang['type']."</td><td class=colhead>".$tracker_lang['name']."</td>".($use_ttl ? "<td class=colhead align=center>".$tracker_lang['ttl']."</td>" : "")."<td class=colhead align=center>".$tracker_lang['size']."</td><td class=colhead align=right>".$tracker_lang['details_seeding']."</td><td class=colhead align=right>".$tracker_lang['details_leeching']."</td><td class=colhead align=center>".$tracker_lang['uploaded']."</td>\n" .
    "<td class=colhead align=center>".$tracker_lang['downloaded']."</td><td class=colhead align=center>".$tracker_lang['ratio']."</td></tr>\n";
  while ($arr = mysqli_fetch_assoc($res))
  {
    if ($arr["downloaded"] > 0)
    {
      $ratio = number_format($arr["uploaded"] / $arr["downloaded"], 3);
      $ratio = "<font color=" . get_ratio_color($ratio) . ">$ratio</font>";
    }
    else
      if ($arr["uploaded"] > 0)
        $ratio = "Inf.";
      else
        $ratio = "---";
    $catid = $arr["catid"];
	$catimage = htmlspecialchars_uni($arr["image"]);
	$catname = htmlspecialchars_uni($arr["catname"]);
	$ttl = ($ttl_days*24) - floor((gmtime() - sql_timestamp_to_unix_timestamp($arr["added"])) / 3600);
	if ($ttl == 1) $ttl .= "&nbsp;час"; else $ttl .= "&nbsp;часов";
	$size = str_replace(" ", "<br />", mksize($arr["size"]));
	$uploaded = str_replace(" ", "<br />", mksize($arr["uploaded"]));
	$downloaded = str_replace(" ", "<br />", mksize($arr["downloaded"]));
	$seeders = number_format($arr["seeders"]);
	$leechers = number_format($arr["leechers"]);
    $ret .= "<tr><td style='padding: 0px'><a href=\"browse.php?cat=$catid\"><img src=\"pic/cats/$catimage\" alt=\"$catname\" border=\"0\" /></a></td>\n" .
		"<td><a href=details.php?id=$arr[torrent]&amp;hit=1><b>" . $arr["torrentname"] .
		"</b></a></td>".($use_ttl ? "<td align=center>$ttl</td>" : "")."<td align=center>$size</td><td align=right>$seeders</td><td align=right>$leechers</td><td align=center>$uploaded</td>\n" .
		"<td align=center>$downloaded</td><td align=center>$ratio</td></tr>\n";
  }
  $ret .= "</table>\n";
  return $ret;
}

function kz_format_minutes($minutes) {
	$minutes = max(0, (int)$minutes);
	$hours = floor($minutes / 60);
	$mins = $minutes % 60;

	return number_format($hours, 0, '.', ' ') . " час. " . $mins . " мин.";
}

function kz_public_rank_name($user) {
	$class = isset($user["class"]) ? (int)$user["class"] : UC_USER;

	if ($class === UC_POWER_USER && !empty($user["added"]) && $user["added"] !== "0000-00-00 00:00:00") {
		$registered_at = sql_timestamp_to_unix_timestamp($user["added"]);
		if ($registered_at > 0 && (TIMENOW - $registered_at) >= (86400 * 365 * 3)) {
			return 'Заслуженный Зритель';
		}
	}

	return get_user_class_name($class);
}

function kz_public_rank_color($user) {
	$class = isset($user["class"]) ? (int)$user["class"] : UC_USER;

	switch ($class) {
		case UC_POWER_USER:
			return '#E08A00';
		case UC_VIP:
			return '#BEA000';
		case UC_UPLOADER:
			return '#8C8CB5';
		case UC_MODERATOR:
			return '#D02BC0';
		case UC_ADMINISTRATOR:
			return '#9B8CD8';
		case UC_SYSOP:
			return '#5B2B2B';
		default:
			return '#000000';
	}
}

function kz_torrent_limit_for_user($user) {
	$class = isset($user["class"]) ? (int)$user["class"] : UC_USER;
	$rank = kz_public_rank_name($user);

	if ($rank === 'Заслуженный Зритель') {
		return 16;
	}

	if ($class >= UC_POWER_USER) {
		return 8;
	}

	return 3;
}

function kz_sidebar_box($title, $items) {
	$html = "<div class=\"bx2_0\">\n<ul class=\"men\">\n";
	$html .= "<li class=\"tp2 center b\">" . $title . "</li>\n";

	foreach ($items as $item) {
		$html .= "<li><span class=\"bulet\"></span>" . $item . "</li>\n";
	}

	$html .= "</ul>\n</div>\n";

	return $html;
}

function kz_profile_panel_start() {
	return "<div class=\"bx2_0\" style=\"margin-bottom:6px;\"><div class=\"pad5x5\" style=\"padding:0;\">";
}

function kz_profile_panel_end() {
	return "</div></div>";
}

function kz_profile_row($label, $value, $label_width = 140) {
	return "<tr>"
		. "<td class=\"rowhead\" style=\"width:" . (int)$label_width . "px; color:#E47D00; text-align:left; padding:2px 8px 2px 8px; white-space:nowrap;\">$label</td>"
		. "<td style=\"padding:2px 8px 2px 8px;\">$value</td>"
		. "</tr>\n";
}

$id = intval($_GET["id"]);

if (!is_valid_id($id))
  bark($tracker_lang['invalid_id']);

$r = @sql_query("SELECT * FROM users WHERE id=$id") or sqlerr(__FILE__, __LINE__);
$user = mysqli_fetch_array($r) or bark("Нет пользователя с таким ID $id.");
if ($user["status"] == "pending") die;
$r = sql_query("SELECT torrents.id, torrents.name, torrents.seeders, torrents.added, torrents.leechers, torrents.category, categories.name AS catname, categories.image AS catimage, categories.id AS catid FROM torrents LEFT JOIN categories ON torrents.category = categories.id WHERE owner=$id ORDER BY name") or sqlerr(__FILE__, __LINE__);
if (mysqli_num_rows($r) > 0) {
  $torrents = "<table class=main border=1 cellspacing=0 cellpadding=5>\n" .
    "<tr><td class=colhead>".$tracker_lang['type']."</td><td class=colhead>".$tracker_lang['name']."</td>".($use_ttl ? "<td class=colhead align=center>".$tracker_lang['ttl']."</td>" : "")."<td class=colhead>".$tracker_lang['tracker_seeders']."</td><td class=colhead>".$tracker_lang['tracker_leechers']."</td></tr>\n";
  while ($a = mysqli_fetch_assoc($r)) {
	$ttl = ($ttl_days*24) - floor((gmtime() - sql_timestamp_to_unix_timestamp($a["added"])) / 3600);
	if ($ttl == 1) $ttl .= "&nbsp;час"; else $ttl .= "&nbsp;часов";
		//$r2 = sql_query("SELECT name, image FROM categories WHERE id=$a[category]") or sqlerr(__FILE__, __LINE__);
		//$a2 = mysqli_fetch_assoc($r2);
		$cat = "<a href=\"browse.php?cat=$a[catid]\"><img src=\"pic/cats/$a[catimage]\" alt=\"$a[catname]\" border=\"0\" /></a>";
      $torrents .= "<tr><td style='padding: 0px'>$cat</td><td><a href=\"details.php?id=" . $a["id"] . "&hit=1\"><b>" . $a["name"] . "</b></a></td>"
      	.($use_ttl ? "<td align=center>$ttl</td>" : "")
        ."<td align=right>$a[seeders]</td><td align=right>$a[leechers]</td></tr>\n";
  }
  $torrents .= "</table>";
}

$it = sql_query("SELECT u.id, u.username, u.class, i.id AS invitedid, i.username AS invitedname, i.class AS invitedclass FROM users AS u LEFT JOIN users AS i ON i.id = u.invitedby WHERE u.invitedroot = $id OR u.invitedby = $id ORDER BY u.invitedby");
if (mysqli_num_rows($it) >= 1) {
	$invitetree = "<table class=\"main\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\"><tr>".
		"<td class=\"colhead\">Пользователь</td><td class=\"colhead\">Пригласил</td>";
	while ($inviter = mysqli_fetch_array($it))
		$invitetree .= "<tr><td><a href=\"userdetails.php?id=$inviter[id]\">".get_user_class_color($inviter["class"], $inviter["username"])."</a></td><td><a href=\"userdetails.php?id=$inviter[invitedid]\">".get_user_class_color($inviter["invitedclass"], $inviter["invitedname"])."</a></td></tr>";
	$invitetree .= "</table>";
}

if ($user["ip"] && (get_user_class() >= UC_MODERATOR || $user["id"] == $CURUSER["id"])) {
  $ip = $user["ip"];
  $dom = @gethostbyaddr($user["ip"]);
  if ($dom == $user["ip"] || @gethostbyname($dom) != $user["ip"])
    $addr = $ip;
  else
  {
    $dom = strtoupper($dom);
    $domparts = explode(".", $dom);
    $domain = $domparts[count($domparts) - 2];
    if ($domain == "COM" || $domain == "CO" || $domain == "NET" || $domain == "NE" || $domain == "ORG" || $domain == "OR" )
      $l = 2;
    else
      $l = 1;
    $addr = "$ip ($dom)";
  }
}

$r = sql_query("SELECT snatched.torrent AS id, snatched.uploaded, snatched.seeder, snatched.downloaded, snatched.startdat, snatched.completedat, snatched.last_action, categories.name AS catname, categories.image AS catimage, categories.id AS catid, torrents.name, torrents.seeders, torrents.leechers FROM snatched JOIN torrents ON torrents.id = snatched.torrent JOIN categories ON torrents.category = categories.id WHERE snatched.finished='yes' AND userid = $id ORDER BY torrent") or sqlerr(__FILE__,__LINE__);
if (mysqli_num_rows($r) > 0) {
$completed = "<table class=\"main\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">\n" .
  "<tr><td class=\"colhead\">Тип</td><td class=\"colhead\">Название</td><td class=\"colhead\">Раздающих</td><td class=\"colhead\">Качающих</td><td class=\"colhead\">Раздал</td><td class=\"colhead\">Скачал</td><td class=\"colhead\">Рейтинг</td><td class=\"colhead\">Начал / Закончил</td><td class=\"colhead\">Действие</td><td class=\"colhead\">Сидирует</td></tr>\n";
while ($a = mysqli_fetch_array($r)) {
if ($a["downloaded"] > 0) {
      $ratio = number_format($a["uploaded"] / $a["downloaded"], 3);
      $ratio = "<font color=\"" . get_ratio_color($ratio) . "\">$ratio</font>";
   } else
	if ($a["uploaded"] > 0)
        $ratio = "Inf.";
	else
		$ratio = "---";
$uploaded = mksize($a["uploaded"]);
$downloaded = mksize($a["downloaded"]);
if ($a["seeder"] == 'yes')
	$seeder = "<font color=\"green\">Да</font>";
else
	$seeder = "<font color=\"red\">Нет</font>";
	$cat = "<a href=\"browse.php?cat=$a[catid]\"><img src=\"pic/cats/$a[catimage]\" alt=\"$a[catname]\" border=\"0\" /></a>";
    $completed .= "<tr><td style=\"padding: 0px\">$cat</td><td><nobr><a href=\"details.php?id=" . $a["id"] . "&amp;hit=1\"><b>" . $a["name"] . "</b></a></nobr></td>" .
      "<td align=\"right\">$a[seeders]</td><td align=\"right\">$a[leechers]</td><td align=\"right\">$uploaded</td><td align=\"right\">$downloaded</td><td align=\"center\">$ratio</td><td align=\"center\"><nobr>$a[startdat]<br />$a[completedat]</nobr></td><td align=\"center\"><nobr>$a[last_action]</nobr></td><td align=\"center\">$seeder</td>\n";
}
$completed .= "</table>";
}

if ($user["added"] == "0000-00-00 00:00:00") {
    $joindate = 'N/A';
} else {
    $joindate = $user["added"] . " (" . get_et(sql_timestamp_to_unix_timestamp($user["added"])) . " " . $tracker_lang['ago'] . ")";
}

$lastseen = $user["last_access"];

if ($lastseen == "0000-00-00 00:00:00") {
    $lastseen = $tracker_lang['never'];
} else {
    $lastseen .= " (" . get_et(sql_timestamp_to_unix_timestamp($lastseen)) . " " . $tracker_lang['ago'] . ")";
}

$res = sql_query("SELECT COUNT(*) FROM comments WHERE user = " . sqlesc((int)$user["id"])) or sqlerr(__FILE__, __LINE__);
$arr3 = mysqli_fetch_row($res);
$torrentcomments = (int)$arr3[0];
//if ($user['donated'] > 0)
//  $don = "<img src=pic/starbig.gif>";

$res = sql_query("SELECT name, flagpic FROM countries WHERE id = $user[country] LIMIT 1") or sqlerr(__FILE__, __LINE__);
$country_name = '';
$country_flag = '';
if (mysqli_num_rows($res) == 1)
{
  $arr = mysqli_fetch_assoc($res);
  $country_name = htmlspecialchars_uni($arr['name']);
  $country_flag = htmlspecialchars_uni($arr['flagpic']);
  $country = "<td class=\"embedded\"><img src=\"pic/flag/$arr[flagpic]\" alt=\"$arr[name]\" style=\"margin-left: 8pt\"></td>";
}

//if ($user["donor"] == "yes") $donor = "<td class=embedded><img src=pic/starbig.gif alt='Donor' style='margin-left: 4pt'></td>";
//if ($user["warned"] == "yes") $warned = "<td class=embedded><img src=pic/warnedbig.gif alt='Warned' style='margin-left: 4pt'></td>";

if ($user["gender"] == "1") $gender = "<img src=\"".$pic_base_url."/male.gif\" alt=\"Парень\" title=\"Парень\">";
elseif ($user["gender"] == "2") $gender = "<img src=\"".$pic_base_url."/female.gif\" alt=\"Девушка\" title=\"Девушка\">";
//elseif ($user["gender"] == "Н/Д") $gender = "<td class=embedded><img src=".$pic_base_url."/na.gif alt='Н/Д' style='margin-left: 4pt'></td>";

$res = sql_query("SELECT torrent, added, uploaded, downloaded, torrents.name AS torrentname, categories.name AS catname, categories.id AS catid, size, image, category, seeders, leechers FROM peers LEFT JOIN torrents ON peers.torrent = torrents.id LEFT JOIN categories ON torrents.category = categories.id WHERE userid = $id AND seeder='no'") or sqlerr(__FILE__, __LINE__);
if (mysqli_num_rows($res) > 0)
  $leeching = maketable($res);
$res = sql_query("SELECT torrent, added, uploaded, downloaded, torrents.name AS torrentname, categories.name AS catname, categories.id AS catid, size, image, category, seeders, leechers FROM peers LEFT JOIN torrents ON peers.torrent = torrents.id LEFT JOIN categories ON torrents.category = categories.id WHERE userid = $id AND seeder='yes'") or sqlerr(__FILE__, __LINE__);
if (mysqli_num_rows($res) > 0)
  $seeding = maketable($res);

///////////////// BIRTHDAY MOD /////////////////////

$age = '';

if (!empty($user["birthday"]) && $user["birthday"] != "0000-00-00") {
    $tzoffset = isset($CURUSER["tzoffset"]) ? (int)$CURUSER["tzoffset"] : 0;

    $current = date("Y-m-d", time() + $tzoffset * 60);

    list($year2, $month2, $day2) = explode('-', $current);

    $birthday = date("Y-m-d", strtotime($user["birthday"]));
    list($year1, $month1, $day1) = explode('-', $birthday);

    $age = (int)$year2 - (int)$year1;

    if ((int)$month2 < (int)$month1 || ((int)$month2 == (int)$month1 && (int)$day2 < (int)$day1)) {
        $age--;
    }
}

///////////////// BIRTHDAY MOD /////////////////////
stdhead("Пользователь :: " . $user["username"]);

$enabled = $user["enabled"] == 'yes';
$uploaded_total = mksize($user["uploaded"]);
$downloaded_total = mksize($user["downloaded"]);
$seed_total = kz_format_minutes(isset($user["seedtime"]) ? $user["seedtime"] : 0);
$leech_total = kz_format_minutes(isset($user["leechtime"]) ? $user["leechtime"] : 0);
$current_theme = function_exists('select_theme') ? select_theme() : 'TBDev';
$avatar_url = $user["avatar"] ? htmlspecialchars_uni($user["avatar"]) : "themes/$current_theme/images/default_avatar.gif";
$profile_name = htmlspecialchars_uni($user["username"]);
$public_rank_name = kz_public_rank_name($user);
$public_rank_color = kz_public_rank_color($user);
$torrent_limit = kz_torrent_limit_for_user($user);
$today_uploaded = "0 Б";
$today_downloaded = "0 Б";
$today_seed = "0 мин.";
$today_leech = "0 мин.";
$today_download_count = 0;
$birth_display = ($user["birthday"] != '0000-00-00' && !empty($user["birthday"])) ? date("d F Y", strtotime($user["birthday"])) . " года" : "не указана";
$city_display = !empty($user["city"]) ? htmlspecialchars_uni($user["city"]) : "не указано";
$favorite_movie = !empty($user["favorite_movie"]) ? htmlspecialchars_uni($user["favorite_movie"]) : "не указано";
$favorite_persons = !empty($user["favorite_persons"]) ? htmlspecialchars_uni($user["favorite_persons"]) : "не указано";
$uploaded_torrent_count = 0;
$res_upload_count = sql_query("SELECT COUNT(*) FROM torrents WHERE owner = $id") or sqlerr(__FILE__, __LINE__);
if ($res_upload_count) {
	$row_upload_count = mysqli_fetch_row($res_upload_count);
	$uploaded_torrent_count = (int)$row_upload_count[0];
}
$comments_label = $torrentcomments > 0 ? "<a href=\"userhistory.php?action=viewcomments&amp;id=$id\">$torrentcomments комментариев</a>" : "нет комментариев";
$torrents_label = $uploaded_torrent_count > 0 ? "<a href=\"#uploaded-list\">$uploaded_torrent_count раздач</a>" : "нет раздач";
$profile_class_html = "<span style=\"color:$public_rank_color; font-weight:bold;\">$public_rank_name</span>";

if (!isset($showpmbutton)) {
	$showpmbutton = 0;
}

if ($CURUSER["id"] != $user["id"]) {
	if (get_user_class() >= UC_MODERATOR) {
		$showpmbutton = 1;
	} elseif ($user["acceptpms"] == "yes") {
		$r = sql_query("SELECT id FROM blocks WHERE userid = " . sqlesc($user["id"]) . " AND blockid = " . sqlesc($CURUSER["id"])) or sqlerr(__FILE__, __LINE__);
		$showpmbutton = (mysqli_num_rows($r) == 1 ? 0 : 1);
	} elseif ($user["acceptpms"] == "friends") {
		$r = sql_query("SELECT id FROM friends WHERE userid = " . sqlesc($user["id"]) . " AND friendid = " . sqlesc($CURUSER["id"])) or sqlerr(__FILE__, __LINE__);
		$showpmbutton = (mysqli_num_rows($r) == 1 ? 1 : 0);
	}
}

print("<div class=\"mn_wrap\">\n");

if (!$enabled) {
	print("<div class=\"bx1 center b red\">Этот аккаунт отключен</div>\n");
}

print("<table class=\"tables2 w100p\">\n<tr>\n");
print("<td class=\"top w200\">\n");

print("<div class=\"bx2_0\"><ul class=\"men\">\n");
print("<li class=\"img center\" style=\"padding:8px 8px 10px 8px;\"><img src=\"" . $avatar_url . "\" alt=\"" . $profile_name . "\" style=\"display:block; width:190px; max-width:190px; margin:0 auto;\"></li>\n");
print("</ul></div>\n");

print(kz_sidebar_box("Меню пользователя", array(
	($showpmbutton ? '<a href="message.php?action=sendmessage&amp;receiver=' . (int)$user["id"] . '">Личные сообщения</a>' : 'Личные сообщения'),
	'<a href="userdetails.php?id=' . (int)$user["id"] . '">Мой профиль</a>',
	'<a href="my.php">Редактировать профиль</a>',
	'<a href="javascript:void(0)">Мои группы</a>',
	'<a href="friends.php">Мои списки друзей</a>',
	'<a href="mytorrents.php">Мои раздачи</a>'
)));
print(kz_sidebar_box("Репутация", array(
	'<a href="mysimpaty.php?id=' . (int)$user["id"] . '">Полученные отзывы</a>',
	'<a href="simpaty.php">Отданные отзывы</a>'
)));
print(kz_sidebar_box("Закладки", array(
	'<a href="bookmarks.php">Раздачи</a>',
	'<a href="javascript:void(0)">Группы</a>',
	'<a href="users.php">Пользователи</a>',
	'<a href="personsearch.php">Персоны</a>'
)));
print(kz_sidebar_box("История", array(
	'<a href="userhistory.php?id=' . (int)$user["id"] . '">Скачанного</a>',
	'<a href="userhistory.php?action=viewcomments&amp;id=' . (int)$user["id"] . '">Комментариев</a>',
	'<a href="javascript:void(0)">Голосований</a>'
)));
print(kz_sidebar_box("Голоса", array(
	'<a href="javascript:void(0)">Получить голоса</a>',
	'<a href="javascript:void(0)">Управление голосами</a>',
	'<a href="suggest.php">Оставить пожелание</a>',
	'<a href="javascript:void(0)">Обнулить счетчик скачиваний</a>'
)));

print("</td>\n");
print("<td class=\"top\">\n");

print(kz_profile_panel_start());
print("<table class=\"tables2 w100p\" style=\"background:#EEF7FF;\">\n");
print("<tr><td colspan=\"2\" style=\"padding:6px 8px 4px 8px; color:#E47D00; font-weight:bold;\">" . $profile_name . "</td></tr>\n");
print("<tr><td colspan=\"2\" style=\"padding:0 0 4px 0;\"><div style=\"height:2px; background:#f1d29c;\"></div></td></tr>\n");
print(kz_profile_row("Звание", $profile_class_html, 140));
print("<tr><td colspan=\"2\" style=\"padding:0 0 4px 0;\"><div style=\"height:2px; background:#f1d29c;\"></div></td></tr>\n");
print(kz_profile_row("Залил", "<span style=\"color:#E47D00; font-weight:bold;\">$uploaded_total</span> ( сегодня: <span style=\"color:#E47D00; font-weight:bold;\">$today_uploaded</span> )", 140));
print(kz_profile_row("Скачал", "<span style=\"color:#E47D00; font-weight:bold;\">$downloaded_total</span> ( сегодня: <span style=\"color:#E47D00; font-weight:bold;\">$today_downloaded</span> )", 140));
$ratio_value = $user["downloaded"] > 0 ? number_format($user["uploaded"] / $user["downloaded"], 2) : '---';
print(kz_profile_row("Рейтинг", "<span style=\"color:#E47D00; font-weight:bold;\">$ratio_value</span>", 140));
print(kz_profile_row("Сид", "<span style=\"color:#E47D00; font-weight:bold;\">$seed_total</span> ( сегодня: <span style=\"color:#E47D00; font-weight:bold;\">$today_seed</span> )", 140));
print(kz_profile_row("Пир", "<span style=\"color:#E47D00; font-weight:bold;\">$leech_total</span> ( сегодня: <span style=\"color:#E47D00; font-weight:bold;\">$today_leech</span> )", 140));
print(kz_profile_row("Торренты", "<span style=\"color:#E47D00; font-weight:bold;\">Доступно в сутки ( $torrent_limit ) Скачано ( $today_download_count ) Последний</span> ( <a class=\"sba\" href=\"#\">здесь</a> )", 140));
print("</table>\n");
print(kz_profile_panel_end());

print(kz_profile_panel_start());
print("<table class=\"tables2 w100p\" style=\"background:#EEF7FF;\">\n");
print(kz_profile_row("Раздачи", $torrents_label, 140));
print(kz_profile_row("Комментарии", $comments_label, 140));
print("</table>\n");
print(kz_profile_panel_end());

print(kz_profile_panel_start());
print("<table class=\"tables2 w100p\" style=\"background:#EEF7FF;\">\n");
print(kz_profile_row("Зарегистрирован", "<span style=\"color:#E47D00; font-weight:bold;\">$joindate</span>", 140));
print(kz_profile_row("Был на трекере", "<span style=\"color:#E47D00; font-weight:bold;\">$lastseen</span>", 140));
print(kz_profile_row("Место жительства", ($country_flag !== '' ? "<img src=\"pic/flag/$country_flag\" alt=\"$country_name\" style=\"vertical-align:middle; margin-right:6px;\">" : "") . "<span style=\"color:#5A71B0;\">" . ($country_name !== '' ? $country_name : "не указано") . "</span>", 140));
print(kz_profile_row("Дата рождения", "<span style=\"color:#5A71B0;\">$birth_display</span>", 140));
print(kz_profile_row("Города", "<span style=\"color:#5A71B0;\">$city_display</span>", 140));
print(kz_profile_row("Любимый фильм", "<span style=\"color:#5A71B0;\">$favorite_movie</span>", 140));
print(kz_profile_row("Любимые персоны", "<span style=\"color:#5A71B0;\">$favorite_persons</span>", 140));
print("</table>\n");
print(kz_profile_panel_end());

print(kz_profile_panel_start());
print("<table class=\"tables2 w100p\" style=\"background:#EEF7FF;\">\n");
print("<tr><td style=\"padding:6px 8px; color:#E47D00; font-weight:bold;\">Проверить подключения ( сид / пир ) к трекеру ( <a class=\"sba\" href=\"testport.php\">проверить</a> )</td></tr>\n");
print("</table>\n");
print(kz_profile_panel_end());

if ($user["info"]) {
	print(kz_profile_panel_start());
	print("<div class=\"pad5x5\" style=\"background:#EEF7FF;\">" . format_comment($user["info"]) . "</div>");
	print(kz_profile_panel_end());
}

if ($uploaded_torrent_count > 0 || !empty($torrents)) {
	print(kz_profile_panel_start());
	print("<a id=\"uploaded-list\"></a><div class=\"pad5x5\" style=\"background:#EEF7FF;\">");
	print($torrents ? $torrents : "нет раздач");
	print("</div>");
	print(kz_profile_panel_end());
}

print(kz_profile_panel_start());
print("<table class=\"tables2 w100p\" style=\"background:#EEF7FF;\">\n");
print("<tr><td class=\"center\" style=\"padding:6px 8px;\">Кто Онлайн здесь, на этой странице [ <a class=\"sba\" href=\"javascript:void(0)\">помочь проекту</a> ]</td></tr>\n");
print("<tr><td style=\"padding:6px 8px; color:#E47D00; font-weight:bold;\">$profile_name</td></tr>\n");
print("</table>\n");
print(kz_profile_panel_end());

print("</td>\n</tr>\n</table>\n");
print("</div>\n");

if (get_user_class() >= UC_MODERATOR && $user["class"] < get_user_class())
{
  begin_frame("Редактирование пользователя", true);
  print("<form method=\"post\" action=\"modtask.php\">\n");
  print("<input type=\"hidden\" name=\"action\" value=\"edituser\">\n");
  print("<input type=\"hidden\" name=\"userid\" value=\"$id\">\n");
  print("<input type=\"hidden\" name=\"returnto\" value=\"userdetails.php?id=$id\">\n");
  print("<table class=\"main\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">\n");
  print("<tr><td class=\"rowhead\">Заголовок</td><td colspan=\"2\" align=\"left\"><input type=\"text\" size=\"60\" name=\"title\" value=\"" . htmlspecialchars_uni($user[title]) . "\"></tr>\n");
	$avatar = htmlspecialchars_uni($user["avatar"]);
  print("<tr><td class=\"rowhead\">Аватар</td><td colspan=\"2\" align=\"left\"><input type=\"text\" size=\"60\" name=\"avatar\" value=\"$avatar\"></tr>\n");
	// we do not want mods to be able to change user classes or amount donated...
	if ($CURUSER["class"] < UC_ADMINISTRATOR)
	  print("<input type=\"hidden\" name=\"donor\" value=\"$user[donor]\">\n");
	else {
	  print("<tr><td class=\"rowhead\">Донор</td><td colspan=\"2\" align=\"left\"><input type=\"radio\" name=\"donor\" value=\"yes\"" .($user["donor"] == "yes" ? " checked" : "").">Да <input type=\"radio\" name=\"donor\" value=\"no\"" .($user["donor"] == "no" ? " checked" : "").">Нет</td></tr>\n");
	}

	if (get_user_class() == UC_MODERATOR && $user["class"] > UC_VIP)
	  print("<input type=\"hidden\" name=\"class\" value=\"$user[class]\">\n");
	else
	{
	  print("<tr><td class=\"rowhead\">Класс</td><td colspan=\"2\" align=\"left\"><select name=\"class\">\n");
	  if (get_user_class() == UC_SYSOP)
	  	$maxclass = UC_SYSOP;
	  elseif (get_user_class() == UC_MODERATOR)
	    $maxclass = UC_VIP;
	  else
	    $maxclass = get_user_class() - 1;
	  for ($i = 0; $i <= $maxclass; ++$i)
	    print("<option value=\"$i\"" . ($user["class"] == $i ? " selected" : "") . ">$prefix" . get_user_class_name($i) . "\n");
	  print("</select></td></tr>\n");
	}
	print("<tr><td class=\"rowhead\">Сбросить день рождения</td><td colspan=\"2\" align=\"left\"><input type=\"radio\" name=\"resetb\" value=\"yes\">Да<input type=\"radio\" name=\"resetb\" value=\"no\" checked>Нет</td></tr>\n");
	$modcomment = htmlspecialchars_uni($user["modcomment"]);
	$supportfor = htmlspecialchars_uni($user["supportfor"]);
	print("<tr><td class=rowhead>Поддержка</td><td colspan=2 align=left><input type=radio name=support value=yes" .($user["support"] == "yes" ? " checked" : "").">Да <input type=radio name=support value=no" .($user["support"] == "no" ? " checked" : "").">Нет</td></tr>\n");
	print("<tr><td class=rowhead>Поддержка для:</td><td colspan=2 align=left><textarea cols=60 rows=6 name=supportfor>$supportfor</textarea></td></tr>\n");
	print("<tr><td class=rowhead>История пользователя</td><td colspan=2 align=left><textarea cols=60 rows=6".(get_user_class() < UC_SYSOP ? " readonly" : " name=modcomment").">$modcomment</textarea></td></tr>\n");
	print("<tr><td class=rowhead>Добавить заметку</td><td colspan=2 align=left><textarea cols=60 rows=3 name=modcomm></textarea></td></tr>\n");
	$warned = $user["warned"] == "yes";

 	print("<tr><td class=\"rowhead\" rowspan=\"2\">Предупреждение</td>
 	<td align=\"center\" colspan=\"2\">" .
  ( $warned
  ? "<font color=\"red\">Пользователь предупреждён</font>"
 	: "<font color=\"green\">Предупреждения нет</font>" ) ."</td></tr>");

	if ($warned) {

		print("<tr><td>Оставить предупреждённым?<br />");
		print("<input name=\"warned\" value=\"yes\" type=\"radio\" checked>Да<input name=\"warned\" value=\"no\" type=\"radio\">Нет");

		$warneduntil = $user['warneduntil'];
		if ($warneduntil == '0000-00-00 00:00:00')
    		print("<td align=\"center\">Предупреждение на неограниченый срок</td></tr>\n");
		else {
    		print("<td align=\"center\">Предупреждение действует до<br />" . date('d.m.Y H:i:s', strtotime($warneduntil)));
	    	print(" (осталось " . get_lt(strtotime($warneduntil)) . ")</td></tr>\n");
 	    }
  } else {
    print("<tr><td>Предупредить на:<br />");
    print("<select name=\"warnlength\">\n");
    print("<option value=\"0\">------</option>\n");
    print("<option value=\"1\">1 неделю</option>\n");
    print("<option value=\"2\">2 недели</option>\n");
    print("<option value=\"4\">4 недели</option>\n");
    print("<option value=\"8\">8 недель</option>\n");
    print("<option value=\"255\">Неограничено</option>\n");
    print("</select></td><td>Причина предупреждения:<br />");
    print("<input type=\"text\" size=\"60\" name=\"warnpm\"></td></tr>");
  }
    /*print("<tr><td class=\"rowhead\" rowspan=\"2\">Включен</td><td colspan=\"2\" align=\"left\"><input name=\"enabled\" value=\"yes\" type=\"radio\"" . ($enabled ? " checked" : "") . ">Да <input name=\"enabled\" value=\"no\" type=\"radio\"" . (!$enabled ? " checked" : "") . ">Нет</td></tr>\n");
    if ($enabled)
    	print("<tr><td colspan=\"2\" align=\"left\">Причина отключения:&nbsp;<input type=\"text\" name=\"disreason\" size=\"60\" /></td></tr>");
	else
		print("<tr><td colspan=\"2\" align=\"left\">Причина включения:&nbsp;<input type=\"text\" name=\"enareason\" size=\"60\" /></td></tr>");*/

	print("<tr><td class=\"rowhead\" rowspan=\"2\">Включен</td><td align=\"center\" colspan=\"2\">".($enabled ? "<font color=\"green\">Пользователь включен</font>" : "<font color=\"red\">Пользователь отключен</font>")."</td></tr>");

$disabler = <<<DIS
<select name="dislength">
	<option value="0">------</option>
	<option value="1">1 неделю</option>
	<option value="2">2 недели</option>
	<option value="4">4 недели</option>
	<option value="8">8 недель</option>
	<option value="255">Неограничено</option>
</select>
DIS;

	if ($enabled)
		print("<tr><td>Отключить на:<br />$disabler</td><td>Причина отключения:<br /><input type=\"text\" name=\"disreason\" size=\"60\" /></td></td></tr>");
	else
		print("<tr><td>Включить?<br /><input name=\"enabled\" value=\"yes\" type=\"radio\">Да <input name=\"enabled\" value=\"no\" type=\"radio\" checked>Нет<br /></td><td>Причина включения:<br /><input type=\"text\" name=\"enareason\" size=\"60\" /></td></tr>");

?>
<script type="text/javascript">

function togglepic(bu, picid, formid)
{
    var pic = document.getElementById(picid);
    var form = document.getElementById(formid);
    
    if(pic.src == bu + "/pic/plus.gif")
    {
        pic.src = bu + "/pic/minus.gif";
        form.value = "minus";
    }else{
        pic.src = bu + "/pic/plus.gif";
        form.value = "plus";
    }
}

</script>
<?
  print("<tr><td class=\"rowhead\">Изменить раздачу</td><td align=\"left\"><img src=\"pic/plus.gif\" id=\"uppic\" onClick=\"togglepic('$DEFAULTBASEURL','uppic','upchange')\" style=\"cursor: pointer;\">&nbsp;<input type=\"text\" name=\"amountup\" size=\"10\" /><td>\n<select name=\"formatup\">\n<option value=\"mb\">MB</option>\n<option value=\"gb\">GB</option></select></td></tr>");
  print("<tr><td class=\"rowhead\">Изменить скачку</td><td align=\"left\"><img src=\"pic/plus.gif\" id=\"downpic\" onClick=\"togglepic('$DEFAULTBASEURL','downpic','downchange')\" style=\"cursor: pointer;\">&nbsp;<input type=\"text\" name=\"amountdown\" size=\"10\" /><td>\n<select name=\"formatdown\">\n<option value=\"mb\">MB</option>\n<option value=\"gb\">GB</option></select></td></tr>");
  print("<tr><td class=\"rowhead\">Сбросить passkey</td><td colspan=\"2\" align=\"left\"><input name=\"resetkey\" value=\"1\" type=\"checkbox\"></td></tr>\n");
  if ($CURUSER["class"] < UC_ADMINISTRATOR)
  	print("<input type=\"hidden\" name=\"deluser\">");
  else
  	print("<tr><td class=\"rowhead\">Удалить</td><td colspan=\"2\" align=\"left\"><input type=\"checkbox\" name=\"deluser\"></td></tr>");
  print("</td></tr>");
  print("<tr><td colspan=\"3\" align=\"center\"><input type=\"submit\" class=\"btn\" value=\"ОК\"></td></tr>\n");
  print("</table>\n");
  print("<input type=\"hidden\" id=\"upchange\" name=\"upchange\" value=\"plus\"><input type=\"hidden\" id=\"downchange\" name=\"downchange\" value=\"plus\">\n");
  print("</form>\n");
  end_frame();
}
stdfoot();

