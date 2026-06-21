# Аудит движка Kinozal

Дата: 2026-06-20  
Срез: `main`, последний коммит `b0675b4 поменял внешний вид системы редактирования пользователя`

## Объем проверки

Проверены PHP-точки входа, `include/*`, темы, Docker-конфигурация, SQL-схема и горячие страницы: `announce.php`, `browse.php`, `details.php`, `index.php`, загрузка/редактирование торрентов, личные сообщения, закладки, группы, пользователи, кеш и cleanup.

Проверка синтаксиса:

```bash
php -d short_open_tag=1 -l
```

Результат: проверено 223 PHP-файла, parse error нет.

Ограничение аудита: это статический кодовый аудит без продакшен-дампа БД, real traffic/profile trace и EXPLAIN ANALYZE на живых объемах. Поэтому часть выводов про нагрузку основана на форме запросов, индексах и типичном профиле torrent tracker.

## Статус исправлений

Начаты исправления по аудиту.

- Закрыто: базовая CSRF-защита `hash4u` для опасных GET/POST действий, logout с токеном, безопасный local redirect, cookie flags `HttpOnly`/`SameSite=Lax`/`Secure` на HTTPS.
- Закрыто: PHP 8 fatal в `log.php`, отключение `display_errors` по умолчанию в Docker/runtime.
- Закрыто: announce-коллизии `peer_id`, уникальность `snatched`, индексы горячего пути announce, отключение синхронного TCP connectability check по умолчанию.
- Закрыто: переход новых паролей на `password_hash()`, совместимая проверка старых MD5-хэшей, lazy rehash при логине, запрет логина/регистрации через GET credentials.
- Закрыто: восстановление пароля больше не генерирует пароль и не отправляет его письмом; вместо этого используется одноразовый reset-token и форма установки нового пароля.
- Частично закрыто: новые и ротируемые passkey теперь 32 base62-символа, схема расширена до `varchar(64)`, старые 10-символьные passkey остаются валидными для совместимости.
- Закрыто: reset-token получил отдельный TTL в `users.editsecret_expires`, проверку срока при открытии/сбросе и cleanup просроченных токенов.
- Частично закрыто: новые passkey для скачиваемых `.torrent` сохраняются как HMAC-хеши в `user_passkeys`; announce/RSS сначала проверяют хешированную таблицу и только затем legacy `users.passkey`.
- Закрыто дополнительно: CSRF-токен обязателен для редактирования профиля, смены темы, комментариев, удаления ЛС, благодарностей, управления новостями и ключевых форм групп.
- Закрыто: upload/edit lifecycle получил DB transaction, запись `.torrent` во временный файл и atomic rename после успешного commit; добавлена CLI-проверка рассинхрона `bin/repair-torrents.php`.
- Закрыто: IP handling принимает IPv6/private `REMOTE_ADDR`, поддерживает trusted proxy headers через `KZ_TRUSTED_PROXIES`, а `users.ip/passkey_ip` в схеме уже расширены до `varchar(45)`.
- Схема обновлена в едином файле `database/database.sql`; отдельные миграционные файлы не используются.

Осталось: полная миграция/отзыв старых plain `users.passkey`, перевод всех GET-мутаций на POST без совместимого режима, EXPLAIN на живой БД, тестовый контур/CI.

## Краткий вывод

Движок рабочий, но это все еще процедурный legacy PHP/TBDev-код с частичными современными заплатками. Видны хорошие улучшения: `mysqli`-обертки, Redis-кеш, batch cleanup, индексы под каталог, мультитрекер, подготовка под PHP 8. Но критические зоны остаются:

- безопасность форм и действий: много state-changing GET без реальной CSRF-защиты;
- устаревшая авторизация: MD5-пароли, небезопасные cookie, логин может идти через GET;
- announce hot path делает слишком много синхронной работы и имеет гонки в `peers/snatched`;
- каталог и похожие раздачи используют неиндексируемые `LIKE '%...%'` и JSON-фильтры;
- нет тестов/CI/миграций, а runtime продолжает держать много схемной логики;
- часть кода может фатально падать на PHP 8 не из-за синтаксиса, а из-за legacy-семантики.

## Приоритеты

### P0. Исправить сразу

#### 1. CSRF и state-changing GET

Проблема: множество действий меняют данные через GET или принимают токен как необязательный. Referer-check в `include/bittorrent.php` не заменяет CSRF: он срабатывает только для POST и только если `HTTP_REFERER` есть.

Доказательства:

- `include/bittorrent.php:53-119` - проверка POST по referer, не общий CSRF-токен.
- `bookmarks.php:144-198` - добавление/удаление групп, пользователей, персон через GET; ссылки передают `hash4u`, но обработчик его не проверяет.
- `friends.php:152-202` - добавление/удаление друзей и блоков через GET; `hash4u` генерируется в `friends.php:62-65`, но не валидируется.
- `groupexinvite.php:21-101` - вступить/выйти/одобрить/кикнуть/сменить роль через GET без проверки `hash4u`.
- `comment.php:175-201` - модераторское удаление комментария через GET.
- `markread.php:34-45` - массовая вставка в `readtorrents` через GET.
- `update_multi.php:13-34` - сетевое обновление внешних трекеров через GET.
- `restoreclass.php:34-36` - изменение `override_class` через GET.
- `logout.php:29-35` - logout без проверки hash.
- `takemessage.php:15-17` - токен проверяется только если он прислан; отсутствие токена проходит.

Риск: любой внешний сайт может заставить авторизованного пользователя выполнить действие: удалить комментарий, добавить/убрать друзей, вступить/выйти из группы, дернуть тяжелое обновление мультитрекера, отметить весь каталог прочитанным.

Что сделать:

1. Ввести единый `csrf_token()` / `csrf_verify()` поверх `hash4u` или отдельного session token.
2. Все мутации перевести на POST.
3. Временно для совместимости: GET-мутации разрешать только при валидном `hash4u`, затем убрать.
4. Для `takemessage.php` требовать токен всегда.

#### 2. Авторизация, пароли, cookie

Проблема: парольная модель устарела и небезопасна.

Доказательства:

- `takelogin.php:31-32` - логин/пароль принимаются из GET или POST.
- `takelogin.php:44-57` - проверка пароля через `md5(secret . password . secret)`.
- `takesignup.php:199` и `takeprofedit.php:118` - новые/смененные пароли тоже MD5.
- `recover.php:95-101` - новый пароль генерируется через `mt_rand()` и кладется в MD5.
- `recover.php:106-123` - новый пароль отправляется письмом в открытом виде.
- `include/functions.php:1326-1341` - cookie ставятся без `HttpOnly`, `Secure`, `SameSite`; pass cookie - детерминированный MD5 от passhash, cookie salt и подсети.
- `include/config.php:63-65` - cookie salt хранится в репозитории.
- `include/functions.php:1187-1193` и `database/database.sql:1087` - passkey всего 10 base62-символов и хранится как plain token.

Риск: утечка URL/логов может раскрыть пароль; утечка БД дает быстрый offline bruteforce MD5; cookie легче украсть через XSS/смешанный контент; восстановление пароля раскрывает пароль через email.

Что сделать:

1. Запретить GET credentials: только POST.
2. Перейти на `password_hash(PASSWORD_ARGON2ID)` или минимум `PASSWORD_BCRYPT`.
3. Сделать lazy migration при успешном логине.
4. Recovery заменить на одноразовый reset-token с TTL; пароль пользователь задает сам.
5. Cookie ставить с `HttpOnly`, `Secure` при HTTPS, `SameSite=Lax/Strict`.
6. Увеличить passkey до 32+ байт hex/base64url, хранить хеш passkey или хотя бы иметь возможность ротации.

#### 3. Гонки и некорректность announce-данных

Проблема: `announce.php` - самая горячая точка, но таблицы и код допускают дубли/коллизии.

Доказательства:

- `announce.php:150` делает `INSERT ... SELECT ... WHERE NOT EXISTS` в `snatched`.
- `database/database.sql:782-800` у `snatched` есть только `KEY (torrent, userid)`, нет `UNIQUE`.
- `announce.php:89-95` ищет self peer по `torrent + peer_id + passkey`.
- `database/database.sql:531-559` уникальность `peers` задана как `UNIQUE (torrent, peer_id)`, без `userid/passkey`.
- `announce.php:151` вставляет peer; при совпавшем `peer_id` у разных пользователей будет duplicate key, хотя self lookup его не найдет.
- `announce.php:213-217` обновляет counters и `snatched` отдельными запросами без транзакции.

Риск: дубли в истории скачиваний, неверная статистика, случайные отказы announce у пользователей с одинаковым `peer_id`, дрейф `seeders/leechers`, неконсистентный ratio/history.

Что сделать:

1. Перед миграцией почистить дубли `snatched`.
2. Добавить `UNIQUE KEY snatched_user_torrent (torrent, userid)`.
3. Пересмотреть уникальность peers: `UNIQUE (torrent, userid, peer_id)` или `UNIQUE (torrent, passkey, peer_id)`.
4. `snatched` писать через `INSERT ... ON DUPLICATE KEY UPDATE`.
5. Критические изменения stats объединить в транзакцию или сделать идемпотентными.

#### 4. Реальный PHP 8 fatal в `log.php`

Синтаксис проходит, но runtime падает.

Доказательства:

- `include/php_errors.log` содержит `Undefined constant "tracker" in log.php:40`.
- В текущем коде `log.php:40-45` сравнивает `$type == tracker`, `$type == bans`, `$type == release`, `$type == exchange`, `$type == torrent`, `$type == error` без кавычек.

Что сделать: заменить на строки (`'tracker'`, `'bans'`, ...), безопасно читать `$_GET['type'] ?? ''`, ограничить whitelist.

#### 5. Debug/secrets в окружении

Проблема: режим разработки выглядит включенным в контейнере и bootstrap.

Доказательства:

- `Dockerfile:34-40` включает `display_errors=On`, `error_reporting=E_ALL`.
- `include/init.php:4-5` и `include/bittorrent.php:24-26` включают отображение ошибок.
- `include/secrets.php:9-18` хранит DB credentials, включая local root без пароля.
- `docker-compose.yml:22-28` публикует MySQL наружу на `3312`, phpMyAdmin на `8099`, Redis на `6379`.
- `include/php_errors.log` лежит в репозитории и содержит пути/ошибки.
- `.htaccess` в корне содержит только `ErrorDocument`; `include/.htaccess` использует старый синтаксис `Order allow,deny / deny from all`, который зависит от Apache access_compat.

Риск: утечки stack traces, SQL errors, путей и конфигов; случайный запуск dev-compose как public deployment открывает MySQL/phpMyAdmin/Redis.

Что сделать:

1. Разделить dev/prod ini. В prod: `display_errors=Off`, `log_errors=On`.
2. Секреты только из env/secret store; `include/secrets.php` оставить как template.
3. Убрать `include/php_errors.log` из репозитория, добавить `*.log` в `.gitignore`.
4. Закрыть внешние порты MySQL/Redis/phpMyAdmin или ограничить localhost.
5. Переписать deny правила на Apache 2.4: `Require all denied`.

### P1. Высокий приоритет

#### 6. Announce hot path слишком тяжелый

Доказательства:

- `announce.php:77-95` на каждый announce читает user, torrent, self peer.
- `announce.php:138-146` при новом peer синхронно делает TCP connectability check с timeout 1-3 секунды.
- `announce.php:150-217` выполняет несколько write-запросов.
- `announce.php:231-239` выдает peers через случайный offset и `LIMIT offset, rsize`; на больших swarm offset становится дорогим.
- `include/functions_announce.php:230-270` `check_port()` делает сетевую операцию прямо в announce-запросе.

Что оптимизировать:

1. Убрать обязательный connectability check из announce. Делать async worker, sampling или проверку только при первом download.php/ручном тесте.
2. Снизить количество writes: update user stats только если delta > 0 уже есть, но counters/torrent updates лучше агрегировать или делать строго идемпотентно.
3. Peer list выбирать по indexed id window, без большого offset.
4. Проверить индексы `peers`: добавить составные под выборку и cleanup, например `(torrent, seeder, last_action, id)`, `(userid, seeder, last_action)`.
5. Вынести announce в отдельный минимальный endpoint/service без полной legacy-обвязки.

#### 7. Каталог и top используют неиндексируемый поиск

Доказательства:

- `browse.php:178-190` ищет через `LIKE '%...%'` по `name`, `description`, `keywords`.
- `browse.php:193-210` фильтр формата ходит по `name/keywords/description/descr` и `JSON_VALID/JSON_EXTRACT(... ) REGEXP`.
- `browse.php:250-259` делает `COUNT(*)` по тем же условиям.
- `browse.php:325-340` затем делает rows query с тем же WHERE.
- `top.php:108`, `top.php:186-196`, `top.php:219-255` повторяют похожие `LIKE '%...%'` и count/rows.
- В `database/database.sql:842-905` нет FULLTEXT или generated columns для этих фильтров.

Риск: при росте `torrents` каталог станет главным CPU/IO bottleneck; кеш на 120 секунд помогает только повторным одинаковым запросам.

Что сделать:

1. Добавить FULLTEXT по `torrents(name, keywords, description)` или вынести поиск в Manticore/Meilisearch/Elasticsearch.
2. Нормализовать важные поля в отдельные колонки: `year`, `quality`, `video_codec`, `audio_codec`, `release_kind`, `country`, `genre`.
3. Для JSON в `torrent_details.data` добавить generated columns и индексы, а не `JSON_EXTRACT REGEXP` в WHERE.
4. Разделить count и rows: для сложного поиска можно показывать approximate/limited count.

#### 8. `details.php` делает дорогой поиск похожих

Доказательства:

- `details.php:380-392` строит SELECT с `ORDER BY (seeders + times_completed + comments)`.
- `details.php:407-415` добавляет `LIKE '%term%'` по `name/keywords/description/descr`.
- `details.php:421-441` объединяет 4 таких запроса через `UNION ALL`, кеш 300 секунд.

Риск: страница деталей - частая, а похожие раздачи могут сканировать большую часть каталога.

Что сделать:

1. Считать похожие offline/cron и хранить `torrent_related`.
2. Если оставить online: ограничить по category/year/release_kind indexed fields, убрать `descr LIKE`.
3. Кешировать дольше и инвалидировать точечно по конкретному torrent/category, не всей группой `details:*`.

#### 9. Главная и блоки: много агрегатов и широкая инвалидация

Доказательства:

- `index.php:91-235` и `index.php:237-370` собирают блоки большими JSON-агрегатами.
- `include/blocks.php:151-164` кеширует HTML блоков, ключ зависит от request URI/user class/date/filemtime.
- `include/cache.php:418-473` при изменениях `torrents/comments/users` инвалидирует `browse:*`, `details:*`, `userdetails:*`, `block:*`, `index:*`.

Риск: при активном сайте любые uploads/comments/users updates будут сдувать почти весь полезный кеш. На cache miss главная может быть тяжелой.

Что сделать:

1. Для главной сделать materialized/homepage cache, обновляемый cron/worker.
2. Разделить cache groups: `details:tid`, `browse:category`, `index:releases`, `block:stats`.
3. Добавить stampede protection: short lock в Redis на rebuild.
4. Announce counters либо не инвалидируют browse/details, либо инвалидируют только короткоживущие счетчики.

#### 10. Сессии пишутся в БД на каждый web request

Доказательства:

- `include/functions.php:553-630` `user_session()` делает `INSERT ... ON DUPLICATE KEY UPDATE` в `sessions` на каждый запрос.
- Cleanup старых sessions идет в `include/cleanup.php:495-496`.

Риск: `sessions` становится постоянным write hotspot, особенно для гостей и ajax/ресурсов, если bootstrap вызывается часто.

Что сделать:

1. Перенести online sessions в Redis с TTL.
2. Если оставить MySQL: throttling по sid, обновлять не чаще 30-60 секунд.
3. Не писать URL/useragent каждый раз, если не изменились.

#### 11. Upload/edit не транзакционные

Статус: закрыто для `takeupload.php` и `takeedit.php`: DB-часть обернута в транзакцию, `.torrent` сначала пишется во временный файл и переносится в рабочий путь после commit; добавлена безопасная CLI-проверка `bin/repair-torrents.php`.

Доказательства:

- `takeupload.php:203-253` вставляет `torrents`, trackers, details, parsed descr, checkcomm, files, затем пишет `.torrent` на диск.
- `takeedit.php:237-284` сначала пишет `.torrent`, удаляет/вставляет `files`, сохраняет trackers, затем обновляет `torrents`.
- `takeedit.php:274-277` может переписать announce list в файле отдельно от DB.

Риск: ошибка между шагами оставит БД без файла, файл без актуальной БД, частично записанные trackers/details/files.

Что сделать:

1. Обернуть DB-часть в transaction.
2. Файл писать во временный путь, затем atomic rename после успешного commit.
3. При ошибке делать rollback и удалять временные файлы.
4. Добавить repair-команду для поиска рассинхрона DB/filesystem.

#### 12. Удаление торрента оставляет хвосты и может падать на `unlink`

Доказательства:

- `include/functions.php:1357-1374` `deletetorrent()` удаляет часть связанных таблиц и без проверки вызывает `unlink($torrent_dir.'/'.$id.'.torrent')`.
- Не удаляются явно `torrent_details`, `thanks`, возможно часть новых модулей/связей.
- Cleanup TTL в `include/cleanup.php:477-489` тоже не удаляет `torrent_details` и `thanks`.

Что сделать:

1. Сделать единый `torrent_delete($id, options)` со списком всех связанных таблиц.
2. Добавить FK/cascade там, где возможно.
3. `unlink` только через `is_file`, ошибки логировать, но не валить request.
4. Добавить orphan cleanup для `torrent_details`, `thanks`, `torrent_trackers`, `torrents_descr`, `files`, `comments_parsed`.

### P2. Средний приоритет

#### 13. IP/IPv6/proxy handling

Статус: закрыто: `users.ip` и `users.passkey_ip` уже `varchar(45)`, `getip()` больше не превращает private Docker/reverse-proxy адреса в `0.0.0.0`, IPv6 проходит как валидный `REMOTE_ADDR`, proxy headers читаются только от trusted proxy.

Доказательства:

- `include/functions.php:740-772` `getip()` без доверия proxy headers возвращает `REMOTE_ADDR`, но `validip()` отбрасывает private/reserved; в Docker/reverse proxy легко получить `0.0.0.0`.
- `database/database.sql:1054` и `database/database.sql:1089` - `users.ip` и `users.passkey_ip` имеют `varchar(15)`, IPv6 не помещается.
- `database/database.sql:531-550` peers уже допускает `ip varchar(64)`.

Риск: неверные баны, cookie привязка к подсети, passkey_ip, анти-double-reg и сессии ломаются за reverse proxy/IPv6.

Что сделать:

1. `users.ip`, `users.passkey_ip` -> `varchar(45)`.
2. Добавить trusted proxy config: доверять `X-Forwarded-For` только от известных proxy IP.
3. Не превращать private IP в `0.0.0.0` внутри docker/dev; хранить реальный REMOTE_ADDR.

#### 14. Внешние сетевые запросы в пользовательском web-flow

Доказательства:

- `include/upload.php:801-835` синхронно ходит во внешние URL, следует редиректам.
- `include/upload.php:906-984` дергает IMDb/Kinopoisk при autofill рейтинга.
- `update_multi.php:29` вызывает `multitracker_update_torrent_trackers()` из web request.
- `include/multitracker.php:703-824` делает HTTP/UDP scrape внешних трекеров.
- `include/multitracker.php:875-920` ручное обновление идет с budget, но все равно внутри request.

Риск: зависания страниц, SSRF через редиректы/неожиданные DNS-ответы, внешние rate limits.

Что сделать:

1. Все внешние fetch/scrape вынести в очередь.
2. Для curl включить protocol restrictions, запрет редиректов на private/reserved IP, max redirects, DNS/IP allowlist по сервисам.
3. Кешировать negative results дольше и показывать пользователю “обновляется”.

#### 15. Runtime cleanup и runtime migrations

Доказательства:

- `include/functions.php:373-375` `autoclean` регистрируется на shutdown только для `index.php`.
- `include/cleanup.php:93-504` делает тяжелый batch cleanup: сверка torrent files, удаление peers, пересчет counters, inactive users, warnings/bans/classes, TTL delete, sessions.
- `browse.php` вызывает ensure schema функций на обычный request; `KZ_AUTO_MIGRATIONS` выключен в `include/config.php:68-69`, но смешение схемы и runtime остается.

Риск: случайный пользовательский запрос платит за maintenance; если главную не открывают, cleanup не запускается; изменения схемы трудно контролировать.

Что сделать:

1. Перенести cleanup в CLI cron/worker с lock.
2. Runtime ensure оставить только read-only sanity, все DDL - миграциями.
3. Добавить `bin/cleanup.php`, `bin/migrate.php`, `bin/rebuild-counters.php`.

#### 16. Нет тестов и CI

Доказательства:

- В репозитории нет `phpunit.xml`, тестовых suites или composer проекта на весь движок.
- Найденные `test*.php` - это web pages/modules, не automated tests.
- Lint проходит, но не ловит runtime fatal в `log.php`.

Что сделать:

1. Добавить минимальный CI: PHP lint, PHPStan/Psalm baseline, проверка SQL schema.
2. Unit tests для чистых функций: escaping, csrf, password migration, bencode, upload parsing.
3. Integration tests на Docker DB для: signup/login, upload/edit/delete torrent, announce lifecycle, comments, bookmarks/friends.

## Узкие места по страницам

### `announce.php`

Главный bottleneck под нагрузкой.

Тяжелые места:

- SQL на каждый announce: user/torrent/self peer.
- Синхронный `check_port()`.
- Несколько writes в `users`, `peers`, `snatched`, `torrents`.
- Peer list через random offset.

Оптимизация:

- async connectability;
- идемпотентные upserts;
- точные unique keys;
- Redis/rate-limit для repeated announces;
- отдельный lightweight bootstrap без сессий, тем, блоков.

### `browse.php` / `top.php`

Главный read bottleneck каталога.

Тяжелые места:

- `LIKE '%...%'`;
- JSON фильтры в WHERE;
- `COUNT(*)` по сложному WHERE;
- per-user read marker в rows query.

Оптимизация:

- search engine или FULLTEXT;
- generated columns;
- approximate counts;
- отдельный быстрый путь для дефолтной выдачи;
- cache key без лишней персонализации там, где read marker можно догрузить отдельным запросом.

### `details.php`

Тяжелые места:

- похожие раздачи через `UNION ALL` + `LIKE`;
- `t.*` + большие text/json поля на каждый details;
- JSON_ARRAYAGG trackers в SQL;
- GET hit update.

Оптимизация:

- precomputed related;
- разделить базовую карточку и тяжелые details;
- hit/views писать async/batched;
- comments и trackers догружать отдельными блоками с точечной инвалидацией.

### `index.php` / blocks

Тяжелые места:

- много агрегатов на cache miss;
- широкая инвалидация;
- block HTML cache зависит от URI/date/class.

Оптимизация:

- materialized homepage;
- отдельные TTL для stats/top/releases/news;
- Redis lock против stampede.

### Users/profile

Тяжелые места:

- `userdetails.php` использует derived count по всей `simpaty` для одного профиля.
- `users.php:269-295` при сортировках строит grouped derived tables по `torrents`, `comments`, `simpaty`.

Оптимизация:

- для одного профиля считать `COUNT(*) WHERE touserid = id`;
- держать counters в `users` или отдельной stats table;
- background rebuild counters.

## Что переписывать

### 1. Auth/session слой

Переписывать первым. Сейчас это смесь cookie-MD5, IP/subnet binding, sessions table, `hash4u`, legacy password. Нужен отдельный модуль:

- password_hash/password_verify;
- session cookie flags;
- CSRF;
- recovery tokens;
- login throttling;
- trusted proxy IP.

### 2. DB layer

Сейчас много ручной сборки SQL строк и legacy `mysql_*` wrappers.

Нужно:

- единый DB adapter на `mysqli` или PDO;
- prepared statements для новых/переписанных модулей;
- transaction helpers;
- query logging/profiling;
- gradual migration без большого взрыва.

### 3. Announce service

У announce другой профиль нагрузки, ему не нужна большая часть web runtime.

Нужно:

- отдельный минимальный bootstrap;
- точные schema constraints;
- upserts;
- async connectability;
- отдельные метрики latency/error rate.

### 4. Catalog/search

`LIKE '%...%'` не масштабируется. Каталог надо переводить на нормальную поисковую модель:

- normalized metadata;
- FULLTEXT/search engine;
- generated columns для JSON;
- precomputed facets.

### 5. Upload/edit/delete torrent lifecycle

Нужен единый сервис жизненного цикла раздачи:

- parse torrent;
- validate;
- transaction DB writes;
- atomic file write;
- related rows;
- rollback/repair;
- delete cascade.

## Быстрый план работ

### Неделя 1: закрыть P0

1. Починить `log.php` runtime fatal.
2. Выключить `display_errors` в prod bootstrap/Docker profile.
3. Добавить обязательный CSRF verify в `takemessage.php`.
4. Проверять `hash4u` во всех GET-мутациях как временный hotfix.
5. Запретить GET login/password.
6. Убрать `include/php_errors.log` из репозитория.

### Неделя 2: данные и announce

1. Очистить дубли `snatched`.
2. Добавить unique key `snatched(torrent, userid)`.
3. Пересмотреть unique key `peers`.
4. Переписать `snatched` insert на upsert.
5. Отключить/sampling для synchronous port check.
6. Добавить metrics/log timings для announce.

### Недели 3-4: каталог и кеш

1. Включить FULLTEXT/search engine.
2. Вынести year/quality/video/audio в индексируемые поля.
3. Переписать `details_related` на precomputed или indexed-only запросы.
4. Разделить cache groups и добавить stampede lock.
5. Сессии перенести в Redis или throttle MySQL writes.

### Месяц 2: архитектура

1. Auth rewrite + password migration.
2. DB adapter + transaction helpers.
3. Upload/edit/delete service.
4. CLI migrations/cleanup.
5. Тестовый контур на Docker DB.

## Конкретные schema changes

Перед применением нужно проверить реальные дубли.

```sql
-- 1. найти дубли snatched
SELECT torrent, userid, COUNT(*) AS c
FROM snatched
GROUP BY torrent, userid
HAVING c > 1;

-- 2. после очистки
ALTER TABLE snatched
  ADD UNIQUE KEY snatched_torrent_user (torrent, userid);

-- 3. IPv6
ALTER TABLE users
  MODIFY ip varchar(45) NOT NULL DEFAULT '',
  MODIFY passkey_ip varchar(45) NOT NULL DEFAULT '';

-- 4. возможный peers key, точный вариант выбрать после анализа клиентов
ALTER TABLE peers
  DROP KEY torrent_peer_id,
  ADD UNIQUE KEY torrent_user_peer (torrent, userid, peer_id),
  ADD KEY torrent_seeder_action_id (torrent, seeder, last_action, id),
  ADD KEY userid_seeder_action (userid, seeder, last_action);
```

Для поиска:

```sql
ALTER TABLE torrents
  ADD FULLTEXT KEY ft_torrent_search (name, keywords, description);
```

Для JSON-фильтров лучше не индексировать `mediumtext data` напрямую, а добавить generated/stored columns после утверждения формата `torrent_details.data`.

## Что уже выглядит хорошо

- `php -l` на PHP 8.5 с `short_open_tag=1` прошел без синтаксических ошибок.
- Есть Redis cache layer с fallback.
- Есть SQL debug table в `stdfoot()` при debug mode.
- У многих горячих таблиц уже есть базовые индексы: `torrents` для browse, `comments`, `messages`, `readtorrents`, `torrent_trackers`.
- `include/blocks.php` уже убрал старый `eval/create_function` подход для шаблонов.
- Cleanup стал batch-oriented, а не N+1 по каждому torrent.

## Финальный риск-рейтинг

Самый большой риск безопасности: CSRF + legacy auth/cookies.

Самый большой риск производительности: announce с синхронным port check и каталог с `LIKE '%...%'`.

Самый большой риск корректности данных: `snatched` без unique key и `peers` unique по `torrent,peer_id`.

Самый большой риск сопровождения: отсутствие тестов/миграций и ручная сборка SQL по всему коду.

Если чинить строго по эффекту, порядок такой:

1. CSRF/state-changing GET.
2. `log.php` fatal и prod debug/secrets.
3. Password/cookie/recovery hardening.
4. `snatched/peers` constraints и announce upsert.
5. Убрать connectability check из горячего announce.
6. Search/FULLTEXT/generated columns.
7. Transactions для upload/edit/delete.
8. Redis sessions/cache invalidation.
9. Runtime cleanup -> CLI worker.
10. Tests/CI.
