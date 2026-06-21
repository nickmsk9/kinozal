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

require_once("include/bittorrent.php");

dbconn();
loggedinorreturn();
parked();

if (@ini_get('output_handler') == 'ob_gzhandler' AND @ob_get_length() !== false)
{	// if output_handler = ob_gzhandler, turn it off and remove the header sent by PHP
	@ob_end_clean();
	header('Content-Encoding:');
}

/*if (!preg_match(':^/(\d{1,10})/(.+)\.torrent$:', $_SERVER["PATH_INFO"], $matches))
	httperr();*/

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if (!is_valid_id($id))
	stderr($tracker_lang['error'],$tracker_lang['invalid_id']);

/*$id = 0 + $matches[1];
if (!$id)
	httperr();*/

$res = sql_query("SELECT name, filename, owner, banned FROM torrents WHERE id = ".sqlesc($id)) or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_assoc($res);
if (!$row)
	stderr($tracker_lang['error'], $tracker_lang['invalid_id']);

$name = $row['filename'];

$fn = "$torrent_dir/$id.torrent";

if (!$row || !is_file($fn) || !is_readable($fn))
	stderr($tracker_lang['error'], $tracker_lang['unable_to_read_torrent']);

if ($row['banned'] == 'yes' && $row['owner'] != $CURUSER['id'] && get_user_class() < UC_MODERATOR)
	stderr($tracker_lang['error'], 'Упс, а торрентик-то забанен!');

$download_limit = user_effective_torrent_limit($CURUSER);
$downloaded_today = torrent_downloads_today((int)$CURUSER['id']);
$already_downloaded_today = torrent_download_seen_today((int)$CURUSER['id'], $id);

if (!$already_downloaded_today && $downloaded_today >= $download_limit) {
	stderr($tracker_lang['error'], "Ваш суточный лимит раздач исчерпан. Доступно в сутки: $download_limit.");
}

sql_query("UPDATE torrents SET hits = hits + 1 WHERE id = ".sqlesc($id));

$name = str_replace(array(',', ';'), '', $name);

require_once "include/BDecode.php";
require_once "include/BEncode.php";
require_once "include/multitracker.php";

$download_passkey = tracker_issue_user_passkey($CURUSER);

$dict = bdecode(file_get_contents($fn));

$local_announce = $announce_urls[0] . "?passkey=$download_passkey";
$external_announces = array();
$seen_announces = array();

$collect_announce = function ($url) use (&$external_announces, &$seen_announces) {
	$url = multitracker_normalize_url($url);
	if ($url === '' || !multitracker_valid_announce_url($url)) {
		return;
	}
		if (multitracker_is_local_announce_alias($url)) {
			return;
		}

	$key = multitracker_url_key($url);
	if (isset($seen_announces[$key])) {
		return;
	}

	$seen_announces[$key] = true;
	$external_announces[] = $url;
};

if (!empty($dict['announce'])) {
	$collect_announce($dict['announce']);
}
if (!empty($dict['announce-list']) && is_array($dict['announce-list'])) {
	foreach ($dict['announce-list'] as $tier) {
		if (is_array($tier)) {
			foreach ($tier as $url) {
				$collect_announce($url);
			}
		} else {
			$collect_announce($tier);
		}
	}
}

$dict['announce'] = $local_announce;
$dict['announce-list'] = array(array($local_announce));
foreach ($external_announces as $url) {
	$dict['announce-list'][] = array($url);
}

torrent_download_register((int)$CURUSER['id'], $id);

header ("Expires: Tue, 1 Jan 1980 00:00:00 GMT");
header ("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header ("Cache-Control: no-store, no-cache, must-revalidate");
header ("Cache-Control: post-check=0, pre-check=0", false);
header ("Pragma: no-cache");
//header ("X-Powered-by: TBDev Yuna Scatari Edition - http://bit-torrent.kiev.ua");
header ("Accept-Ranges: bytes");
header ("Connection: close");
header ("Content-Transfer-Encoding: binary");
header ("Content-Disposition: attachment; filename=\"".$name."\"");
header ("Content-Type: application/x-bittorrent");
ob_implicit_flush(true);

print(BEncode($dict));

?>
