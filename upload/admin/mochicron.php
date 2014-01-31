<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ MochiAds game feeds reader for cron jobs
/ parameters
/		user: your username used to access admin cp
/		password: your admin password
/ 	rating: (all, everyone, teen, mature) restrict the game feed 
						to only containing a particular content rating
/		category: slug of any category to limit the type of games in the feed
/		tags: (comma-separated list of tags) used to filter results 
						returned by mochi server
/		games: limit the number of games in the feed
/		skip: offset parameter to fetch older games in smaller chunks
/
/ sample: GET "mochicron.php?user=admin&password=secret&rating=all&games=5"		 						
/ sample: GET "mochicron.php?user=admin&password=secret&games=100&skip=100"		 						
/ sample: GET "mochicron.php?user=admin&password=secret&games=100&tags=card,casino"		 						
/ sample: GET "mochicron.php?user=admin&password=secret&games=100&category=Board%20Game"		 						
/		
/*******************************************************************/
require( '../include/config.php' );
require( '../include/helpers.php' );
require( '../include/class.Database.php' );
require( '../include/class.Cache.php' );     
require( 'mochiimporter.php' );
		
// make sure the script is called by the same server
if( $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR'] )
{
	echo( 'This script is not for direct access. You should setup a cron job for running it!' );
	exit();
}		

// check username/password
if( ($_GET['user'] != $cSite['sAdminName']) || (md5($_GET['password']) != $cSite['sAdminPassword']) )
{
	echo( 'Error: wrong username or password.' );
	exit();
}			

// Init key objects		
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );
if( !$db )
{
	echo( 'Error: Can\'t init database.');
	exit();
}

$cache = new CCache();
if( !$cache )
{
	echo( 'Error: Can\'t init cache.');
	exit();
}

// check database for compatibility
$res = $db->query( 'SELECT * FROM '.$cMain['dbPrefix'].'games LIMIT 1' );
$fields = $db->get_result_fields( $res );
$fieldNames = array();
foreach($fields as $field )
	$fieldNames[] = $field->name;
if( !in_array('coins_revshare_enabled', $fieldNames) )
{
	echo( 'Error: database structure is obsolete, please open the "Content->Mochi Games" admin cp\'s page in your browser.' );
	exit();
}

// init settings array
$settings = array(
	'publisher_id'	=>	'',
	'rating'				=>	'',
	'category'			=>	'',
	'tags'					=>	'',
	'import_games'	=>	0,
	'import_skip'		=>	0,
);

$savedSettings = $db->super_query('SELECT * FROM '.$cMain['dbPrefix'].'mochisettings LIMIT 1', false);
if( empty($savedSettings['publisher_id']) )
{
	echo( 'Publisher ID is not set. Please check the settings on the "Content->Mochi Games" page inside your admin cp.' );
	exit();
}			

$settings['publisher_id'] = $savedSettings['publisher_id'];
$settings['rating'] = $_GET['rating'];
$settings['category'] = mysql_escape_string($_GET['category']);
$settings['tags'] = mysql_escape_string($_GET['tags']);
$settings['import_games']	= $_GET['games'];
$settings['import_skip'] = $_GET['skip'];

// import games!
$timeStart = time();
list( $addedGames, $skippedGames, $message ) = importMochiGames( $db, $settings, true );
$timeResult = time() - $timeStart;


// Assemble report message
if( is_array($addedGames) )
	$message .= ' ' . count($addedGames) . ' games added; '; 
if( is_array($skippedGames) )
	$message .= count($skippedGames) . ' games skipped; ';
$message .= "Spent $timeResult seconds.";

$output = "Import Results:
------------------------------------------------
$message
	
	
";
	
$gotGames = false;
if( count($addedGames) > 0 )
{
	$output .= 'Added games:
------------------------------------------------		
';
	$gotGames = true;				
	foreach( $addedGames as $game )
		$output .= "$game
";
	$output .= '
		
';
}

if( count($skippedGames) > 0 )
{
	$output .= 'Skipped games:
------------------------------------------------
';				
	$gotGames = true;				
	foreach( $skippedGames as $game )
		$output .= "$game
";
	$output .= '
		
';
}
	
if( !$gotGames )
	$output .= 'No games processed... check PAS-ERRORS.log for possible reason.';
	echo( $output );
?>