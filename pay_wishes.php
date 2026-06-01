<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/pay.php';

dbconn(false);
loggedinorreturn();
pay_ensure_schema();

$user = pay_user((int)$CURUSER['id']);
$count = pay_wishes_count();
$perpage = 20;
list($pagertop, $pagerbottom, $limit_sql) = pager($perpage, $count, '/pay_wishes.php?');
preg_match('/LIMIT\s+([0-9]+),([0-9]+)/i', $limit_sql, $m);
$offset = isset($m[1]) ? (int)$m[1] : 0;
$limit = isset($m[2]) ? (int)$m[2] : $perpage;
$wishes = pay_wishes($limit, $offset);

$hide_right_blocks = true;
stdhead('Пожелания');

?>
<?php pay_layout_start('wishes', $user); ?>

<div class="bx1">
	На странице представлены пожелания пользователей. Вы также можете оставить свое пожелание в разделе <a href="/pay_mode.php" class="sbab">Управление голосами</a>. Дополнительные голоса можно получить в разделе <a href="/pay.php" class="sbab">Голоса и рейтинг</a>.
</div>

<?php if ($pagertop) { ?>
	<div class="pad0x0x5x0"><?= $pagertop ?></div>
<?php } ?>

<div class="bx1">
	<?php if (!$wishes) { ?>
		<div class="pad10x10 center">Пожеланий пока нет.</div>
	<?php } ?>
	<?php foreach ($wishes as $wish) { ?>
		<div class="bx5x5">
			<img src="/pic/emty.gif" class="i2 c<?= max(1, min(37, ((int)$wish['userid'] % 37) + 1)) ?>">
			<?= pay_user_link($wish) ?>
			<span class="small"><?= pay_h($wish['added']) ?></span>
			<div class="pad5x5"><?= nl2br(pay_h($wish['text'])) ?></div>
		</div>
	<?php } ?>
</div>

<?php if ($pagerbottom) { ?>
	<div class="pad5x5"><?= $pagerbottom ?></div>
<?php } ?>

<?php pay_layout_end(array('/pay_wishes.php%', '%/pay_wishes.php%')); ?>
<?php

stdfoot();

?>
