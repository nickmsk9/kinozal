<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/kz_pay.php';

dbconn(false);
loggedinorreturn();
kz_pay_ensure_schema();

$user = kz_pay_user((int)$CURUSER['id']);
$count = kz_pay_wishes_count();
$perpage = 20;
list($pagertop, $pagerbottom, $limit_sql) = pager($perpage, $count, '/pay_wishes.php?');
preg_match('/LIMIT\s+([0-9]+),([0-9]+)/i', $limit_sql, $m);
$offset = isset($m[1]) ? (int)$m[1] : 0;
$limit = isset($m[2]) ? (int)$m[2] : $perpage;
$wishes = kz_pay_wishes($limit, $offset);

$hide_right_blocks = true;
stdhead('Пожелания');

?>
<?php kz_pay_layout_start('wishes', $user); ?>

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
			<?= kz_pay_user_link($wish) ?>
			<span class="small"><?= kz_pay_h($wish['added']) ?></span>
			<div class="pad5x5"><?= nl2br(kz_pay_h($wish['text'])) ?></div>
		</div>
	<?php } ?>
</div>

<?php if ($pagerbottom) { ?>
	<div class="pad5x5"><?= $pagerbottom ?></div>
<?php } ?>

<?php kz_pay_layout_end(array('/pay_wishes.php%', '%/pay_wishes.php%')); ?>
<?php

stdfoot();

?>
