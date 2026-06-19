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

if (get_user_class() < UC_MODERATOR)
	stderr($tracker_lang['error'], "Permission denied.");

$hide_right_blocks = true;
stdhead("Статистика");
?>

<STYLE TYPE="text/css" MEDIA=screen>
<!--
  a.colheadlink:link, a.colheadlink:visited{
	font-weight: bold;
	color: #FFFFFF;
	text-decoration: none;
	}

	a.colheadlink:hover {
  	text-decoration: underline;
	}
-->
</STYLE>

<?
begin_main_frame();

if (!function_exists('stats_query_rows')) {
	function stats_query_rows($sql)
	{
		$res = sql_query($sql) or sqlerr(__FILE__, __LINE__);
		$rows = array();
		while ($row = mysqli_fetch_assoc($res)) {
			$rows[] = $row;
		}
		return $rows;
	}
}

if (!function_exists('stats_remember')) {
	function stats_remember($key, $ttl, callable $loader)
	{
		return function_exists('tracker_cache_remember')
			? tracker_cache_remember($key, $ttl, $loader)
			: $loader();
	}
}

$counts = stats_remember('stats:counts:v1', 30, function () {
	$res = sql_query("
		SELECT
			(SELECT COUNT(*) FROM torrents) AS torrents_count,
			(SELECT COUNT(*) FROM peers) AS peers_count
	") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);
	return array(
		'torrents_count' => (int)($row['torrents_count'] ?? 0),
		'peers_count' => (int)($row['peers_count'] ?? 0),
	);
});

$n_tor = (int)($counts['torrents_count'] ?? 0);
$n_peers = (int)($counts['peers_count'] ?? 0);

$uporder = isset($_GET['uporder']) ? htmlspecialchars_uni($_GET['uporder']) : '';
$catorder = isset($_GET['catorder']) ? htmlspecialchars_uni($_GET["catorder"]) : '';

if ($uporder == "lastul")
	$orderby = "last DESC, name";
elseif ($uporder == "torrents")
	$orderby = "n_t DESC, name";
elseif ($uporder == "peers")
	$orderby = "n_p DESC, name";
else
	$orderby = "name";

$query = "SELECT
		u.id,
		u.username AS name,
		t.last,
		COALESCE(t.n_t, 0) AS n_t,
		COALESCE(p.n_p, 0) AS n_p
	FROM users AS u
	LEFT JOIN (
		SELECT owner, MAX(added) AS last, COUNT(*) AS n_t
		FROM torrents
		GROUP BY owner
	) AS t ON t.owner = u.id
	LEFT JOIN (
		SELECT t.owner, COUNT(p.id) AS n_p
		FROM torrents AS t
		INNER JOIN peers AS p ON p.torrent = t.id
		GROUP BY t.owner
	) AS p ON p.owner = u.id
	WHERE u.class >= ".UC_UPLOADER."
	ORDER BY $orderby";

$uploaders = stats_remember('stats:uploaders:v1:' . md5($orderby), 60, function () use ($query) {
	return stats_query_rows($query);
});

if (!$uploaders)
	stdmsg("Извините", "Нет заливающих.");
else
{
	begin_frame("Статистика заливающих", True);
	begin_table();
	print("<tr>\n
	<td class=colhead><a href=\"" . $_SERVER['PHP_SELF'] . "?uporder=uploader&amp;catorder=$catorder\" class=colheadlink>Заливающий</a></td>\n
	<td class=colhead><a href=\"" . $_SERVER['PHP_SELF'] . "?uporder=lastul&amp;catorder=$catorder\" class=colheadlink>Последняя заливка</a></td>\n
	<td class=colhead><a href=\"" . $_SERVER['PHP_SELF'] . "?uporder=torrents&amp;catorder=$catorder\" class=colheadlink>Торрентов</a></td>\n
	<td class=colhead>Завершено</td>\n
	<td class=colhead><a href=\"" . $_SERVER['PHP_SELF'] . "?uporder=peers&amp;catorder=$catorder\" class=colheadlink>Пиров</a></td>\n
	<td class=colhead>Завершено</td>\n
	</tr>\n");
	foreach ($uploaders as $uper)
	{
		print("<tr><td><a href=userdetails.php?id=".$uper['id']."><b>".$uper['name']."</b></a></td>\n");
		print("<td " . ($uper['last']?(">".$uper['last']." (".get_elapsed_time(sql_timestamp_to_unix_timestamp($uper['last']))." назад)"):"align=center>---") . "</td>\n");
		print("<td align=right>" . $uper['n_t'] . "</td>\n");
		print("<td align=right>" . ($n_tor > 0?number_format(100 * $uper['n_t']/$n_tor,1)."%":"---") . "</td>\n");
		print("<td align=right>" . $uper['n_p']."</td>\n");
		print("<td align=right>" . ($n_peers > 0?number_format(100 * $uper['n_p']/$n_peers,1)."%":"---") . "</td></tr>\n");
	}
	end_table();
	end_frame();
}

if ($n_tor == 0)
	stdmsg("Извините", "Данные по категориям отсутствуют!");
else
{
  if ($catorder == "lastul")
		$orderby = "last DESC, c.name";
	elseif ($catorder == "torrents")
		$orderby = "n_t DESC, c.name";
	elseif ($catorder == "peers")
		$orderby = "n_p DESC, name";
	else
		$orderby = "c.name";

  $category_query = "SELECT
		c.name,
		t.last,
		COALESCE(t.n_t, 0) AS n_t,
		COALESCE(p.n_p, 0) AS n_p
	FROM categories AS c
	LEFT JOIN (
		SELECT category, MAX(added) AS last, COUNT(*) AS n_t
		FROM torrents
		GROUP BY category
	) AS t ON t.category = c.id
	LEFT JOIN (
		SELECT t.category, COUNT(p.id) AS n_p
		FROM torrents AS t
		INNER JOIN peers AS p ON p.torrent = t.id
		GROUP BY t.category
	) AS p ON p.category = c.id
	ORDER BY $orderby";

	$categories = stats_remember('stats:categories:v1:' . md5($orderby), 60, function () use ($category_query) {
		return stats_query_rows($category_query);
	});

	begin_frame("Активность категорий", True);
	begin_table();
	print("<tr><td class=colhead><a href=\"" . $_SERVER['PHP_SELF'] . "?uporder=$uporder&amp;catorder=category\" class=colheadlink>Категория</a></td>
	<td class=colhead><a href=\"" . $_SERVER['PHP_SELF'] . "?uporder=$uporder&amp;catorder=lastul\" class=colheadlink>Последняя заливка</a></td>
	<td class=colhead><a href=\"" . $_SERVER['PHP_SELF'] . "?uporder=$uporder&amp;catorder=torrents\" class=colheadlink>Торрентов</a></td>
	<td class=colhead>Завершено</td>
	<td class=colhead><a href=\"" . $_SERVER['PHP_SELF'] . "?uporder=$uporder&amp;catorder=peers\" class=colheadlink>Пиров</a></td>
	<td class=colhead>Завершено</td></tr>\n");
	foreach ($categories as $cat)
	{
		print("<tr><td class=rowhead>" . $cat['name'] . "</b></a></td>");
		print("<td " . ($cat['last']?(">".$cat['last']." (".get_elapsed_time(sql_timestamp_to_unix_timestamp($cat['last']))." назад)"):"align = center>---") ."</td>");
		print("<td align=right>" . $cat['n_t'] . "</td>");
		print("<td align=right>" . number_format(100 * $cat['n_t']/$n_tor,1) . "%</td>");
		print("<td align=right>" . $cat['n_p'] . "</td>");
		print("<td align=right>" . ($n_peers > 0?number_format(100 * $cat['n_p']/$n_peers,1)."%":"---") . "</td>\n");
	}
	end_table();
	end_frame();
}

end_main_frame();
stdfoot();
die;
?>
