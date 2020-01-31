-- /*
-- * $RCSfile: cyrup.pgsql.sql,v $ $Revision: 1.3 $
-- * $Author: slim_lv $ $Date: 2008/08/25 14:45:45 $
-- * This file is part of CYRUP project
-- * by Yuri Pimenov (up@msh.lv) & Deniss Gaplevsky (slim@msh.lv)
-- * Thanks for postgresql code goes to Brett Van Sprewenburg <brett(at)ataxxia.com>
-- */

CREATE DATABASE "mail";
\connect mail;

--
-- Table structure for table `cyrup_accounts`
--

CREATE TABLE "cyrup_accounts" (  
  "id" SERIAL NOT NULL ,
  "account" VARCHAR(100) NOT NULL,
  "password" VARCHAR(50) NOT NULL,
  "domain_id" INTEGER NOT NULL DEFAULT '0',
  "quota" BIGINT NULL DEFAULT '0',
  "first_name" VARCHAR(100) NULL DEFAULT NULL,
  "surname" VARCHAR(100) NULL DEFAULT NULL,
  "phone" VARCHAR(100) NULL DEFAULT NULL,
  "other_email" VARCHAR(100) NULL DEFAULT NULL,
  "info" VARCHAR(250) NULL DEFAULT NULL,
  "enabled" SMALLINT NULL DEFAULT NULL,
  PRIMARY KEY ("id"),
  UNIQUE ("account")
); 
CREATE INDEX "cyrup_accounts_account" ON "cyrup_accounts" ("account");

--
-- Table structure for table `cyrup_admins`
--

CREATE TABLE "cyrup_admins" (
  "id" SERIAL NOT NULL ,
  "username" VARCHAR(30) NULL DEFAULT NULL,
  "password" VARCHAR(50) NULL DEFAULT NULL,
  "rights" VARCHAR(100) NULL DEFAULT NULL,
  "info" VARCHAR(250) NULL DEFAULT NULL,
  PRIMARY KEY ("id")
); 


--
-- Table structure for table `cyrup_aliases`
--

CREATE TABLE "cyrup_aliases" (  
  "id" SERIAL NOT NULL ,
  "account_id" INTEGER NOT NULL DEFAULT '0',
  "domain_id" INTEGER NOT NULL DEFAULT '0',
  "alias" VARCHAR(100) NOT NULL DEFAULT '',
  "aliased_to" VARCHAR(250) NOT NULL DEFAULT '',
  "enabled" SMALLINT NULL DEFAULT NULL,
  PRIMARY KEY ("id")
); 
CREATE INDEX "cyrup_aliases_alias" ON "cyrup_aliases" ("alias");


--
-- Table structure for table `cyrup_default_rcpt`
--

CREATE TABLE "cyrup_default_rcpt" (
  "domain_id" SERIAL NOT NULL ,
  "alias" VARCHAR(100) NULL DEFAULT NULL,
  "aliased_to" VARCHAR(250) NULL DEFAULT NULL,
  PRIMARY KEY ("domain_id")
); 


--
-- Table structure for table `cyrup_domains`
--

CREATE TABLE "cyrup_domains" (
  "id" SERIAL NOT NULL ,
  "domain" VARCHAR(100) NOT NULL DEFAULT '',
  "accounts_max" BIGINT NULL DEFAULT '0',
  "aliases_max" BIGINT NULL DEFAULT '0',
  "quota" BIGINT NULL DEFAULT '0',
  "info" VARCHAR(250) NULL DEFAULT NULL,
  "account_suffix" VARCHAR(100) NOT NULL DEFAULT '',
  "enabled" SMALLINT NULL DEFAULT NULL,
  PRIMARY KEY ("id")
); 


--
-- Table structure for table `cyrup_maillists`
--

CREATE TABLE "cyrup_maillists" (
  "id" SERIAL NOT NULL ,
  "domain_id" INTEGER NULL DEFAULT NULL,
  "alias" VARCHAR(100) NULL DEFAULT NULL,
  "aliased_to" TEXT NULL DEFAULT NULL,
  "enabled" SMALLINT NULL DEFAULT NULL,
  PRIMARY KEY ("id"),
  UNIQUE ("alias")
); 


--
-- Table structure for table `cyrup_sessions`
--

CREATE TABLE "cyrup_sessions" (
  "sesskey" VARCHAR(32) NOT NULL DEFAULT '',
  "expiry" BIGINT NOT NULL DEFAULT '0',
  "value" TEXT NOT NULL DEFAULT '',
  PRIMARY KEY ("sesskey")
); 

CREATE USER postfix PASSWORD 'FIXME';
GRANT DELETE,INSERT,SELECT,UPDATE ON cyrup_accounts, cyrup_admins, cyrup_aliases, cyrup_default_rcpt, cyrup_domains, cyrup_maillists, cyrup_sessions TO postfix;
GRANT SELECT,UPDATE ON cyrup_accounts_id_seq, cyrup_admins_id_seq, cyrup_aliases_id_seq, cyrup_default_rcpt_domain_id_seq, cyrup_domains_id_seq, cyrup_maillists_id_seq TO postfix;
INSERT INTO cyrup_admins VALUES (DEFAULT,'admin','','','Mega admin');
INSERT INTO cyrup_accounts VALUES (DEFAULT,'cyrus','FIXME',0,0,DEFAULT,DEFAULT,DEFAULT,DEFAULT,'cyrus admin','1');
