<?php

if (!defined('ADMIN_FILE')) {
    die('Illegal File Access');
}

if (!function_exists('CupsAdmin')) {
    function CupsAdmin()
    {
        global $admin_file, $CURUSER;

        kz_cups_ensure_schema();

        $messages = array();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cups'])) {
            $cup_users = isset($_POST['cup_user']) && is_array($_POST['cup_user']) ? $_POST['cup_user'] : array();

            foreach (kz_cups_catalog() as $cup) {
                $cup_id = (int)$cup['id'];
                $username = trim((string)($cup_users[$cup_id] ?? ''));

                if ($username === '') {
                    kz_cups_release($cup_id, 'manual');
                    continue;
                }

                $user = kz_cups_find_user_by_username($username);

                if (!$user) {
                    $messages[] = 'Пользователь "' . kz_cups_h($username) . '" не найден для кубка "' . kz_cups_h($cup['title']) . '".';
                    continue;
                }

                kz_cups_assign($cup_id, (int)$user['id'], 'manual', 0, (int)$CURUSER['id'], 'Назначено из админки');
                $messages[] = kz_cups_h($cup['title']) . ': назначен пользователю ' . kz_cups_h($user['username']) . '.';
            }
        }

        if (!empty($_GET['force']) || (isset($_POST['force_update']) && $_POST['force_update'])) {
            kz_cups_update_auto(true);
            $messages[] = 'Автообновление кубков выполнено.';
        }

        if (!empty($messages)) {
            stdmsg('Переходящие кубки', implode('<br />', $messages));
        }

        $current = kz_cups_current();

        echo '<div class="mn_wrap">';
        echo '<div class="tp1_title"><b>Переходящие кубки</b></div>';
        echo '<div class="tp1_body">';
        echo '<p>Ручное назначение имеет приоритет: автоматическая система не перезапишет кубок, пока он назначен администратором.</p>';
        echo '<p><a href="' . kz_cups_h($admin_file) . '.php?op=CupsAdmin&amp;force=1">Обновить авто-кубки сейчас</a></p>';
        echo '<form method="post" action="' . kz_cups_h($admin_file) . '.php?op=CupsAdmin">';
        echo '<input type="hidden" name="save_cups" value="1">';
        echo '<table class="tables2 w100p">';
        echo '<tr>';
        echo '<td class="colhead center">№</td>';
        echo '<td class="colhead">Кубок</td>';
        echo '<td class="colhead">Текущий обладатель</td>';
        echo '<td class="colhead center">Источник</td>';
        echo '<td class="colhead">Назначить вручную</td>';
        echo '</tr>';

        foreach ($current as $row) {
            $cup_id = (int)$row['cup_id'];
            $current_user = '—';
            $manual_value = '';

            if (!empty($row['userid']) && !empty($row['username'])) {
                $username = kz_cups_h($row['username']);
                $current_user = '<a href="userdetails.php?id=' . (int)$row['userid'] . '">'
                    . get_user_class_color((int)$row['class'], $username)
                    . '</a>';

                if ($row['source'] === 'manual') {
                    $manual_value = $username;
                }
            }

            $source = $row['source'] === 'manual' ? 'Админ' : ($row['source'] === 'auto' ? 'Авто' : '—');

            echo '<tr>';
            echo '<td class="center">' . (int)$row['sort'] . '</td>';
            echo '<td><b>' . kz_cups_h($row['icon']) . ' ' . kz_cups_h($row['title']) . '</b></td>';
            echo '<td>' . $current_user . '</td>';
            echo '<td class="center">' . $source . '</td>';
            echo '<td><input type="text" name="cup_user[' . $cup_id . ']" value="' . kz_cups_h($manual_value) . '" size="35"> <span class="small">пусто = снять ручное назначение</span></td>';
            echo '</tr>';
        }

        echo '<tr><td colspan="5" class="center"><input type="submit" class="btn" value="Сохранить"></td></tr>';
        echo '</table>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }
}

switch ($op) {
    case 'CupsAdmin':
        CupsAdmin();
        break;
}

?>
