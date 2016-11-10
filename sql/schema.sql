/*
Source Database       : retracker
Target Server Type    : MYSQL
Target Server Version : 50713
File Encoding         : 65001

Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
Created on 02.03.2016. Last modified on 10.11.2016
*/


SET FOREIGN_KEY_CHECKS=0;

/*
-- ----------------------------
-- Table structure for `tracker` (for  announce_old.php)
-- ----------------------------
DROP TABLE IF EXISTS `tracker`;
CREATE TABLE `tracker` (
  `info_hash` char(20) NOT NULL,
  `ip` char(8) NOT NULL,
  `port` smallint(5) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`info_hash`,`ip`,`port`) USING BTREE
) ENGINE=MEMORY DEFAULT CHARSET=cp1251;
*/

/*
-- ----------------------------
-- Table structure for `announce` (for  announce_old2.php)
-- ----------------------------
DROP TABLE IF EXISTS `announce`;
CREATE TABLE `announce` (
  `torrent_id` bigint(20) NOT NULL,
  `peer_hash` varchar(32) NOT NULL DEFAULT '',
  `ip` varchar(8) NOT NULL DEFAULT '',
  `ipv6` char(32) NOT NULL,
  `port` smallint(5) unsigned NOT NULL DEFAULT '0',
  `seeder` tinyint(1) NOT NULL DEFAULT '0',
  `info_hash_hex` char(40) NOT NULL DEFAULT '',
  `uploaded` int(11) NOT NULL DEFAULT '0',
  `downloaded` int(11) NOT NULL DEFAULT '0',
  `left` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`peer_hash`),
  KEY `torrent_id` (`torrent_id`),
  KEY `ip` (`ip`)
) ENGINE=MEMORY DEFAULT CHARSET=cp1251;

-- ----------------------------
-- Table structure for `torrent`
-- ----------------------------
DROP TABLE IF EXISTS `torrent`;
CREATE TABLE `torrent` (
  `torrent_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `info_hash_hex` char(40) NOT NULL DEFAULT '',
  `seeders` mediumint(8) NOT NULL DEFAULT '0',
  `leechers` mediumint(8) NOT NULL DEFAULT '0',
  `reg_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `size` bigint(20) NOT NULL DEFAULT '0',
  `comment` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`torrent_id`),
  UNIQUE KEY `info_hash_hex` (`info_hash_hex`) USING BTREE,
  KEY `leechers` (`leechers`) USING BTREE,
  KEY `seeders` (`seeders`) USING BTREE,
  KEY `reg_time` (`reg_time`) USING BTREE,
  KEY `name` (`name`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

*/

-- ----------------------------
-- Table structure for `announce`
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
-- Table structure for `bittorrent`
-- ----------------------------
DROP TABLE IF EXISTS `bittorrent`;
CREATE TABLE `bittorrent` (
  `info_hash_hex` char(40) NOT NULL DEFAULT '',
  `seeders` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `leechers` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `comment` varchar(255) NOT NULL DEFAULT '',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`info_hash_hex`),
  KEY `name` (`name`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
