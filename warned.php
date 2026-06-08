<?php

declare(strict_types=1);

require_once __DIR__ . '/include/bittorrent.php';

dbconn();
loggedinorreturn();

if (get_user_class() < UC_MODERATOR) {
    stderr($tracker_lang['error'] ?? 'Ошибка', 'Отказано в доступе.');
}

function warned_h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function warned_date(?string $date): string
{
    if (
        $date === null ||
        $date === '' ||
        $date === '0000-00-00 00:00:00' ||
        $date === '0000-00-00'
    ) {
        return '-';
    }

    return warned_h(substr($date, 0, 10));
}

function warned_ratio(float|int $uploaded, float|int $downloaded): string
{
    if ($downloaded <= 0) {
        return '---';
    }

    $ratio = number_format($uploaded / $downloaded, 3, '.', '');
    $color = get_ratio_color($ratio);

    if ($color) {
        return '<span style="color:' . warned_h($color) . '">' . warned_h($ratio) . '</span>';
    }

    return warned_h($ratio);
}

stdhead('Предупрежденные пользователи');

$warnedCount = (int)get_row_count('users', "WHERE warned = 'yes'");

begin_main_frame();
begin_frame('Предупрежденные пользователи: (' . number_format($warnedCount) . ')');

$res = sql_query("
    SELECT
        id,
        username,
        class,
        donor,
        added,
        last_access,
        uploaded,
        downloaded,
        warneduntil
    FROM users
    WHERE warned = 'yes'
      AND enabled = 'yes'
    ORDER BY
        CASE
            WHEN downloaded > 0 THEN uploaded / downloaded
            ELSE uploaded
        END ASC
") or sqlerr(__FILE__, __LINE__);

if (!$res instanceof mysqli_result) {
    stderr($tracker_lang['error'] ?? 'Ошибка', 'Ошибка выполнения SQL-запроса.');
}

if (mysqli_num_rows($res) < 1) {
    echo '<table class="tables1" width="100%" cellspacing="0" cellpadding="5">';
    echo '<tr><td class="center">Предупрежденных пользователей нет.</td></tr>';
    echo '</table>';

    end_frame();
    end_main_frame();
    stdfoot();
    exit;
}

echo '<form action="nowarn.php" method="post">';

echo '<table class="tables1" width="100%" cellspacing="0" cellpadding="5">';

echo '<tr class="colhead center">';
echo '<td width="90">Пользователь</td>';
echo '<td width="70">Зарегистрирован</td>';
echo '<td width="75">Последний раз был на трекере</td>';
echo '<td width="75">Класс</td>';
echo '<td width="70">Закачал</td>';
echo '<td width="70">Раздал</td>';
echo '<td width="45">Рейтинг</td>';
echo '<td width="125">Окончание</td>';
echo '<td width="65">Убрать</td>';
echo '<td width="65">Отключить</td>';
echo '</tr>';

while ($arr = mysqli_fetch_assoc($res)) {
    $userId = (int)($arr['id'] ?? 0);

    $username = warned_h($arr['username'] ?? '');
    $userClass = get_user_class_name((int)($arr['class'] ?? 0));

    $uploadedBytes = (float)($arr['uploaded'] ?? 0);
    $downloadedBytes = (float)($arr['downloaded'] ?? 0);

    $uploaded = warned_h(mksize($uploadedBytes));
    $downloaded = warned_h(mksize($downloadedBytes));

    $ratio = warned_ratio($uploadedBytes, $downloadedBytes);

    $added = warned_date($arr['added'] ?? null);
    $lastAccess = warned_date($arr['last_access'] ?? null);
    $warnedUntil = warned_date($arr['warneduntil'] ?? null);

    $donorIcon = '';
    if (($arr['donor'] ?? '') === 'yes') {
        $donorIcon = ' <img src="pic/star.gif" border="0" alt="Donor" title="Donor">';
    }

    echo '<tr>';

    echo '<td align="left">';
    echo '<a href="userdetails.php?id=' . $userId . '"><b>' . $username . '</b></a>' . $donorIcon;
    echo '</td>';

    echo '<td align="center">' . $added . '</td>';
    echo '<td align="center">' . $lastAccess . '</td>';
    echo '<td align="center">' . warned_h($userClass) . '</td>';
    echo '<td align="center">' . $downloaded . '</td>';
    echo '<td align="center">' . $uploaded . '</td>';
    echo '<td align="center">' . $ratio . '</td>';
    echo '<td align="center">' . $warnedUntil . '</td>';

    echo '<td align="center" style="background:#008000">';
    echo '<input type="checkbox" name="usernw[]" value="' . $userId . '">';
    echo '</td>';

    echo '<td align="center" style="background:#FF0000">';
    echo '<input type="checkbox" name="desact[]" value="' . $userId . '">';
    echo '</td>';

    echo '</tr>';
}

if (get_user_class() >= UC_ADMINISTRATOR) {
    echo '<tr>';
    echo '<td colspan="10" align="right">';
    echo '<input type="hidden" name="nowarned" value="nowarned">';
    echo '<input type="submit" name="submit" value="Применить">';
    echo '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</form>';

end_frame();
end_main_frame();

stdfoot();

?>
