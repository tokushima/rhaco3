CREATE TABLE `queue_dao` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL,
  `data` varchar(1000),
  `lock` float,
  `fin` timestamp null default null,
  `priority` int(11) NOT NULL DEFAULT '3',
  `create_date` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB



