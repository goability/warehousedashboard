-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 09, 2019 at 02:22 AM
-- Server version: 5.7.23
-- PHP Version: 7.2.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `warehouse`
--

-- --------------------------------------------------------

--
-- Table structure for table `Client`
--

CREATE TABLE `Client` (
  `UserID` bigint(20) NOT NULL,
  `ProviderID` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `Occupant`
--

CREATE TABLE `Occupant` (
  `UserID` bigint(20) NOT NULL,
  `StorageLocationID` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `Provider`
--

CREATE TABLE `Provider` (
  `ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Address` varchar(100) NOT NULL,
  `City` varchar(100) NOT NULL,
  `State` varchar(10) NOT NULL,
  `ZIP` int(16) NOT NULL,
  `ownerID` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `StorageBin`
--

CREATE TABLE `StorageBin` (
  `ID` bigint(20) NOT NULL,
  `Name` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `StorageContainer`
--

CREATE TABLE `StorageContainer` (
  `ID` bigint(20) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text NOT NULL,
  `SizeXInches` int(11) NOT NULL,
  `SizeYInches` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `StorageFacility`
--

CREATE TABLE `StorageFacility` (
  `ID` bigint(20) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Address` varchar(100) NOT NULL,
  `City` varchar(60) NOT NULL,
  `State` varchar(3) NOT NULL,
  `ZIP` varchar(10) NOT NULL,
  `Notes` text NOT NULL,
  `website` varchar(100) NOT NULL,
  `phone` varchar(16) NOT NULL,
  `lat` float(10,6) NOT NULL,
  `lng` float(10,6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `StorageFacilityInventory`
--

CREATE TABLE `StorageFacilityInventory` (
  `StorageLocationID` bigint(20) NOT NULL,
  `StorageItemID` bigint(20) NOT NULL,
  `StorageContainerID` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `StorageFacilityWorker`
--

CREATE TABLE `StorageFacilityWorker` (
  `UserID` bigint(20) NOT NULL,
  `StorageFacilityID` bigint(20) NOT NULL,
  `ProviderID` bigint(20) NOT NULL,
  `LastActionTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Active` tinyint(1) NOT NULL,
  `Notes` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `StorageItem`
--

CREATE TABLE `StorageItem` (
  `ID` bigint(20) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text NOT NULL,
  `SizeXInches` smallint(6) NOT NULL,
  `ItemProperties` mediumtext NOT NULL COMMENT 'JSON Properties'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `StorageItemMovement`
--

CREATE TABLE `StorageItemMovement` (
  `ID` bigint(20) NOT NULL,
  `StorageItemID` bigint(20) NOT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `FromLocationID` bigint(20) NOT NULL,
  `ToLocationID` bigint(20) NOT NULL,
  `MoverUserID` bigint(20) NOT NULL COMMENT 'Worker or User with access',
  `RequestorID` bigint(20) NOT NULL COMMENT 'Who requests it',
  `Notes` text COMMENT 'Optional 65K'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `StorageLocation`
--

CREATE TABLE `StorageLocation` (
  `ID` bigint(20) NOT NULL,
  `StorageFacilityID` bigint(20) NOT NULL,
  `Row` varchar(4) NOT NULL,
  `Col` varchar(4) NOT NULL,
  `Shelf` varchar(4) NOT NULL,
  `XShelf` varchar(4) NOT NULL,
  `YShelf` varchar(4) NOT NULL,
  `ZShelf` varchar(4) NOT NULL,
  `FacilityCoords` varchar(60) NOT NULL,
  `Tags` tinytext NOT NULL,
  `lat` float(10,6) NOT NULL,
  `lng` float(10,6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `StoragePallette`
--

CREATE TABLE `StoragePallette` (
  `ID` bigint(20) NOT NULL,
  `Name` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `User`
--

CREATE TABLE `User` (
  `ID` bigint(20) NOT NULL,
  `FirstName` varchar(60) NOT NULL,
  `MiddleName` varchar(60) NOT NULL,
  `LastName` varchar(60) NOT NULL,
  `CompanyName` varchar(100) NOT NULL,
  `Address` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Provider`
--
ALTER TABLE `Provider`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Name` (`Name`);

--
-- Indexes for table `StorageBin`
--
ALTER TABLE `StorageBin`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `StorageContainer`
--
ALTER TABLE `StorageContainer`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `StorageFacility`
--
ALTER TABLE `StorageFacility`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `StorageFacilityWorker`
--
ALTER TABLE `StorageFacilityWorker`
  ADD UNIQUE KEY `ProviderWorker` (`UserID`,`ProviderID`),
  ADD UNIQUE KEY `FacilityWorker` (`UserID`,`StorageFacilityID`);

--
-- Indexes for table `StorageItem`
--
ALTER TABLE `StorageItem`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `StorageItemMovement`
--
ALTER TABLE `StorageItemMovement`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `StorageItemID` (`ID`,`StorageItemID`),
  ADD UNIQUE KEY `RequestorID` (`RequestorID`);

--
-- Indexes for table `StorageLocation`
--
ALTER TABLE `StorageLocation`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `StoragePallette`
--
ALTER TABLE `StoragePallette`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Provider`
--
ALTER TABLE `Provider`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `StorageBin`
--
ALTER TABLE `StorageBin`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `StorageContainer`
--
ALTER TABLE `StorageContainer`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `StorageFacility`
--
ALTER TABLE `StorageFacility`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `StorageItem`
--
ALTER TABLE `StorageItem`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `StorageItemMovement`
--
ALTER TABLE `StorageItemMovement`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `StorageLocation`
--
ALTER TABLE `StorageLocation`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `StoragePallette`
--
ALTER TABLE `StoragePallette`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `User`
--
ALTER TABLE `User`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
