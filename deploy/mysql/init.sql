-- phpMyAdmin SQL Dump
-- version 4.9.6
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 13, 2020 at 08:17 PM
-- Server version: 10.2.10-MariaDB
-- PHP Version: 7.2.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `warehouse`
--
CREATE DATABASE IF NOT EXISTS `warehouse` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `warehouse`;

-- --------------------------------------------------------

--
-- Table structure for table `adminusers`
--

CREATE TABLE `adminusers` (
  `userid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `associationrequest`
--

CREATE TABLE `associationrequest` (
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

CREATE TABLE `authorization` (
  `userid` int(11) NOT NULL DEFAULT 0,
  `authcode` varchar(60) DEFAULT NULL,
  `accesstoken` varchar(100) DEFAULT NULL,
  `expires_unix_time` bigint(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `captcha`
--

CREATE TABLE `captcha` (
  `id` smallint(6) NOT NULL,
  `question` varchar(100) NOT NULL,
  `answerscsv` varchar(300) NOT NULL,
  `imagepath` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='simple question-answer captcha security service';

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `userid` bigint(20) NOT NULL,
  `providerid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `movementitemtype`
--

CREATE TABLE `movementitemtype` (
  `ID` tinyint(4) NOT NULL,
  `Name` text DEFAULT NULL COMMENT 'Item, Pallet, Container'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `provider`
--

CREATE TABLE `provider` (
  `id` bigint(20) NOT NULL,
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
  `notes` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `providerowners`
--

CREATE TABLE `providerowners` (
  `providerid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `receiver`
--

CREATE TABLE `receiver` (
  `clientid` bigint(20) NOT NULL,
  `receiverid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `shipment`
--

CREATE TABLE `shipment` (
  `id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `itemid` bigint(20) NOT NULL,
  `qty` mediumint(9) NOT NULL,
  `userid_requestor` bigint(20) NOT NULL,
  `userid_approver` bigint(20) DEFAULT NULL,
  `userid_puller` bigint(20) DEFAULT NULL,
  `userid_receiver` bigint(20) DEFAULT NULL,
  `lotnumber` varchar(60) NOT NULL,
  `tag` varchar(60) NOT NULL,
  `notes` text DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_approved` datetime DEFAULT NULL,
  `date_shipped` datetime DEFAULT NULL,
  `date_needed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `shipmentapprovals`
--

CREATE TABLE `shipmentapprovals` (
  `id` bigint(20) NOT NULL,
  `shipmentid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `shipmentfulfilled`
--

CREATE TABLE `shipmentfulfilled` (
  `id` bigint(20) NOT NULL,
  `shipmentid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `shipmentrequests`
--

CREATE TABLE `shipmentrequests` (
  `id` bigint(20) NOT NULL,
  `shipmentid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storage`
--

CREATE TABLE `storage` (
  `id` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `itemid` bigint(20) NOT NULL,
  `qty` smallint(6) NOT NULL,
  `userid_requestor` bigint(20) NOT NULL,
  `userid_approver` bigint(20) DEFAULT NULL,
  `userid_stocker` bigint(20) DEFAULT NULL,
  `lotnumber` varchar(60) NOT NULL,
  `tag` varchar(60) NOT NULL,
  `notes` text DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_approved` datetime DEFAULT NULL,
  `date_stored` datetime DEFAULT NULL,
  `date_needed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storageapprovals`
--

CREATE TABLE `storageapprovals` (
  `id` bigint(20) NOT NULL,
  `storageid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagebin`
--

CREATE TABLE `storagebin` (
  `id` bigint(20) NOT NULL,
  `name` varchar(10) NOT NULL,
  `providerid` bigint(20) NOT NULL,
  `facilityid` bigint(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sizexinches` varchar(6) DEFAULT NULL,
  `sizeyinches` varchar(6) DEFAULT NULL,
  `sizezinches` varchar(6) DEFAULT NULL,
  `weightpounds` varchar(6) DEFAULT NULL,
  `full` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagebininventory`
--

CREATE TABLE `storagebininventory` (
  `binid` bigint(20) NOT NULL,
  `palletid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagecontainer`
--

CREATE TABLE `storagecontainer` (
  `id` bigint(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `providerid` bigint(20) NOT NULL,
  `description` text DEFAULT NULL,
  `sizexinches` int(11) DEFAULT NULL,
  `sizeyinches` int(11) DEFAULT NULL,
  `sizezinches` int(11) DEFAULT NULL,
  `weightpounds` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagecontainerinventory`
--

CREATE TABLE `storagecontainerinventory` (
  `containerid` bigint(20) NOT NULL,
  `itemid` bigint(20) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `lastQty` int(11) NOT NULL DEFAULT 0,
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacility`
--

CREATE TABLE `storagefacility` (
  `id` bigint(20) NOT NULL,
  `ownerid` bigint(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(100) DEFAULT NULL,
  `city` varchar(60) DEFAULT NULL,
  `state` varchar(3) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `notes` mediumtext DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `emailaddress` varchar(100) DEFAULT NULL,
  `phone` varchar(16) DEFAULT NULL,
  `lat` float(10,6) DEFAULT NULL,
  `lng` float(10,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacilityinventory`
--

CREATE TABLE `storagefacilityinventory` (
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

CREATE TABLE `storagefacilityowners` (
  `facilityid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacilityproviders`
--

CREATE TABLE `storagefacilityproviders` (
  `facilityid` bigint(20) NOT NULL,
  `providerid` bigint(20) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagefacilityworkers`
--

CREATE TABLE `storagefacilityworkers` (
  `userid` bigint(20) DEFAULT NULL,
  `facilityid` bigint(20) DEFAULT NULL,
  `providerid` bigint(20) DEFAULT NULL,
  `lastactiontimestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `notes` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagefulfilled`
--

CREATE TABLE `storagefulfilled` (
  `id` bigint(20) NOT NULL,
  `storageid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storageitem`
--

CREATE TABLE `storageitem` (
  `id` bigint(20) NOT NULL,
  `ownerid` bigint(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `sizexinches` varchar(6) DEFAULT NULL,
  `sizeyinches` varchar(6) DEFAULT NULL,
  `sizezinches` int(6) DEFAULT NULL,
  `weightpounds` float DEFAULT NULL,
  `uom` varchar(10) DEFAULT NULL,
  `imagename_main` varchar(100) DEFAULT NULL,
  `imagename_small` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagelocation`
--

CREATE TABLE `storagelocation` (
  `id` bigint(20) NOT NULL,
  `facilityid` bigint(20) DEFAULT NULL,
  `name` varchar(60) DEFAULT NULL,
  `row` varchar(10) DEFAULT NULL,
  `col` varchar(10) DEFAULT NULL,
  `shelf` varchar(10) DEFAULT NULL,
  `xshelf` varchar(4) DEFAULT NULL,
  `yshelf` varchar(4) DEFAULT NULL,
  `zshelf` varchar(4) DEFAULT NULL,
  `facilitycoords` varchar(100) DEFAULT NULL,
  `tags` tinytext DEFAULT NULL,
  `lat` float(10,6) DEFAULT NULL,
  `lng` float(10,6) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagelocationinventory`
--

CREATE TABLE `storagelocationinventory` (
  `locationid` bigint(20) NOT NULL,
  `binid` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagelotnumber`
--

CREATE TABLE `storagelotnumber` (
  `id` bigint(20) NOT NULL,
  `lotnumber` varchar(60) DEFAULT NULL,
  `tag` varchar(60) DEFAULT NULL,
  `itemid` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `storagepallet`
--

CREATE TABLE `storagepallet` (
  `id` bigint(20) NOT NULL,
  `name` varchar(10) NOT NULL,
  `providerid` bigint(20) NOT NULL,
  `facilityid` bigint(20) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `sizexinches` varchar(6) DEFAULT NULL,
  `sizeyinches` varchar(6) DEFAULT NULL,
  `sizezinches` varchar(6) DEFAULT NULL,
  `full` tinyint(1) NOT NULL DEFAULT 0,
  `empty` tinyint(4) NOT NULL DEFAULT 1,
  `usable` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagepalletinventory`
--

CREATE TABLE `storagepalletinventory` (
  `palletid` bigint(20) NOT NULL,
  `storageid` bigint(20) DEFAULT NULL,
  `itemid` bigint(20) DEFAULT NULL,
  `item_qty` smallint(6) NOT NULL DEFAULT 0,
  `containerid` bigint(20) DEFAULT NULL,
  `lotnumber` varchar(60) DEFAULT NULL,
  `tag` varchar(60) DEFAULT NULL,
  `confirmed` tinyint(4) NOT NULL DEFAULT 0,
  `shipment_request_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `storagerequests`
--

CREATE TABLE `storagerequests` (
  `id` bigint(20) NOT NULL,
  `storageid` bigint(20) NOT NULL,
  `userid` bigint(20) NOT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) NOT NULL,
  `type` varchar(20) NOT NULL,
  `userid` bigint(20) DEFAULT NULL,
  `clientid` bigint(20) DEFAULT NULL,
  `receiverid` bigint(20) DEFAULT NULL,
  `itemid` bigint(20) DEFAULT NULL,
  `providerid` bigint(20) DEFAULT NULL,
  `binid` bigint(20) DEFAULT NULL,
  `palletid` bigint(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `time_stamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` bigint(20) NOT NULL,
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
  `upasswd` varchar(255) NOT NULL,
  `profileimagepath` varchar(60) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `workitem`
--

CREATE TABLE `workitem` (
  `id` bigint(20) NOT NULL,
  `shipmentid` bigint(20) DEFAULT NULL,
  `storageid` bigint(20) DEFAULT NULL,
  `userid` bigint(20) NOT NULL,
  `date_started` datetime DEFAULT NULL,
  `date_completed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adminusers`
--
ALTER TABLE `adminusers`
  ADD UNIQUE KEY `userid` (`userid`);

--
-- Indexes for table `captcha`
--
ALTER TABLE `captcha`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`userid`);

--
-- Indexes for table `movementitemtype`
--
ALTER TABLE `movementitemtype`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `provider`
--
ALTER TABLE `provider`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `Name` (`name`);

--
-- Indexes for table `shipment`
--
ALTER TABLE `shipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipmentapprovals`
--
ALTER TABLE `shipmentapprovals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipmentfulfilled`
--
ALTER TABLE `shipmentfulfilled`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipmentrequests`
--
ALTER TABLE `shipmentrequests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storage`
--
ALTER TABLE `storage`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storageapprovals`
--
ALTER TABLE `storageapprovals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storagebin`
--
ALTER TABLE `storagebin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storagebininventory`
--
ALTER TABLE `storagebininventory`
  ADD UNIQUE KEY `palletid` (`palletid`);

--
-- Indexes for table `storagecontainer`
--
ALTER TABLE `storagecontainer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storagecontainerinventory`
--
ALTER TABLE `storagecontainerinventory`
  ADD KEY `itemid` (`itemid`),
  ADD KEY `containerid` (`containerid`);

--
-- Indexes for table `storagefacility`
--
ALTER TABLE `storagefacility`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storagefacilityworkers`
--
ALTER TABLE `storagefacilityworkers`
  ADD UNIQUE KEY `ProviderWorker` (`userid`,`providerid`),
  ADD UNIQUE KEY `FacilityWorker` (`userid`,`facilityid`);

--
-- Indexes for table `storagefulfilled`
--
ALTER TABLE `storagefulfilled`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storageitem`
--
ALTER TABLE `storageitem`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storagelocation`
--
ALTER TABLE `storagelocation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storagelotnumber`
--
ALTER TABLE `storagelotnumber`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storagepallet`
--
ALTER TABLE `storagepallet`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `storagepalletinventory`
--
ALTER TABLE `storagepalletinventory`
  ADD UNIQUE KEY `pallet_storage` (`palletid`,`storageid`);

--
-- Indexes for table `storagerequests`
--
ALTER TABLE `storagerequests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emailaddress` (`emailaddress`),
  ADD UNIQUE KEY `profilename` (`profilename`);

--
-- Indexes for table `workitem`
--
ALTER TABLE `workitem`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `captcha`
--
ALTER TABLE `captcha`
  MODIFY `id` smallint(6) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `movementitemtype`
--
ALTER TABLE `movementitemtype`
  MODIFY `ID` tinyint(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `provider`
--
ALTER TABLE `provider`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipment`
--
ALTER TABLE `shipment`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipmentapprovals`
--
ALTER TABLE `shipmentapprovals`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipmentfulfilled`
--
ALTER TABLE `shipmentfulfilled`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipmentrequests`
--
ALTER TABLE `shipmentrequests`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storage`
--
ALTER TABLE `storage`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storageapprovals`
--
ALTER TABLE `storageapprovals`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storagebin`
--
ALTER TABLE `storagebin`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storagecontainer`
--
ALTER TABLE `storagecontainer`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storagefacility`
--
ALTER TABLE `storagefacility`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storagefulfilled`
--
ALTER TABLE `storagefulfilled`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storageitem`
--
ALTER TABLE `storageitem`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storagelocation`
--
ALTER TABLE `storagelocation`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storagelotnumber`
--
ALTER TABLE `storagelotnumber`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storagepallet`
--
ALTER TABLE `storagepallet`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storagerequests`
--
ALTER TABLE `storagerequests`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workitem`
--
ALTER TABLE `workitem`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;
