<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/groupex.php';

dbconn(false);
loggedinorreturn();
kz_groups_ensure_schema();

function groupex_create_bark($message)
{
	stderr('Создание группы', $message);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'create') {
	$name = kz_groups_request_text($_POST['name'] ?? '');
	$avatar = trim((string)($_POST['avatar'] ?? ''));
	$private = ($_POST['private'] ?? 'no') === 'yes' ? 'yes' : 'no';
	$type = (int)($_POST['type'] ?? 1);
	$cat = (int)($_POST['cat'] ?? 0);
	$subcat = (int)($_POST['subcatsel'] ?? 0);
	$description = trim((string)($_POST['description'] ?? ''));

	if (function_exists('mb_strlen')) {
		$name_len = mb_strlen($name, 'UTF-8');
		$description_len = mb_strlen($description, 'UTF-8');
	} else {
		$name_len = strlen($name);
		$description_len = strlen($description);
	}

	if ($name_len < 5) {
		groupex_create_bark('Короткое название группы.');
	}
	if (!isset(kz_groups_types()[$type])) {
		groupex_create_bark('Не выбран тип группы.');
	}
	if (!isset(kz_groups_categories()[$cat])) {
		groupex_create_bark('Не выбрана категория.');
	}
	if (!isset(kz_groups_subcategories_for($cat)[$subcat])) {
		groupex_create_bark('Не выбрана подкатегория.');
	}
	if ($description_len < 50) {
		groupex_create_bark('Короткое описание, нужно не менее 50 символов.');
	}

	global $link, $CURUSER;
	$userid = (int)$CURUSER['id'];
	sql_query("
		INSERT INTO groupex_groups
			(name, avatar, private, type, cat, subcat, description, owner_id, members_count, created_at, updated_at)
		VALUES
			(" . sqlesc($name) . ', ' . sqlesc($avatar) . ', ' . sqlesc($private) . ', ' . $type . ', ' . $cat . ', ' . $subcat . ', ' . sqlesc($description) . ', ' . $userid . ", 1, NOW(), NOW())
	") or sqlerr(__FILE__, __LINE__);

	$group_id = (int)mysqli_insert_id($link);
	sql_query("
		INSERT INTO groupex_members (group_id, userid, role, status, added_at, updated_at)
		VALUES ($group_id, $userid, 'owner', 'member', NOW(), NOW())
	") or sqlerr(__FILE__, __LINE__);
	kz_groups_log($group_id, $userid, 'create', 'Создана группа');
	kz_groups_refresh_counts($group_id);

	header('Location: /groupex.php?id=' . $group_id);
	exit;
}

$hide_right_blocks = true;
stdhead('Создание новой группы');
kz_groups_subcat_script(array('gsearch_subcatsel' => (int)($_GET['subcatsel'] ?? 0), 'subcatsel' => 0));

?>
<script type="text/javascript">
function checkform()
{
	if ($("#name").val().length < 5) {
		alert("Короткое название!");
		$("#name").focus();
		return false;
	}
	if ($("#cat").val() == 0) {
		alert("Не выбрана категория");
		$("#cat").focus();
		return false;
	}
	if ($("#subcatsel").val() == 0) {
		alert("Вы не указали подкатегорию для группы");
		$("#subcatsel").focus();
		return false;
	}
	if ($("#description").val().length < 50) {
		alert("Короткое описание, нужно не менее 50 символов");
		$("#description").focus();
		return false;
	}
	return true;
}
</script>

<div class="bx2">
	<div class="pad0x0x5x0">
		<a href="/groupexlist.php" class="sbab">Список групп</a>
		::
		<a href="/mygroups.php" class="sbab">Мои группы</a>
		::
		<a href="/groupexcreate.php" class="sbab">Создание новой группы</a>
	</div>
	<?php kz_groups_search_sidebar('На этой странице Вы можете создать новую группу.', false); ?>
	<div class="mn3_content">
		<form name="cmt" id="cmt" method="post" action="/groupexcreate.php" onsubmit="return checkform();">
			<div class="bx1">
				<table class="tables1 w100p">
					<tr>
						<td class="w175">Название группы:</td>
						<td><input type="text" name="name" id="name" class="w98p" value=""></td>
					</tr>
					<tr>
						<td>Аватар группы:</td>
						<td><input type="text" name="avatar" id="avatar" class="w98p" value=""></td>
					</tr>
					<tr>
						<td>Доступ к группе:</td>
						<td>
							<select name="private" class="w250">
								<option value="no" selected>Все могут присоединиться</option>
								<option value="yes">Только по разрешению Руководства группы</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>Тип группы:</td>
						<td>
							<select name="type" id="type" class="w250">
								<?= kz_groups_options(kz_groups_types(), 1) ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>Категория:</td>
						<td>
							<select name="cat" id="cat" onchange="kzGroupsSubcatFor('cat', 'subcatsel');" class="w250">
								<?= kz_groups_options(kz_groups_categories(), 0, 'Выберите из списка категорию') ?>
							</select>
							<select name="subcatsel" id="subcatsel" class="w250">
								<option value="0">Не выбрана категория</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>Описание группы:</td>
						<td>
							<div class="cmet_e_but">
								<ul>
									<li><input class="buttonS" type="button" value="b" style="font-weight:bold;" onclick="return kzGroupsInsertCode('description','b')"></li>
									<li><input class="buttonS" type="button" value="i" style="font-style:italic;" onclick="return kzGroupsInsertCode('description','i')"></li>
									<li><input class="buttonS" type="button" value="u" style="text-decoration:underline;" onclick="return kzGroupsInsertCode('description','u')"></li>
									<li><input class="buttonS" type="button" value="quote" onclick="return kzGroupsInsertCode('description','quote')"></li>
									<li><input class="buttonS" type="button" value="url" onclick="return kzGroupsInsertCode('description','url')"></li>
									<li><input class="buttonS" type="button" value="img" onclick="return kzGroupsInsertCode('description','img')"></li>
								</ul>
								<div class="clr"></div>
							</div>
							<div class="cmet_e_inp">
								<textarea id="description" name="description" cols="70" rows="8" class="w98p"></textarea>
							</div>
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							<input type="hidden" name="action" value="create">
							<input type="submit" value="Создать" class="buttonS">
						</td>
					</tr>
				</table>
			</div>
		</form>
	</div>
	<div class="clr"></div>
</div>
<?php
stdfoot();

?>
