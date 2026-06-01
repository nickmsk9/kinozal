SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `torrent_details` (
  `tid` int(10) unsigned NOT NULL,
  `release_kind` varchar(20) NOT NULL DEFAULT 'video',
  `poster_url` text NOT NULL,
  `rgroup` int(10) unsigned NOT NULL DEFAULT '0',
  `rgroup_button` varchar(255) NOT NULL DEFAULT '',
  `torrent_file_updated_at` datetime NULL DEFAULT NULL,
  `form_mode` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `section_modes` varchar(20) NOT NULL DEFAULT '0,0,0,0',
  `data` mediumtext NOT NULL,
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`tid`),
  KEY `release_kind` (`release_kind`),
  KEY `rgroup` (`rgroup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `categories` MODIFY `name` varchar(80) NOT NULL DEFAULT '';

CREATE TEMPORARY TABLE `_kz_category_map` AS
SELECT
  `id` AS `old_id`,
  CAST(SUBSTRING_INDEX(`image`, '.', 1) AS UNSIGNED) AS `new_id`
FROM `categories`
WHERE `image` REGEXP '^[0-9]+\\.gif$';

UPDATE `torrents` AS `t`
INNER JOIN `_kz_category_map` AS `m` ON `m`.`old_id` = `t`.`category`
SET `t`.`category` = `m`.`new_id`;

DELETE FROM `categories`;

INSERT INTO `categories` (`id`, `sort`, `name`, `image`) VALUES
  (45,10,'Сериал - Русский','45.gif'),
  (46,20,'Сериал - Буржуйский','46.gif'),
  (8,30,'Кино - Комедия','8.gif'),
  (6,40,'Кино - Боевик / Военный','6.gif'),
  (15,50,'Кино - Триллер / Детектив','15.gif'),
  (17,60,'Кино - Драма','17.gif'),
  (35,70,'Кино - Мелодрама','35.gif'),
  (39,80,'Кино - Индийское','39.gif'),
  (13,90,'Кино - Фантастика','13.gif'),
  (14,100,'Кино - Фэнтези','14.gif'),
  (24,110,'Кино - Ужас / Мистика','24.gif'),
  (11,120,'Кино - Приключения','11.gif'),
  (10,130,'Кино - Наше Кино','10.gif'),
  (9,140,'Кино - Исторический','9.gif'),
  (47,150,'Кино - Азиатский','47.gif'),
  (18,160,'Кино - Документальный','18.gif'),
  (37,170,'Кино - Спорт','37.gif'),
  (12,180,'Кино - Детский / Семейный','12.gif'),
  (7,190,'Кино - Классика','7.gif'),
  (48,200,'Кино - Концерт','48.gif'),
  (49,210,'Кино - Передачи / ТВ-шоу','49.gif'),
  (50,220,'Кино - ТВ-шоу Мир','50.gif'),
  (38,230,'Кино - Театр, Опера, Балет','38.gif'),
  (16,240,'Кино - Эротика','16.gif'),
  (21,250,'Мульт - Буржуйский','21.gif'),
  (22,260,'Мульт - Русский','22.gif'),
  (20,270,'Мульт - Аниме','20.gif'),
  (1,280,'Другое - Видеоклипы','1.gif'),
  (3,290,'Музыка - Буржуйская','3.gif'),
  (4,300,'Музыка - Русская','4.gif'),
  (5,310,'Музыка - Сборники','5.gif'),
  (42,320,'Музыка - Классическая','42.gif'),
  (2,330,'Другое - АудиоКниги','2.gif'),
  (23,340,'Другое - Игры','23.gif'),
  (32,350,'Другое - Программы','32.gif'),
  (40,360,'Другое - Дизайн / Графика','40.gif'),
  (41,370,'Другое - Библиотека','41.gif');
