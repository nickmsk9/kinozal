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

$res = sql_query('SELECT COUNT(id) AS users_count FROM users');
$row = mysqli_fetch_assoc($res);
$users = isset($row['users_count']) ? (int)$row['users_count'] : 0;

if ($maxusers > 0 && $users >= $maxusers) {
    stderr($tracker_lang['error'], sprintf($tracker_lang['signup_users_limit'], number_format($maxusers)));
}

$agree = $_POST['agree'] ?? '';

if ($agree !== 'yes') {
    stdhead('Правила трекера');
    ?>

    <div style="width:80%; margin: 0 auto;">
        <fieldset class="fieldset">
            <legend>Правила трекера</legend>

            <form method="post" action="<?= signup_h($_SERVER['PHP_SELF'] ?? 'signup.php') ?>">
                <table cellpadding="4" cellspacing="0" border="0" style="width:100%" class="tableinborder">
                    <tr>
                        <td class="tablea">
                            Для продолжения регистрации, Вы должны согласиться со следующими правилами:
                        </td>
                    </tr>

                    <tr>
                        <td class="tablea" style="font-size:11px; font-family:verdana, geneva, lucida, 'lucida grande', arial, helvetica, sans-serif;">
                            <div class="page" style="border: thin inset; padding: 6px; overflow: auto; height: 170px;">
                                <p><strong>Правила трекера</strong></p>

                                <p>
                                    Регистрация на трекере абсолютно бесплатна! Настоятельно рекомендуем ознакомиться
                                    с правилами нашего проекта. Если вы согласны со всеми условиями, поставьте галочку
                                    рядом с «Я согласен» и нажмите «Регистрация». Если вы передумали регистрироваться,
                                    нажмите <a href="<?= signup_h($DEFAULTBASEURL ?? '/') ?>">здесь</a>, чтобы вернуться
                                    на главную страницу.
                                </p>

                                <p>
                                    Хотя модераторы и администраторы, обслуживающие <?= signup_h($SITENAME ?? '') ?>,
                                    стараются удалять все оскорбительные и некорректные сообщения из трекера,
                                    все равно все сообщения просмотреть невозможно. Сообщения отражают точку зрения
                                    только автора, но не администрации трекера, соответственно только автор несет
                                    ответственность за содержание сообщения.
                                </p>

                                <p>
                                    Соглашаясь с нашими правилами, вы обязуетесь выполнять требования трекера в целом,
                                    а также требования законодательства РФ.
                                </p>

                                <p>
                                    Администрация трекера оставляет за собой право удалять, изменять, переносить или
                                    закрывать любую тему или сообщение по своему усмотрению.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="tablea">
                            <label>
                                <input class="tablea" type="checkbox" name="agree" value="yes">
                                <input type="hidden" name="do" value="register">
                                <strong>
                                    Я согласен исполнять установленные правила, посещая <?= signup_h($SITENAME ?? '') ?>.
                                </strong>
                            </label>
                        </td>
                    </tr>
                </table>

                <p style="text-align:center;">
                    <input class="tableinborder" type="submit" value="Регистрация">
                </p>
            </form>
        </fieldset>
    </div>

    <?php
    stdfoot();
    die();
}

stdhead($tracker_lang['signup_signup']);

$countries = '<option value="0">' . signup_h($tracker_lang['signup_not_selected']) . "</option>\n";

$ct_r = sql_query('SELECT id, name FROM countries ORDER BY name');
while ($ct_a = mysqli_fetch_assoc($ct_r)) {
    $country_id = (int)$ct_a['id'];
    $country_name = signup_h($ct_a['name']);
    $countries .= '<option value="' . $country_id . '">' . $country_name . "</option>\n";
}
?>

<span style="color:red; font-weight:bold;">
    <?= signup_h($tracker_lang['signup_use_cookies']) ?>
</span>

<?php
if ($deny_signup && $allow_invite_signup) {
    stdmsg('Внимание', 'Регистрация доступна только тем, у кого есть код приглашения!');
}
?>

<p></p>

<form method="post" action="takesignup.php">
    <table border="1" cellspacing="0" cellpadding="10">
        <tr>
            <td align="right" class="heading"><?= signup_h($tracker_lang['signup_username']) ?></td>
            <td align="left">
                <input type="text" size="40" name="wantusername">
            </td>
        </tr>

        <tr>
            <td align="right" class="heading"><?= signup_h($tracker_lang['signup_password']) ?></td>
            <td align="left">
                <input type="password" size="40" name="wantpassword">
            </td>
        </tr>

        <tr>
            <td align="right" class="heading"><?= signup_h($tracker_lang['signup_password_again']) ?></td>
            <td align="left">
                <input type="password" size="40" name="passagain">
            </td>
        </tr>

        <tr valign="top">
            <td align="right" class="heading"><?= signup_h($tracker_lang['signup_email']) ?></td>
            <td align="left">
                <input type="text" size="40" name="email">
                <br>
                <span class="small"><?= signup_h($tracker_lang['signup_email_must_be_valid']) ?></span>
            </td>
        </tr>

        <tr>
            <td align="right" class="heading"><?= signup_h($tracker_lang['signup_gender']) ?></td>
            <td align="left">
                <label>
                    <input type="radio" name="gender" value="1">
                    <?= signup_h($tracker_lang['signup_male']) ?>
                </label>

                <label>
                    <input type="radio" name="gender" value="2">
                    <?= signup_h($tracker_lang['signup_female']) ?>
                </label>
            </td>
        </tr>

        <?php

        $year = '<select name="year"><option value="0000">' . signup_h($tracker_lang['my_year']) . "</option>\n";

        for ($i = 1920; $i <= ((int)date('Y') - 13); $i++) {
            $year .= '<option value="' . $i . '">' . $i . "</option>\n";
        }

        $year .= "</select>\n";

        $birthmonths = [
            '01' => $tracker_lang['my_months_january'],
            '02' => $tracker_lang['my_months_february'],
            '03' => $tracker_lang['my_months_march'],
            '04' => $tracker_lang['my_months_april'],
            '05' => $tracker_lang['my_months_may'],
            '06' => $tracker_lang['my_months_june'],
            '07' => $tracker_lang['my_months_jule'],
            '08' => $tracker_lang['my_months_august'],
            '09' => $tracker_lang['my_months_september'],
            '10' => $tracker_lang['my_months_october'],
            '11' => $tracker_lang['my_months_november'],
            '12' => $tracker_lang['my_months_december'],
        ];

        $month = '<select name="month"><option value="00">' . signup_h($tracker_lang['my_month']) . "</option>\n";

        foreach ($birthmonths as $month_no => $show_month) {
            $month .= '<option value="' . signup_h($month_no) . '">' . signup_h($show_month) . "</option>\n";
        }

        $month .= "</select>\n";

        $day = '<select name="day"><option value="00">' . signup_h($tracker_lang['my_day']) . "</option>\n";

        for ($i = 1; $i <= 31; $i++) {
            $value = sprintf('%02d', $i);
            $day .= '<option value="' . $value . '">' . $value . "</option>\n";
        }

        $day .= "</select>\n";

        tr($tracker_lang['my_birthdate'], $year . $month . $day, 1);

        tr(
            $tracker_lang['my_country'],
            '<select name="country">' . "\n" . $countries . "\n" . '</select>',
            1
        );

        tr(
            $tracker_lang['signup_contact'],
            '
            <table cellspacing="3" cellpadding="0" width="100%" border="0">
                <tr>
                    <td style="font-size:11px; font-family:verdana, geneva, lucida, arial, helvetica, sans-serif;">
                        ' . signup_h($tracker_lang['my_contact_icq']) . '<br>
                        <img alt="" src="pic/contact/icq.gif" width="17" height="17">
                        <input maxlength="30" size="25" name="icq">
                    </td>

                    <td style="font-size:11px; font-family:verdana, geneva, lucida, arial, helvetica, sans-serif;">
                        ' . signup_h($tracker_lang['my_contact_aim']) . '<br>
                        <img alt="" src="pic/contact/aim.gif" width="17" height="17">
                        <input maxlength="30" size="25" name="aim">
                    </td>
                </tr>

                <tr>
                    <td style="font-size:11px; font-family:verdana, geneva, lucida, arial, helvetica, sans-serif;">
                        ' . signup_h($tracker_lang['my_contact_msn']) . '<br>
                        <img alt="" src="pic/contact/msn.gif" width="17" height="17">
                        <input maxlength="50" size="25" name="msn">
                    </td>

                    <td style="font-size:11px; font-family:verdana, geneva, lucida, arial, helvetica, sans-serif;">
                        ' . signup_h($tracker_lang['my_contact_yahoo']) . '<br>
                        <img alt="" src="pic/contact/yahoo.gif" width="17" height="17">
                        <input maxlength="30" size="25" name="yahoo">
                    </td>
                </tr>

                <tr>
                    <td style="font-size:11px; font-family:verdana, geneva, lucida, arial, helvetica, sans-serif;">
                        ' . signup_h($tracker_lang['my_contact_skype']) . '<br>
                        <img alt="" src="pic/contact/skype.gif" width="17" height="17">
                        <input maxlength="32" size="25" name="skype">
                    </td>

                    <td style="font-size:11px; font-family:verdana, geneva, lucida, arial, helvetica, sans-serif;">
                        ' . signup_h($tracker_lang['my_contact_mirc']) . '<br>
                        <img alt="" src="pic/contact/mirc.gif" width="17" height="17">
                        <input maxlength="30" size="25" name="mirc">
                    </td>
                </tr>
            </table>',
            1
        );

        tr(
            $tracker_lang['my_website'],
            '<input type="text" name="website" size="40" value="">',
            1
        );

        if ($use_captcha && $users > 0) {
            include_once __DIR__ . '/include/captcha.php';

            $hash = create_captcha();
            $safe_hash = signup_h($hash);
            $safe_ss_uri = signup_h($ss_uri ?? '');

            tr(
                'Код подтверждения',
                '
                <input type="text" name="imagestring" size="20" value="">

                <p>
                    Пожалуйста, введите текст, изображенный на картинке внизу.<br>
                    Этот процесс предотвращает автоматическую регистрацию.
                </p>

                <table>
                    <tr>
                        <td class="block" rowspan="2">
                            <img
                                id="captcha"
                                src="captcha.php?imagehash=' . $safe_hash . '"
                                alt="Captcha"
                                ondblclick="document.getElementById(\'captcha\').src = \'captcha.php?imagehash=' . $safe_hash . '&amp;\' + Math.random();"
                            >
                        </td>

                        <td class="block">
                            <img
                                alt=""
                                src="themes/' . $safe_ss_uri . '/images/reload.gif"
                                style="cursor:pointer;"
                                onclick="document.getElementById(\'captcha\').src = \'captcha.php?imagehash=' . $safe_hash . '&amp;\' + Math.random();"
                            >
                        </td>
                    </tr>

                    <tr>
                        <td class="block">
                            <a href="captcha_mp3.php?imagehash=' . $safe_hash . '">
                                <img
                                    alt=""
                                    src="themes/' . $safe_ss_uri . '/images/listen.gif"
                                    style="cursor:pointer;"
                                    border="0"
                                >
                            </a>
                        </td>
                    </tr>
                </table>

                <font color="red">Код чувствителен к регистру</font><br>
                Кликните два раза на картинке, чтобы обновить картинку.

                <input type="hidden" name="imagehash" value="' . $safe_hash . '">
                ',
                1
            );
        }

        if ($allow_invite_signup) {
            tr(
                'Код приглашения',
                '<p>Если у вас есть код приглашения от пригласившего, введите его ниже.</p>
                <input type="text" name="invite" maxlength="32" size="32">',
                1
            );
        }

        ?>

        <tr>
            <td align="right" class="heading"></td>
            <td align="left">
                <label>
                    <input type="checkbox" name="rulesverify" value="yes">
                    <?= signup_h($tracker_lang['signup_i_have_read_rules']) ?>
                </label>
                <br>

                <label>
                    <input type="checkbox" name="faqverify" value="yes">
                    <?= signup_h($tracker_lang['signup_i_will_read_faq']) ?>
                </label>
                <br>

                <label>
                    <input type="checkbox" name="ageverify" value="yes">
                    <?= signup_h($tracker_lang['signup_i_am_13_years_old_or_more']) ?>
                </label>
            </td>
        </tr>

        <tr>
            <td colspan="2" align="center">
                <input type="submit" value="<?= signup_h($tracker_lang['signup_signup']) ?>" style="height:25px">
            </td>
        </tr>
    </table>
</form>

<?php

stdfoot();

?>