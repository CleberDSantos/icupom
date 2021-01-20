DROP TABLE IF EXISTS `PREFIX_themeconfigurator`;

CREATE TABLE IF NOT EXISTS `PREFIX_themeconfigurator` (
  `id_item`    INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `id_shop`    INT(11) UNSIGNED    NOT NULL,
  `id_lang`    INT(11) UNSIGNED    NOT NULL,
  `item_order` INT(11) UNSIGNED    NOT NULL,
  `title`      VARCHAR(100),
  `title_use`  TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `hook`       VARCHAR(100),
  `url`        TEXT,
  `target`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `image`      VARCHAR(100),
  `image_w`    VARCHAR(10),
  `image_h`    VARCHAR(10),
  `html`       TEXT,
  `active`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_item`)
)
  ENGINE = DB_ENGINE
  DEFAULT CHARSET = utf8mb4
  COLLATE utf8mb4_unicode_ci;
