/*
Navicat MySQL Data Transfer

Source Server         : 172.26.25.207_wuyan_test
Source Server Version : 50559
Source Host           : 172.26.25.207:3306
Source Database       : iveryone_test

Target Server Type    : MYSQL
Target Server Version : 50559
File Encoding         : 65001

Date: 2018-05-17 11:40:33
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for aditems
-- ----------------------------
DROP TABLE IF EXISTS `aditems`;
CREATE TABLE `aditems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `advertisers` varchar(45) NOT NULL DEFAULT '' COMMENT '广告商',
  `fee` bigint(20) unsigned NOT NULL,
  `start_timestamp` int(11) unsigned NOT NULL DEFAULT '0',
  `end_timestamp` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '1',
  `placement` varchar(45) NOT NULL DEFAULT '',
  `placement_timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `plan` int(10) unsigned NOT NULL DEFAULT '0',
  `adwords` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=551 DEFAULT CHARSET=utf8;
SET FOREIGN_KEY_CHECKS=1;
