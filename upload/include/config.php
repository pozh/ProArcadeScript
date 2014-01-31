<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ main configuration file for ProArcadeScript. It can't be changed
/ using admin control panel.
/
/*******************************************************************/
require_once( "sitesettings.php" );
require_once( "language.php" );
require_once( "dbsettings.php" );
require_once( "comments_config.php" );
require_once( "cron.php" );
require_once( "blocks_config.php" );
require_once( "pages_config.php" );
require_once( 'usersettings.php' );

$version = '1.6';

// strings for DB queries. Please do not edit
$cSort = array( 
	"rand"	=>	"RAND()",
	"date"	=>	"added DESC", 
	"title"	=>	"title ASC", 
	"plays_total"	=>	"plays_total DESC", 
	"plays_today"	=>	"plays_today DESC",
	"rating"		=>	"rating DESC"
);

$cExt = array( 
	'swf'	=>	'block_game.html', 		// Flash Games
	'ccn'	=>	'block_ccngame.html',	// Vitalise Games
	'dcr' => 'block_dcr.html'
);

$cImgExt = array( 'jpg', 'gif', 'png' );

Error_Reporting(E_ALL & ~E_NOTICE);
return;
?>