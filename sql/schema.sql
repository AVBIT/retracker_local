/*
Source Database       : retracker
Target Server Type    : MYSQL
Target Server Version : 50713
File Encoding         : 65001

Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
Created on 02.03.2016. Last modified on 23.11.2016
*/


SET FOREIGN_KEY_CHECKS=0;


-- ----------------------------
-- Table structure for `tracker` (for  announce_easy.php) - Lite version without GUI and SCRAPE-action (use only db table 'tracker')
-- ----------------------------
DROP TABLE IF EXISTS `tracker`;
CREATE TABLE `tracker` (
  `info_hash` char(20) NOT NULL,
  `ip` char(8) NOT NULL,
  `port` smallint(5) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`info_hash`,`ip`,`port`) USING BTREE
) ENGINE=MEMORY DEFAULT CHARSET=cp1251;




-- ----------------------------
-- Table structure for `announce` (for  announce_ng.php && GUI && SCRAPE-action)
-- ----------------------------
DROP TABLE IF EXISTS `announce`;
CREATE TABLE `announce` (
  `peer_hash` varchar(32) NOT NULL DEFAULT '',
  `ip` varchar(8) NOT NULL DEFAULT '',
  `ipv6` char(32) NOT NULL DEFAULT '',
  `port` smallint(5) unsigned NOT NULL DEFAULT '0',
  `seeder` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `info_hash_hex` char(40) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `comment` varchar(255) NOT NULL DEFAULT '',
  `uploaded` int(11) unsigned NOT NULL DEFAULT '0',
  `downloaded` int(11) unsigned NOT NULL DEFAULT '0',
  `left` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`peer_hash`),
  KEY `ip` (`ip`),
  KEY `info_hash_hex` (`info_hash_hex`) USING BTREE
) ENGINE=MEMORY DEFAULT CHARSET=cp1251;

-- ----------------------------
-- Table structure for `announce_resolver` (for  announce_ng.php && GUI && SCRAPE-action)
-- ----------------------------
DROP TABLE IF EXISTS `announce_resolver`;
CREATE TABLE `announce_resolver` (
  `info_hash_hex` char(40) NOT NULL DEFAULT '',
  `seeders` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `leechers` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `comment` varchar(255) NOT NULL DEFAULT '',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`info_hash_hex`)
) ENGINE=MEMORY DEFAULT CHARSET=cp1251;

-- ----------------------------
-- Table structure for `history` (for  announce_ng.php && GUI && SCRAPE-action)
-- ----------------------------
DROP TABLE IF EXISTS `history`;
CREATE TABLE `history` (
  `info_hash_hex` char(40) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `comment` varchar(255) NOT NULL DEFAULT '',
  `reg_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`info_hash_hex`),
  FULLTEXT KEY `index_search` (`name`,`comment`,`info_hash_hex`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
