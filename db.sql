-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Generation Time: Dec 17, 2012 at 11:08 PM
-- Server version: 5.0.91-log
-- PHP Version: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE IF NOT EXISTS `class` (
  `class` varchar(10) collate latin1_general_cs NOT NULL,
  `year` int(5) unsigned default NULL,
  PRIMARY KEY  (`class`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;

-- --------------------------------------------------------

--
-- Table structure for table `info`
--

CREATE TABLE IF NOT EXISTS `info` (
  `key` varchar(50) character set latin1 collate latin1_german1_ci NOT NULL,
  `value` varchar(150) character set latin1 collate latin1_german1_ci default NULL,
  PRIMARY KEY  (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;

-- --------------------------------------------------------

--
-- Table structure for table `plan`
--

CREATE TABLE IF NOT EXISTS `plan` (
  `pid` int(11) unsigned NOT NULL auto_increment,
  `time` int(11) unsigned NOT NULL,
  `class` varchar(11) collate latin1_general_cs NOT NULL,
  `subject` varchar(11) collate latin1_general_cs NOT NULL,
  `room` varchar(11) collate latin1_general_cs default NULL,
  `type` int(10) unsigned NOT NULL,
  `updated` int(10) unsigned NOT NULL,
  `description` text character set latin1 collate latin1_german1_ci,
  `date` int(11) unsigned NOT NULL,
  `deleted` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`pid`),
  UNIQUE KEY `time-class-subject` (`time`,`class`,`subject`),
  KEY `sid` (`subject`),
  KEY `rid` (`room`),
  KEY `tid` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE IF NOT EXISTS `room` (
  `room` varchar(10) collate latin1_general_cs NOT NULL,
  PRIMARY KEY  (`room`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE IF NOT EXISTS `subject` (
  `subject` varchar(10) collate latin1_general_cs NOT NULL,
  PRIMARY KEY  (`subject`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;

-- --------------------------------------------------------

--
-- Table structure for table `type`
--

CREATE TABLE IF NOT EXISTS `type` (
  `tid` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(40) character set latin1 collate latin1_german1_ci NOT NULL,
  `nice` varchar(40) character set latin1 collate latin1_german1_ci default NULL,
  PRIMARY KEY  (`tid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
