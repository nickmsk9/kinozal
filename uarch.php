<?php

require_once 'include/bittorrent.php';

dbconn(false);

function uarch_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

$smiles = array(
	array('userid' => 2396813, 'username' => 'Ananasix', 'class' => 8, 'flag' => 'c8', 'icons' => array('s_dv'), 'image' => '/pic/uarch_smile.jpg'),
	array('userid' => 2396813, 'username' => 'Ananasix', 'class' => 8, 'flag' => 'c8', 'icons' => array('s_dv'), 'image' => 'https://i5.imageban.ru/out/2026/05/20/b8c58358e2cafa5f3c184cf88940e2bd.jpg'),
	array('userid' => 2396813, 'username' => 'Ananasix', 'class' => 8, 'flag' => 'c8', 'icons' => array('s_dv'), 'image' => 'https://i2.imageban.ru/out/2026/05/20/471f11a790b3c8f17f0e49f986c29b50.jpg'),
	array('userid' => 6324626, 'username' => 'LuckyDevil', 'class' => 6, 'flag' => 'c4', 'icons' => array('s9-10'), 'image' => 'https://i127.fastpic.org/big/2026/0519/48/2ac2588d07108b1b5b8b8124c9663a48.jpg'),
	array('userid' => 3381063, 'username' => 'Аneta', 'class' => 7, 'flag' => 'c8', 'icons' => array('s_dv', 's17'), 'image' => 'https://i1.imageban.ru/out/2026/05/17/d4360d3ac113891ee612343ad9c60c98.png'),
	array('userid' => 3381063, 'username' => 'Аneta', 'class' => 7, 'flag' => 'c8', 'icons' => array('s_dv', 's17'), 'image' => 'https://i6.imageban.ru/out/2026/05/17/922d9438d05157cc93fb5c51be1ae4ba.jpg'),
);

stdhead('Архив улыбки');
?>
<style>
.ulib { float:left; width:190px; min-height:230px; margin:0 8px 10px 0; }
.ulib img.uarch-img { max-width:175px; height:auto; }
.uarch-intro { line-height:1.45; }
</style>

<div class="bx1 uarch-intro">
	<b>Архив улыбки</b>
	- Здесь Вы можете приятно провести время и посмотреть улыбки, добавленные ранее на проект. Улыбнитесь вместе с нами, хорошего Вам настроения!
</div>

<div class="bx1">
	<?php foreach ($smiles as $smile) { ?>
		<div class="ulib">
			<ul class="men">
				<li class="tp2 lh center">
					Улыбка от
					<img src="/pic/emty.gif" class="i2 <?= uarch_h($smile['flag']) ?>" alt="">
					<a href="/userdetails.php?id=<?= (int)$smile['userid'] ?>" class="u<?= (int)$smile['class'] ?>"><?= uarch_h($smile['username']) ?></a>
					<?php foreach ($smile['icons'] as $icon) { ?>
						<i class="i1 <?= uarch_h($icon) ?>"></i>
					<?php } ?>
				</li>
				<li class="center">
					<img src="<?= uarch_h($smile['image']) ?>" width="175" class="uarch-img" alt="">
				</li>
			</ul>
		</div>
	<?php } ?>
	<div class="clr"></div>
</div>
<?php
stdfoot();

