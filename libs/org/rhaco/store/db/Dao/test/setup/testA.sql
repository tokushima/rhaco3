-- phpMyAdmin SQL Dump
-- version 3.2.5
-- http://www.phpmyadmin.net
--
-- ホスト: localhost
-- 生成時間: 2010 年 12 月 15 日 01:27
-- サーバのバージョン: 5.1.44
-- PHP のバージョン: 5.3.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- データベース: `testA`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `calc`
--

CREATE TABLE `calc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `price` int(11) NOT NULL,
  `type` varchar(10) NOT NULL,
  `name` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `cross_parent`
--

CREATE TABLE `cross_parent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `date_time`
--

CREATE TABLE `date_time` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ts` timestamp NULL DEFAULT NULL,
  `date` date DEFAULT NULL,
  `idate` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE `unique_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code1` varchar(255) NULL DEFAULT NULL,
  `code2` varchar(255) DEFAULT NULL,
  `code3` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `double_primary`
--

CREATE TABLE `double_primary` (
  `id1` int(11) NOT NULL,
  `id2` int(11) NOT NULL,
  `value` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- テーブルの構造 `find`
--

CREATE TABLE `find` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(11) DEFAULT NULL,
  `value1` varchar(10) DEFAULT NULL,
  `value2` varchar(10) DEFAULT NULL,
  `updated` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `init_has_child`
--

CREATE TABLE `init_has_child` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `value` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `init_has_child_two`
--

CREATE TABLE `init_has_child_two` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id1` int(11) NOT NULL,
  `parent_id2` int(11) NOT NULL,
  `value` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `init_has_parent`
--

CREATE TABLE `init_has_parent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `join_a`
--

CREATE TABLE `join_a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `join_b`
--

CREATE TABLE `join_b` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `join_c`
--

CREATE TABLE `join_c` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `a_id` int(11) DEFAULT NULL,
  `b_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `limit_verify`
--

CREATE TABLE `limit_verify` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value1` varchar(10) DEFAULT NULL,
  `value2` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `many_child`
--

CREATE TABLE `many_child` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `value` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `many_parent`
--

CREATE TABLE `many_parent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `ref_find`
--

CREATE TABLE `ref_find` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `ref_ref_find`
--

CREATE TABLE `ref_ref_find` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `replication`
--

CREATE TABLE `replication` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- テーブルの構造 `sub_find`
--

CREATE TABLE `sub_find` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(10) DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `unique_triple_verify`
--

CREATE TABLE `unique_triple_verify` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `u1` int(11) DEFAULT NULL,
  `u2` int(11) DEFAULT NULL,
  `u3` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `unique_verify`
--

CREATE TABLE `unique_verify` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `u1` int(11) DEFAULT NULL,
  `u2` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- --------------------------------------------------------

--
-- テーブルの構造 `update_model`
--

CREATE TABLE `update_model` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
