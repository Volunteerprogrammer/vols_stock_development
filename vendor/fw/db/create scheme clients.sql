CREATE DATABASE `mrsg` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;


CREATE TABLE `action` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `code` varchar(24) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='  ';

CREATE TABLE `booking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_role_id` int(11) NOT NULL,
  `status` enum('booked','waitlist','deleted') NOT NULL DEFAULT 'booked',
  `booked_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `booked_by` int(11) DEFAULT NULL,
  `deleted_time` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_session_role_idx` (`session_role_id`) USING BTREE,
  KEY `booking_user_idx_idx` (`user_id`),
  CONSTRAINT `booking_session_role_idx` FOREIGN KEY (`session_role_id`) REFERENCES `session_role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `booking_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=727 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `client` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `given name` varchar(64) DEFAULT NULL,
  `family_name` varchar(64) DEFAULT NULL,
  `email` varchar(256) DEFAULT NULL,
  `suburb_postcode` varchar(45) DEFAULT NULL,
  `gender` enum('MALE','FEMALE','OTHER') DEFAULT NULL,
  `age_group` enum('12','22','31','40','55','68') DEFAULT NULL,
  `residence` enum('Rent','OwnHome','Temporary','Other') DEFAULT NULL,
  `interpreter` tinyint(4) DEFAULT NULL,
  `language` varchar(45) DEFAULT NULL,
  `nationality` varchar(45) DEFAULT NULL,
  `aborigine_TSislander` tinyint(4) DEFAULT NULL,
  `collection` enum('self','carer') DEFAULT NULL,
  `carer_name` varchar(45) DEFAULT NULL,
  `concession_card` tinyint(4) DEFAULT NULL,
  `dietary` int(11) DEFAULT NULL COMMENT '1=Gluten free\n2=Vegetarian\n4=Vegan\n8=DairyFree\n16=Nut Free\n32=Other\n',
  `comments` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `client_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `name` varchar(65) DEFAULT NULL,
  `relationship` varchar(45) DEFAULT NULL,
  `year_of_birth` int(11) DEFAULT NULL,
  `nationality` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_client_member_client_idx` (`client_id`),
  CONSTRAINT `fk_client_member_client` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `client_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_client_session_session_idx` (`session_id`),
  KEY `fk_client_session_client_idx` (`client_id`),
  CONSTRAINT `fk_client_session_client` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_client_session_session` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` varchar(45) DEFAULT NULL,
  `name` varchar(256) NOT NULL,
  `value` varchar(4096) NOT NULL,
  `comment` varchar(4096) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `senddate` datetime DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `email` mediumtext DEFAULT NULL,
  `response` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2165 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email_session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_emailsession_email_id` (`email_id`),
  KEY `fk_emailsession_session_id` (`session_id`),
  CONSTRAINT `fk_emailsession_email_id` FOREIGN KEY (`email_id`) REFERENCES `email` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_emailsession_session_id` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=392 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='   ';

CREATE TABLE `email_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_emailuser_email_id` (`email_id`),
  KEY `fk_emailuser_user_id` (`user_id`),
  CONSTRAINT `fk_emailuser_email_id` FOREIGN KEY (`email_id`) REFERENCES `email` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_emailuser_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=869 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='   ';

CREATE TABLE `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `name` varchar(256) NOT NULL,
  `starttime` time NOT NULL DEFAULT '00:00:00',
  `endtime` time NOT NULL DEFAULT '23:59:00',
  `leadtime` int(11) NOT NULL DEFAULT 16,
  `publishedleadtime` int(11) NOT NULL DEFAULT 12,
  `bookingalertlevels` varchar(255) NOT NULL COMMENT 'c',
  `bookingalertperiods` varchar(255) NOT NULL,
  `recurrence` enum('Once-only','Daily','Weekly','Monthly','Yearly') NOT NULL DEFAULT 'Weekly',
  `dailyoption` int(11) NOT NULL DEFAULT 1,
  `dailyinterval` int(11) NOT NULL DEFAULT 1,
  `weeklyinterval` int(11) NOT NULL DEFAULT 1,
  `weeklydow` int(11) NOT NULL DEFAULT 126,
  `monthlyoption` int(11) NOT NULL DEFAULT 1,
  `monthlydayofmonth` int(11) NOT NULL DEFAULT 1,
  `monthlyinterval0` int(11) NOT NULL DEFAULT 1,
  `monthlywhichdow` int(11) NOT NULL DEFAULT 3,
  `monthlydow` int(11) NOT NULL DEFAULT 5,
  `monthlyinterval1` int(11) NOT NULL DEFAULT 1,
  `yearlyoption` int(11) NOT NULL DEFAULT 0,
  `yearlydom` int(11) NOT NULL DEFAULT 1,
  `yearlymonth0` int(11) NOT NULL DEFAULT 0,
  `yearlywhichdom` int(11) NOT NULL DEFAULT 0,
  `yearlywhichday` int(11) NOT NULL DEFAULT 0,
  `yearlymonth1` int(11) NOT NULL DEFAULT 0,
  `startdate` date NOT NULL,
  `pageindex` int(11) NOT NULL DEFAULT 0 COMMENT 'In multievent pages, where does this event come in the display',
  `pagedepth` int(11) NOT NULL DEFAULT 0 COMMENT 'How many sessions to display on the roster page - default value',
  PRIMARY KEY (`id`),
  KEY `fk_event_paget_idx` (`page_id`),
  CONSTRAINT `fk_event_page` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='      ';

CREATE TABLE `event_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `min_quantity` int(11) DEFAULT NULL,
  `max_quantity` int(11) DEFAULT NULL,
  `waitlist` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_er_event_id` (`event_id`),
  KEY `FK_er_role_id` (`role_id`),
  CONSTRAINT `FK_er_event_id` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_er_role_id` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `replyto_id` int(11) DEFAULT NULL,
  `messagecol` mediumtext DEFAULT NULL,
  `from_id` int(11) DEFAULT NULL,
  `to_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='      ';

CREATE TABLE `page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pagenumber` int(11) NOT NULL,
  `pageconstant` varchar(45) NOT NULL,
  `usepagenum` int(11) NOT NULL DEFAULT 0,
  `pagetype` tinyint(4) NOT NULL COMMENT 'meaning:  1=SYSTEM, 2=ROSTER,3=EDITOR, 4=OTHER.   SYSTEM means the page is access via system processes. It will not appear in a menu.',
  `unrestricted` tinyint(4) NOT NULL COMMENT 'This means the page is open to all users without restriction. No Permissions required',
  `submenu` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Use -1 to NOT include this page in any menu',
  `menuid` varchar(45) NOT NULL,
  `menutext` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pagenumber` (`pagenumber`),
  KEY `idx_pageconstant` (`pageconstant`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='  ';

CREATE TABLE `page_access` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(10) unsigned NOT NULL,
  `page_num` varchar(11) NOT NULL,
  `accesstime` timestamp NULL DEFAULT current_timestamp(),
  `notes` varchar(1000) NOT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `session_id_ordered` (`session_id`,`accesstime`),
  KEY `accesstime` (`accesstime`)
) ENGINE=InnoDB AUTO_INCREMENT=42154 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `page_action` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) DEFAULT NULL,
  `action_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pageaction_action` (`action_id`),
  KEY `fk_pageaction_page` (`page_id`),
  CONSTRAINT `fk_pageaction_action` FOREIGN KEY (`action_id`) REFERENCES `action` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pageaction_page` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=329 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `phpsession` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `phpsession` varchar(45) NOT NULL,
  `isadmin` tinyint(4) DEFAULT NULL,
  `starttime` datetime DEFAULT NULL,
  `last_access` datetime DEFAULT NULL,
  `client_ip` varchar(64) DEFAULT NULL,
  `timezone` varchar(64) DEFAULT NULL,
  `created` varchar(45) DEFAULT 'CURRENT_TIMESTAMP',
  `locktime` timestamp NULL DEFAULT NULL,
  `lockedby` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2245 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='    ';

CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `cellname` varchar(45) NOT NULL,
  `rosterindex` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='    ';

CREATE TABLE `role_pageaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) DEFAULT NULL,
  `pageaction_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rolepageaction_role_idx` (`role_id`),
  KEY `fk_rolepageaction_pageaction_idx` (`pageaction_id`),
  CONSTRAINT `fk_rolepageaction_pageaction` FOREIGN KEY (`pageaction_id`) REFERENCES `page_action` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rolepageaction_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=438 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `security` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(65) DEFAULT NULL,
  `code` varchar(45) DEFAULT NULL,
  `expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=154 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `start` timestamp NULL DEFAULT NULL,
  `finish` timestamp NULL DEFAULT NULL,
  `is_holiday` tinyint(4) NOT NULL,
  `holiday_name` varchar(64) NOT NULL,
  `published` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `session_event_idx` (`event_id`) USING BTREE,
  CONSTRAINT `session_event_fk` FOREIGN KEY (`event_id`) REFERENCES `event` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=651 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `session_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `min_quantity` int(11) NOT NULL,
  `max_quantity` int(11) NOT NULL,
  `waitlist` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `FK_session_id` FOREIGN KEY (`session_id`) REFERENCES `session` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_id` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=508 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `given_name` varchar(64) NOT NULL,
  `family_name` varchar(64) NOT NULL,
  `display_name` varchar(64) NOT NULL,
  `email` varchar(256) NOT NULL,
  `mobile` varchar(12) NOT NULL,
  `username` varchar(64) NOT NULL,
  `password` varchar(256) NOT NULL,
  `isadmin` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_UNIQUE` (`username`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_userrole_user` (`user_id`) USING BTREE,
  KEY `idx_userrole_role` (`role_id`) USING BTREE,
  CONSTRAINT `fk_userrole_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_userrole_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=256 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
