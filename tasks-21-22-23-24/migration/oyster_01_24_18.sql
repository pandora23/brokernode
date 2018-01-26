/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50710
 Source Host           : localhost
 Source Database       : oyster

 Target Server Type    : MySQL
 Target Server Version : 50710
 File Encoding         : utf-8

 Date: 01/24/2018 00:54:45 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `brokernode`
-- ----------------------------
DROP TABLE IF EXISTS `brokernode`;
CREATE TABLE `brokernode` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(255) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;

-- ----------------------------
--  Records of `brokernode`
-- ----------------------------
BEGIN;
INSERT INTO `brokernode` VALUES ('1', '177.83.187.88', '1'), ('2', '211.45.218.173', '1'), ('3', '247.50.252.199', '1'), ('4', '177.31.37.141', '1'), ('5', '20.116.209.143', '1'), ('6', '172.34.165.80', '1'), ('7', '164.247.37.148', '1'), ('8', '49.236.226.74', '1'), ('9', '28.177.251.7', '1'), ('10', '100.169.203.143', '1'), ('11', '163.38.157.228', '1'), ('12', '170.96.50.232', '1'), ('13', '138.187.187.133', '1'), ('14', '17.127.37.94', '1'), ('15', '249.85.240.204', '1'), ('16', '91.102.183.185', '1'), ('17', '87.24.198.181', '1'), ('18', '78.65.195.87', '1'), ('19', '26.65.44.183', '1'), ('20', '22.180.49.157', '1'), ('21', '138.181.70.59', '1'), ('22', '103.75.65.145', '1'), ('23', '46.208.103.167', '1'), ('24', '29.13.42.52', '1'), ('25', '107.109.248.136', '1'), ('26', '155.25.215.164', '1'), ('27', '71.8.221.71', '1'), ('28', '17.193.188.63', '1'), ('29', '127.101.198.172', '1'), ('30', '16.234.184.240', '1'), ('31', '132.192.158.176', '1'), ('32', '158.253.207.128', '1'), ('33', '243.10.84.75', '1'), ('34', '64.169.200.82', '1'), ('35', '66.210.155.232', '1'), ('36', '138.96.136.239', '1'), ('37', '251.154.238.177', '1'), ('38', '181.140.72.85', '1'), ('39', '241.139.225.34', '1'), ('40', '229.120.192.209', '1'), ('41', '35.14.131.51', '1'), ('42', '254.105.64.23', '1'), ('43', '20.124.161.140', '1'), ('44', '204.123.183.231', '1'), ('45', '26.142.97.0', '1'), ('46', '235.120.214.81', '1'), ('47', '102.134.6.7', '1'), ('48', '78.141.223.163', '1'), ('49', '59.179.112.82', '1'), ('50', '45.228.160.182', '1'), ('51', '151.250.95.234', '1'), ('52', '65.236.138.164', '1'), ('53', '227.51.190.42', '1'), ('54', '174.153.64.168', '1'), ('55', '130.174.47.109', '1'), ('56', '118.197.161.103', '1'), ('57', '248.61.21.2', '1'), ('58', '88.47.204.162', '1'), ('59', '194.218.132.87', '1'), ('60', '77.40.56.244', '1'), ('61', '127.90.98.40', '1'), ('62', '200.144.156.233', '1'), ('63', '99.249.162.87', '1'), ('64', '77.169.154.185', '1'), ('65', '224.220.114.25', '1'), ('66', '93.188.129.96', '1'), ('67', '199.84.113.106', '1'), ('68', '39.30.71.210', '1'), ('69', '14.219.10.247', '1'), ('70', '120.89.95.240', '1'), ('71', '254.64.14.105', '1'), ('72', '141.105.248.4', '1'), ('73', '221.125.37.216', '1'), ('74', '221.162.167.150', '1'), ('75', '227.230.226.64', '1'), ('76', '3.193.94.250', '1'), ('77', '137.207.17.177', '1'), ('78', '219.34.3.252', '1'), ('79', '131.47.118.7', '1'), ('80', '63.88.95.58', '1'), ('81', '17.184.1.183', '1'), ('82', '250.154.112.177', '1'), ('83', '39.64.186.253', '1'), ('84', '6.126.86.183', '1'), ('85', '235.196.39.78', '1'), ('86', '117.71.46.247', '1'), ('87', '48.39.86.139', '1'), ('88', '92.125.219.16', '1'), ('89', '45.100.174.157', '1'), ('90', '247.81.182.156', '1'), ('91', '44.79.197.3', '1'), ('92', '42.38.28.247', '1'), ('93', '70.230.167.116', '1'), ('94', '93.68.97.175', '1'), ('95', '140.32.185.103', '1'), ('96', '82.173.128.107', '1'), ('97', '8.205.61.141', '1'), ('98', '100.165.232.89', '1'), ('99', '198.210.166.144', '1');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;