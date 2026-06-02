<?php

// -----------------------------------------------------------------------------
// Главная обёртка (табличная разметка, как в оригинале)
// -----------------------------------------------------------------------------

/**
 * Открывает главную таблицу контента.
 */
function begin_main_frame(): void
{
    echo '<table class="main" width="100%" border="0" cellspacing="0" cellpadding="0">';
    echo '<tr><td class="embedded">';
}

/**
 * Закрывает главную таблицу контента.
 */
function end_main_frame(): void
{
    echo '</td></tr></table>';
}

// -----------------------------------------------------------------------------
// Простая таблица (используется в некоторых местах)
// -----------------------------------------------------------------------------

/**
 * Открывает таблицу с классом main.
 *
 * @param bool $fullwidth  Добавить width="100%"
 * @param int  $padding    Отступы внутри ячеек (атрибут cellpadding устарел, оставлен для совместимости)
 */
function begin_table(bool $fullwidth = false, int $padding = 5): void
{
    $width = $fullwidth ? ' width="100%"' : '';
    // cellpadding оставлен, так как в CSS нет явного управления отступами в ячейках этой таблицы
    echo '<table class="main"' . $width . ' cellspacing="0" cellpadding="' . (int)$padding . '">';
}

/**
 * Закрывает таблицу.
 */
function end_table(): void
{
    echo '</table>';
}

// -----------------------------------------------------------------------------
// Современные блоки (рамки) на основе div
// -----------------------------------------------------------------------------

/**
 * Открывает рамку (блок) с заголовком.
 *
 * @param string $caption Заголовок (будет экранирован)
 * @param bool   $center  Выравнивание содержимого по центру
 * @param int    $padding Внутренний отступ (px)
 */
function begin_frame(string $caption = '', bool $center = false, int $padding = 10): void
{
    $padding = max(0, $padding);
    echo '<div class="clr"></div>';
    echo '<div class="frame-wrap">';

    if ($caption !== '') {
        echo '<div class="pad0x0x5x0">';
        echo '<ul class="lis">';
        echo '<li class="mn">' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</li>';
        echo '</ul>';
        echo '</div>';
    }

    echo '<div class="bx1_0">';
    $classes = ['pad' . $padding . 'x' . $padding];
    if ($center) {
        $classes[] = 'center';
    }
    echo '<div class="' . implode(' ', $classes) . '">';
}

/**
 * Вставляет дополнительный отступ внутри текущего блока (аналог "прикрепить").
 *
 * @param int $padding Новый внутренний отступ
 */
function attach_frame(int $padding = 10): void
{
    $padding = max(0, $padding);
    echo '</div>'; // закрываем предыдущий padXxX
    echo '<div class="clr"></div>';
    echo '<div class="pad' . $padding . 'x' . $padding . '">';
}

/**
 * Закрывает рамку.
 */
function end_frame(): void
{
    echo '</div>';      // закрываем padXxX
    echo '</div>';      // закрываем bx1_0
    echo '</div>';      // закрываем frame-wrap
    echo '<div class="clr"></div>';
}

// -----------------------------------------------------------------------------
// Блок смайлов (адаптирован под PHP 8)
// -----------------------------------------------------------------------------

/**
 * Выводит таблицу со всеми смайлами.
 * 
 * @param array $smilies         Ассоциативный массив: код => имя файла
 * @param string $baseUrl        Базовый URL до папки /pic/smilies/
 */
function insert_smilies_frame(array $smilies, string $baseUrl = ''): void
{
    if (empty($smilies)) {
        return;
    }

    begin_frame('Смайлы', true);

    echo '<table class="main" cellspacing="0" cellpadding="5">';
    echo '<tr><td class="colhead">Написание</td><td class="colhead">Смайл</td></tr>';

    foreach ($smilies as $code => $filename) {
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($baseUrl . $filename, ENT_QUOTES, 'UTF-8');
        echo '<tr>';
        echo '<td>' . $safeCode . '</td>';
        echo '<td><img src="' . $safeUrl . '" alt="' . $safeCode . '"></td>';
        echo '</tr>';
    }

    echo '</table>';
    end_frame();
}

// -----------------------------------------------------------------------------
// Блок меню (безопасная версия, без eval)
// -----------------------------------------------------------------------------

/**
 * Выводит боковой блок меню из HTML-файла.
 *
 * @param string $title   Заголовок блока (не используется, оставлен для совместимости)
 * @param string $content Содержимое (не используется, оставлен для совместимости)
 * @param string $width   Ширина (не используется)
 *
 * @return void
 */
function blok_menu(string $title, string $content, string $width = '155'): void
{
    global $ss_uri;

    // Формируем путь к файлу шаблона
    $templateFile = 'themes/' . $ss_uri . '/html/block-left.html';
    if (!file_exists($templateFile)) {
        // Если файла нет – выводим заглушку, чтобы не ломать вёрстку
        echo '<div class="mn_wrap">Меню не найдено</div>';
        return;
    }

    // Безопасное чтение файла
    $html = file_get_contents($templateFile);
    if ($html === false) {
        echo '<div class="mn_wrap">Ошибка чтения меню</div>';
        return;
    }

    // ВНИМАНИЕ: если файл содержит PHP-код, он НЕ будет выполнен.
    // Это гарантирует безопасность. Если вам нужен динамический HTML,
    // используйте include, но тогда убедитесь, что файл не может быть подменён.
    echo $html;
}