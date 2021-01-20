CREATE TABLE IF NOT EXISTS `PREFIX_info` (
  `id_info` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_shop` INT(11) UNSIGNED          DEFAULT NULL,
  PRIMARY KEY (`id_info`)
)
  ENGINE = InnoDb
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_info_lang` (
  `id_info` INT(11) UNSIGNED NOT NULL,
  `id_lang` INT(11) UNSIGNED NOT NULL,
  `text`    TEXT             NOT NULL,
  PRIMARY KEY (`id_info`, `id_lang`)
)
  ENGINE = InnoDb
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_unicode_ci;
