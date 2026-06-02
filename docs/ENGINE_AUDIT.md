# Аудит движка kinozal.lv

Дата: 2026-06-02

## Что уже внедрено

- Добавлен централизованный backend-кэш `include/cache.php`.
- Redis подключается лениво и не валит сайт, если расширения `Redis` или сервера Redis нет.
- Добавлены настройки кэша в `include/secrets.php` с возможностью переопределения в `include/config.local.php` или `include/secrets.local.php`.
- `include/core.php` подключает слой кэша до первого вызова `dbconn()`.
- `site_settings` кэшируются через Redis на 300 секунд и сбрасываются при сохранении.
- Активные блоки `orbital_blocks` читаются через общий хелпер и кэшируются на 30 секунд.
- `index.php` использует общий хелпер блоков вместо дублирующего SQL.
- Тяжёлые preload-запросы главной страницы кэшируются на 30 секунд по ключу страницы и класса пользователя.
- Капча теперь хранится в сессии, Redis и файловом fallback-кэше с TTL 900 секунд.
- В `sql_query()` добавлен централизованный сброс кэша при изменениях в таблицах настроек, блоков и данных главной.

## Найденные устаревшие места

- В проекте примерно 223 PHP-файла, часть из них очень крупная: `include/upload.php`, `include/functions.php`, `include/functions_global.php`, `details.php`, `include/groupex.php`, `admin/modules/blocks.php`.
- Остались вызовы и совместимость `mysql_*`: `announce.php`, `scrape.php`, `rss.php`, `recover.php`, `stats.php`, `usersearch.php`, `uploaders.php`, `include/functions_announce.php`, `include/functions_global.php`.
- Есть устаревшие конструкции PHP: `each()` в `torrent_info.php` и `moresmiles.php`, `get_magic_quotes_gpc()` в announce/scrape-части.
- В `include/functions.php` есть `preg_replace(... /e ...)` в `convert_unicode()`, это несовместимо с современным PHP и должно быть заменено на `preg_replace_callback()`.
- Много прямых `SELECT COUNT(*)` в списках и профилях: `browse.php`, `users.php`, `userdetails.php`, `userhistory.php`, `mytorrents.php`, `bookmarks.php`, `top.php`, `include/pay.php`.
- Повторяются проверки схемы через `SHOW TABLES` и `SHOW COLUMNS` в разных модулях. Лучше вынести единый schema-helper и запускать миграции отдельно, а не в обычном request path.
- В коде много глобальных переменных и больших процедурных файлов. Это мешает тестированию и делает изменения рискованными.
- В `themes/*/template.php` читаются HTML-шаблоны через `file_get_contents`; это можно кэшировать отдельно, но менять шаблонный слой сейчас не нужно из-за требования не трогать внешний вид.

## Приоритеты оптимизации без изменения вида

1. Заменить `mysql_*`-обёртки и старые вызовы на единый `mysqli` API.
2. Вынести повторяющиеся SQL-счётчики в backend-хелперы с коротким Redis TTL.
3. Перевести `SHOW TABLES/SHOW COLUMNS` из runtime в installer/admin migration flow.
4. Разделить `include/functions.php` и `include/functions_global.php` на небольшие файлы: db, html helpers, auth/session, torrent helpers, misc.
5. Добавить единый слой безопасного чтения настроек, блоков, справочников и категорий.
6. Добавить инвалидацию Redis по доменным ключам: `settings`, `blocks`, `index`, `users`, `torrents`.
7. Оставить CSS, темы и HTML-структуру без изменений; все улучшения вести через backend-хелперы и SQL.

## Риски

- Глобальный процедурный код затрудняет автоматическое сокращение без регрессий.
- Часть файлов содержит текст в повреждённой кодировке; массовые форматирования могут испортить строки.
- Redis-кэш с короткими TTL безопасен, но для долгого TTL нужна точная доменная инвалидация.
- Старые PHP-конструкции могут быть скрыты в редко используемых страницах, поэтому нужна поэтапная замена с lint-проверкой.
- В Docker расширение Redis устанавливается через `pecl redis`, но в локальном OSPanel PHP 8.1 оно сейчас не загружено. До включения расширения сайт будет работать через fallback без Redis.
- CLI-загрузка ядра без HTTP-переменных показывает предупреждения в `include/init.php` и `include/config.php`; это отдельная точка для чистки bootstrap-кода.
