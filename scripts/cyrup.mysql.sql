-- /*
-- * $RCSfile: cyrup.mysql.sql,v $ $Revision: 1.8 $
-- * $Author: slim_lv $ $Date: 2010/08/02 09:17:03 $
-- * This file is part of CYRUP project
-- * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
-- */
--
-- MySQL dump 9.08
--
-- Host: localhost    Database: mail
---------------------------------------------------------
-- Server version	4.0.13

--
-- Table structure for table 'cyrup_accounts'
--

CREATE DATABASE mail;

USE mail;

CREATE TABLE cyrup_accounts (
  id int(11) NOT NULL auto_increment,
  account varchar(100) NOT NULL default '',
  password varchar(50) BINARY NOT NULL default '',
  domain_id int(11) NOT NULL default '0',
  quota int(10) unsigned default '0',
  first_name varchar(100) default NULL,
  surname varchar(100) default NULL,
  phone varchar(100) default NULL,
  other_email varchar(100) default NULL,
  info varchar(250) default NULL,
  enabled tinyint(4) default NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY account (account),
  KEY account_2 (account)
) TYPE=MyISAM;

--
-- Table structure for table 'cyrup_admins'
--

CREATE TABLE cyrup_admins (
  id int(11) NOT NULL auto_increment,
  username varchar(30) default NULL,
  password varchar(50) BINARY default NULL,
  rights varchar(100) default NULL,
  info varchar(250) default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;

--
-- Table structure for table 'cyrup_aliases'
--

CREATE TABLE cyrup_aliases (
  id int(11) NOT NULL auto_increment,
  account_id int(11) NOT NULL default '0',
  domain_id int(11) NOT NULL default '0',
  alias varchar(100) NOT NULL default '',
  aliased_to varchar(250) NOT NULL default '',
  enabled tinyint(4) default NULL,
  PRIMARY KEY  (id),
  KEY alias_2 (alias)
) TYPE=MyISAM;

--
-- Table structure for table 'cyrup_default_rcpt'
--

CREATE TABLE cyrup_default_rcpt (
  domain_id int(11) NOT NULL auto_increment,
  alias varchar(100) default NULL,
  aliased_to varchar(250) default NULL,
  PRIMARY KEY  (domain_id)
) TYPE=MyISAM;

--
-- Table structure for table 'cyrup_domains'
--

CREATE TABLE cyrup_domains (
  id int(11) NOT NULL auto_increment,
  domain varchar(100) NOT NULL default '',
  accounts_max int(10) unsigned default '0',
  aliases_max int(10) unsigned default '0',
  quota int(10) unsigned default '0',
  info varchar(250) default NULL,
  account_suffix varchar(100) NOT NULL default '',
  enabled tinyint(4) default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;

--
-- Table structure for table 'cyrup_maillists'
--

CREATE TABLE cyrup_maillists (
  id int(11) NOT NULL auto_increment,
  domain_id int(11) default NULL,
  alias varchar(100) default NULL,
  aliased_to text,
  enabled tinyint(3) unsigned default NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY alias (alias)
) TYPE=MyISAM;

--
-- Table structure for table 'cyrup_sessions'
--

CREATE TABLE cyrup_sessions (
  sesskey varchar(32) NOT NULL default '',
  expiry int(11) unsigned NOT NULL default '0',
  value text NOT NULL,
  PRIMARY KEY  (sesskey)
) TYPE=MyISAM;

GRANT DELETE,INSERT,LOCK TABLES,SELECT,UPDATE ON mail.* TO postfix@localhost IDENTIFIED BY 'FIXME';
INSERT INTO cyrup_admins VALUES (DEFAULT,'admin',SHA1('FIXME'),'','Mega admin');
INSERT INTO cyrup_accounts VALUES (DEFAULT,'cyrus',SHA1('FIXME'),0,0,DEFAULT,DEFAULT,DEFAULT,DEFAULT,'cyrus admin','1');
