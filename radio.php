<?php

require_once 'include/bittorrent.php';

dbconn(true);
require_once 'include/kz_radio.php';

$action = (string)($_GET['action'] ?? '');

if ($action === 'getch') {
	kz_radio_ensure_schema();

	$tab = (int)($_GET['tabch'] ?? 11);
	$tab = $tab === 12 ? 12 : 11;
	$limit = isset($_GET['imes']) ? (int)$_GET['imes'] : 80;
	$error = '';

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['t'])) {
		$error = kz_radio_add_chat_message($tab, (string)$_POST['t']);
	}

	$rows = kz_radio_chat_messages($tab, $limit);
	header('Content-Type: text/html; charset=UTF-8');
	?>
	<!DOCTYPE html>
	<html lang="ru">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="refresh" content="90">
		<style>
			body { margin:0; padding:0; background:#fffff5; color:#000; font:14px/1.25 Tahoma, Arial, sans-serif; }
			a { color:#5A71B0; text-decoration:none; font-weight:bold; }
			a:hover { color:#BC2A4D; text-decoration:underline; }
			.chat-error { margin:0 0 4px; padding:6px 8px; background:#fff; border:3px solid #f1d29c; color:#b00000; font-weight:bold; }
			.chat-row { margin:0 0 1px; }
			.chat-head { min-height:22px; padding:3px 6px; background:#f1d29c; font-weight:bold; overflow:hidden; }
			.chat-head .time { float:right; color:#000; font-weight:normal; }
			.chat-text { min-height:34px; padding:8px 14px; background:#f4fbff url('/themes/TBDev/images/sbg.gif') repeat-x top; border-left:1px solid #e7d8bd; border-right:1px solid #e7d8bd; word-wrap:break-word; }
			.empty { padding:12px; background:#f4fbff url('/themes/TBDev/images/sbg.gif') repeat-x top; border:3px solid #f1d29c; }
			.u0,.u1,.u2,.u3,.u4,.u5,.u6,.u7,.u8,.u9 { font-weight:bold; }
			.u3 { color:#fb4a01; } .u8 { color:#5870ad; }
		</style>
	</head>
	<body>
	<?php if ($error !== '') { ?>
		<div class="chat-error"><?= kz_radio_h($error) ?></div>
	<?php } ?>
	<?php if (!$rows) { ?>
		<div class="empty">Сообщений пока нет.</div>
	<?php } ?>
	<?php foreach ($rows as $row) {
		$username = (string)$row['username'];
		$userclass = (int)$row['userclass'];
		$userid = (int)$row['userid'];
		$added = (string)$row['added'];
		$text = nl2br(kz_radio_h($row['text']));
		$when = $added !== '' ? date('d.m.Y в H:i', strtotime($added)) : '';
		?>
		<div class="chat-row">
			<div class="chat-head">
				<span><img src="/pic/ru.gif" width="20" height="15" alt=""> </span>
				<?php if ($userid > 0) { ?>
					<a href="/userdetails.php?id=<?= $userid ?>" target="_top" class="u<?= $userclass ?>"><?= kz_radio_h($username) ?></a>
				<?php } else { ?>
					<span class="u<?= $userclass ?>"><?= kz_radio_h($username) ?></span>
				<?php } ?>
				<span class="time"><?= kz_radio_h($when) ?></span>
			</div>
			<div class="chat-text"><?= $text ?></div>
		</div>
	<?php } ?>
	<script>
		if (window.parent && window.parent !== window && window.parent.document.forms.mss) {
			window.parent.document.forms.mss.t.value = '';
			window.parent.document.forms.mss.t.focus();
		}
		window.scrollTo(0, document.body.scrollHeight);
	</script>
	</body>
	</html>
	<?php
	exit;
}

$settings = kz_radio_settings();
$dj = kz_radio_find_user($settings['dj_user_id']);
$djName = $dj ? (string)$dj['username'] : (string)$settings['dj_name'];
if (!$dj && (int)$settings['dj_user_id'] > 0) {
	$djName = 'ДиДжей не найден';
}
$djClass = $dj ? (int)$dj['class'] : 3;
$djUserId = $dj ? (int)$dj['id'] : (int)$settings['dj_user_id'];
$streamUrl = ((string)$settings['offline_mode'] === '1') ? '/sounds/silent.mp3' : kz_radio_url($settings['stream_url_128'], '/sounds/silent.mp3');

stdhead('Радио Кинозал.ТВ');
?>
<style>
.radio-page { background:#fffff5; min-height:650px; }
.radio-table td { vertical-align:top; }
.radio-side { width:150px; min-width:150px; background:url('/pic/rado_pro.jpg') bottom left no-repeat; }
.radio-label { width:90px; text-align:right; padding:10px 10px; font-weight:bold; white-space:nowrap; }
.radio-player { width:428px; padding:10px; background:#fff; border:1px solid #d9d9d9; border-radius:5px; overflow:hidden; }
.radio-logo { width:100px; height:100px; float:left; margin-right:20px; border:1px solid #d9d9d9; border-radius:5px; background:#fff url('/pic/rado_pro.jpg') center center / cover no-repeat; }
.radio-elements { float:left; width:290px; padding-top:12px; }
.radio-song { display:block; width:280px; overflow:hidden; white-space:nowrap; color:#000; font:30px/1.15 "Arial Narrow", "PT Sans Narrow", Tahoma, Arial, sans-serif; }
.radio-mini { width:280px; overflow:hidden; color:#000; font:16px/1.35 "Arial Narrow", Tahoma, Arial, sans-serif; }
.radio-control { margin:7px 0 6px; width:270px; height:31px; background:linear-gradient(#f8f8f8,#dedede); border-top:1px solid #eee; border-bottom:1px solid #c7c7c7; box-shadow:inset 0 1px 2px rgba(0,0,0,.08); overflow:hidden; }
.radio-btn { float:left; width:38px; height:31px; border:0; cursor:pointer; background:transparent url('/pic/play.png') center center / 21px 21px no-repeat; opacity:.55; }
.radio-btn.is-playing { background-image:url('/pic/pause.png'); opacity:.8; }
.radio-time { float:left; width:92px; text-align:center; font:bold 18px/31px Arial, sans-serif; color:#222; }
.radio-progress { float:left; width:92px; height:7px; margin-top:12px; background:#cfcfcf; border-radius:2px; box-shadow:inset 0 1px 1px rgba(0,0,0,.25); }
.radio-progress span { display:block; width:0; height:7px; background:#8aa1d3; border-radius:2px; }
.radio-volume { float:right; width:42px; height:31px; background:repeating-linear-gradient(90deg,#333 0,#333 2px,transparent 2px,transparent 5px); opacity:.65; clip-path:polygon(0 70%,100% 20%,100% 90%,0 90%); }
.radio-clear { clear:both; }
.radio-admin { float:right; font-weight:bold; }
.radio-rules { padding:10px; line-height:1.45; }
.radio-rules h3 { margin:0 0 8px; color:#5A71B0; font-size:16px; }
.radio-rules pre { margin:0; white-space:pre-wrap; font:13px/1.45 Tahoma, Arial, sans-serif; }
.radio-chat-frame { height:400px; overflow-y:scroll; -webkit-overflow-scrolling:touch; }
@media (max-width: 760px) {
	.radio-side { display:none; }
	.radio-label { display:block; width:auto; text-align:left; }
	.radio-table, .radio-table tbody, .radio-table tr, .radio-table td { display:block; width:auto; }
	.radio-player { width:auto; }
	.radio-logo { width:90px; height:90px; }
	.radio-elements { width:calc(100% - 115px); }
	.radio-song,.radio-mini,.radio-control { width:100%; max-width:280px; }
}
</style>

<div class="radio-page">
	<table class="table2 w100p radio-table">
		<tr>
			<td class="radio-side" rowspan="7"></td>
			<td class="radio-label"></td>
			<td>
				<div class="bx1">
					<a href="https://forum.kinozal.tv/showthread.php?t=63859" target="_blank" class="sbab">Правила</a> |
					<a href="#radio-dj-rules" class="sbab">Набор в диджеи</a> |
					<a href="#radio-announce" class="sbab">Анонс</a>
					<?php if (function_exists('get_user_class') && get_user_class() >= UC_ADMINISTRATOR) { ?>
						<span class="radio-admin"><a href="/admincp.php?op=RadioAdmin" class="sbab">Админка радио</a></span>
					<?php } ?>
				</div>
			</td>
		</tr>
		<tr>
			<td class="radio-label"></td>
			<td>
				<div class="bx2_0">
					<table class="table2 w100p"><tr><td>
						<div id="radioheart-player" class="radio-player">
							<div class="radio-logo"></div>
							<div class="radio-elements">
								<marquee class="radio-song" id="radio-current-song"><?= kz_radio_h($settings['current_song']) ?></marquee>
								<div class="radio-control">
									<button type="button" id="radio-play" class="radio-btn" title="Слушать"></button>
									<span id="radio-time" class="radio-time">00:00:00</span>
									<span class="radio-progress"><span id="radio-progress"></span></span>
									<span class="radio-volume"></span>
								</div>
								<div class="radio-mini">Следует: <span id="radio-next-song"><?= kz_radio_h($settings['next_song']) ?></span></div>
								<div class="radio-mini">Слушают: <b><?= kz_radio_h($settings['listeners']) ?></b> - <?= kz_radio_h($settings['kbps']) ?> кбит/c</div>
								<audio id="radio-audio" preload="none" src="<?= kz_radio_h($streamUrl) ?>"></audio>
							</div>
							<div class="radio-clear"></div>
						</div>
					</td></tr></table>
				</div>
			</td>
		</tr>
		<tr>
			<td class="radio-label">ДиДжей</td>
			<td>
				<div class="bx1">
					<?php if ($dj) { ?>
						<a href="/userdetails.php?id=<?= $djUserId ?>" class="u<?= $djClass ?>"><?= kz_radio_h($djName) ?></a><i class="i1 s8"></i><i class="i1 cb8"></i>
					<?php } else { ?>
						<span class="u<?= $djClass ?>"><?= kz_radio_h($djName !== '' ? $djName : 'ДиДжей не выбран') ?></span>
					<?php } ?>
					<span class="floatright b"><a class="sbab" href="<?= kz_radio_h(kz_radio_url($settings['public_url'], '/radio.php')) ?>"><?= kz_radio_h($settings['public_url']) ?></a></span>
				</div>
			</td>
		</tr>
		<tr>
			<td class="radio-label">Передача</td>
			<td>
				<div class="bx1">
					Слушать<br>
					(320 кбит/c) : <a href="<?= kz_radio_h(kz_radio_url($settings['playlist_url_320'], '#')) ?>" class="sbab"><?= kz_radio_h($settings['playlist_url_320']) ?></a><br>
					(128 кбит/c) : <a href="<?= kz_radio_h(kz_radio_url($settings['playlist_url_128'], '#')) ?>" class="sbab"><?= kz_radio_h($settings['playlist_url_128']) ?></a>
				</div>
			</td>
		</tr>
		<tr>
			<td class="radio-label">От ДиДжея</td>
			<td>
				<div class="bx1">
					<a href="<?= kz_radio_h(kz_radio_url($settings['order_url'], '#')) ?>" class="sbab">Стол заказов</a><br>
					<a href="<?= kz_radio_h(kz_radio_url($settings['order_full_url'], $settings['order_image'])) ?>" target="_blank">
						<img src="<?= kz_radio_h(kz_radio_url($settings['order_image'], '/pic/radio_ban.jpg')) ?>" alt="" style="max-width:100%; height:auto;">
					</a>
				</div>
			</td>
		</tr>
		<tr id="radio-announce">
			<td class="radio-label"></td>
			<td><div class="bx1"><a href="<?= kz_radio_h(kz_radio_url($settings['group_url'], '#')) ?>" class="sbab"><?= kz_radio_h($settings['group_title']) ?></a><br><?= nl2br(kz_radio_h($settings['announce_text'])) ?></div></td>
		</tr>
		<tr id="radio-dj-rules">
			<td class="radio-label">Набор</td>
			<td><div class="bx1 radio-rules"><h3>Набор в ДиДжеи</h3><pre><?= kz_radio_h($settings['rules_text']) ?></pre></div></td>
		</tr>
	</table>

	<?php if ((string)$settings['chat_enabled'] === '1') { ?>
		<table class="w100p">
			<tr><td class="mn2">
				<ul class="lis">
					<li id="11_tabch" class="mn"><a onclick="return radioTab(11)" href="">Болталка</a></li>
					<li id="12_tabch"><a onclick="return radioTab(12)" href="">Технический</a></li>
				</ul>
			</td></tr>
			<tr><td>
				<div id="start_chbox" class="bx2_0">
					<div class="radio-chat-frame">
						<iframe src="/radio.php?action=getch" width="100%" height="400" frameborder="0" name="chbox" marginwidth="0" marginheight="0" scrolling="yes" style="border:0; overflow-x:auto; overflow-y:visible; display:block;"></iframe>
					</div>
				</div>
				<form action="/radio.php?action=getch" target="chbox" name="mss" method="post">
					<div class="bx1">
						<div class="cmet_e_inp"><textarea id="t" name="t" cols="70" rows="5" class="w98p"></textarea></div>
						<div class="cmet_e_inp">
							<input class="buttonS" type="button" value="Отправить" onclick="sendRadioMessage();">
							<span class="floatright">[ <a href="javascript:history_chat();">история</a> ] [ <a onclick="return radioRefresh()" href="">перезагрузить</a> ]</span>
						</div>
					</div>
				</form>
			</td></tr>
		</table>
	<?php } ?>
</div>

<script>
var radioTabId = 11;
function radioTab(tab) {
	radioTabId = tab === 12 ? 12 : 11;
	document.getElementById('11_tabch').className = '';
	document.getElementById('12_tabch').className = '';
	document.getElementById(radioTabId + '_tabch').className = 'mn';
	return radioRefresh();
}
function radioRefresh() {
	document.getElementById('start_chbox').innerHTML = '<div class="radio-chat-frame"><iframe src="/radio.php?action=getch&tabch=' + radioTabId + '" width="100%" height="400" frameborder="0" name="chbox" marginwidth="0" marginheight="0" scrolling="yes" style="border:0; overflow-x:auto; overflow-y:visible; display:block;"></iframe></div>';
	return false;
}
function history_chat() {
	window.open('/radio.php?action=getch&tabch=' + radioTabId + '&imes=200', 'history_chat', 'toolbars=0, scrollbars=1, location=0, statusbars=0, menubars=0, resizable=1, width=600, height=450, left=70, top=50');
}
function sendRadioMessage() {
	var form = document.forms.mss;
	if (!form || form.t.value.replace(/\s+/g, '').length < 5) {
		alert('ОШИБКА! Минимум 5 символов!');
		return false;
	}
	form.action = '/radio.php?action=getch&tabch=' + radioTabId;
	form.submit();
	return false;
}
(function () {
	var audio = document.getElementById('radio-audio');
	var button = document.getElementById('radio-play');
	var time = document.getElementById('radio-time');
	var progress = document.getElementById('radio-progress');
	var fallback = '/sounds/silent.mp3';
	var started = 0;

	function pad(n) { return String(n).padStart(2, '0'); }
	function tick() {
		if (!audio || audio.paused) return;
		var seconds = Math.floor((Date.now() - started) / 1000);
		time.innerHTML = pad(Math.floor(seconds / 3600)) + ':' + pad(Math.floor(seconds / 60) % 60) + ':' + pad(seconds % 60);
		progress.style.width = ((seconds % 60) / 60 * 100) + '%';
		window.setTimeout(tick, 1000);
	}
	button.onclick = function () {
		if (audio.paused) {
			started = Date.now();
			audio.play().then(function () {
				button.className = 'radio-btn is-playing';
				tick();
			}).catch(function () {
				audio.src = fallback;
				audio.play();
				document.getElementById('radio-current-song').innerHTML = 'Локальный режим: поток недоступен';
				button.className = 'radio-btn is-playing';
				tick();
			});
		} else {
			audio.pause();
			button.className = 'radio-btn';
		}
	};
	audio.onerror = function () {
		if (audio.src.indexOf(fallback) === -1) {
			audio.src = fallback;
			document.getElementById('radio-current-song').innerHTML = 'Локальный режим: поток недоступен';
		}
	};
})();
</script>
<?php
stdfoot();
