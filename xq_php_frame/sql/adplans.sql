/*
Navicat MySQL Data Transfer

Source Server         : 172.26.25.207_wuyan_test
Source Server Version : 50559
Source Host           : 172.26.25.207:3306
Source Database       : iveryone_test

Target Server Type    : MYSQL
Target Server Version : 50559
File Encoding         : 65001

Date: 2018-05-17 11:40:24
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for adplans
-- ----------------------------
DROP TABLE IF EXISTS `adplans`;
CREATE TABLE `adplans` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '方案名称',
  `columns` varchar(60000) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT '' COMMENT '方案需要的字段',
  `status` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SET FOREIGN_KEY_CHECKS=1;
