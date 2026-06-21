<?


require "include/bittorrent.php";
dbconn();
loggedinorreturn();

$return = $_SERVER['HTTP_REFERER'] ?? '';
$valid_actions = array('add', 'addforum','delete');
$request_action = $_POST['action'] ?? ($_GET['action'] ?? '');
$action = (in_array($request_action, $valid_actions, true) ? $request_action : '');

// action: add -------------------------------------------------------------
if ($action == 'add') {
        if ($CURUSER["warned"] == 'yes') {
                stderr($tracker_lang['error'], "У вас предупреждение и вы не можете ставить людям респекты.");
        }
        $current_time = get_date_time();
        $targetid = intval($_GET['targetid']);
        $resp_type = (isset($_GET['good'])?1:0);
        $type = $_GET['type'];

        if (!is_valid_id($targetid)) {
                stderr($tracker_lang['error'], "Неправильный ID $targetid.");
        }
        if (get_row_count("users", "WHERE id = $targetid") == 0)
        		stderr($tracker_lang['error'],"Такого пользователя не существует!");
        if ($CURUSER["id"] == $targetid) {
                stderr($tracker_lang['error'],"Вы не можете давать респект или антиреспект себе.");
        }

        $r = sql_query('SELECT id FROM simpaty WHERE touserid=' . $targetid . ' AND type = ' . sqlesc($type) . ' AND fromuserid = ' . $CURUSER['id']) or sqlerr(__FILE__, __LINE__);
        if (mysqli_num_rows($r) == 1) {
                stderr ($tracker_lang['error'],"Вы уже давали респект за это действие этому пользователю.");
        }

        if (function_exists('reputation_left_today') && reputation_left_today((int)$CURUSER['id']) <= 0 && get_user_class() < UC_ADMINISTRATOR) {
                stderr($tracker_lang['error'], "РЎСѓС‚РѕС‡РЅС‹Р№ Р»РёРјРёС‚ РѕС‚Р·С‹РІРѕРІ Рє СЂРµРїСѓС‚Р°С†РёРё РёСЃС‡РµСЂРїР°РЅ.");
        }

        if (isset($_POST["description"]) && trim($_POST["description"]) == '') {
                stderr($tracker_lang['error'], "Комментарий не может быть пустым.");
        }
        if (!isset($_POST["description"])) {
        stderr("","<p>Напишите причину, по которой вы выдаете " . ($resp_type == 1?"респект":"антиреспект") . " пользователю:</p>
        <form action=\"" . $_SERVER["PHP_SELF"] . "?action=add&amp;" . ($resp_type == 1?'good':'bad') . "&amp;type=".htmlspecialchars_uni($type)."&amp;targetid=$targetid\" method=\"post\">
        <input type=text name=description maxlength=300 size=100></textarea>
		<input type=\"hidden\" name=\"hash4u\" value=\"" . htmlspecialchars_uni(tracker_user_form_token()) . "\" />
		".(isset($_GET["returnto"]) ? "<input type=\"hidden\" name=\"returnto\" value=\"" . htmlspecialchars_uni($_GET["returnto"]) . "\" />\n" : "").
        "<input type=submit value=".($resp_type == 1?"Респект":"Антиреспект").">
        </form>");
        }
        tracker_require_form_token('POST');
        sql_query ('INSERT INTO simpaty VALUES (0, ' . $targetid . ', ' . $CURUSER['id'] . ', ' . sqlesc($CURUSER['username']) . ', ' . ($resp_type==0?1:0) . ', ' . ($resp_type==1?1:0) . ', ' . sqlesc($type) . ', ' . sqlesc($current_time) . ', ' . sqlesc(htmlspecialchars_uni($_POST["description"])) . ')') or sqlerr(__FILE__, __LINE__);
        if ($resp_type == 1) {
                sql_query('UPDATE users SET simpaty = simpaty + 1 WHERE id = ' . $targetid) or sqlerr(__FILE__, __LINE__);
        } else {
                sql_query('UPDATE users SET simpaty = IF(simpaty > 0, simpaty - 1, 0) WHERE id = ' . $targetid) or sqlerr(__FILE__, __LINE__);
        }
        // mod by StirolXXX (Yuna Scatari)
		$msg = "Пользователь [url=userdetails.php?id=" . $CURUSER['id'] ."]" . $CURUSER['username'] . "[/url] поставил вам " . ($resp_type == 1?'респект':'антиреспект') . " в репутацию со следующим сообщением: \n[quote]" . htmlspecialchars_uni($_POST["description"]) . "[/quote]";
		$subject = "Уведомление об изменении репутации";
		send_pm(0, $targetid, get_date_time(), $subject, $msg);
		//sql_query("INSERT INTO messages (sender, receiver, added, msg, subject) VALUES (0, $targetid, NOW(), $msg, \"Уведомление об изменении репутации\")");
        // mod by StirolXXX (Yuna Scatari)
		if (isset($_POST["returnto"])) {
			$returl = tracker_safe_local_redirect($_POST["returnto"], '/');
			header("Refresh: 2; url=$returl");
		}
        stdhead(($resp_type == 1?"Респект":"Антиреспект") . " добавлен");
        stdmsg($tracker_lang['success'],"<p>Пользователь успешно получил " . ($resp_type == 1?"респект":"антиреспект") . " от вас.</p>".(isset($_POST["returnto"]) ? "Сейчас вы будете переадресованы на страницу, откуда вы пришли." : ""));
        if (isset($_POST["returnto"])) {
        	print("<p><a href=\"".htmlspecialchars_uni($_POST["returnto"])."\">Нажмите сюда, если вы не были переадресованы</a></p>");
        }
}

if ($action == 'delete') {
        if(get_user_class() < UC_SYSOP) {
                stderr($tracker_lang['error'], "У вас нет прав на удаление респектов.");
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                http_response_code(405);
                header('Allow: POST');
                exit('Method Not Allowed');
        }

        tracker_require_form_token('POST');

        $respect_id = intval($_POST['respect_id'] ?? 0);
        $respect_type = (string)($_POST['respect_type'] ?? '');
        $touserid = intval($_POST['touserid'] ?? 0);
        sql_query ('DELETE FROM simpaty WHERE id = ' . $respect_id) or sqlerr(__LINE__,__FILE__);
        if ($respect_type == 'bad')
        	sql_query ('UPDATE users SET simpaty = IF(simpaty > 0, simpaty - 1, 0) WHERE id = ' . $touserid) or sqlerr(__LINE__,__FILE__);
        else
			sql_query ('UPDATE users SET simpaty = simpaty + 1 WHERE id = ' . $touserid) or sqlerr(__LINE__,__FILE__);
        /*if (mysql_affected_rows != 1) {
        	stderr($tracker_lang['error'], "Не могу удалить ".($respect_type == 'good'?"респект":"антиреспект").".");
        }*/
        if (isset($_POST["returnto"])) {
                $returl = tracker_safe_local_redirect($_POST["returnto"], '/mysimpaty.php');
			header("Refresh: 2; url=$returl");
        };
        stdhead();
        stdmsg($tracker_lang['success'], "<p>".($respect_type == 'good' ? "Респект" : "Антиреспект")." удален успешно.</p>".(isset($_POST["returnto"]) ? "Сейчас вы будете переадресованы на страницу, откуда вы пришли." : ""));
        if (isset($_POST["returnto"])) {
                print("<p><a href=\"".htmlspecialchars_uni($returl)."\">Нажмите сюда, если вы не были переадресованы</a></p>");
        }
        stdfoot();
        die();
}
?>
