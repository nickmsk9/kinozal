<?php

if (!defined('UC_SYSOP')) {
	die('Прямой доступ запрещён.');
}

$seconds = 0.001;

if (function_exists('timer') && isset($tstart)) {
	$seconds = (float)timer() - (float)$tstart;
}

if ($seconds <= 0) {
	$seconds = 0.001;
}

$querytime = isset($querytime) ? (float)$querytime : 0.0;
$queries = isset($queries) ? (int)$queries : 0;

$phptime = max(0, $seconds - $querytime);
$sqltime = max(0, $querytime);

$percentphp = number_format(($phptime / $seconds) * 100, 2);
$percentsql = number_format(($sqltime / $seconds) * 100, 2);

$seconds = number_format($seconds, 4, '.', '');
$year = date('Y');

$page_generated = 'Страница создана за ' . $seconds . ' сек. '
	. 'SQL-запросов: ' . $queries . '. '
	. 'PHP: ' . $percentphp . '%, SQL: ' . $percentsql . '%.';

if (function_exists('show_blocks')) {
	show_blocks('d');
}

?>


	</div><!-- /.mn3_content -->

	<div class="clr"></div>
</div><!-- /.content -->

<div class="clr"></div>
</div><!-- /#main -->

<?php
if (function_exists('show_blocks')) {
	show_blocks('f');
}
?>

<div id="footer">
	<div class="footer_inner justify">
		Файлы для обмена предоставлены пользователями,<br>
		Администрация не несет ответственности за их содержание.<br>
		Просьба не заливать файлы, защищенные авторскими правами.<br>
		[ <a class="sba" href="/doku.php">Общие правила</a> ]
		[ <a class="sba" href="/photo.php">Доступные фотохостинги</a> ]
		@2006 - <?= $year ?><br>
		<?= engine_copyright_notice() ?>
	</div>
</div>

</div><!-- /#body_wrapper -->
</body>
</html>
