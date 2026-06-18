<?


define ('IN_ANNOUNCE', true);
require_once('./include/core_announce.php');

dbconn(false);

$r = "d" . benc_str("files") . "d";

$fields = "info_hash, times_completed, seeders, leechers";

if (!isset($_GET["info_hash"]))
	$query = "SELECT $fields FROM torrents ORDER BY info_hash";
else {
	if (get_magic_quotes_gpc())
		$hash = bin2hex(stripslashes($_GET["info_hash"]));
	else
		$hash = bin2hex($_GET["info_hash"]);
	if (strlen($_GET["info_hash"]) != 20)
		err("Invalid info-hash (".strlen($_GET["info_hash"]).")");
	$query = "SELECT $fields FROM torrents WHERE info_hash = " . sqlesc($hash);
}

$res = mysql_query($query) or err(mysql_error());

while ($row = mysql_fetch_assoc($res)) {
	$r .= "20:" . pack("H*", ($row["info_hash"])) . "d" .
		benc_str("complete") . "i" . $row["seeders"] . "e" .
		benc_str("downloaded") . "i" . $row["times_completed"] . "e" .
		benc_str("incomplete") . "i" . $row["leechers"] . "e" .
		"e";
}

$r .= "ee";

header("Content-Type: text/plain");
header("Pragma: no-cache");
print($r);

?>
