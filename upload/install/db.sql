CREATE TABLE IF NOT EXISTS `arcade_ban` (
  `ip` varchar(15) NOT NULL default '',
  `time` int(10) unsigned NOT NULL default '0',
  `type` int(1) unsigned NOT NULL default '0',
  KEY `time` (`time`),
  KEY `ip` (`ip`)
) TYPE=MyISAM;


CREATE TABLE IF NOT EXISTS `arcade_categories` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `latin_title` varchar(255) NOT NULL default '',
  `show_on_main` int(1) unsigned NOT NULL default '0',
  `description` text NOT NULL,
  `keywords` varchar(255) NOT NULL default '',
  `position` int(3) unsigned NOT NULL default '0',
  `games` int(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `position` (`position`)
) TYPE=MyISAM;


CREATE TABLE IF NOT EXISTS `arcade_comments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `game_id` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `added` int(10) unsigned NOT NULL default '0',
  `ip` varchar(15) NOT NULL default '',
  `text` text NOT NULL,
  `active` int(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
)  TYPE=MyISAM;


CREATE TABLE IF NOT EXISTS `arcade_favorites` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `game_id` int(10) unsigned NOT NULL default '0',
  KEY `user_id` (`user_id`)
) TYPE=MyISAM;


CREATE TABLE IF NOT EXISTS `arcade_games` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `category_id` int(5) unsigned NOT NULL default '0',
  `added` int(10) NOT NULL default '0',
  `active` int(1) unsigned NOT NULL default '0',
  `title` varchar(128) NOT NULL default '',
  `latin_title` varchar(255) NOT NULL default '',
  `file` text NOT NULL,
  `thumbnail` varchar(255) NOT NULL default '',
  `large_img` varchar(128) NOT NULL default '',
  `plays_total` int(9) unsigned NOT NULL default '0',
  `plays_today` int(5) unsigned NOT NULL default '0',
  `keywords` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `width` int(4) unsigned NOT NULL default '0',
  `height` int(4) unsigned NOT NULL default '0',
  `votes` int(7) unsigned NOT NULL default '0',
  `votes_value` int(8) unsigned NOT NULL default '0',
  `rating` int(3) NOT NULL default '0',
  `featured` int(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `category_id` (`category_id`),
  KEY `plays_total` (`plays_total`),
  KEY `added` (`added`),
  KEY `rating` (`rating`),
  KEY `latin_title` (`latin_title`),
  FULLTEXT KEY `description` (`description`)
) TYPE=MyISAM;


CREATE TABLE IF NOT EXISTS `arcade_log` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `ip` varchar(15) NOT NULL default '',
  `time` int(10) unsigned NOT NULL default '0',
  `action` varchar(64) NOT NULL default '',
  `game_id` int(10) unsigned NOT NULL default '0',
  KEY `ip` (`ip`)
) TYPE=MyISAM;


CREATE TABLE IF NOT EXISTS `arcade_news` (
  `id` int(9) unsigned NOT NULL auto_increment,
  `title` varchar(128) NOT NULL default '',
  `summary` varchar(255) NOT NULL default '',
  `text` text NOT NULL,
  `date` int(10) unsigned default '0',
  `active` int(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `date` (`date`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS `arcade_related` (
  `game_id` int(10) unsigned NOT NULL default '0',
  `ip` varchar(15) NOT NULL default '',
  `time` int(10) unsigned NOT NULL default '0',
  KEY `game_id` (`game_id`),
  KEY `ip` (`ip`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS `arcade_stats` (
  `date` int(5) unsigned NOT NULL default '0',
  `plays` int(6) unsigned NOT NULL default '0',
  `new_users` int(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`date`)
) TYPE=MyISAM;


CREATE TABLE IF NOT EXISTS `arcade_stoplist` (
  `id` int(7) unsigned NOT NULL auto_increment,
  `string` varchar(255) NOT NULL default '',
  `count` int(2) unsigned NOT NULL default '0',
  `active` int(1) unsigned NOT NULL default '1',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS `arcade_submit` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `type` int(1) unsigned NOT NULL default '0',
  `status` int(1) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `keywords` varchar(255) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `filename` varchar(255) NOT NULL default '',
  `screenshot` varchar(255)  NOT NULL default '',
  `thumbnail` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS `arcade_userdata` (
  `time` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `param` int(2) unsigned NOT NULL default '0',
  `value` varchar(255) NOT NULL default '',
  KEY `user_id` (`user_id`),
  KEY `param` (`param`),
  KEY `value` (`value`),
  KEY `time` (`time`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS `arcade_users` (
  `id` int(6) NOT NULL auto_increment,
  `username` varchar(50) NOT NULL default '',
  `password` varchar(32) default NULL,
  `email` varchar(50) NOT NULL default '0',
  `gameplays` mediumint(10) NOT NULL default '0',
  `verified` tinyint(1) NOT NULL default '0',
  `subscribed` tinyint(1) NOT NULL default '1',
  `location` text NOT NULL,
  `gender` int(1) unsigned NOT NULL default '0',
  `joined` int(10) unsigned NOT NULL default '0',
  `ip` varchar(200) NOT NULL default '',
  `activation_code` int(10) unsigned NOT NULL default '0',
  `avatar` varchar(128) NOT NULL default '',
  `last_login` int(10) unsigned NOT NULL default '0',
  `rating` int(7) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `email` (`email`),
  KEY `username` (`username`)
) TYPE=MyISAM;

INSERT INTO `arcade_categories` VALUES (1, 'Arcades', 'Arcades', 1, 'Arcade games', 'arcade games', 5, 0);
INSERT INTO `arcade_categories` VALUES (2, 'Puzzle', 'Puzzle', 1, 'Puzzle games', 'puzzle games', 7, 0);
INSERT INTO `arcade_categories` VALUES (3, 'Sport', 'Sport', 1, 'Sport games', 'sport games', 20, 0);
INSERT INTO `arcade_categories` VALUES (4, 'Shoot''em Up', 'Shootem-Up', 1, 'Shoot''em Up games', 'Shoot''em Up games', 22, 0);
INSERT INTO `arcade_categories` VALUES (5, 'Racing', 'Racing', 1, 'Racing games', 'Racing games', 23, 0);

