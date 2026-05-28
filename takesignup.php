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


require_once("include/bittorrent.php");

dbconn();

if (isset($_POST['wusername']) && !isset($_POST['wantusername'])) {
    $_POST['wantusername'] = $_POST['wusername'];
}
if (isset($_POST['wpassword3']) && !isset($_POST['wantpassword'])) {
    $_POST['wantpassword'] = $_POST['wpassword3'];
}
if (isset($_POST['wgender']) && !isset($_POST['gender'])) {
    $_POST['gender'] = ($_POST['wgender'] === 'Female') ? '2' : '1';
}
if (isset($_POST['bday_year']) && !isset($_POST['year'])) {
    $_POST['year'] = $_POST['bday_year'];
}
if (isset($_POST['bday_month']) && !isset($_POST['month'])) {
    $_POST['month'] = $_POST['bday_month'];
}
if (isset($_POST['bday_day']) && !isset($_POST['day'])) {
    $_POST['day'] = $_POST['bday_day'];
}

$deny_signup = !empty($deny_signup);
$allow_invite_signup = !empty($allow_invite_signup);
$use_email_act = !empty($use_email_act);
$use_captcha = !empty($use_captcha);
$enable_adv_antidreg = !empty($enable_adv_antidreg);
$check_for_working_mta = !empty($check_for_working_mta);

if ($deny_signup && !$allow_invite_signup) {
    stderr($tracker_lang['error'], $tracker_lang['signup_disabled']);
}

if (!empty($CURUSER)) {
    stderr($tracker_lang['error'], sprintf($tracker_lang['signup_already_registered'], $SITENAME));
}

$users = get_row_count("users");

if ($maxusers > 0 && $users >= $maxusers) {
    stderr($tracker_lang['error'], sprintf($tracker_lang['signup_users_limit'], number_format($maxusers)));
}

if (!mkglobal("wantusername:wantpassword:passagain:email")) {
    stderr($tracker_lang['error'], $tracker_lang['dad']);
}

function bark($msg)
{
    global $tracker_lang;

    stdhead();
    stdmsg($tracker_lang['error'], $msg, 'error');
    stdfoot();

    exit;
}

function validusername($username)
{
    $username = trim((string)$username);

    if ($username === '') {
        return false;
    }

    if (mb_strlen($username, 'UTF-8') > 12) {
        return false;
    }

    return (bool)preg_match('/^[a-zA-Z0-9_а-яА-ЯёЁьъЬЪ]+$/u', $username);
}

$wantusername = trim((string)$wantusername);
$wantpassword = (string)$wantpassword;
$passagain = (string)$passagain;
$email = trim(strtolower((string)$email));

$gender = isset($_POST['gender']) ? trim((string)$_POST['gender']) : '';
$country = isset($_POST['country']) ? (int)$_POST['country'] : 0;

$year = isset($_POST['year']) ? (int)$_POST['year'] : 0;
$month = isset($_POST['month']) ? (int)$_POST['month'] : 0;
$day = isset($_POST['day']) ? (int)$_POST['day'] : 0;

if ($wantusername === '' || $wantpassword === '' || $email === '' || $gender === '' || $country <= 0) {
    bark("Все поля обязательны для заполнения.");
}

if (mb_strlen($wantusername, 'UTF-8') > 12) {
    bark("Извините, имя пользователя слишком длинное. Максимум 12 символов.");
}

if (!validusername($wantusername)) {
    bark("Неверное имя пользователя.");
}

if ($wantpassword !== $passagain) {
    bark("Пароли не совпадают. Похоже, вы ошиблись. Попробуйте еще раз.");
}

if (strlen($wantpassword) < 6) {
    bark("Извините, пароль слишком короткий. Минимум 6 символов.");
}

if (strlen($wantpassword) > 40) {
    bark("Извините, пароль слишком длинный. Максимум 40 символов.");
}

if ($wantpassword === $wantusername) {
    bark("Извините, пароль не может совпадать с именем пользователя.");
}

if (!validemail($email)) {
    bark("Это не похоже на реальный email адрес.");
}

$email_parts = explode('@', $email, 2);
$domain = isset($email_parts[1]) ? $email_parts[1] : '';

if ($domain === '' || !mail_possible($email)) {
    bark('Почты в таком домене быть не может (' . htmlspecialchars_uni($domain) . ')');
}

if (!checkdate($month, $day, $year)) {
    stderr($tracker_lang['error'], "Похоже, вы указали неверную дату рождения.");
}

$birthday = sprintf('%04d-%02d-%02d', $year, $month, $day);

$rulesverify = isset($_POST['rulesverify']) ? $_POST['rulesverify'] : '';
$faqverify = isset($_POST['faqverify']) ? $_POST['faqverify'] : '';
$ageverify = isset($_POST['ageverify']) ? $_POST['ageverify'] : '';

if ($rulesverify !== 'yes' || $faqverify !== 'yes' || $ageverify !== 'yes') {
    stderr($tracker_lang['error'], "Извините, вы не подходите для того, чтобы стать членом этого сайта.");
}

// Проверку MTA отключаем.
// На локальном сервере OSPanel / Windows она часто зависает или ошибочно блокирует регистрацию.
// Достаточно проверки формата email и mail_possible().
$check_for_working_mta = false;

$email_exists = get_row_count('users', 'WHERE email = ' . sqlesc($email));

if ($email_exists != 0) {
    bark("E-mail адрес " . htmlspecialchars_uni($email) . " уже зарегистрирован в системе.");
}

$inviter = 0;
$invitedroot = 0;
$invite = isset($_POST['invite']) ? trim((string)$_POST['invite']) : '';

if ($deny_signup && $allow_invite_signup) {
    if ($invite === '') {
        stderr($tracker_lang['error'], "Для регистрации вам нужно ввести код приглашения.");
    }

    if (strlen($invite) !== 32) {
        stderr($tracker_lang['error'], "Вы ввели неправильный код приглашения.");
    }

    $res = sql_query("SELECT inviter FROM invites WHERE invite = " . sqlesc($invite) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_row($res);

    if (!$row || empty($row[0])) {
        stderr($tracker_lang['error'], "Код приглашения, введенный вами, не рабочий.");
    }

    $inviter = (int)$row[0];

    $res = sql_query("SELECT invitedroot FROM users WHERE id = " . sqlesc($inviter) . " LIMIT 1") or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_row($res);

    $invitedroot = $row ? (int)$row[0] : 0;
}

if ($use_captcha && $users) {
    $imagehash = isset($_POST['imagehash']) ? trim((string)$_POST['imagehash']) : '';
    $imagestring = isset($_POST['imagestring']) ? trim((string)$_POST['imagestring']) : '';

    if ($imagestring === '') {
        bark("Вы должны ввести код подтверждения.");
    }

    $captcha_count = get_row_count(
        "captcha",
        "WHERE imagehash = " . sqlesc($imagehash) . " AND imagestring = " . sqlesc($imagestring)
    );

    sql_query("DELETE FROM captcha WHERE imagehash = " . sqlesc($imagehash)) or sqlerr(__FILE__, __LINE__);

    if ($captcha_count == 0) {
        bark("Вы ввели неправильный код подтверждения.");
    }
}

$ip = getip();

if (isset($_COOKIE[COOKIE_UID]) && is_numeric($_COOKIE[COOKIE_UID]) && $users && $enable_adv_antidreg) {
    $cid = (int)$_COOKIE[COOKIE_UID];

    $res = sql_query("SELECT enabled FROM users WHERE id = " . sqlesc($cid) . " ORDER BY id DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
    $co = mysqli_fetch_row($res);

    if ($co && $co[0] === 'no') {
        sql_query("UPDATE users SET ip = " . sqlesc($ip) . ", last_access = NOW() WHERE id = " . sqlesc($cid)) or sqlerr(__FILE__, __LINE__);
        bark("Ваш IP забанен на этом трекере. Регистрация невозможна.");
    }

    bark("Регистрация невозможна.");
} else {
    $res = sql_query("SELECT enabled, id FROM users WHERE ip = " . sqlesc($ip) . " ORDER BY last_access DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
    $b = mysqli_fetch_row($res);

    if ($b && $b[0] === 'no') {
        $banned_id = (int)$b[1];

        setcookie(COOKIE_UID, (string)$banned_id, time() + 31536000, "/");

        bark("Ваш IP забанен на этом трекере. Регистрация невозможна.");
    }
}

$secret = mksecret();
$wantpasshash = md5($secret . $wantpassword . $secret);
$editsecret = "";

$status = 'confirmed';
$theme = select_theme();
if (theme_resolve_name($theme) === 'TBDev') {
    $theme = 'Основная';
}
$added = get_date_time();
$passkey = md5($wantusername . $email . $added . mt_rand());

$fields = array(
    'username',
    'passhash',
    'secret',
    'editsecret',
    'gender',
    'country',
    'email',
    'status',
    'added',
    'birthday',
    'invitedby',
    'invitedroot',
    'theme',
    'ip',
    'passkey',
    'simpaty'
);

$values = array(
    $wantusername,
    $wantpasshash,
    $secret,
    $editsecret,
    $gender,
    $country,
    $email,
    $status,
    $added,
    $birthday,
    $inviter,
    $invitedroot,
    $theme,
    $ip,
    $passkey,
    function_exists('kz_reputation_signup_value') ? kz_reputation_signup_value() : 1
);

if (!$users) {
    $fields[] = 'class';
    $values[] = UC_SYSOP;
}

$sql = "INSERT INTO users (" . implode(", ", $fields) . ") VALUES (" . implode(", ", array_map("sqlesc", $values)) . ")";

$ret = sql_query($sql);

if (!$ret) {
    global $link;
    $db = ($link instanceof mysqli) ? $link : null;

    $errno = $db ? mysqli_errno($db) : 0;
    $error = $db ? mysqli_error($db) : 'Unknown MySQL error';

    if ($errno == 1062) {
        bark("Пользователь " . htmlspecialchars_uni($wantusername) . " уже зарегистрирован.");
    }

    bark(
        "Ошибка регистрации MySQL #" . $errno . ": " .
        htmlspecialchars_uni($error) .
        "<br><br><b>SQL:</b><br>" .
        htmlspecialchars_uni($sql)
    );
}

$id = mysqli_insert_id($link);

if ($invite !== '') {
    sql_query("DELETE FROM invites WHERE invite = " . sqlesc($invite)) or sqlerr(__FILE__, __LINE__);
}

write_log("Зарегистрирован новый пользователь " . $wantusername, "FFFFFF", "tracker");

$psecret = md5($editsecret);

$remote_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

$body = <<<EOD
Вы зарегистрировались на $SITENAME и указали этот адрес как обратный ($email).

Если это были не вы, пожалуйста, проигнорируйте это письмо. Пользователь, который ввел ваш E-Mail адрес, имеет IP адрес $remote_addr. Пожалуйста, не отвечайте.

Для подтверждения вашей регистрации вам нужно пройти по следующей ссылке:

$DEFAULTBASEURL/confirm.php?id=$id&secret=$psecret

После того как вы это сделаете, вы сможете использовать ваш аккаунт. Если вы этого не сделаете,
ваш новый аккаунт будет удален через пару дней. Мы рекомендуем вам прочитать правила
и FAQ прежде чем вы начнете использовать $SITENAME.
EOD;

if ($use_email_act && $users) {
    if (!sent_mail($email, $SITENAME, $SITEEMAIL, "Подтверждение регистрации на $SITENAME", $body, false)) {
        write_log("Проблема с отправкой письма для активации на адрес " . $email, "FF0000", "errors");

        logincookie($id, $wantpasshash);

        sql_query("UPDATE users SET status = 'confirmed' WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);

        header("Location: ok.php?type=confirm");
        exit;
    }
} else {
    logincookie($id, $wantpasshash);
}

$type = !$users ? "sysop" : "signup&email=" . urlencode($email);

header("Location: ok.php?type=" . $type);
exit;

?>
