/*
Navicat MySQL Data Transfer

Source Server         : 172.26.25.207_wuyan_test
Source Server Version : 50559
Source Host           : 172.26.25.207:3306
Source Database       : iveryone_test

Target Server Type    : MYSQL
Target Server Version : 50559
File Encoding         : 65001

Date: 2018-05-17 11:34:52
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for adsense
-- ----------------------------
DROP TABLE IF EXISTS `adsense`;
CREATE TABLE `adsense` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(45) NOT NULL DEFAULT '' COMMENT '用户id',
  `adwords` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '广告id',
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `advertisers` varchar(45) NOT NULL DEFAULT '' COMMENT '广告商',
  PRIMARY KEY (`id`),
  KEY `ut` (`uid`,`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=2279 DEFAULT CHARSET=utf8;
SET FOREIGN_KEY_CHECKS=1;
