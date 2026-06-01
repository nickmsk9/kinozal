<?php

require_once 'include/bittorrent.php';

dbconn(false);
require_once 'include/uarch.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	loggedinorreturn();
	$error = uarch_add_smile((string)($_POST['image_url'] ?? ''));

	if ($error === '') {
		header('Location: /uarch.php?added=1');
		exit;
	}
}

$smiles = uarch_smiles(true, 96);

stdhead('Архив улыбки');
?>
<style>
.ulib { float:left; width:190px; min-height:245px; margin:0 8px 10px 0; }
.ulib img.uarch-img { max-width:175px; max-height:190px; height:auto; }
.uarch-intro { line-height:1.45; }
.uarch-form input[type=text] { width:78%; max-width:620px; }
</style>

<div class="bx1 uarch-intro">
	<b>Архив улыбки</b>
	- Здесь Вы можете приятно провести время и посмотреть улыбки, добавленные пользователями проекта. Улыбнитесь вместе с нами, хорошего Вам настроения!
</div>

<div class="bx1 uarch-form">
	<?php if (!empty($_GET['added'])) { ?>
		<div class="green"><b>Улыбка добавлена.</b></div>
	<?php } ?>
	<?php if ($error !== '') { ?>
		<div class="red"><b><?= uarch_h($error) ?></b></div>
	<?php } ?>
	<?php if (!empty($CURUSER)) { ?>
		<form method="post" action="/uarch.php">
			<input type="text" name="image_url" value="" placeholder="https://site.ru/image.jpg">
			<input type="submit" class="buttonS" value="Добавить улыбку">
		</form>
	<?php } else { ?>
		<a href="/login.php?returnto=uarch.php" class="sba">Войдите</a>, чтобы добавить улыбку.
	<?php } ?>
</div>

<div class="bx1">
	<?php if ($smiles) { ?>
		<?php foreach ($smiles as $smile) {
			$userid = (int)$smile['userid'];
			$username = (string)$smile['display_username'];
			$userclass = (int)$smile['display_class'];
			$image = (string)$smile['image_url'];
		?>
			<div class="ulib">
				<ul class="men">
					<li class="tp2 lh center">
						Улыбка от
						<?php if ($userid > 0 && $username !== '') { ?>
							<a href="/userdetails.php?id=<?= $userid ?>" class="u<?= $userclass ?>"><?= uarch_h($username) ?></a>
						<?php } else { ?>
							<span class="u<?= $userclass ?>"><?= uarch_h($username !== '' ? $username : 'Пользователь') ?></span>
						<?php } ?>
					</li>
					<li class="center">
						<img src="<?= uarch_h($image) ?>" width="175" class="uarch-img" alt="">
					</li>
				</ul>
			</div>
		<?php } ?>
	<?php } else { ?>
		<div class="center">Улыбок пока нет. Будьте первым, кто добавит хорошее настроение.</div>
	<?php } ?>
	<div class="clr"></div>
</div>
<?php
stdfoot();

?>
