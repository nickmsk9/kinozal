<?php


require_once("include/bittorrent.php");

dbconn();
loggedinorreturn();

header("Content-Type: text/html; charset=UTF-8");

function rate_bark($msg)
{
	echo $msg;
	exit;
}

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$rating = (int)($_POST['rating'] ?? $_GET['rating'] ?? 0);

if (!is_valid_id($id)) {
	rate_bark("Неверная раздача.");
}

if ($rating < 1 || $rating > 10) {
	rate_bark("Неверная оценка.");
}

$res = sql_query("SELECT id FROM torrents WHERE id = $id LIMIT 1") or sqlerr(__FILE__, __LINE__);
if (!mysqli_fetch_assoc($res)) {
	rate_bark("Такой раздачи нет.");
}

$userid = (int)$CURUSER['id'];
$own = sql_query("SELECT id FROM ratings WHERE torrent = $id AND user = $userid ORDER BY id ASC") or sqlerr(__FILE__, __LINE__);
$ids = array();
while ($row = mysqli_fetch_assoc($own)) {
	$ids[] = (int)$row['id'];
}

if ($ids) {
	$rating_id = array_shift($ids);
	sql_query("UPDATE ratings SET rating = $rating, added = NOW() WHERE id = $rating_id") or sqlerr(__FILE__, __LINE__);
	if ($ids) {
		sql_query("DELETE FROM ratings WHERE id IN (" . implode(',', $ids) . ")") or sqlerr(__FILE__, __LINE__);
	}
	$message = "Ваша оценка обновлена.";
} else {
	sql_query("INSERT INTO ratings (torrent, user, rating, added) VALUES ($id, $userid, $rating, NOW())") or sqlerr(__FILE__, __LINE__);
	$message = "Ваша оценка принята.";
}

$total = mysqli_fetch_assoc(sql_query("SELECT COUNT(*) AS votes, COALESCE(SUM(rating), 0) AS sumrating FROM ratings WHERE torrent = $id")) or sqlerr(__FILE__, __LINE__);
$votes = (int)$total['votes'];
$sum = (int)$total['sumrating'];
$avg = $votes > 0 ? round($sum / $votes, 1) : 0;

sql_query("UPDATE torrents SET numratings = $votes, ratingsum = $sum WHERE id = $id") or sqlerr(__FILE__, __LINE__);

echo $message . " Оценка <b>" . number_format($avg, 1) . "</b> из <b>10</b><br>Голосов <b>" . $votes . "</b>";

?>
