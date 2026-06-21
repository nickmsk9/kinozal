<?


require_once("include/bittorrent.php");

$id = (int)($_GET['id'] ?? 0);
$md5 = (string)($_GET['hash'] ?? '');
$email = trim(urldecode((string)($_GET['email'] ?? '')));

if (!$id || $md5 === '' || $email === '' || !validemail($email))
	httperr();

dbconn();

$res = sql_query("SELECT editsecret FROM users WHERE id = $id");
$row = mysqli_fetch_array($res);

if (!$row)
	httperr();

$sec = hash_pad($row["editsecret"]);
if (preg_match('/^ *$/s', $sec))
	httperr();
if (!hash_equals(md5($sec . $email . $sec), $md5))
	httperr();

sql_query("UPDATE users SET editsecret='', editsecret_expires = NULL, email=" . sqlesc($email) . " WHERE id = $id AND editsecret = " . sqlesc($row["editsecret"]));

if (!mysql_affected_rows())
	httperr();

header("Location: my.php?emailch=1");

?>
