<?php

declare(strict_types=1);

require_once __DIR__ . '/include/bittorrent.php';

dbconn(true);
loggedinorreturn();

if (get_user_class() < UC_MODERATOR) {
    stderr(
        $tracker_lang['error'] ?? 'Ошибка',
        $tracker_lang['access_denied'] ?? 'Доступ запрещен.'
    );
}

function uploaders_h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function uploaders_ratio(float|int $uploaded, float|int $downloaded): string
{
    if ($downloaded > 0) {
        return number_format($uploaded / $downloaded, 3, '.', '');
    }

    return $uploaded > 0 ? 'Inf.' : '---';
}

function uploaders_last_upload(?string $date): string
{
    if ($date === null || $date === '' || $date === '0000-00-00 00:00:00') {
        return '---';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '---';
    }

    return get_elapsed_time($timestamp) . ' назад (' . date('d.m.Y', $timestamp) . ')';
}

$res = sql_query("
    SELECT
        u.id,
        u.username,
        u.class,
        u.uploaded,
        u.downloaded,
        u.donor,
        u.warned,
        u.enabled,
        COUNT(t.id) AS torrent_count,
        MAX(t.added) AS last_upload
    FROM users AS u
    LEFT JOIN torrents AS t ON t.owner = u.id
    WHERE u.class = " . (int)UC_UPLOADER . "
    GROUP BY
        u.id,
        u.username,
        u.class,
        u.uploaded,
        u.downloaded,
        u.donor,
        u.warned,
        u.enabled
    ORDER BY u.username ASC
") or sqlerr(__FILE__, __LINE__);

$uploaders = array();
while ($row = mysqli_fetch_assoc($res)) {
    $uploaders[] = $row;
}

$hide_right_blocks = true;
stdhead('Аплоадеры');
?>
<div class="bx2">
    <div class="pad0x0x5x0">
        <h1>
            <span class="bulet"></span>
            <a href="uploaders.php" class="sbab">Информация об аплоадерах</a>
        </h1>
    </div>

    <div class="bx2_0">
        <div class="pad10x10">
            <div class="pad0x0x10x0">
                Всего аплоадеров: <b><?= count($uploaders) ?></b>
            </div>

            <?php if ($uploaders) { ?>
                <table class="brd w100p">
                    <tr class="colhead center">
                        <td class="nw">№</td>
                        <td>Пользователь</td>
                        <td class="nw">Раздал / скачал</td>
                        <td>Рейтинг</td>
                        <td>Раздач</td>
                        <td>Последняя загрузка</td>
                        <td>ЛС</td>
                    </tr>

                    <?php foreach ($uploaders as $index => $uploader) { ?>
                        <?php
                        $userId = (int)$uploader['id'];
                        $uploaded = (float)$uploader['uploaded'];
                        $downloaded = (float)$uploader['downloaded'];
                        $icons = function_exists('get_user_icons') ? get_user_icons($uploader) : '';
                        ?>
                        <tr>
                            <td class="center"><?= $index + 1 ?></td>
                            <td>
                                <a class="u<?= (int)$uploader['class'] ?>" href="userdetails.php?id=<?= $userId ?>">
                                    <?= uploaders_h($uploader['username']) ?>
                                </a>
                                <?= $icons ?>
                            </td>
                            <td class="center nw">
                                <?= uploaders_h(mksize($uploaded)) ?> / <?= uploaders_h(mksize($downloaded)) ?>
                            </td>
                            <td class="center"><?= uploaders_ratio($uploaded, $downloaded) ?></td>
                            <td class="center"><?= (int)$uploader['torrent_count'] ?></td>
                            <td class="center nw"><?= uploaders_h(uploaders_last_upload($uploader['last_upload'])) ?></td>
                            <td class="center">
                                <a class="buttonS" href="sendmessage.php?receiver=<?= $userId ?>">Написать</a>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <div class="bx5x5 center">Аплоадеры не найдены.</div>
            <?php } ?>
        </div>
    </div>
</div>
<?php
stdfoot();
