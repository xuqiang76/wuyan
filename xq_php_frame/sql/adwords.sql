/*
Navicat MySQL Data Transfer

Source Server         : 172.26.25.207_wuyan_test
Source Server Version : 50559
Source Host           : 172.26.25.207:3306
Source Database       : iveryone_test

Target Server Type    : MYSQL
Target Server Version : 50559
File Encoding         : 65001

Date: 2018-05-23 09:26:10
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for adwords
-- ----------------------------
DROP TABLE IF EXISTS `adwords`;
CREATE TABLE `adwords` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `advertisers` varchar(45) NOT NULL DEFAULT '0' COMMENT '广告商',
  `quota` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '广告剩余数量',
  `plan` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '广告方案  关注广告 1 2 3 ；动态广告 10001 10002 10003',
  `start_timestamp` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_timestamp` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态 1正常 2完成 3取消',
  `remark` varchar(60000) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT '' COMMENT '备注内容',
  `price` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '广告单价',
  `number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '广告总数',
  `privilege` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '投递给 0所有人 1认证的用户 ',
  `type` varchar(16) NOT NULL DEFAULT '' COMMENT '广告类型 feed / follow 等等',
  `strategy` varchar(1024) NOT NULL DEFAULT '' COMMENT '广告策略结构体',
  PRIMARY KEY (`id`),
  KEY `index2` (`advertisers`)
) ENGINE=InnoDB AUTO_INCREMENT=774 DEFAULT CHARSET=utf8;
SET FOREIGN_KEY_CHECKS=1;
