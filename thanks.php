<?



require_once("include/bittorrent.php");
dbconn();

loggedinorreturn();
parked();
tracker_require_form_token('POST');

$userid = $CURUSER["id"];
$torrentid = (int)($_POST["torrentid"] ?? 0);

if (empty($torrentid)) {
	stdmsg($tracker_lang["error"], "Не пытайся меня взломать!");
}

$ajax = (string)($_POST["ajax"] ?? '');
if ($ajax == "yes") {
	sql_query("INSERT IGNORE INTO thanks (torrentid, userid) VALUES ($torrentid, $userid)") or sqlerr(__FILE__, __LINE__);

	$thanksby = '';
	$can_not_thanks = false;
	$count = 0;
	$thanked_sql = sql_query("SELECT thanks.userid, users.username, users.class FROM thanks INNER JOIN users ON thanks.userid = users.id WHERE thanks.torrentid = $torrentid ORDER BY thanks.id ASC") or sqlerr(__FILE__, __LINE__);
	while ($thanked_row = mysqli_fetch_assoc($thanked_sql)) {
		$count++;
		if ($thanked_row["userid"] == $CURUSER["id"])
			$can_not_thanks = true;
		$thanks_userid = (int)$thanked_row["userid"];
		$username = $thanked_row["username"];
		$class = (int)$thanked_row["class"];
		$thanksby .= "<a href=\"userdetails.php?id=$thanks_userid\">".get_user_class_color($class, $username)."</a>, ";
	}
	if ($count == 0) {
		$thanksby = $tracker_lang['none_yet'];
	} else {
		if ($thanksby)
			$thanksby = substr($thanksby, 0, -2);
	}
		$hash = htmlspecialchars_uni($CURUSER['hash4u'] ?? tracker_user_form_token());
		$thanksby = "<div id=\"ajax\"><form action=\"thanks.php\" method=\"post\">
		<input type=\"submit\" name=\"submit\" onclick=\"send(); return false;\" value=\"".$tracker_lang['thanks']."\"".($can_not_thanks ? " disabled" : "").">
		<input type=\"hidden\" name=\"hash4u\" value=\"$hash\">
		<input type=\"hidden\" name=\"torrentid\" value=\"$torrentid\">".$thanksby."
	</form></div>";
	header ("Content-Type: text/html; charset=" . $tracker_lang['language_charset']);
	print $thanksby;
} else {
	sql_query("INSERT IGNORE INTO thanks (torrentid, userid) VALUES ($torrentid, $userid)") or sqlerr(__FILE__, __LINE__);
	header("Location: $DEFAULTBASEURL/details.php?id=$torrentid&thanks=1");
}
?>
