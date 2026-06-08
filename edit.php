<?

require_once("include/bittorrent.php");
require_once("include/upload.php");
require_once("include/multitracker.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if (!$id) {
	die();
}

if (!$id) {
	die();
}

dbconn();

loggedinorreturn();

upload_ensure_schema();
multitracker_ensure_schema();

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

$details = upload_load_details($id);
$kind = !empty($details['exists']) ? upload_normalize_kind($details['release_kind']) : upload_kind_by_category((int)$row['category']);
if (empty($details['exists']) && !empty($row['ori_descr'])) {
	$details['data']['mode'] = 1;
	$details['data']['section_modes'] = array(1, 1, 1, 1);
	if ($kind === 'video') {
		$details['data']['advanced']['desc2'] = $row['ori_descr'];
	} else {
		$details['data']['templates'][$kind]['advanced']['desc2'] = $row['ori_descr'];
	}
}

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
if (get_user_class() >= UC_MODERATOR) {
	$service_controls .= '<br><br><b>Внешние трекеры</b><br>';
	$service_controls .= '<textarea name="external_trackers" rows="6" class="w100p">' . h(multitracker_external_textarea_value($id)) . '</textarea>';
	$service_controls .= '<div class="n">По одному announce URL на строку. Наш announce добавляется первым автоматически.</div>';
}

$state = array(
	'id' => $id,
	'name' => $row['name'],
	'kind' => $kind,
	'category' => (int)$row['category'],
	'allow_file' => true,
	'returnto' => $returnto,
	'details' => $details,
	'service_controls' => $service_controls,
);

$hide_right_blocks = true;
stdhead("Редактирование торрента \"" . $row["name"] . "\"");

?>
<div class="bx2">
	<? upload_render_info_sidebar(); ?>
	<div class="mn3_content">
		<? upload_render_form('/takeedit.php', 'Сохранить изменения', $state, true); ?>

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
						<? if ($returnto !== '') { ?><input type="hidden" name="returnto" value="<?= h($returnto) ?>"><? } ?>
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
