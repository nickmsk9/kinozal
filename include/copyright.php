<?php

if (!defined('IN_TRACKER')) {
	die('Direct access denied.');
}

function copyright_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function copyright_contact_email()
{
	return $GLOBALS['SITEEMAIL'] ?? ('copyright@' . ($_SERVER['HTTP_HOST'] ?? 'kinozal.tv'));
}

function copyright_tabs($active)
{
	$items = array(
		'main' => array('/copyright.php', 'Раздел правообладателей'),
		'add' => array('/copyright_addblock.php', 'Добавить блокировку'),
		'log' => array('/copyright_log.php', 'Лог блокировок'),
	);

	echo '<div class="pad0x0x5x0"><ul class="lis">';
	foreach ($items as $key => $item) {
		echo '<li' . ($active === $key ? ' class="mn"' : '') . '><a href="' . copyright_h($item[0]) . '">' . copyright_h($item[1]) . '</a></li>';
	}
	echo '</ul></div>';
}

function copyright_menu($contact_email)
{
	$email = copyright_h($contact_email);
	?>
	<div class="mn1_menu">
		<ul class="men">
			<li class="img">
				<a href="/copyright.php"><img src="/pic/bn/p_copyright.jpg" height="75" class="block w200" alt=""></a>
			</li>
			<li class="tp">Информация</li>
			<li class="justify">
				<p>Раздел правообладателей предназначен для обладателей прав на информацию. Администрация обязуется в кратчайшие сроки обработать Ваши заявления. В случае выявленного нарушения Ваших исключительных прав мы гарантируем незамедлительное блокирование ссылок на скачивание.</p>
			</li>
			<li class="tp"><h2>Меню блокировок</h2></li>
			<li><span class="bulet"></span><a href="/copyright.php">Раздел правообладателей</a></li>
			<li><span class="bulet"></span><a href="/copyright_addblock.php">Добавить блокировку</a></li>
			<li><span class="bulet"></span><a href="/copyright_log.php">Лог блокировок</a></li>
			<li class="tp">Дополнительно</li>
			<li class="justify">
				<p>Просим компании и организации обращаться с официальных электронных адресов. Претензии с открытых почтовых сервисов, таких как mail.ru, yandex.ru, gmail.com, обрабатываться не будут по причине спама и возможных махинаций от третьих лиц. Крупным правообладателям или их представителям мы предоставляем доступ к блокировке раздач. Письма по вопросам сотрудничества принимаются в электронном виде.<br>Наш email: <b><a href="mailto:<?= $email ?>"><?= $email ?></a></b></p>
			</li>
		</ul>
	</div>
	<?php
}

function copyright_online_box($patterns)
{
	echo page_online_box($patterns, 'никого нет на странице');
}

?>
