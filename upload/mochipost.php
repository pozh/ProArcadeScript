<?php

/*******************************************************************
/ ProArcadeScript 
/ Description:
/ MochiAds game auto post (mochiserver will call this script 
/ each time you click "auto post" on the mochiads site) 
/
/*******************************************************************/
require( "include/config.php" );
require( "include/helpers.php" );
require( "include/class.Database.php" );
require( "include/class.Cache.php" );
require( 'include/json.php' );


function mochi_log($msg) 
{
  $filename = 'mochi.log';
  $output = date('Y-m-d H:i:s') . ': ' . $msg . "\n";
 	$f = fopen( $filename, "a" );
	if( $f )
	{
		fwrite( $f, $output );
		fclose( $f );
	}
}

$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );
if( !$db )
{
	mochi_log( 'Error - can\'t init database.' );
	return;
}

// read settings, if not exist, assign zero values
$settings = $db->super_query('SELECT * FROM '.$cMain['dbPrefix'].'mochisettings LIMIT 1', false);
if( !$settings )
{
	mochi_log( 'Error - mochi reader settings not initialized' );
	return;
}
if( empty($settings['publisher_id']) )
{
	mochi_log( 'Error - publisher ID is not set.  Open admin CP -> content -> mochi games and set it up.' );
	return;
}

$publisher_id = $settings['publisher_id'];

// helper functions used by json_decode in PHP4
function object_to_array($data) 
{
  if(is_array($data) || is_object($data))
  {
    $result = array(); 
    foreach($data as $key => $value)
    { 
      $result[$key] = object_to_array($value); 
    }
    return $result;
  }
  return $data;
}

if( !function_exists('json_decode') ) {
	function json_decode($data, $isArray) 
	{
    $json = new Services_JSON();
    return( object_to_array($json->decode($data)) );
  }
}


// mysql datetime to unixtimestamp
function convert_datetime($str)
{
	list($date, $time) = explode('T', $str);
	list($year, $month, $day) = explode('-', $date);
	list($hour, $minute, $second) = explode(':', $time);
 	$timestamp = mktime($hour, $minute, $second, $month, $day, $year);
   return $timestamp;
}

mochi_log( '*** Start adding game ***');

// add missed fields in the games table
$res = $db->query( 'SELECT * FROM '.$cMain['dbPrefix'].'games LIMIT 1' );
$fields = $db->get_result_fields( $res );
$fieldNames = array();
foreach($fields as $field )
	$fieldNames[] = $field->name;
if( !in_array('coins_revshare_enabled', $fieldNames) )
{	
	$query = "ALTER TABLE ".$cMain['dbPrefix']."games 
		ADD coins_revshare_enabled INT(1) NOT NULL default 0,
		ADD coins_enabled INT(1) NOT NULL default 0,
		ADD screen1_thumb VARCHAR(255) NOT NULL default '',
		ADD screen1_url VARCHAR(255) NOT NULL default '',
		ADD screen2_thumb VARCHAR(255) NOT NULL default '',
		ADD screen2_url VARCHAR(255) NOT NULL default '',
		ADD screen3_thumb VARCHAR(255) NOT NULL default '',
		ADD screen3_url VARCHAR(255) NOT NULL default '',
		ADD screen4_url VARCHAR(255) NOT NULL default '',
		ADD video_url VARCHAR(255) NOT NULL default '',
		ADD author VARCHAR(255) NOT NULL default '',
		ADD game_tag VARCHAR(255) NOT NULL default '',
		ADD updated INT(10) NOT NULL default 0, 
		ADD leaderboard_enabled INT(1) NOT NULL default 0,
		ADD instructions TEXT NOT NULL default ''";
	$db->query( $query, false );
}                                                   

// take care about the game
if (isset($_REQUEST['game_tag'])) 
{
	mochi_log( 'game_tag found, continue' );
	$urlrequest = "http://www.mochiads.com/feeds/games/$publisher_id/".$_REQUEST['game_tag']."/?format=json";
	if( $file_content = file_get_contents($urlrequest) )
	{
		if( $gamearr = json_decode( $file_content, true ) )
		{
	  	$game = $gamearr['games'][0];
	  	addToDB( $game, $db );
	  }
		else
	     mochi_log( 'Error: json_decode have failed. Please make sure your publisher ID is correct.' );
	}
	else
		mochi_log( 'Error: can\'t get xml data. Please make sure your publisher ID is correct.' );
}
else
  mochi_log( 'Error: game_tag is not set.' );


function addToDB( $game, $db )
{
  mochi_log( 'addToDB function started,' );
	$slug = $game['slug'];
	$name = mysql_escape_string($game['name']);
	$game_tag = $game['game_tag'];  
	mochi_log( 'Game: ' . $name );

	// Check for game's existence in the games table
	$skip = false;
	$query = 'SELECT id, title, game_tag FROM '.$GLOBALS['cMain']['dbPrefix'].'games WHERE game_tag = "'.$game_tag.'" LIMIT 1';
	$existing = $db->query( $query, false );
	if( $db->num_rows($existing) > 0 )
	{
		$skip = true;
		$gamerecord = $db->get_row( $existing );
	  mochi_log( 'The game '.$gamerecord['title'].' (ID:'.$gamerecord['id'].') already exists in your database (found by its game_tag.) ' );
	}
	else
	{
		$existing = $db->query( 'SELECT latin_title FROM '.$GLOBALS['cMain']['dbPrefix'].'games WHERE latin_title LIKE "'.$name.'" LIMIT 1', false );
		if( $db->num_rows($existing) > 0 )
		{
			$skip = true;
			$gamerecord = $db->get_row( $existing );
		  mochi_log( 'The game '.$gamerecord['title'].' (ID:'.$gamerecord['id'].') already exists in your database (found by its name.) ' );
		}
	}	
	
	// Proceed if the game does not exist there
	if( !$skip )
	{
	   mochi_log( 'game does not exists in the database, let\'s add it.' );
	
	$tag = $game['game_tag'];
 	$coins_revshare_enabled = $game['coins_revshare_enabled'] ? 1 : 0;
 	$coins_enabled = $game['coins_enabled'] ? 1 : 0;
	$screen1_thumb = mysql_escape_string($game['$screen1_thumb']);
	$screen1_url = mysql_escape_string($game['$screen1_url']);
	$screen2_thumb = mysql_escape_string($game['$screen2_thumb']);
	$screen2_url = mysql_escape_string($game['$screen2_url']);
	$screen3_thumb = mysql_escape_string($game['$screen3_thumb']);
	$screen3_url = mysql_escape_string($game['$screen3_url']);
	$screen4_url = mysql_escape_string($game['$screen4_url']); 	
	$video_url = mysql_escape_string($game['video_url']);
	$author = mysql_escape_string($game['author']);
	$description = mysql_escape_string($game['description']);
	$width = $game['width'];
	$height = $game['height'];
	$thumbnail_url = $game['thumbnail_url'];
	$instructions = mysql_escape_string($game['instructions']);
	$rating = $game['rating'];
	$game_url = $game['game_url'];
	$swf_url = $game['swf_url'];
	$tempCategories = strtolower( mysql_escape_string((implode(',', $game['categories']))) );  // originally it's an array
	$categories = explode(',', $tempCategories); 			
	$keywords = mysql_escape_string(implode(', ', $game['tags']) );  // originally it's an array
	$updated = convert_datetime( $game['updated'] );  // original format sample: 2009-06-03T20:48:05-08:00
	$leaderboard_enabled = $game['leaderboard_enabled'] ? 1 : 0;
	
	// copy game file to my server
	$my_swf_url = "content/swf/".$slug.".swf";
	if( $swf = fopen( $my_swf_url, "w" ) )
  {	
    fwrite( $swf, file_get_contents($swf_url) );
	  fclose( $swf );
	}
	else
	   mochi_log( 'Error: can\t open '.$my_swf_url. 'for writing.' );

	// copy thumbnail to my server
	$my_thumbnail_url = "content/thumbs/".$slug.".gif";
	if( $thumb = fopen( $my_thumbnail_url, "w" ) )
	{
	   fwrite( $thumb, file_get_contents($thumbnail_url) );
	   fclose( $thumb );
	}
	else
	   mochi_log( 'Error: can\t open '.$my_thumbnail_url. 'for writing.' );
	
	// also screenshot (the same image actually)
	$my_screenshot_url = "content/screenshots/".$slug.".gif";
	if( $shot = fopen( $my_screenshot_url, "w" ) )
	{
    fwrite( $shot, file_get_contents($thumbnail_url) );
	  fclose( $shot );
	}
	else
	   mochi_log( 'Error: can\t open '.$my_screenshot_url. 'for writing.' );

	// filenames on my server
	$my_game_url = $slug.".swf";
	$my_thumb_url = $slug.".gif";

	$my_date_added = time();
	
	// find appropriate category for the game, generate latin_title
	$categoryFound = false;
	$tempCategories = $db->super_query( 'SELECT id, latin_title FROM '.$GLOBALS['cMain']['dbPrefix'].'categories', true );
	$myCategoriesNumber = count($tempCategories);
	$myCategories = array();
	if( empty($tempCategories) )
	{
	   mochi_log( 'Error: can\'t get categories existing in our database' );
	   die( 'Error, see mochi.log for details.' );
	} 
	foreach( $tempCategories as $item )
		$myCategories[$item['id']] = strtolower( $item['latin_title'] );
	$categoryIDs = array_keys( $myCategories );
	$myCategory = $categoryIDs[0];	// by default it'll be the first category found in my db

	if( !empty($game['category']) )
	{
		foreach( $myCategories as $key => $record )
		{
			if( $categoryFound ) continue;
			$similarity = 0;
			similar_text( $record, $game['category'], $similarity );
			if( $similarity > 75 )
			{
				$categoryFound = true;
				$myCategory = $key;						
			}
		}
	}				
	// if main category is not found, try secondary categories instead.
	if( !$categoryFound )
		foreach( $myCategories as $key => $record )
			foreach( $categories as $category )
			{
				if( $categoryFound ) continue;
				$similarity = 0;
				similar_text( $category, $record, $similarity );
				if( $similarity > 75 )
				{
					$categoryFound = true;
					$myCategory = $key;						
				}
			}
	$myCategoryTitle = $myCategories[$myCategory];			

	$latin_title = Normalize( $name );
	if( empty($keywords) )
	   $keywords = substr( $description, 0, 255 );

	// insert the record into my games table.
	$query = "INSERT INTO ".$GLOBALS['cMain']['dbPrefix']."games(category_id, added, active, title, latin_title, "
	. "file, thumbnail, large_img, keywords, description, width, height, coins_revshare_enabled, coins_enabled, screen1_thumb,
		screen1_url, screen2_thumb, screen2_url, screen3_thumb, screen3_url, screen4_url, video_url, author, game_tag, updated, 
		leaderboard_enabled, instructions) VALUES( ";
	$query .= "$myCategory, '$my_date_added', 1, '$name', '$latin_title', '$my_game_url', '$my_thumb_url', '$my_thumb_url', "
	. "'$keywords', '$description', $width, $height, $coins_revshare_enabled, $coins_enabled, '$screen1_thumb', '$screen1_url', 
		'$screen2_thumb', '$screen2_url', '$screen3_thumb', '$screen3_url', '$screen4_url', '$video_url', '$author', '$game_tag', 
		$updated, $leaderboard_enabled, '$instructions')";

	if( !$db->query( $query, false ) )
	   mochi_log( 'Error: can\'t insert game into database. Query was: '. $query . '; error returned: '.$db->last_error() );
	
 	// clear cache
 	mochi_log( 'Clear cache' );
	$cache = new CCache();
	$cache->delete( 'home', 0, 0 );
	$cache->delete( 'cat', 0, $myCategoryTitle );
	mochi_log( 'All done' );
	}  // if not exists
}
?>
