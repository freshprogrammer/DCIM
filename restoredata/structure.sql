-- phpMyAdmin SQL Dump
-- version 2.11.11.3
-- http://www.phpmyadmin.net
--
-- Host: 45.40.164.51
-- Generation Time: Oct 13, 2018 at 02:15 AM
-- Server version: 5.5.51
-- PHP Version: 5.1.6
--
-- DCIM DBv3
--

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `freshdcim`
--

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_badge`
--

DROP TABLE IF EXISTS `dcimlog_badge`;
CREATE TABLE IF NOT EXISTS `dcimlog_badge` (
  `badgelogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` char(1) NOT NULL DEFAULT 'I',
  `badgeid` int(8) NOT NULL,
  `hno` int(8) NOT NULL,
  `name` varchar(128) NOT NULL,
  `badgeno` varchar(8) NOT NULL,
  `status` varchar(1) NOT NULL,
  `issue` date NOT NULL,
  `hand` date NOT NULL,
  `returned` date NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`badgelogid`),
  KEY `hno` (`hno`),
  KEY `badgeno` (`badgeno`),
  KEY `qauser` (`qauser`),
  KEY `badgeid` (`badgeid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_customer`
--

DROP TABLE IF EXISTS `dcimlog_customer`;
CREATE TABLE IF NOT EXISTS `dcimlog_customer` (
  `customerlogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` char(1) NOT NULL DEFAULT 'I',
  `hno` int(8) NOT NULL,
  `cno` int(8) NOT NULL,
  `name` varchar(128) NOT NULL,
  `note` text NOT NULL,
  `status` varchar(1) NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`customerlogid`),
  KEY `qauser` (`qauser`),
  KEY `hno` (`hno`),
  KEY `cno` (`cno`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_device`
--

DROP TABLE IF EXISTS `dcimlog_device`;
CREATE TABLE IF NOT EXISTS `dcimlog_device` (
  `devicelogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` char(1) NOT NULL DEFAULT 'I',
  `deviceid` int(8) NOT NULL,
  `hno` int(8) NOT NULL,
  `locationid` int(8) NOT NULL,
  `name` varchar(64) NOT NULL,
  `altname` varchar(64) NOT NULL,
  `member` int(2) NOT NULL DEFAULT '0',
  `note` text NOT NULL,
  `unit` int(3) NOT NULL,
  `type` varchar(1) NOT NULL,
  `size` varchar(8) NOT NULL,
  `status` varchar(1) NOT NULL,
  `asset` varchar(8) NOT NULL,
  `serial` varchar(20) NOT NULL,
  `model` varchar(30) NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`devicelogid`),
  KEY `hno` (`hno`,`locationid`,`type`),
  KEY `qauser` (`qauser`),
  KEY `deviceid` (`deviceid`),
  KEY `locationid` (`locationid`),
  KEY `name` (`name`),
  KEY `altname` (`altname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_deviceport`
--

DROP TABLE IF EXISTS `dcimlog_deviceport`;
CREATE TABLE IF NOT EXISTS `dcimlog_deviceport` (
  `deviceportlogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` char(1) NOT NULL DEFAULT 'I',
  `deviceportid` int(8) NOT NULL,
  `deviceid` int(8) NOT NULL,
  `pic` int(2) NOT NULL DEFAULT '0',
  `port` int(2) NOT NULL,
  `type` varchar(1) NOT NULL DEFAULT 'E',
  `mac` varchar(20) NOT NULL,
  `speed` varchar(8) NOT NULL,
  `note` text NOT NULL,
  `status` varchar(1) NOT NULL DEFAULT 'D',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`deviceportlogid`),
  KEY `deviceid` (`deviceid`),
  KEY `qauser` (`qauser`),
  KEY `deviceportid` (`deviceportid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_location`
--

DROP TABLE IF EXISTS `dcimlog_location`;
CREATE TABLE IF NOT EXISTS `dcimlog_location` (
  `locationlogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` char(1) NOT NULL DEFAULT 'I',
  `locationid` int(8) NOT NULL,
  `roomid` int(8) NOT NULL,
  `name` varchar(50) NOT NULL,
  `altname` varchar(50) NOT NULL DEFAULT '',
  `type` char(1) NOT NULL DEFAULT '',
  `units` int(3) NOT NULL DEFAULT '1',
  `xpos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `ypos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `width` decimal(6,2) NOT NULL DEFAULT '0.00',
  `depth` decimal(6,2) NOT NULL DEFAULT '0.00',
  `orientation` varchar(1) NOT NULL DEFAULT 'N',
  `keyno` varchar(20) NOT NULL,
  `allocation` varchar(1) NOT NULL DEFAULT 'E',
  `order` varchar(1) NOT NULL DEFAULT 'S',
  `visible` char(1) NOT NULL DEFAULT '',
  `note` text NOT NULL,
  `edituser` int(8) NOT NULL DEFAULT '0',
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`locationlogid`),
  KEY `site_colo_name` (`name`),
  KEY `qauser` (`qauser`),
  KEY `locationid` (`locationid`),
  KEY `roomid` (`roomid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_portconnection`
--

DROP TABLE IF EXISTS `dcimlog_portconnection`;
CREATE TABLE IF NOT EXISTS `dcimlog_portconnection` (
  `portconnectionlogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` char(1) NOT NULL DEFAULT 'I',
  `portconnectionid` int(8) NOT NULL,
  `childportid` int(8) NOT NULL DEFAULT '0',
  `parentportid` int(8) NOT NULL DEFAULT '0',
  `patches` varchar(50) NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`portconnectionlogid`),
  KEY `port1id` (`childportid`),
  KEY `port2id` (`parentportid`),
  KEY `qauser` (`qauser`),
  KEY `portconnectionid` (`portconnectionid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_portvlan`
--

DROP TABLE IF EXISTS `dcimlog_portvlan`;
CREATE TABLE IF NOT EXISTS `dcimlog_portvlan` (
  `portvlanlogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` char(1) NOT NULL DEFAULT 'I',
  `portvlanid` int(8) NOT NULL,
  `deviceportid` int(8) NOT NULL DEFAULT '0',
  `vlan` int(8) NOT NULL DEFAULT '0',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`portvlanlogid`),
  KEY `deviceportid` (`deviceportid`),
  KEY `vlan` (`vlan`),
  KEY `qauser` (`qauser`),
  KEY `portvlanid` (`portvlanid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_powercircuit`
--

DROP TABLE IF EXISTS `dcimlog_powercircuit`;
CREATE TABLE IF NOT EXISTS `dcimlog_powercircuit` (
  `powercircuitlogid` smallint(8) NOT NULL AUTO_INCREMENT,
  `logtype` char(1) NOT NULL DEFAULT 'I',
  `powercircuitid` smallint(8) NOT NULL,
  `powerpanelid` int(8) NOT NULL,
  `circuit` tinyint(3) NOT NULL,
  `volts` smallint(3) NOT NULL DEFAULT '120',
  `amps` tinyint(3) NOT NULL DEFAULT '20',
  `status` varchar(1) NOT NULL,
  `load` decimal(4,2) NOT NULL DEFAULT '0.00',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`powercircuitlogid`),
  KEY `qauser` (`qauser`),
  KEY `powerid` (`powercircuitid`),
  KEY `powerpanelid` (`powerpanelid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_powercircuitloc`
--

DROP TABLE IF EXISTS `dcimlog_powercircuitloc`;
CREATE TABLE IF NOT EXISTS `dcimlog_powercircuitloc` (
  `powercircuitloclogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` char(1) NOT NULL DEFAULT 'I',
  `powercircuitlocid` int(8) NOT NULL,
  `powercircuitid` int(8) NOT NULL DEFAULT '0',
  `locationid` int(8) NOT NULL DEFAULT '0',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`powercircuitloclogid`),
  KEY `locationid` (`locationid`),
  KEY `powerid` (`powercircuitid`),
  KEY `qauser` (`qauser`),
  KEY `powerlocid` (`powercircuitlocid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_powerpanel`
--

DROP TABLE IF EXISTS `dcimlog_powerpanel`;
CREATE TABLE IF NOT EXISTS `dcimlog_powerpanel` (
  `powerpanellogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` varchar(1) NOT NULL DEFAULT 'I',
  `powerpanelid` int(8) NOT NULL,
  `powerupsid` int(8) NOT NULL,
  `roomid` int(8) NOT NULL,
  `name` varchar(50) NOT NULL,
  `amps` int(4) NOT NULL,
  `circuits` int(3) NOT NULL DEFAULT '0',
  `xpos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `ypos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `width` decimal(6,2) NOT NULL DEFAULT '0.00',
  `depth` decimal(6,2) NOT NULL DEFAULT '0.00',
  `orientation` varchar(1) NOT NULL DEFAULT 'N',
  `note` text NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`powerpanellogid`),
  KEY `powerpanelid` (`powerpanelid`),
  KEY `powerupsid` (`powerupsid`),
  KEY `roomid` (`roomid`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_powerups`
--

DROP TABLE IF EXISTS `dcimlog_powerups`;
CREATE TABLE IF NOT EXISTS `dcimlog_powerups` (
  `powerupslogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` varchar(1) NOT NULL DEFAULT 'I',
  `powerupsid` int(8) NOT NULL,
  `siteid` int(8) NOT NULL,
  `name` varchar(32) NOT NULL,
  `volts` int(5) NOT NULL,
  `amps` int(5) NOT NULL,
  `note` text NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`powerupslogid`),
  KEY `powerupsid` (`powerupsid`),
  KEY `siteid` (`siteid`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_room`
--

DROP TABLE IF EXISTS `dcimlog_room`;
CREATE TABLE IF NOT EXISTS `dcimlog_room` (
  `roomlogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` varchar(1) NOT NULL DEFAULT 'I',
  `roomid` int(8) NOT NULL,
  `siteid` int(8) NOT NULL,
  `name` varchar(50) NOT NULL,
  `fullname` varchar(128) NOT NULL,
  `custaccess` varchar(1) NOT NULL DEFAULT 'T',
  `xpos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `ypos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `width` decimal(6,2) NOT NULL DEFAULT '0.00',
  `depth` decimal(6,2) NOT NULL DEFAULT '0.00',
  `orientation` varchar(1) NOT NULL DEFAULT 'N',
  `layer` tinyint(1) NOT NULL DEFAULT '0',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`roomlogid`),
  KEY `roomid` (`roomid`),
  KEY `siteid` (`siteid`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_site`
--

DROP TABLE IF EXISTS `dcimlog_site`;
CREATE TABLE IF NOT EXISTS `dcimlog_site` (
  `sitelogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` char(1) NOT NULL DEFAULT 'I',
  `siteid` int(8) NOT NULL,
  `name` varchar(64) NOT NULL,
  `fullname` varchar(128) NOT NULL,
  `width` decimal(6,2) NOT NULL DEFAULT '0.00',
  `depth` decimal(6,2) NOT NULL DEFAULT '0.00',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`sitelogid`),
  KEY `qauser` (`qauser`),
  KEY `siteid` (`siteid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcimlog_vlan`
--

DROP TABLE IF EXISTS `dcimlog_vlan`;
CREATE TABLE IF NOT EXISTS `dcimlog_vlan` (
  `vlanlogid` int(8) NOT NULL AUTO_INCREMENT,
  `logtype` char(1) NOT NULL DEFAULT 'I',
  `vlanid` int(8) NOT NULL,
  `vlan` int(8) NOT NULL,
  `subnet` varchar(18) NOT NULL,
  `mask` varchar(15) NOT NULL,
  `first` varchar(15) NOT NULL,
  `last` varchar(15) NOT NULL,
  `gateway` varchar(15) NOT NULL,
  `note` text NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`vlanlogid`),
  KEY `vlan` (`vlan`),
  KEY `qauser` (`qauser`),
  KEY `vlanid` (`vlanid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_badge`
--

DROP TABLE IF EXISTS `dcim_badge`;
CREATE TABLE IF NOT EXISTS `dcim_badge` (
  `badgeid` int(8) NOT NULL AUTO_INCREMENT,
  `hno` int(8) NOT NULL,
  `name` varchar(128) NOT NULL,
  `badgeno` varchar(8) NOT NULL,
  `status` varchar(1) NOT NULL,
  `issue` date NOT NULL,
  `hand` date NOT NULL,
  `returned` date NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`badgeid`),
  KEY `hno` (`hno`),
  KEY `badgeno` (`badgeno`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_config`
--

DROP TABLE IF EXISTS `dcim_config`;
CREATE TABLE IF NOT EXISTS `dcim_config` (
  `configid` int(8) NOT NULL AUTO_INCREMENT,
  `appname` varchar(200) NOT NULL DEFAULT 'DCIM Demo',
  `pagetitle` varchar(200) NOT NULL DEFAULT 'DCIM',
  `versionnote` varchar(200) NOT NULL DEFAULT 'note',
  `cookiedurration` int(3) NOT NULL DEFAULT '36',
  `cookiedurrationipad` int(3) NOT NULL DEFAULT '2',
  `badgesEnabled` varchar(1) NOT NULL DEFAULT 'T',
  `subnetsEnabled` varchar(1) NOT NULL DEFAULT 'T',
  `qaenabled` varchar(1) NOT NULL DEFAULT 'T',
  `demoenvironment` varchar(1) NOT NULL DEFAULT 'F',
  `dbversion` varchar(10) NOT NULL DEFAULT '1',
  PRIMARY KEY (`configid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_customer`
--

DROP TABLE IF EXISTS `dcim_customer`;
CREATE TABLE IF NOT EXISTS `dcim_customer` (
  `hno` int(8) NOT NULL AUTO_INCREMENT,
  `cno` int(8) NOT NULL,
  `name` varchar(128) NOT NULL,
  `note` text NOT NULL,
  `status` varchar(1) NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`hno`),
  KEY `qauser` (`qauser`),
  KEY `cno` (`cno`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_device`
--

DROP TABLE IF EXISTS `dcim_device`;
CREATE TABLE IF NOT EXISTS `dcim_device` (
  `deviceid` int(8) NOT NULL AUTO_INCREMENT,
  `hno` int(8) NOT NULL,
  `locationid` int(8) NOT NULL,
  `name` varchar(64) NOT NULL,
  `altname` varchar(64) NOT NULL,
  `member` int(2) NOT NULL DEFAULT '0',
  `note` text NOT NULL,
  `unit` int(3) NOT NULL,
  `type` varchar(1) NOT NULL,
  `size` varchar(8) NOT NULL,
  `status` varchar(1) NOT NULL,
  `asset` varchar(8) NOT NULL,
  `serial` varchar(20) NOT NULL,
  `model` varchar(30) NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`deviceid`),
  KEY `hno` (`hno`,`locationid`,`type`),
  KEY `qauser` (`qauser`),
  KEY `locationid` (`locationid`),
  KEY `name` (`name`),
  KEY `altname` (`altname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_deviceport`
--

DROP TABLE IF EXISTS `dcim_deviceport`;
CREATE TABLE IF NOT EXISTS `dcim_deviceport` (
  `deviceportid` int(8) NOT NULL AUTO_INCREMENT,
  `deviceid` int(8) NOT NULL,
  `pic` int(2) NOT NULL DEFAULT '0',
  `port` int(2) NOT NULL,
  `type` varchar(1) NOT NULL DEFAULT 'E',
  `mac` varchar(20) NOT NULL,
  `speed` varchar(8) NOT NULL,
  `note` text NOT NULL,
  `status` varchar(1) NOT NULL DEFAULT 'D',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`deviceportid`),
  KEY `deviceid` (`deviceid`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_location`
--

DROP TABLE IF EXISTS `dcim_location`;
CREATE TABLE IF NOT EXISTS `dcim_location` (
  `locationid` int(8) NOT NULL AUTO_INCREMENT,
  `roomid` int(8) NOT NULL,
  `name` varchar(50) NOT NULL,
  `altname` varchar(50) NOT NULL DEFAULT '',
  `type` char(1) NOT NULL DEFAULT '',
  `units` int(3) NOT NULL DEFAULT '1',
  `xpos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `ypos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `width` decimal(6,2) NOT NULL DEFAULT '0.00',
  `depth` decimal(6,2) NOT NULL DEFAULT '0.00',
  `orientation` varchar(1) NOT NULL DEFAULT 'N',
  `keyno` varchar(20) NOT NULL,
  `allocation` varchar(1) NOT NULL DEFAULT 'E',
  `order` varchar(1) NOT NULL DEFAULT 'S',
  `visible` char(1) NOT NULL DEFAULT '',
  `note` text NOT NULL,
  `edituser` int(8) NOT NULL DEFAULT '0',
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`locationid`),
  KEY `site_colo_name` (`name`),
  KEY `qauser` (`qauser`),
  KEY `roomid` (`roomid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_portconnection`
--

DROP TABLE IF EXISTS `dcim_portconnection`;
CREATE TABLE IF NOT EXISTS `dcim_portconnection` (
  `portconnectionid` int(8) NOT NULL AUTO_INCREMENT,
  `childportid` int(8) NOT NULL DEFAULT '0',
  `parentportid` int(8) NOT NULL DEFAULT '0',
  `patches` varchar(50) NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`portconnectionid`),
  KEY `port1id` (`childportid`),
  KEY `port2id` (`parentportid`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_portvlan`
--

DROP TABLE IF EXISTS `dcim_portvlan`;
CREATE TABLE IF NOT EXISTS `dcim_portvlan` (
  `portvlanid` int(8) NOT NULL AUTO_INCREMENT,
  `deviceportid` int(8) NOT NULL DEFAULT '0',
  `vlan` int(8) NOT NULL DEFAULT '0',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`portvlanid`),
  KEY `deviceportid` (`deviceportid`),
  KEY `vlan` (`vlan`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_powercircuit`
--

DROP TABLE IF EXISTS `dcim_powercircuit`;
CREATE TABLE IF NOT EXISTS `dcim_powercircuit` (
  `powercircuitid` smallint(8) NOT NULL AUTO_INCREMENT,
  `powerpanelid` int(8) NOT NULL,
  `circuit` tinyint(3) NOT NULL,
  `volts` smallint(3) NOT NULL DEFAULT '120',
  `amps` tinyint(3) NOT NULL DEFAULT '20',
  `status` varchar(1) NOT NULL,
  `load` decimal(4,2) NOT NULL DEFAULT '0.00',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`powercircuitid`),
  KEY `qauser` (`qauser`),
  KEY `powerpanelid` (`powerpanelid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_powercircuitloc`
--

DROP TABLE IF EXISTS `dcim_powercircuitloc`;
CREATE TABLE IF NOT EXISTS `dcim_powercircuitloc` (
  `powercircuitlocid` int(8) NOT NULL AUTO_INCREMENT,
  `powercircuitid` int(8) NOT NULL DEFAULT '0',
  `locationid` int(8) NOT NULL DEFAULT '0',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`powercircuitlocid`),
  KEY `locationid` (`locationid`),
  KEY `powerid` (`powercircuitid`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_powerpanel`
--

DROP TABLE IF EXISTS `dcim_powerpanel`;
CREATE TABLE IF NOT EXISTS `dcim_powerpanel` (
  `powerpanelid` int(8) NOT NULL AUTO_INCREMENT,
  `powerupsid` int(8) NOT NULL,
  `roomid` int(8) NOT NULL,
  `name` varchar(50) NOT NULL,
  `amps` int(4) NOT NULL,
  `circuits` int(3) NOT NULL DEFAULT '0',
  `xpos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `ypos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `width` decimal(6,2) NOT NULL DEFAULT '0.00',
  `depth` decimal(6,2) NOT NULL DEFAULT '0.00',
  `orientation` varchar(1) NOT NULL DEFAULT 'N',
  `note` text NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`powerpanelid`),
  KEY `powerupsid` (`powerupsid`),
  KEY `roomid` (`roomid`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_powerups`
--

DROP TABLE IF EXISTS `dcim_powerups`;
CREATE TABLE IF NOT EXISTS `dcim_powerups` (
  `powerupsid` int(8) NOT NULL AUTO_INCREMENT,
  `siteid` int(8) NOT NULL,
  `name` varchar(32) NOT NULL,
  `volts` int(5) NOT NULL,
  `amps` int(5) NOT NULL,
  `note` text NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`powerupsid`),
  KEY `siteid` (`siteid`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_room`
--

DROP TABLE IF EXISTS `dcim_room`;
CREATE TABLE IF NOT EXISTS `dcim_room` (
  `roomid` int(8) NOT NULL AUTO_INCREMENT,
  `siteid` int(8) NOT NULL,
  `name` varchar(50) NOT NULL,
  `fullname` varchar(128) NOT NULL,
  `custaccess` varchar(1) NOT NULL DEFAULT 'T',
  `xpos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `ypos` decimal(6,2) NOT NULL DEFAULT '0.00',
  `width` decimal(6,2) NOT NULL DEFAULT '0.00',
  `depth` decimal(6,2) NOT NULL DEFAULT '0.00',
  `orientation` varchar(1) NOT NULL DEFAULT 'N',
  `layer` tinyint(1) NOT NULL DEFAULT '0',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`roomid`),
  KEY `siteid` (`siteid`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_site`
--

DROP TABLE IF EXISTS `dcim_site`;
CREATE TABLE IF NOT EXISTS `dcim_site` (
  `siteid` int(8) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `fullname` varchar(128) NOT NULL,
  `width` decimal(6,2) NOT NULL DEFAULT '0.00',
  `depth` decimal(6,2) NOT NULL DEFAULT '0.00',
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`siteid`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_user`
--

DROP TABLE IF EXISTS `dcim_user`;
CREATE TABLE IF NOT EXISTS `dcim_user` (
  `userid` int(8) NOT NULL AUTO_INCREMENT,
  `siteid` int(8) NOT NULL,
  `username` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `pass` varchar(60) NOT NULL,
  `email` varchar(128) NOT NULL,
  `initials` varchar(4) NOT NULL,
  `note` text NOT NULL,
  `permission` varchar(1) NOT NULL,
  `passwordreset` int(8) NOT NULL,
  `lastactivity` datetime NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`userid`),
  KEY `username` (`username`),
  KEY `siteid` (`siteid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `dcim_vlan`
--

DROP TABLE IF EXISTS `dcim_vlan`;
CREATE TABLE IF NOT EXISTS `dcim_vlan` (
  `vlanid` int(8) NOT NULL AUTO_INCREMENT,
  `vlan` int(8) NOT NULL,
  `subnet` varchar(18) NOT NULL,
  `mask` varchar(15) NOT NULL,
  `first` varchar(15) NOT NULL,
  `last` varchar(15) NOT NULL,
  `gateway` varchar(15) NOT NULL,
  `note` text NOT NULL,
  `edituser` int(8) NOT NULL,
  `editdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `qauser` int(8) NOT NULL DEFAULT '-1',
  `qadate` datetime NOT NULL,
  PRIMARY KEY (`vlanid`),
  KEY `vlan` (`vlan`),
  KEY `qauser` (`qauser`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
