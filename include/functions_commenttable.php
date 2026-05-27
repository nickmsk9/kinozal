<?php

# IMPORTANT: Do not edit below unless you know what you are doing!
if(!defined('IN_TRACKER'))
  die('Прямой вызов запрещён.');

function commenttable($rows, $redaktor = "comment") {
	global $CURUSER, $avatar_max_width;

	$count = 0;
	foreach ($rows as $row)	{
			    if ($row["downloaded"] > 0) {
			    	$ratio = $row['uploaded'] / $row['downloaded'];
			    	$ratio = number_format($ratio, 2);
			    } elseif ($row["uploaded"] > 0) {
			    	$ratio = "Inf.";
			    } else {
			    	$ratio = "---";
			    }
			     if (strtotime($row["last_access"]) > gmtime() - 600) {
			     	$online = "online";
			     	$online_text = "В сети";
			     } else {
			     	$online = "offline";
			     	$online_text = "Не в сети";
			     }

	   print("<table class=maibaugrand width=100% border=1 cellspacing=0 cellpadding=3>");
	   print("<tr><td class=colhead align=\"left\" colspan=\"2\" height=\"24\">");

    if (isset($row["username"]))
		{
			$title = $row["title"];
			if ($title == ""){
				$title = get_user_class_name($row["class"]);
			}else{
				$title = htmlspecialchars_uni($title);
			}
		   print(":: <img src=\"pic/buttons/button_".$online.".gif\" alt=\"".$online_text."\" title=\"".$online_text."\" style=\"position: relative; top: 2px;\" border=\"0\" height=\"14\">"
		       ." <a name=comm". $row["id"]." href=userdetails.php?id=" . $row["user"] . " class=altlink_white><b>". get_user_class_color($row["class"], htmlspecialchars_uni($row["username"])) . "</b></a> ::"
		       .($row["donor"] == "yes" ? "<img src=pic/star.gif alt='Donor'>" : "") . ($row["warned"] == "yes" ? "<img src=\"/pic/warned.gif\" alt=\"Warned\">" : "") . " $title ::\n")
		       ." <img src=\"pic/upl.gif\" alt=\"upload\" border=\"0\" width=\"12\" height=\"12\"> ".mksize($row["uploaded"]) ." :: <img src=\"pic/down.gif\" alt=\"download\" border=\"0\" width=\"12\" height=\"12\"> ".mksize($row["downloaded"])." :: <font color=\"".get_ratio_color($ratio)."\">$ratio</font> :: ";

	       } else {
			print("<a name=\"comm" . $row["id"] . "\"><i>[Anonymous]</i></a>\n");
	       }

	$avatar = ($CURUSER["avatars"] == "yes" ? htmlspecialchars_uni($row["avatar"]) : "");
	if (!$avatar){$avatar = "pic/default_avatar.gif"; }
	
	if (md5($row['text']) == $row['text_hash'])
		$text = $row['text_parsed'];
	else {
		$text = format_comment($row['text']);
		sql_query('INSERT INTO comments_parsed (cid, text_hash, text_parsed) VALUES ('.implode(', ', array_map('sqlesc', array($row['id'], md5($row['text']), $text))).')') or sqlerr(__FILE__,__LINE__);
	}

	if ($row["editedby"]) {
	       //$res = mysql_fetch_assoc(sql_query("SELECT * FROM users WHERE id = $row[editedby]")) or sqlerr(__FILE__,__LINE__);
	       $text .= "<p><font size=1 class=small>Последний раз редактировалось <a href=userdetails.php?id=$row[editedby]><b>$row[editedbyname]</b></a> в $row[editedat]</font></p>\n";
	 }
		print("</td></tr>");
		print("<tr valign=top>\n");
		print("<td style=\"padding: 0px; width: 5%;\" align=\"center\"><img src=$avatar width=\"$avatar_max_width\"> </td>\n");
		print("<td width=100% class=text>");
		//print("<span style=\"float: right\"><a href=\"#top\"><img title=\"Top\" src=\"pic/top.gif\" alt=\"Top\" border=\"0\" width=\"15\" height=\"13\"></a></span>");
		print("$text</td>\n");
		print("</tr>\n");
		print("<tr><td class=colhead align=\"center\" colspan=\"2\">");
		print"<div style=\"float: left; width: auto;\">"
			.($CURUSER ? " [<a href=\"".$redaktor.".php?action=quote&amp;cid=$row[id]\" class=\"altlink_white\">Цитата</a>]" : "")
			.($row["user"] == $CURUSER["id"] || get_user_class() >= UC_MODERATOR ? " [<a href=".$redaktor.".php?action=edit&amp;cid=$row[id] class=\"altlink_white\">Изменить</a>]" : "")
		    .(get_user_class() >= UC_MODERATOR ? " [<a href=\"".$redaktor.".php?action=delete&amp;cid=$row[id]\" class=\"altlink_white\">Удалить</a>]" : "")
		    .($row["editedby"] && get_user_class() >= UC_MODERATOR ? " [<a href=\"".$redaktor.".php?action=vieworiginal&amp;cid=$row[id]\" class=\"altlink_white\">Оригинал</a>]" : "")
		    .(get_user_class() >= UC_MODERATOR ? " IP: ".($row["ip"] ? "<a href=\"usersearch.php?ip=$row[ip]\" class=\"altlink_white\">".$row["ip"]."</a>" : "Неизвестен" ) : "")
		    ."</div>";

		print("<div align=\"right\"><!--<font size=1 class=small>-->Комментарий добавлен: ".$row["added"]." GMT<!--</font>--></td></tr>");
		print("</table><br>");
  }

}

?>