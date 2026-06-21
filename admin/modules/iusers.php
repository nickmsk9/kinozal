<?php

if (!defined('ADMIN_FILE')) {
    die('Illegal File Access');
}

function iusers_h($value): string
{
    return htmlspecialchars_uni((string)$value);
}

function iUsers(): void
{
    global $admin_file, $CURUSER, $link;

    $username = '';
    $email = '';
    $success = array();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $username = trim((string)($_POST['iname'] ?? ''));
        $password = (string)($_POST['ipass'] ?? '');
        $email = trim((string)($_POST['imail'] ?? ''));

        if ($username === '') {
            stdmsg('Ошибка', 'Укажите имя пользователя.', 'error');
        } elseif ($password === '' && $email === '') {
            stdmsg('Ошибка', 'Укажите новый пароль или новую почту.', 'error');
        } else {
            $res = sql_query("
                SELECT id, username, class, email
                FROM users
                WHERE username = " . sqlesc($username) . "
                LIMIT 1
            ") or sqlerr(__FILE__, __LINE__);
            $user = mysqli_fetch_assoc($res);

            if (!$user) {
                stdmsg('Ошибка', 'Пользователь не найден.', 'error');
            } elseif ((int)$user['id'] === (int)$CURUSER['id']) {
                stdmsg('Ошибка', 'Свои данные изменяйте в настройках профиля.', 'error');
            } elseif ((int)$user['class'] >= (int)$CURUSER['class']) {
                write_log(
                    'Администратор ' . $CURUSER['username']
                    . ' пытался изменить учётные данные пользователя ' . $username
                    . ' равного или более высокого класса.',
                    'red',
                    'error'
                );
                stdmsg(
                    'Ошибка',
                    'Нельзя изменять учётные данные пользователя равного или более высокого класса.',
                    'error'
                );
            } else {
                $updates = array();
                $revoke_passkeys = false;

                if ($password !== '') {
                    if (strlen($password) > 40) {
                        stdmsg('Ошибка', 'Пароль не должен превышать 40 символов.', 'error');
                    } else {
                        $secret = mksecret();
                        $updates[] = 'secret = ' . sqlesc($secret);
                        $updates[] = 'passhash = ' . sqlesc(tracker_password_hash($password));
                        $revoke_passkeys = true;
                        $success[] = 'пароль';
                    }
                }

                if ($email !== '') {
                    if (!validemail($email)) {
                        stdmsg('Ошибка', 'Указан некорректный E-mail.', 'error');
                        $updates = array();
                        $success = array();
                    } else {
                        $emailRes = sql_query("
                            SELECT id
                            FROM users
                            WHERE email = " . sqlesc($email) . "
                              AND id <> " . (int)$user['id'] . "
                            LIMIT 1
                        ") or sqlerr(__FILE__, __LINE__);

                        if (mysqli_fetch_assoc($emailRes)) {
                            stdmsg('Ошибка', 'Этот E-mail уже используется другим пользователем.', 'error');
                            $updates = array();
                            $success = array();
                        } elseif (strcasecmp($email, (string)$user['email']) !== 0) {
                            $updates[] = 'email = ' . sqlesc($email);
                            $success[] = 'почта';
                        }
                    }
                }

                if ($updates) {
                    sql_query("
                        UPDATE users
                        SET " . implode(', ', $updates) . "
                        WHERE id = " . (int)$user['id'] . "
                        LIMIT 1
                    ") or sqlerr(__FILE__, __LINE__);

                    if (mysqli_affected_rows($link) > 0) {
                        if ($revoke_passkeys) {
                            tracker_revoke_user_passkeys((int)$user['id']);
                        }

                        write_log(
                            'Администратор ' . $CURUSER['username']
                            . ' изменил учётные данные пользователя ' . $user['username']
                            . ': ' . implode(', ', $success) . '.'
                        );

                        stdmsg(
                            'Данные пользователя обновлены',
                            'Пользователь: <b>' . iusers_h($user['username']) . '</b><br>'
                            . 'Изменено: ' . iusers_h(implode(', ', $success)) . '.'
                        );

                        $email = '';
                    } else {
                        stdmsg('Ошибка', 'Новые данные совпадают с текущими.', 'error');
                    }
                }
            }
        }
    }
    ?>

    <div style="width: 100%; text-align: center;">
        <div style="width: 100%; max-width: 620px; display: inline-block; text-align: left;">
            <div class="mn_wrap">
                <div class="tp1_title"><b>Учётные данные пользователя</b></div>
                <div class="tp1_body">
                    <div class="bx1_0">
                        <div class="pad10x10">
                            Можно изменить пароль, E-mail или оба поля сразу.
                            После смены пароля активные passkey пользователя будут отозваны.
                        </div>
                    </div>

                    <form method="post" action="<?=iusers_h($admin_file);?>.php?op=iUsers" autocomplete="off">
                        <input type="hidden" name="op" value="iUsers">

                        <table class="tables1 w100p">
                            <tr>
                                <td class="rowhead w150">
                                    <label for="iusers-name">Пользователь</label>
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        id="iusers-name"
                                        name="iname"
                                        class="w300"
                                        value="<?=iusers_h($username);?>"
                                        required
                                        autofocus
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td class="rowhead">
                                    <label for="iusers-password">Новый пароль</label>
                                </td>
                                <td>
                                    <input
                                        type="password"
                                        id="iusers-password"
                                        name="ipass"
                                        class="w300"
                                        maxlength="40"
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td class="rowhead">
                                    <label for="iusers-email">Новая почта</label>
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        id="iusers-email"
                                        name="imail"
                                        class="w300"
                                        maxlength="80"
                                        value="<?=iusers_h($email);?>"
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td class="small">
                                    Пустое поле останется без изменений.
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="right">
                                    <input type="submit" class="buttonS" value=" Сохранить изменения ">
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}

if (($op ?? '') === 'iUsers') {
    iUsers();
}
