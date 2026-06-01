<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/copyright.php';

dbconn(false);

$hide_right_blocks = true;
stdhead('Лог блокировок');

$contact_email = copyright_contact_email();

?>
<div class="bx2">
	<div style="padding:0 5px 7px 0;">
		<h1>
			<span class="bulet"></span><a href="/copyright.php" class="sbab">Раздел правообладателей</a>
			::
			<a href="/copyright_log.php" class="sbab">Лог блокировок</a>
		</h1>
	</div>

	<?php copyright_menu($contact_email); ?>

	<div class="mn1_content">
		<?php copyright_tabs('log'); ?>

		<div class="bx1_0">
			Здесь представлены ссылки, которые были закрыты Администрацией, правообладателями или их представителями.<br>
			Мы уважаем интеллектуальную собственность и готовы урегулировать все спорные вопросы, затрагивающие авторские права.<br>
			Если Вы являетесь обладателем исключительных имущественных прав,<br>
			которые нарушаются с использованием данного сайта,<br>
			просьба обращаться к нам письменно в электронном виде.<br>
			Наш email: <b><a href="mailto:<?= copyright_h($contact_email) ?>"><?= copyright_h($contact_email) ?></a></b>
		</div>

		<div class="bx1_0">
			Во избежание раскрытия конфиденциальной информации лог блокировок закрыт.
		</div>
	</div>
	<div class="clr"></div>
</div>

<?php copyright_online_box(array('/copyright_log.php%', '%/copyright_log.php%')); ?>
<?php

stdfoot();

?>
