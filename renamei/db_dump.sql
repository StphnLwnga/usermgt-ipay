--
-- PHP Login Script SQL File
--
-- Run this SQL file to create the neccessary tables and default
-- database entries needed to get the script working.
--
-- Created by The Angry Frog - http://www.angry-frog.com

-- --------------------------------------------------------

--
-- Table structure for table `active_guests`
--

CREATE TABLE `active_guests` (
  `ip` varchar(15) NOT NULL,
  `timestamp` int(11) unsigned NOT NULL,
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `banlist`
--

CREATE TABLE `banlist` (
  `ban_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `ban_username` varchar(255) DEFAULT 0,
  `ban_userid` mediumint(8) unsigned DEFAULT 0,
  `ban_ip` varchar(40) DEFAULT 0,
  `timestamp` int(11) unsigned NOT NULL,
  PRIMARY KEY (`ban_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `configuration`
--

CREATE TABLE `configuration` (
  `config_name` varchar(20) NOT NULL,
  `config_value` varchar(64) NOT NULL,
  PRIMARY KEY (`config_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `configuration`
--

INSERT INTO `configuration` (`config_name`, `config_value`) VALUES
('ACCOUNT_ACTIVATION', '1'),
('ALL_LOWERCASE', '0'),
('ALLOW_MULTI_LOGONS', '0'),
('COOKIE_EXPIRE', '14'),
('COOKIE_PATH', '/'),
('DATE_FORMAT', 'M j, Y, g:i a'),
('EMAIL_FROM_NAME', 'Xavier PHP Login Script'),
('EMAIL_FROM_ADDR', 'email_address@your-website.com'),
('EMAIL_WELCOME', '1'),
('ENABLE_CAPTCHA', '0'),
('GUEST_TIMEOUT', '5'),
('home_page', 'index.php'),
('HOME_SETBYADMIN', '1'),
('login_page', 'index.php'),
('max_user_chars', '36'),
('min_user_chars', '5'),
('max_pass_chars', '120'),
('min_pass_chars', '8'),
('NO_ADMIN_REDIRECT', '1'),
('PERSIST_NOT_EXPIRE', '1'),
('record_online_date', ''),
('record_online_users', ''),
('SITE_DESC', 'Xavier PHP Login Script'),
('SITE_NAME', 'Xavier PHP Login Script'),
('TURN_ON_INDIVIDUAL', '0'),
('USER_HOME_PATH', '/'),
('USERNAME_REGEX', 'letter_num_spaces'),
('USER_TIMEOUT', '10'),
('Version', '3.1.5'),
('WEB_ROOT', 'http://localhost/');

-- --------------------------------------------------------

--
-- Table structure for table `users_groups`
--

CREATE TABLE `users_groups` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` smallint(6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users_groups`
--

INSERT INTO `users_groups` (`id`, `user_id`, `group_id`) VALUES
(1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(50) NOT NULL,
  `group_level` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`group_id`, `group_name`, `group_level`) VALUES
(1, 'Administrators', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(36) NOT NULL,
  `firstname` varchar(40) NOT NULL,
  `lastname` varchar(40) NOT NULL,
  `password` varchar(255) default NULL,
  `userlevel` tinyint(1) unsigned NOT NULL,
  `email` varchar(50) default NULL,
  `timestamp` int(11) unsigned NOT NULL,
  `previous_visit` int(11) unsigned DEFAULT 0,
  `ip` varchar(15) NOT NULL,
  `regdate` int(11) unsigned NOT NULL,
  `lastip` varchar(15) NULL,
  `user_login_attempts` tinyint(4) NULL,
  `user_home_path` varchar(50) NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `log_table`
--

CREATE TABLE `log_table` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED NOT NULL,
  `timestamp` int(11) UNSIGNED NOT NULL,
  `ip` varchar(15) NOT NULL,
  `log_operation` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` char(32) NOT NULL,
  `hashedValidator` char(64) NOT NULL,
  `persistent` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `userid` int(11) UNSIGNED NOT NULL,
  `ipaddress` varchar(15) NOT NULL DEFAULT '0',
  `timestamp` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `expires` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_temp`
--

CREATE TABLE `user_temp` (
  `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(11) UNSIGNED NOT NULL,
  `timestamp` int(11) UNSIGNED NOT NULL,
  `type` varchar(20) NOT NULL,
  `detail` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
