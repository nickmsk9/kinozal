<?

require_once __DIR__ . '/include/bittorrent.php';

dbconn();

function signup_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$deny_signup         = !empty($deny_signup);
$allow_invite_signup = !empty($allow_invite_signup);
$use_captcha         = !empty($use_captcha);
$maxusers            = isset($maxusers) ? (int)$maxusers : 0;

if ($deny_signup && !$allow_invite_signup) {
    stderr($tracker_lang['error'], 'Извините, но регистрация отключена администрацией.');
}

if (!empty($CURUSER)) {
    stderr($tracker_lang['error'], sprintf($tracker_lang['signup_already_registered'], $SITENAME));
}

if (isset($_POST['wact']) && $_POST['wact'] === 'test_name') {
    $username = trim((string)($_POST['wusername'] ?? ''));
    $len = mb_strlen($username, 'UTF-8');

    if ($len < 3) {
        echo '<span style="color:red;">Имя менее 3 символов, проверьте имя</span>';
        exit;
    }
    if ($len > 12) {
        echo '<span style="color:red;">Имя не более 12 символов, проверьте имя</span>';
        exit;
    }
    if (!preg_match('/^[a-zA-Z0-9_а-яА-ЯёЁьъЬЪ]+$/u', $username)) {
        echo '<span style="color:red;">Недопустимые символы в имени</span>';
        exit;
    }

    $res = sql_query("SELECT id FROM users WHERE username = " . sqlesc($username) . " LIMIT 1");
    $exists = mysqli_fetch_assoc($res);
    if ($exists) {
        echo '<span style="color:red;">Имя занято, проверьте имя</span>';
    } else {
        echo '<span style="color:green;">Имя пользователя корректно и не занято</span>';
    }
    exit;
}

$res = sql_query('SELECT COUNT(id) AS users_count FROM users');
$row = mysqli_fetch_assoc($res);
$users = isset($row['users_count']) ? (int)$row['users_count'] : 0;

if ($maxusers > 0 && $users >= $maxusers) {
    stderr($tracker_lang['error'], sprintf($tracker_lang['signup_users_limit'], number_format($maxusers)));
}

stdhead($tracker_lang['signup_signup']);

$countries = '<option value="0">' . signup_h($tracker_lang['signup_not_selected']) . "</option>\n";
$ct_r = sql_query('SELECT id, name FROM countries ORDER BY name');
while ($ct_a = mysqli_fetch_assoc($ct_r)) {
    $countries .= '<option value="' . (int)$ct_a['id'] . '">' . signup_h($ct_a['name']) . "</option>\n";
}

$year = '<select class="styled" name="year"><option value="0000">' . signup_h($tracker_lang['my_year']) . "</option>\n";
for ($i = 1930; $i <= ((int)date('Y') - 13); $i++) {
    $year .= '<option value="' . $i . '">' . $i . "</option>\n";
}
$year .= "</select>\n";

$birthmonths = [
    '01' => $tracker_lang['my_months_january'], '02' => $tracker_lang['my_months_february'],
    '03' => $tracker_lang['my_months_march'], '04' => $tracker_lang['my_months_april'],
    '05' => $tracker_lang['my_months_may'], '06' => $tracker_lang['my_months_june'],
    '07' => $tracker_lang['my_months_jule'], '08' => $tracker_lang['my_months_august'],
    '09' => $tracker_lang['my_months_september'], '10' => $tracker_lang['my_months_october'],
    '11' => $tracker_lang['my_months_november'], '12' => $tracker_lang['my_months_december'],
];
$month = '<select class="styled" name="month"><option value="00">' . signup_h($tracker_lang['my_month']) . "</option>\n";
foreach ($birthmonths as $month_no => $show_month) {
    $month .= '<option value="' . signup_h($month_no) . '">' . signup_h($show_month) . "</option>\n";
}
$month .= "</select>\n";

$day = '<select class="styled" name="day"><option value="00">' . signup_h($tracker_lang['my_day']) . "</option>\n";
for ($i = 1; $i <= 31; $i++) {
    $v = sprintf('%02d', $i);
    $day .= '<option value="' . $v . '">' . $v . "</option>\n";
}
$day .= "</select>\n";

$hash = '';
if ($use_captcha) {
    include_once __DIR__ . '/include/captcha.php';
    $hash = create_captcha();
}
?>
<div style="width: 100%; text-align: center;"><div style="width: 700px; display: inline-block; text-align: left;">
<div class="pad0x0x5x0"><ul class="lis"><li><a href="/login.php">Вход</a></li><li class="mn"><a href="/signup.php">Регистрация в Кинозал.ТВ</a></li><li><a href="/recover.php">Восстановление пароля</a></li></ul></div>
<script type="text/javascript">
function test_name() {
    var un = jQuery('#wusername').val();
    jQuery.post('/signup.php', {wusername: un, wact: 'test_name'}, function(data) {
        jQuery('#tname').html(data);
    });
    return false;
}
</script>
<form method="post" action="takesignup.php" name="upt" id="upt">
<div class="bx1_0"><div class="pad10x10 floatleft"><table class="tables1">
<tr><td class="w150 nw b">Имя / Логин</td><td class="right"><input type="text" size="35" id="wusername" name="wantusername" value=""></td></tr>
<tr><td colspan="2" class="right"><span id="tname">Предварительно проверьте логин</span> <a href="" onclick="return test_name();" class="sba">проверить</a></td></tr>
<tr><td class="w150 nw b">Почта</td><td class="right"><input type="text" size="35" id="email" name="email" value=""></td></tr>
<tr><td colspan="2" class="right">Вам будет выслано письмо с подтверждением</td></tr>
<tr><td class="w150 nw b">Пароль</td><td class="right"><input type="password" size="35" name="wantpassword" value=""></td></tr>
<tr><td class="w150 nw b">Повторите пароль</td><td class="right"><input type="password" size="35" name="passagain" value=""></td></tr>
<tr><td class="w150 nw b">Страна</td><td class="right"><span class="sw200"><select class="styled" name="country" id="country"><?= $countries ?></select></span></td></tr>
<tr><td class="w150 nw b">Дата рождения</td><td class="right"><?= $day ?> <?= $month ?> <?= $year ?></td></tr>
<tr><td class="w150 nw b">Кто Вы</td><td class="right"><span class="sw200"><select class="styled" name="gender" id="wgender"><option value="1">Мужчина</option><option value="2">Женщина</option></select></span></td></tr>
<? if ($hash !== '') { ?>
<tr><td class="w150 nw b">Проверочный вопрос</td><td class="right"><img id="captcha" src="captcha.php?imagehash=<?= signup_h($hash) ?>" alt="Captcha" ondblclick="document.getElementById('captcha').src='captcha.php?imagehash=<?= signup_h($hash) ?>&amp;'+Math.random();"><input type="hidden" name="imagehash" value="<?= signup_h($hash) ?>"></td></tr>
<tr><td class="w150 nw b">Проверочный ответ</td><td class="right"><input type="text" size="15" name="imagestring" class="w60"></td></tr>
<? } ?>
<? if ($allow_invite_signup) { ?>
<tr><td class="w150 nw b">Код приглашения</td><td class="right"><input type="text" name="invite" maxlength="32" size="32"></td></tr>
<? } ?>
<tr style="display:none;"><td></td><td>
<input type="checkbox" name="rulesverify" value="yes" checked>
<input type="checkbox" name="faqverify" value="yes" checked>
<input type="checkbox" name="ageverify" value="yes" checked>
<input type="text" name="website" value=""><input type="text" name="icq" value=""><input type="text" name="aim" value=""><input type="text" name="msn" value=""><input type="text" name="yahoo" value=""><input type="text" name="skype" value=""><input type="text" name="mirc" value="">
</td></tr>
<tr><td colspan="2" class="right"><input class="buttonS" type="submit" value=" Завершить регистрацию "></td></tr>
</table></div>
<div class="pad10x10" style="margin: 0 5px 0 380px;">Добро пожаловать в Кинозал.ТВ<br><br>Регистрация позволит Вам создать личный профиль<br>Вы сможете скачивать и комментировать раздачи<br>Быть участником групп и общаться со зрителями<br>Добавлять в закладки фильмы и страницы персон<br>Участвовать в рейтингах и писать отзывы<br><br>Все поля обязательны к заполнению<br>Вы получите письмо для подтверждения регистрации<br><br>Будем рады Вашему визиту<br>Благодарим за регистрацию<br>С уважением, Администрация Кинозал.ТВ</div>
</div></form></div></div>
<?
stdfoot();
?>
