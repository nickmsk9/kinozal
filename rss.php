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
dbconn();

$passkey = isset($_GET["passkey"]) ? (string) $_GET["passkey"] : '';
if ($passkey) {
	if (!tracker_valid_passkey($passkey)) {
		exit();
	}

	$passkey_hash = tracker_passkey_hash($passkey);
	$user = array(0);
	if (tracker_user_passkeys_available()) {
		$res = sql_query("
			SELECT COUNT(*)
			FROM user_passkeys
			WHERE token_hash = " . sqlesc($passkey_hash) . "
			  AND revoked_at IS NULL
		");
		if ($res) {
			$user = mysqli_fetch_row($res);
		}
	}
	if ((int)$user[0] !== 1) {
		$user = mysqli_fetch_row(sql_query("SELECT COUNT(*) FROM users WHERE passkey = ".sqlesc($passkey)));
	}
	if ((int)$user[0] !== 1) {
		exit();
	}
} else
loggedinorreturn();

$feed = isset($_GET["feed"]) ? (string)$_GET["feed"] : '';

// name a category
$category = array();
$res = sql_query("SELECT id, name FROM categories");
while($cat = mysqli_fetch_assoc($res))
$category[$cat['id']] = $cat['name'];

// RSS Feed description
$DESCR = "RSS Feeds";

$whereParts = array("visible = 'yes'");
if (!empty($_GET['cat'])) {
	$cats = array_filter(array_map('intval', explode(",", (string)$_GET["cat"])));
	if ($cats) {
		$whereParts[] = "category IN (" . implode(", ", $cats) . ")";
	}
}
$where = implode(' AND ', $whereParts);

// start the RSS feed output
header("Content-Type: application/xml");
print("<?xml version=\"1.0\" encoding=\"utf8mb4\" ?>\n<rss version=\"0.91\">\n<channel>\n" .
"<title>" . $SITENAME . "</title>\n<link>" . $DEFAULTBASEURL . "</link>\n<description>" . $DESCR . "</description>\n" .
"<language>en-usde</language>\n<copyright>Copyright © 2006 " . $SITENAME . "</copyright>\n<webMaster>" . $SITEEMAIL . "</webMaster>\n" .
"<image><title><![CDATA[" . $SITENAME . "]]></title>\n<url>" . $DEFAULTBASEURL . "/favicon.gif</url>\n<link>" . $DEFAULTBASEURL . "</link>\n" .
"<width>16</width>\n<height>16</height>\n<description><![CDATA[" . $DESCR . "]]></description>\n<generator><![CDATA[TBDev Yuna Scatari Edition - http://bit-torrent.kiev.ua]]></generator>\n</image>\n");

// get all vars
$res = sql_query("
	SELECT
		t.id, t.name, t.descr, t.filename, t.size, t.category, t.seeders, t.leechers, t.added, t.times_completed,
		COALESCE(SUM(p.downloaded), 0) AS peer_downloaded
	FROM (
		SELECT id, name, descr, filename, size, category, seeders + remote_seeders AS seeders, leechers + remote_leechers AS leechers, added, times_completed
		FROM torrents
		WHERE $where
		ORDER BY added DESC
		LIMIT 15
	) AS t
	LEFT JOIN peers AS p ON p.torrent = t.id AND p.seeder = 'no'
	GROUP BY t.id, t.name, t.descr, t.filename, t.size, t.category, t.seeders, t.leechers, t.added, t.times_completed
	ORDER BY t.added DESC
") or sqlerr(__FILE__, __LINE__);
while ($row = mysqli_fetch_assoc($res)){
$id = (int)$row['id'];
$name = $row['name'];
$descr = $row['descr'];
$filename = $row['filename'];
$size = (float)$row['size'];
$cat = (int)$row['category'];
$seeders = (int)$row['seeders'];
$leechers = (int)$row['leechers'];
$added = $row['added'];

// seeders ?
if($seeders != 1){
$s = "их";
$aktivs="$seeders раздающий($s)";
}
else
$aktivs="нет раздающих";

// leechers ?
if ($leechers != 1){
$l = "ий";
$aktivl="$leechers качающих($l)";
}
else
$aktivl="нет качающих";

// ddl or detail ?
if ($feed == "dl")
$link = "$DEFAULTBASEURL/download.php?id=$id". ($passkey ? "&amp;passkey=$passkey" : "") ."&amp;name=$filename";
else
$link = "$DEFAULTBASEURL/details.php?id=$id&amp;hit=1";

// measure the totalspeed
if ($seeders >= 1 && $leechers >= 1){
$elapsed = max(1, time() - (int)strtotime($added));
$totalspeed = mksize(((float)$size * (int)$row['times_completed'] + (float)$row['peer_downloaded']) / $elapsed) . "/s";
}
else
$totalspeed = "нет траффика";

// output of all data
echo("<item><title><![CDATA[" . $name . "]]></title>\n<link>" . $link . "</link>\n<description><![CDATA[\nКатегория: " . ($category[$cat] ?? '') . " \n Размер: " . mksize($size) . "\n Статус: " . $aktivs . " и " . $aktivl . "\n Скорость: " . $totalspeed . "\n Добавлен: " . $added . "\n Описание:\n " . format_comment($descr) . "\n]]></description>\n</item>\n");
}

echo("</channel>\n</rss>\n");
?>
