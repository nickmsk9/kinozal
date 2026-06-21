<?


require_once("include/bittorrent.php");


$id = (int)($_GET["id"] ?? 0);
$md5 = (string)($_GET["secret"] ?? '');

if (!$id || $md5 === '')
	httperr();

dbconn();
tracker_auth_schema_upgrade();


$res = sql_query("SELECT passhash, editsecret, status FROM users WHERE id = $id");
$row = mysqli_fetch_array($res);

if (!$row)
	httperr();

if ($row["status"] != "pending") {
	header("Location: ok.php?type=confirmed");
	exit();
}

$sec = hash_pad($row["editsecret"]);
if (preg_match('/^ *$/s', $sec) || !hash_equals(md5($sec), $md5))
	httperr();

sql_query("UPDATE users SET status='confirmed', editsecret='', editsecret_expires = NULL WHERE id = $id AND status = 'pending'");

if (!mysql_affected_rows())
	httperr();

logincookie($id, $row["passhash"]);

header("Location: ok.php?type=confirm");

?>
