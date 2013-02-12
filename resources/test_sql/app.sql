CREATE TABLE `queue_dao` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `data` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `lock` float DEFAULT NULL,
  `fin` timestamp NULL DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT '3',
  `create_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `session_dao` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `data` text,
  `expires` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `smtp_blackhole_dao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from` text,
  `to` text,
  `cc` text,
  `bcc` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text,
  `manuscript` text,
  `create_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `test_model` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` double DEFAULT NULL,
  `integer` int(11) DEFAULT NULL,
  `string` varchar(255) DEFAULT NULL,
  `text` text,
  `timestamp` timestamp NULL DEFAULT NULL,
  `boolean` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

