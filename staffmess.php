<?

require "include/bittorrent.php";

dbconn();
loggedinorreturn();

if (get_user_class() < UC_MODERATOR) {
	stderr($tracker_lang['error'], $tracker_lang['access_denied']);
}

$classes = array(
	UC_USER,
	UC_POWER_USER,
	UC_HONOR_USER,
	UC_VIP,
	UC_UPLOADER,
	UC_SENIOR_UPLOADER,
	UC_MANAGER,
	UC_MODERATOR,
	UC_ADMINISTRATOR,
	UC_SYSOP,
);

stdhead("Массовое ЛС");
?>

<h1>Массовое ЛС</h1>

<form method="post" action="/takestaffmess.php">
<table class="main" border="1" cellspacing="0" cellpadding="5">
	<tr>
		<td class="rowhead">Отправитель</td>
		<td>
			<label><input type="radio" name="sender" value="self" checked> <?= htmlspecialchars_uni($CURUSER['username']) ?></label>
			<label><input type="radio" name="sender" value="system"> Система</label>
		</td>
	</tr>
	<tr>
		<td class="rowhead">Получатели</td>
		<td>
			<?php foreach ($classes as $class) { ?>
				<label style="display:block;">
					<input type="checkbox" name="clases[]" value="<?= (int)$class ?>">
					<?= htmlspecialchars_uni(get_user_class_name($class)) ?>
				</label>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<td class="rowhead">Тема</td>
		<td><input type="text" name="subject" class="w100p" maxlength="255"></td>
	</tr>
	<tr>
		<td class="rowhead">Сообщение</td>
		<td><textarea name="msg" rows="12" class="w100p"></textarea></td>
	</tr>
	<tr>
		<td colspan="2" align="center"><input type="submit" class="buttonS" value="Отправить"></td>
	</tr>
</table>
</form>

<?
stdfoot();
?>
