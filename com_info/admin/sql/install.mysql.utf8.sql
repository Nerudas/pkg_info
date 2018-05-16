CREATE TABLE IF NOT EXISTS `#__info` (
  `id`           INT(11)          NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(255)     NOT NULL DEFAULT '',
  `alias`        VARCHAR(400)     NOT NULL DEFAULT '',
  `introtext`    LONGTEXT         NOT NULL DEFAULT '',
  `fulltext`     LONGTEXT         NOT NULL DEFAULT '',
  `introimage`   TEXT             NOT NULL DEFAULT '',
  `header`       TEXT             NOT NULL DEFAULT '',
  `images`       LONGTEXT         NOT NULL DEFAULT '',
  `related`      TEXT             NOT NULL DEFAULT '',
  `state`        TINYINT(3)       NOT NULL DEFAULT '0',
  `created`      DATETIME         NOT NULL DEFAULT '0000-00-00 00:00:00',
	`created_by`   INT(11)          NOT NULL DEFAULT '0',
	`modified`     DATETIME         NOT NULL DEFAULT '0000-00-00 00:00:00',
	`publish_up`   DATETIME         NOT NULL DEFAULT '0000-00-00 00:00:00',
	`publish_down` DATETIME         NOT NULL DEFAULT '0000-00-00 00:00:00',
  `in_work`      TINYINT(3)       NOT NULL DEFAULT '0',
  `attribs`      TEXT             NOT NULL DEFAULT '',
  `metakey`      MEDIUMTEXT       NOT NULL DEFAULT '',
  `metadesc`     MEDIUMTEXT       NOT NULL DEFAULT '',
  `access`       INT(10)          NOT NULL DEFAULT '0',
  `hits`         INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `region`       CHAR(7)          NOT NULL DEFAULT '*',
  `metadata`     MEDIUMTEXT       NOT NULL DEFAULT '',
  `tags_search`  MEDIUMTEXT       NOT NULL DEFAULT '',
  `tags_map`     MEDIUMTEXT       NOT NULL DEFAULT '',
  UNIQUE KEY `id` (`id`)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
AUTO_INCREMENT = 0;