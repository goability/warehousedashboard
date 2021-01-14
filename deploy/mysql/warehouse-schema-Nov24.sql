-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 24, 2020 at 02:43 PM
-- Server version: 5.7.31
-- PHP Version: 7.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `warehouse`
--
CREATE DATABASE IF NOT EXISTS `warehouse` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `warehouse`;

-- --------------------------------------------------------

--
-- Table structure for table `adminusers`
--

DROP TABLE IF EXISTS `adminusers`;
CREATE TABLE IF NOT EXISTS `adminusers` (
  `userid` bigint(20) NOT NULL,
  UNIQUE KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `associationrequest`
--

DROP TABLE IF EXISTS `associationrequest`;
CREATE TABLE IF NOT EXISTS `associationrequest` (
  `requestinguserid` bigint(20) NOT NULL,
  `primarytype` varchar(20) NOT NULL,
  `foreigntype` varchar(20) NOT NULL,
  `primaryrecordid` bigint(11) NOT NULL,
  `foreignrecordid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `authorization`
--

DROP TABLE IF EXISTS `authorization`;
CREATE TABLE IF NOT EXISTS `authorization` (
  `userid` int(11) NOT NULL DEFAULT '0',
  `authcode` varchar(60) DEFAULT NULL,
  `accesstoken` varchar(100) DEFAULT NULL,
  `expires_unix_time` bigint(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `captcha`
--

DROP TABLE IF EXISTS `captcha`;
CREATE TABLE IF NOT EXISTS `captcha` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `question` varchar(100) NOT NULL,
  `answerscsv` varchar(300) NOT NULL,
  `imagepath` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='simple question-answer captcha security service';

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

DROP TABLE IF EXISTS `client`;
CREATE TABLE IF NOT EXISTS `client` (
  `userid` bigint(20) NOT NULL,
  `providerid` bigint(20) NOT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `import-palletcontents`
--

DROP TABLE IF EXISTS `import-palletcontents`;
CREATE TABLE IF NOT EXISTS `import-palletcontents` (
  `palletID` bigint(20) NOT NULL,
  `uom` varchar(100) DEFAULT NULL,
  `lotnumber` varchar(100) DEFAULT NULL,
  `storageitemid` bigint(20) NOT NULL,
  `ownerid` bigint(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `import-transactions`
--

DROP TABLE IF EXISTS `import-transactions`;
CREATE TABLE IF NOT EXISTS `import-transactions` (
  `palletid` bigint(20) NOT NULL,
  `qtyin` int(11) DEFAULT '0',
  `qtyout` int(11) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `movementitemtype`
--

DROP TABLE IF EXISTS `movementitemtype`;
CREATE TABLE IF NOT EXISTS `movementitemtype` (
  `ID` tinyint(4) NOT NULL AUTO_INCREMENT,
  `Name` text COMMENT 'Item, Pallet, Container',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `provider`
--

DROP TABLE IF EXISTS `provider`;
CREATE TABLE IF NOT EXISTS `provider` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(10) NOT NULL,
  `zip` int(16) NOT NULL,
  `ownerid` bigint(20) NOT NULL,
  `website` varchar(100) DEFAULT NULL,
  `emailaddress` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phonealt` varchar(20) DEFAULT NULL,
  `notes` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Name` (`name`),
  KEY `ownerid` (`ownerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `providerowners`
--

DROP TABLE IF EXISTS `providerowners`;
CREATE TABLE IF NOT EXISTS `providerowners` (
  `providerid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `receiver`
--

DROP TABLE IF EXISTS `receiver`;
CREATE TABLE IF NOT EXISTS `receiver` (
  `clientid` bigint(20) NOT NULL,
  `receiverid` bigint(20) NOT NULL,
  KEY `clientreceivers` (`clientid`,`receiverid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `shipment`
--

DROP TABLE IF EXISTS `shipment`;
CREATE TABLE IF NOT EXISTS `shipment` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `itemid` bigint(20) NOT NULL,
  `qty` mediumint(9) NOT NULL,
  `confirmed_pulled_qty` mediumint(9) NOT NULL DEFAULT '0',
  `userid_requestor` bigint(20) NOT NULL,
  `userid_approver` bigint(20) DEFAULT NULL,
  `userid_puller` bigint(20) DEFAULT NULL,
  `userid_receiver` bigint(20) DEFAULT NULL,
  `lotnumber` varchar(60) NOT NULL,
  `tag` varchar(60) NOT NULL,
  `notes` text,
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_approved` datetime DEFAULT NULL,
  `date_shipped` datetime DEFAULT NULL,
  `date_needed` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid_requestor` (`userid_requestor`),
  KEY `itemid` (`itemid`),
  KEY `userid_receiver` (`userid_receiver`),
  KEY `userid_puller` (`userid_puller`),
  KEY `userid_approver` (`userid_approver`),
  KEY `date_created` (`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `shipmentapprovals`
--

DROP TABLE IF EXISTS `shipmentapprovals`;
CREATE TABLE IF NOT EXISTS `shipmentapprovals` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `shipmentid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `shipmentfulfilled`
--

DROP TABLE IF EXISTS `shipmentfulfilled`;
CREATE TABLE IF NOT EXISTS `shipmentfulfilled` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `shipmentid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `shipmentrequests`
--

DROP TABLE IF EXISTS `shipmentrequests`;
CREATE TABLE IF NOT EXISTS `shipmentrequests` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `shipmentid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storage`
--

DROP TABLE IF EXISTS `storage`;
CREATE TABLE IF NOT EXISTS `storage` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `itemid` bigint(20) NOT NULL,
  `qty` smallint(6) NOT NULL,
  `userid_requestor` bigint(20) NOT NULL,
  `userid_approver` bigint(20) DEFAULT NULL,
  `userid_stocker` bigint(20) DEFAULT NULL,
  `lotnumber` varchar(60) DEFAULT NULL,
  `tag` varchar(60) DEFAULT NULL,
  `notes` text,
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_approved` datetime DEFAULT NULL,
  `date_stored` datetime DEFAULT NULL,
  `date_needed` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid_requestor` (`userid_requestor`),
  KEY `itemid` (`itemid`) USING BTREE,
  KEY `userid_stocker` (`userid_stocker`),
  KEY `date_created` (`date_created`),
  KEY `userid_approver` (`userid_approver`),
  KEY `date_approved` (`date_approved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storageapprovals`
--

DROP TABLE IF EXISTS `storageapprovals`;
CREATE TABLE IF NOT EXISTS `storageapprovals` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `storageid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagebin`
--

DROP TABLE IF EXISTS `storagebin`;
CREATE TABLE IF NOT EXISTS `storagebin` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `providerid` bigint(20) NOT NULL,
  `facilityid` bigint(20) DEFAULT NULL,
  `description` text,
  `sizexinches` varchar(6) DEFAULT NULL,
  `sizeyinches` varchar(6) DEFAULT NULL,
  `sizezinches` varchar(6) DEFAULT NULL,
  `weightpounds` varchar(6) DEFAULT NULL,
  `full` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `providerid` (`providerid`),
  KEY `facilityid` (`facilityid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagebininventory`
--

DROP TABLE IF EXISTS `storagebininventory`;
CREATE TABLE IF NOT EXISTS `storagebininventory` (
  `binid` bigint(20) NOT NULL,
  `palletid` bigint(20) NOT NULL,
  KEY `palletandbin` (`palletid`,`binid`) USING BTREE,
  KEY `binid` (`binid`),
  KEY `palletid` (`palletid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagecontainer`
--

DROP TABLE IF EXISTS `storagecontainer`;
CREATE TABLE IF NOT EXISTS `storagecontainer` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `providerid` bigint(20) NOT NULL,
  `description` text,
  `sizexinches` int(11) DEFAULT NULL,
  `sizeyinches` int(11) DEFAULT NULL,
  `sizezinches` int(11) DEFAULT NULL,
  `weightpounds` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagecontainerinventory`
--

DROP TABLE IF EXISTS `storagecontainerinventory`;
CREATE TABLE IF NOT EXISTS `storagecontainerinventory` (
  `containerid` bigint(20) NOT NULL,
  `itemid` bigint(20) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT '0',
  `lastQty` int(11) NOT NULL DEFAULT '0',
  `lastUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `itemid` (`itemid`),
  KEY `containerid` (`containerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacility`
--

DROP TABLE IF EXISTS `storagefacility`;
CREATE TABLE IF NOT EXISTS `storagefacility` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ownerid` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `shortcode` varchar(4) NOT NULL,
  `address` varchar(100) DEFAULT NULL,
  `city` varchar(60) DEFAULT NULL,
  `state` varchar(3) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `notes` mediumtext,
  `website` varchar(100) DEFAULT NULL,
  `emailaddress` varchar(100) DEFAULT NULL,
  `phone` varchar(16) DEFAULT NULL,
  `lat` float(10,6) DEFAULT NULL,
  `lng` float(10,6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ownerid` (`ownerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacilityinventory`
--

DROP TABLE IF EXISTS `storagefacilityinventory`;
CREATE TABLE IF NOT EXISTS `storagefacilityinventory` (
  `storageItemtypeid` tinyint(4) DEFAULT NULL,
  `storagelocationid` bigint(20) NOT NULL,
  `storageItemid` bigint(20) NOT NULL,
  `storagecontainerid` bigint(20) NOT NULL,
  `storagepalletid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacilityowners`
--

DROP TABLE IF EXISTS `storagefacilityowners`;
CREATE TABLE IF NOT EXISTS `storagefacilityowners` (
  `facilityid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacilityproviders`
--

DROP TABLE IF EXISTS `storagefacilityproviders`;
CREATE TABLE IF NOT EXISTS `storagefacilityproviders` (
  `facilityid` bigint(20) NOT NULL,
  `providerid` bigint(20) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacilityworkers`
--

DROP TABLE IF EXISTS `storagefacilityworkers`;
CREATE TABLE IF NOT EXISTS `storagefacilityworkers` (
  `userid` bigint(20) DEFAULT NULL,
  `facilityid` bigint(20) DEFAULT NULL,
  `providerid` bigint(20) DEFAULT NULL,
  `lastactiontimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `notes` mediumtext,
  UNIQUE KEY `ProviderWorker` (`userid`,`providerid`),
  UNIQUE KEY `FacilityWorker` (`userid`,`facilityid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagefulfilled`
--

DROP TABLE IF EXISTS `storagefulfilled`;
CREATE TABLE IF NOT EXISTS `storagefulfilled` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `storageid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storageitem`
--

DROP TABLE IF EXISTS `storageitem`;
CREATE TABLE IF NOT EXISTS `storageitem` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ownerid` bigint(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `sizexinches` varchar(6) DEFAULT NULL,
  `sizeyinches` varchar(6) DEFAULT NULL,
  `sizezinches` int(6) DEFAULT NULL,
  `weightpounds` float DEFAULT NULL,
  `uom` varchar(10) DEFAULT NULL,
  `imagename_main` varchar(100) DEFAULT NULL,
  `imagename_small` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ownerid` (`ownerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagelocation`
--

DROP TABLE IF EXISTS `storagelocation`;
CREATE TABLE IF NOT EXISTS `storagelocation` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `facilityid` bigint(20) DEFAULT NULL,
  `name` varchar(60) DEFAULT NULL,
  `row` varchar(10) DEFAULT NULL,
  `col` varchar(10) DEFAULT NULL,
  `shelf` varchar(10) DEFAULT NULL,
  `xshelf` varchar(4) DEFAULT NULL,
  `yshelf` varchar(4) DEFAULT NULL,
  `zshelf` varchar(4) DEFAULT NULL,
  `facilitycoords` varchar(100) DEFAULT NULL,
  `tags` tinytext,
  `lat` float(10,6) DEFAULT NULL,
  `lng` float(10,6) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagelocationinventory`
--

DROP TABLE IF EXISTS `storagelocationinventory`;
CREATE TABLE IF NOT EXISTS `storagelocationinventory` (
  `locationid` bigint(20) NOT NULL,
  `binid` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagelotnumber`
--

DROP TABLE IF EXISTS `storagelotnumber`;
CREATE TABLE IF NOT EXISTS `storagelotnumber` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `lotnumber` varchar(60) DEFAULT NULL,
  `tag` varchar(60) DEFAULT NULL,
  `itemid` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagepallet`
--

DROP TABLE IF EXISTS `storagepallet`;
CREATE TABLE IF NOT EXISTS `storagepallet` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `tag` varchar(20) DEFAULT NULL,
  `providerid` bigint(20) NOT NULL,
  `facilityid` bigint(20) DEFAULT NULL,
  `Description` text,
  `sizexinches` varchar(6) DEFAULT NULL,
  `sizeyinches` varchar(6) DEFAULT NULL,
  `sizezinches` varchar(6) DEFAULT NULL,
  `full` tinyint(1) NOT NULL DEFAULT '0',
  `empty` tinyint(4) NOT NULL DEFAULT '1',
  `usable` tinyint(4) NOT NULL DEFAULT '1',
  `repeatedbatch` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `providerid` (`providerid`) USING BTREE,
  KEY `name` (`name`),
  KEY `usable` (`usable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagepalletinventory`
--

DROP TABLE IF EXISTS `storagepalletinventory`;
CREATE TABLE IF NOT EXISTS `storagepalletinventory` (
  `palletid` bigint(20) NOT NULL,
  `storageid` bigint(20) DEFAULT NULL,
  `itemid` bigint(20) DEFAULT NULL,
  `item_qty` smallint(6) NOT NULL DEFAULT '0',
  `containerid` bigint(20) DEFAULT NULL,
  `lotnumber` varchar(60) DEFAULT NULL,
  `tag` varchar(60) DEFAULT NULL,
  `confirmed` tinyint(4) NOT NULL DEFAULT '0',
  `shipment_request_id` bigint(20) DEFAULT NULL,
  KEY `itemid` (`itemid`),
  KEY `shipment_request_id` (`shipment_request_id`),
  KEY `palletid` (`palletid`),
  KEY `storageid` (`storageid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagerequests`
--

DROP TABLE IF EXISTS `storagerequests`;
CREATE TABLE IF NOT EXISTS `storagerequests` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `storageid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `userid` bigint(20) DEFAULT NULL,
  `clientid` bigint(20) DEFAULT NULL,
  `receiverid` bigint(20) DEFAULT NULL,
  `itemid` bigint(20) DEFAULT NULL,
  `providerid` bigint(20) DEFAULT NULL,
  `binid` bigint(20) DEFAULT NULL,
  `palletid` bigint(20) DEFAULT NULL,
  `notes` text,
  `time_stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `clientid` (`clientid`),
  KEY `receiverid` (`receiverid`),
  KEY `itemid` (`itemid`),
  KEY `providerid` (`providerid`),
  KEY `binid` (`binid`),
  KEY `palletid` (`palletid`),
  KEY `time_stamp` (`time_stamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(60) NOT NULL,
  `middlename` varchar(60) DEFAULT NULL,
  `lastname` varchar(60) NOT NULL,
  `companyname` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `state` varchar(4) DEFAULT NULL,
  `zip` varchar(12) DEFAULT NULL,
  `phonemobile` varchar(20) DEFAULT NULL,
  `phonehome` varchar(20) DEFAULT NULL,
  `phoneother` varchar(20) DEFAULT NULL,
  `emailaddress` varchar(100) NOT NULL,
  `website` varchar(120) DEFAULT NULL,
  `facebookurl` varchar(120) DEFAULT NULL,
  `linkedinurl` varchar(120) DEFAULT NULL,
  `profilename` varchar(30) DEFAULT NULL,
  `displaycode` text,
  `upasswd` varchar(255) NOT NULL,
  `profileimagepath` varchar(60) DEFAULT NULL,
  `notes` text,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emailaddress` (`emailaddress`),
  UNIQUE KEY `profilename` (`profilename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `workitem`
--

DROP TABLE IF EXISTS `workitem`;
CREATE TABLE IF NOT EXISTS `workitem` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `shipmentid` bigint(20) DEFAULT NULL,
  `storageid` bigint(20) DEFAULT NULL,
  `userid` bigint(20) NOT NULL,
  `date_started` datetime DEFAULT NULL,
  `date_completed` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipmentid` (`shipmentid`),
  KEY `storageid` (`storageid`),
  KEY `date_started` (`date_started`),
  KEY `date_completed` (`date_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `storagepalletinventory`
--
ALTER TABLE `storagepalletinventory` ADD FULLTEXT KEY `lotnumber` (`lotnumber`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions` ADD FULLTEXT KEY `type` (`type`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
