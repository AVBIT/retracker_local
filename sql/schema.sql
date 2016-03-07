/*
Source Database       : retracker
Target Server Type    : MYSQL
Target Server Version : 50544
File Encoding         : 65001

Date: 2016-03-02 10:29:10
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `tracker`
-- ----------------------------
DROP TABLE IF EXISTS `tracker`;
CREATE TABLE `tracker` (
  `info_hash` char(20) CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
  `ip` char(8) CHARACTER SET cp1251 COLLATE cp1251_bin NOT NULL,
  `port` smallint(5) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`info_hash`,`ip`,`port`) USING BTREE
) ENGINE=MEMORY DEFAULT CHARSET=cp1251;

-- ----------------------------
-- Records of tracker
-- ----------------------------
