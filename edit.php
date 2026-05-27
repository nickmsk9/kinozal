<?

require_once("include/bittorrent.php");
require_once("include/kz_upload.php");

if (!mkglobal("id")) {
	die();
}

$id = intval($id);
if (!$id) {
	die();
}

dbconn();

loggedinorreturn();

kz_upload_ensure_schema();

$res = sql_query("SELECT * FROM torrents WHERE id = $id");
$row = mysqli_fetch_array($res);
if (!$row) {
	die();
}

if (!isset($CURUSER) || ($CURUSER["id"] != $row["owner"] && get_user_class() < UC_MODERATOR)) {
	stdhead("Редактирование торрента");
	stdmsg($tracker_lang['error'], "Вы не можете редактировать этот торрент.");
	stdfoot();
	exit;
}

$details = kz_upload_load_details($id);
if (empty($details['exists']) && !empty($row['ori_descr'])) {
	$details['data']['mode'] = 1;
	$details['data']['section_modes'] = array(1, 1, 1, 1);
	$details['data']['advanced']['desc2'] = $row['ori_descr'];
}

$kind = !empty($details['exists']) ? kz_upload_normalize_kind($details['release_kind']) : kz_upload_kind_by_category((int)$row['category']);
$returnto = isset($_GET["returnto"]) ? htmlspecialchars_uni($_GET["returnto"]) : '';

$service_controls = '';
$service_controls .= '<label><input type="checkbox" name="visible" value="1"' . (($row["visible"] == "yes") ? ' checked="checked"' : '') . '> Видимый в торрентах</label><br>';
if (get_user_class() >= UC_ADMINISTRATOR) {
	$service_controls .= '<label><input type="checkbox" name="banned" value="1"' . (($row["banned"] == "yes") ? ' checked="checked"' : '') . '> Забанен</label><br>';
	$service_controls .= '<label><input type="radio" name="free" value="yes"' . (($row["free"] == "yes") ? ' checked="checked"' : '') . '> Золотая раздача</label><br>';
	$service_controls .= '<label><input type="radio" name="free" value="silver"' . (($row["free"] == "silver") ? ' checked="checked"' : '') . '> Серебряная раздача</label><br>';
	$service_controls .= '<label><input type="radio" name="free" value="no"' . (($row["free"] == "no") ? ' checked="checked"' : '') . '> Обычная раздача</label><br>';
	$service_controls .= '<label><input type="checkbox" name="not_sticky" value="no"' . (($row["not_sticky"] == "no") ? ' checked="checked"' : '') . '> Прикрепить этот торрент</label>';
}

$state = array(
	'id' => $id,
	'name' => $row['name'],
	'kind' => $kind,
	'category' => (int)$row['category'],
	'allow_file' => $row['multitracker'] == 'no',
	'returnto' => $returnto,
	'details' => $details,
	'service_controls' => $service_controls,
);

$hide_right_blocks = true;
stdhead("Редактирование торрента \"" . $row["name"] . "\"");

?>
<div class="bx2">
	<? kz_upload_render_info_sidebar(); ?>
	<div class="mn3_content">
		<? kz_upload_render_form('/takeedit.php', 'Сохранить изменения', $state, true); ?>

		<form method="post" action="delete.php">
			<div class="bx1">
				<ul class="men">
					<li class="tp2 b">Удалить торрент</li>
					<li><label><input name="reasontype" type="radio" value="1"> Мертвяк</label> - 0 раздающих, 0 качающих = 0 соединений</li>
					<li><label><input name="reasontype" type="radio" value="2"> Дупликат</label> <input type="text" size="40" name="reason[]"></li>
					<li><label><input name="reasontype" type="radio" value="3"> Nuked</label> <input type="text" size="40" name="reason[]"></li>
					<li><label><input name="reasontype" type="radio" value="4"> Правила</label> <input type="text" size="40" name="reason[]"> (обязательно)</li>
					<li><label><input name="reasontype" type="radio" value="5" checked="checked"> Другое</label> <input type="text" size="40" name="reason[]"> (обязательно)</li>
					<li class="center">
						<input type="hidden" name="id" value="<?= $id ?>">
						<? if ($returnto !== '') { ?><input type="hidden" name="returnto" value="<?= kz_h($returnto) ?>"><? } ?>
						<input type="submit" class="buttonS" value="Удалить">
					</li>
				</ul>
			</div>
		</form>
	</div>
</div>
<?

stdfoot();

?>
