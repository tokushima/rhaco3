<?php
/**
 * Create table sample
 */
$sql = <<< _SQL_
CREATE TABLE `queue_dao` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL,
  `data` varchar(1000),
  `lock` float,
  `fin` TIMESTAMP NULL DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT '3',
  `create_date` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
_SQL_;

print('=================================================='.PHP_EOL);
print($sql.PHP_EOL);
print('=================================================='.PHP_EOL);
