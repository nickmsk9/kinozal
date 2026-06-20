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

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	http_response_code(405);
	header('Allow: POST');
	exit('Method Not Allowed');
}

$username = isset($_POST['username']) ? trim((string)$_POST['username']) : null;
$password = isset($_POST['password']) ? (string)$_POST['password'] : null;

if ($username === null || $password === null)
	die();

dbconn();

function bark($text = "Имя пользователя или пароль неверны")
{
  stderr("Ошибка входа", $text);
}

$res = sql_query("SELECT id, passhash, secret, enabled, status, ip FROM users WHERE username = " . sqlesc($username) . " LIMIT 1");
$row = mysqli_fetch_array($res);

if (!$row)
	bark("Вы не зарегистрированы в системе.");

if ($row["status"] == 'pending')
	bark("Вы еще не активировали свой аккаунт! Активируйте ваш аккаунт и попробуйте снова.");

if (!tracker_password_verify($password, $row['secret'], $row['passhash']))
	bark();

if ($row["enabled"] == "no")
	bark("Этот аккаунт отключен.");

if (tracker_password_needs_rehash($row["passhash"])) {
	$row["passhash"] = tracker_password_hash($password);
	sql_query("
		UPDATE users
		SET passhash = " . sqlesc($row["passhash"]) . "
		WHERE id = " . (int)$row["id"] . "
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);
}

$peers = sql_query("SELECT 1 FROM peers WHERE userid = " . (int)$row["id"] . " LIMIT 1");
$ip = getip();
if (mysqli_num_rows($peers) > 0 && ($row["ip"] ?? '') != $ip && !empty($row["ip"]))
	bark("Этот пользователь на данный момент активен с другого IP. Вход невозможен.");

logincookie($row["id"], $row["passhash"]);

if (!empty($_POST["returnto"])) {
	$returnto = tracker_safe_local_redirect($_POST["returnto"], '/');
	header("Location: $DEFAULTBASEURL" . $returnto);
	exit;
}
header("Location: $DEFAULTBASEURL/");
exit;

?>
