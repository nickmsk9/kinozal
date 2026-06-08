<?


require_once("include/bittorrent.php");


$id = intval($_GET["id"]);
$md5 = strval($_GET["secret"]);

if (!$id)
	httperr();

dbconn();


$res = sql_query("SELECT passhash, editsecret, status FROM users WHERE id = $id");
$row = mysqli_fetch_array($res);

if (!$row)
	httperr();

if ($row["status"] != "pending") {
	header("Location: ok.php?type=confirmed");
	exit();
}

$sec = hash_pad($row["editsecret"]);
if ($md5 != md5($sec))
	httperr();

sql_query("UPDATE users SET status='confirmed', editsecret='' WHERE id = $id AND status = 'pending'");

if (!mysql_affected_rows())
	httperr();

logincookie($id, $row["passhash"]);

header("Location: ok.php?type=confirm");

?>