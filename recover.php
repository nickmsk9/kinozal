<?

/*
// +--------------------------------------------------------------------------+
// | Project:    TBDevYSE - TBDev Yuna Scatari Edition                        |
// +--------------------------------------------------------------------------+
// | This file is part of TBDevYSE. TBDevYSE is based on TBDev,               |
// | originally by RedBeard of TorrentBits, extensively modified by           |
// | Gartenzwerg.                                                             |
// |                                                                          |
// | TBDevYSE is free software; you can redistribute it and/or modify         |
// | it under the terms of the GNU General Public License as published by     |
// | the Free Software Foundation; either version 2 of the License, or        |
// | (at your option) any later version.                                      |
// |                                                                          |
// | TBDevYSE is distributed in the hope that it will be useful,              |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with TBDevYSE; if not, write to the Free Software Foundation,      |
// | Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            |
// +--------------------------------------------------------------------------+
// |                                               Do not remove above lines! |
// +--------------------------------------------------------------------------+
*/

require "include/bittorrent.php";

dbconn();

function recover_h($value): string
{
	return htmlspecialchars_uni((string)$value);
}

function recover_valid_token($secret): bool
{
	return (bool)preg_match('/^[A-Za-z0-9]{20,64}$/', (string)$secret);
}

function recover_success_message(): void
{
	global $tracker_lang;

	stderr(
		$tracker_lang['success'],
		"Если такой E-mail есть в базе, на него отправлена ссылка для установки нового пароля."
	);
}

function recover_send_reset_link($email): void
{
	global $DEFAULTBASEURL, $SITENAME, $SITEEMAIL, $tracker_lang;

	$email = trim((string)$email);
	if ($email === '') {
		stderr($tracker_lang['error'], "Вы должны ввести email адрес");
	}

	$res = sql_query("
		SELECT id, username, email
		FROM users
		WHERE email = " . sqlesc($email) . "
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);
	$arr = mysqli_fetch_assoc($res);

	if (!$arr) {
		recover_success_message();
	}

	$token = mksecret(40);
	sql_query("
		UPDATE users
		SET editsecret = " . sqlesc($token) . "
		WHERE id = " . (int)$arr["id"] . "
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);

	$remote_addr = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '';
	$link = $DEFAULTBASEURL . "/recover.php?id=" . (int)$arr["id"] . "&secret=" . rawurlencode($token);
	$user_email = (string)$arr['email'];

	$body = <<<EOD
Вы, или кто-то другой, запросили восстановление пароля для аккаунта, связанного с этим адресом ($user_email).

Запрос был отправлен с IP адреса $remote_addr.

Если это были не вы, просто проигнорируйте это письмо.

Чтобы установить новый пароль, перейдите по ссылке:

$link

--
$SITENAME
EOD;

	if (!sent_mail($user_email, $SITENAME, $SITEEMAIL, "Восстановление пароля на $SITENAME", $body, false)) {
		stderr($tracker_lang['error'], "Невозможно отправить E-mail. Пожалуйста, сообщите администрации об ошибке.");
	}

	recover_success_message();
}

function recover_fetch_user($id, $secret)
{
	$id = (int)$id;
	$secret = (string)$secret;

	if ($id <= 0 || !recover_valid_token($secret)) {
		httperr();
	}

	$res = sql_query("
		SELECT id, username, editsecret
		FROM users
		WHERE id = $id
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);
	$user = mysqli_fetch_assoc($res);

	if (!$user || (string)$user['editsecret'] === '' || !hash_equals((string)$user['editsecret'], $secret)) {
		httperr();
	}

	return $user;
}

function recover_complete_reset($id, $secret, $password, $passagain): void
{
	global $tracker_lang;

	$user = recover_fetch_user($id, $secret);
	$password = (string)$password;
	$passagain = (string)$passagain;

	if (strlen($password) < 6) {
		stderr($tracker_lang['error'], "Извините, пароль слишком короткий (минимум 6 символов).");
	}
	if (strlen($password) > 40) {
		stderr($tracker_lang['error'], "Извините, пароль слишком длинный (максимум 40 символов).");
	}
	if ($password !== $passagain) {
		stderr($tracker_lang['error'], "Пароли не совпадают. Попробуйте еще раз.");
	}

	$secret_new = mksecret();
	$passhash = tracker_password_hash($password);

	sql_query("
		UPDATE users
		SET secret = " . sqlesc($secret_new) . ",
		    editsecret = '',
		    passhash = " . sqlesc($passhash) . "
		WHERE id = " . (int)$user['id'] . "
		  AND editsecret = " . sqlesc($secret) . "
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);

	stderr(
		$tracker_lang['success'],
		"Пароль изменён. Теперь вы можете войти на сайт с новым паролем."
	);
}

function recover_render_tabs(): void
{
	?>
	<div class="pad0x0x5x0">
		<ul class="lis">
			<li><a href="/login.php">Вход</a></li>
			<li><a href="/signup.php">Регистрация в Кинозал.ТВ</a></li>
			<li class="mn"><a href="/recover.php">Восстановление пароля</a></li>
		</ul>
	</div>
	<?
}

function recover_render_request_form($email_value = ''): void
{
	global $use_captcha;

	stdhead("Восстановление пароля");
	?>
	<div style="width: 100%; text-align: center;">
		<div style="width: 700px; display: inline-block; text-align: left;">
			<? recover_render_tabs(); ?>
			<form method="post" action="recover.php">
				<input type="hidden" name="action" value="request">
				<div class="bx1_0">
					<div class="pad10x10 floatleft">
						<table class="tables1">
							<tr>
								<td class="w150 nw b">Почта</td>
								<td class="right"><input type="text" size="35" id="email" name="email" value="<?= recover_h($email_value) ?>"></td>
							</tr>
							<tr>
								<td colspan="2" align="right">Адрес электронной почты</td>
							</tr>
							<? if ($use_captcha) {
								include_once("include/captcha.php");
								$hash = create_captcha();
							?>
							<tr>
								<td class="w150 nw b">Проверочный вопрос</td>
								<td class="right">
									<img id="captcha" src="<?= recover_h(tracker_captcha_image_url($hash)) ?>" alt="Captcha" ondblclick="this.src='<?= recover_h(tracker_captcha_image_url($hash)) ?>&amp;'+Math.random();">
								</td>
							</tr>
							<tr>
								<td class="w150 nw b">Проверочный ответ</td>
								<td class="right">
									<input type="text" size="15" name="captcha_answer" class="w60" value="">
									<input type="hidden" name="captcha_id" value="<?= recover_h($hash) ?>">
								</td>
							</tr>
							<? } ?>
							<tr>
								<td colspan="2" class="right"><input class="buttonS" type="submit" value=" Восстановить пароль "></td>
							</tr>
						</table>
					</div>
					<div class="pad10x10" style="margin: 0 5px 0 380px;">
						Для восстановления пароля введите почтовый адрес,<br>
						указанный в Вашем профиле<br>
						Вам будет отправлена ссылка для установки нового пароля<br>
						С уважением, Администрация Кинозал.ТВ
					</div>
				</div>
			</form>
		</div>
	</div>
	<?
	stdfoot();
}

function recover_render_reset_form($id, $secret, $username): void
{
	stdhead("Новый пароль");
	?>
	<div style="width: 100%; text-align: center;">
		<div style="width: 700px; display: inline-block; text-align: left;">
			<? recover_render_tabs(); ?>
			<form method="post" action="recover.php" autocomplete="off">
				<input type="hidden" name="action" value="reset">
				<input type="hidden" name="id" value="<?= (int)$id ?>">
				<input type="hidden" name="secret" value="<?= recover_h($secret) ?>">
				<div class="bx1_0">
					<div class="pad10x10 floatleft">
						<table class="tables1">
							<tr>
								<td class="w150 nw b">Пользователь</td>
								<td class="right"><?= recover_h($username) ?></td>
							</tr>
							<tr>
								<td class="w150 nw b"><label for="recover-password">Новый пароль</label></td>
								<td class="right"><input type="password" size="35" id="recover-password" name="password" value=""></td>
							</tr>
							<tr>
								<td class="w150 nw b"><label for="recover-passagain">Повторите пароль</label></td>
								<td class="right"><input type="password" size="35" id="recover-passagain" name="passagain" value=""></td>
							</tr>
							<tr>
								<td colspan="2" class="right"><input class="buttonS" type="submit" value=" Сохранить пароль "></td>
							</tr>
						</table>
					</div>
					<div class="pad10x10" style="margin: 0 5px 0 380px;">
						Введите новый пароль для аккаунта.<br>
						После сохранения старый пароль перестанет работать.
					</div>
				</div>
			</form>
		</div>
	</div>
	<?
	stdfoot();
}

$method = $_SERVER["REQUEST_METHOD"] ?? "GET";

if ($method === "POST") {
	$action = (string)($_POST['action'] ?? 'request');

	if ($action === 'reset') {
		recover_complete_reset(
			(int)($_POST['id'] ?? 0),
			(string)($_POST['secret'] ?? ''),
			(string)($_POST['password'] ?? ''),
			(string)($_POST['passagain'] ?? '')
		);
	}

	if (!empty($use_captcha)) {
		include_once("include/captcha.php");
		if (!tracker_captcha_validate($_POST["captcha_id"] ?? '', $_POST["captcha_answer"] ?? '')) {
			stderr("Ошибка", "Вы ввели неправильный код подтверждения.");
		}
	}

	recover_send_reset_link($_POST["email"] ?? '');
}

if (!empty($_GET)) {
	$user = recover_fetch_user((int)($_GET["id"] ?? 0), (string)($_GET["secret"] ?? ''));
	recover_render_reset_form((int)$user['id'], (string)$_GET["secret"], (string)$user['username']);
	exit;
}

recover_render_request_form($_POST["email"] ?? '');

?>
