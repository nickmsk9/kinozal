<?




require_once("include/bittorrent.php");

dbconn();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

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
$use_email_act = !empty($use_email_act);
$use_captcha = !empty($use_captcha);
$enable_adv_antidreg = !empty($enable_adv_antidreg);
$check_for_working_mta = !empty($check_for_working_mta);

if ($deny_signup) {
    stderr($tracker_lang['error'], $tracker_lang['signup_disabled']);
}

if (!empty($CURUSER)) {
    stderr($tracker_lang['error'], sprintf($tracker_lang['signup_already_registered'], $SITENAME));
}

$users = get_row_count("users");

if ($maxusers > 0 && $users >= $maxusers) {
    stderr($tracker_lang['error'], sprintf($tracker_lang['signup_users_limit'], number_format($maxusers)));
}

$wantusername = isset($_POST['wantusername']) ? trim((string)$_POST['wantusername']) : null;
$wantpassword = isset($_POST['wantpassword']) ? (string)$_POST['wantpassword'] : null;
$passagain = isset($_POST['passagain']) ? (string)$_POST['passagain'] : null;
$email = isset($_POST['email']) ? trim((string)$_POST['email']) : null;

if ($wantusername === null || $wantpassword === null || $passagain === null || $email === null) {
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

if ($use_captcha && $users) {
    include_once __DIR__ . '/include/captcha.php';
    $captcha_id = isset($_POST['captcha_id']) ? trim((string)$_POST['captcha_id']) : '';
    $captcha_answer = isset($_POST['captcha_answer']) ? trim((string)$_POST['captcha_answer']) : '';

    if ($captcha_answer === '') {
        bark("Вы должны ввести код подтверждения.");
    }

    if (!tracker_captcha_validate($captcha_id, $captcha_answer)) {
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

        tracker_setcookie(COOKIE_UID, (string)$banned_id, time() + 31536000);

        bark("Ваш IP забанен на этом трекере. Регистрация невозможна.");
    }
}

$secret = mksecret();
$wantpasshash = tracker_password_hash($wantpassword);
$editsecret = ($use_email_act && $users) ? mksecret() : "";

$status = 'confirmed';
$theme = select_theme();
if (theme_resolve_name($theme) === 'TBDev') {
    $theme = 'Основная';
}
$added = get_date_time();

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
    'theme',
    'ip',
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
    $theme,
    $ip,
    0
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

write_log("Зарегистрирован новый пользователь " . $wantusername, "FFFFFF", "tracker");

$psecret = md5(hash_pad($editsecret));

$remote_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

$body = <<<EOD
Вы зарегистрировались на $SITENAME и указали этот адрес как обратный ($email).

Если это были не вы, пожалуйста, проигнорируйте это письмо. Пользователь, который ввел ваш E-Mail адрес, имеет IP адрес $remote_addr. Пожалуйста, не отвечайте.

Для подтверждения вашей регистрации вам нужно пройти по следующей ссылке:

$DEFAULTBASEURL/confirm.php?id=$id&secret=$psecret

После того как вы это сделаете, вы сможете использовать ваш аккаунт. Если вы этого не сделаете,
ваш новый аккаунт будет удален через пару дней. Мы рекомендуем вам прочитать правила
прежде чем вы начнете использовать $SITENAME.
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
