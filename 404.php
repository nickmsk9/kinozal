<?php

require_once __DIR__ . '/include/bittorrent.php';

dbconn(false);

http_response_code(404);

$hide_right_blocks = true;

stdhead('404 Ошибка');
?>

<div style="width: 100%; text-align: center; padding-top: 5px;">
    <div style="width: 100%; max-width: 700px; display: inline-block; text-align: left;">
        <div class="bx1_0 red">
            <div class="pad10x10">
                <p><b>404 Ошибка</b></p>
                <p>Нет страницы с таким адресом</p>
            </div>
        </div>

        <div class="bx1_0">
            <div class="pad10x10">
                <p>Перейдите на Главную страницу <a href="/" class="sbab">здесь</a>.</p>
                <p>Попробуйте вручную поискать раздачи <a href="/browse.php" class="sbab">здесь</a>.</p>
                <p>Посмотреть Топ раздач <a href="/top.php" class="sbab">здесь</a>.</p>
                <p>Посетить раздел Персон <a href="/personsearch.php" class="sbab">здесь</a>.</p>
                <p>Посетить раздел Групп <a href="/groupexlist.php" class="sbab">здесь</a>.</p>
            </div>
        </div>
    </div>
    <div class="clr"></div>
</div>

<?php
stdfoot();
?>
