-- phpMyAdmin SQL Dump
-- version 3.2.5
-- http://www.phpmyadmin.net
--
-- ホスト: localhost
-- 生成時間: 2010 年 12 月 14 日 17:46
-- サーバのバージョン: 5.1.44
-- PHP のバージョン: 5.3.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- データベース: `testB`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `cross_child`
--

CREATE TABLE `cross_child` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


