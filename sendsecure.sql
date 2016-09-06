-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 06, 2016 at 06:47 PM
-- Server version: 5.7.13-0ubuntu0.16.04.2
-- PHP Version: 7.0.8-0ubuntu0.16.04.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sendsecure`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `address` tinytext NOT NULL,
  `account` varchar(13) NOT NULL,
  `password` varchar(13) NOT NULL,
  `stripe_id` tinytext NOT NULL,
  `uniqid` varchar(23) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `emails`
--

CREATE TABLE `emails` (
  `timestamp` datetime NOT NULL,
  `_smtpuser` tinytext NOT NULL,
  `_rcpttos` text NOT NULL,
  `_datetime` tinytext NOT NULL,
  `_subject` tinytext NOT NULL,
  `_headers` text NOT NULL,
  `_from` tinytext NOT NULL,
  `_to` text NOT NULL,
  `_replyto` tinytext NOT NULL,
  `_cc` text NOT NULL,
  `_message` longblob NOT NULL,
  `_attachments` longblob NOT NULL,
  `bounced` tinyint(1) NOT NULL,
  `uniqid` varchar(23) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `email` varchar(254) NOT NULL,
  `userkey` tinytext NOT NULL,
  `firstname` tinytext NOT NULL,
  `lastname` tinytext NOT NULL,
  `password` tinytext,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `stripe_id` tinytext
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD UNIQUE KEY `uuid` (`uniqid`);

--
-- Indexes for table `emails`
--
ALTER TABLE `emails`
  ADD PRIMARY KEY (`uniqid`),
  ADD KEY `uniqid` (`uniqid`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD UNIQUE KEY `email` (`email`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
