/*
Source Database       : retracker
Target Server Type    : MYSQL
Target Server Version : 50713
File Encoding         : 65001

Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
Created on 02.03.2016. Last modified on 26.10.2016
*/


SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `tracker`
-- ----------------------------
DROP TABLE IF EXISTS `tracker`;
CREATE TABLE `tracker` (
  `info_hash` char(20) NOT NULL,
  `ip` char(8) NOT NULL,
  `port` smallint(5) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`info_hash`,`ip`,`port`) USING BTREE
) ENGINE=MEMORY DEFAULT CHARSET=cp1251;
