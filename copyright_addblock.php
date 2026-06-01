<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/copyright.php';

dbconn(false);

$hide_right_blocks = true;
stdhead('Добавить блокировку');

$contact_email = copyright_contact_email();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['addblock'] ?? '') === 'add') {
	$notice = 'Раздел закрыт. Новые блокировки через эту форму сейчас не принимаются.';
}

?>
<div class="bx2">
	<div style="padding:0 5px 7px 0;">
		<h1>
			<span class="bulet"></span><a href="/copyright.php" class="sbab">Раздел правообладателей</a>
			::
			<a href="/copyright_addblock.php" class="sbab">Добавить блокировку</a>
		</h1>
	</div>

	<?php copyright_menu($contact_email); ?>

	<div class="mn1_content">
		<?php copyright_tabs('add'); ?>

		<?php if ($notice !== '') { ?>
			<div class="bx1_0 u9 b"><?= copyright_h($notice) ?></div>
		<?php } ?>

		<div class="bx1_0 u9 b">
			Вступило в силу судебное решение о блокировке проекта на территории РФ.<br>
			Закрытие ссылок на раздачи по ключу неактуально.<br>
			Раздел закрыт.
		</div>

		<div class="bx1_0">
			Блокировка страниц доступна Администрации проекта, а также крупным правообладателям или их представителям.<br>
			Мы уважаем интеллектуальную собственность и готовы урегулировать все спорные вопросы, затрагивающие авторские права.<br>
			Если Вы являетесь обладателем исключительных имущественных прав,<br>
			которые нарушаются с использованием данного сайта,<br>
			просьба обращаться к нам письменно в электронном виде.<br>
			Наш email: <b><a href="mailto:<?= copyright_h($contact_email) ?>"><?= copyright_h($contact_email) ?></a></b>
		</div>

		<div class="bx1_0">
			Просим добавлять ссылки для блокировки в указанном виде:<br>
			https://kinozal.tv/details.php?id=XXXXX<br>
			https://kinozal.tv/details.php?id=YYYYY<br>
			https://kinozal.tv/details.php?id=ZZZZZ<br>
			https://kinozal.tv/details.php?id=VVVVV
		</div>

		<form method="post" name="upt" id="upt" action="/copyright_addblock.php">
			<input name="addblock" type="hidden" value="add">
			<div class="bx1_0">
				<div class="pad5x5"><textarea class="w98p" rows="8" cols="70" name="l" id="l"></textarea></div>
				<div class="pad5x5"><input type="text" name="key" size="32" value="" placeholder="Ключ правообладателя" class="w200"></div>
				<div class="pad5x5">
					<select name="w" class="w200">
						<option value="20">Блокировка Блок без регистр.</option>
						<option value="0">Открыть ссылки</option>
					</select>
				</div>
				<div class="pad5x5"><input type="submit" value="Добавить блокировку" class="buttonS w200"></div>
			</div>
			<div class="bx1_0">
				Чтобы получить ключ для самостоятельной блокировки ссылок, просим крупных правообладателей или их представителей<br>
				обратиться к нам письменно в электронном виде.
			</div>
		</form>
	</div>
	<div class="clr"></div>
</div>

<?php copyright_online_box(array('/copyright_addblock.php%', '%/copyright_addblock.php%')); ?>
<?php

stdfoot();

?>
