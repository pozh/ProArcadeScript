<?php
/*******************************************************************
/ ProArcadeScript
/ File version: 0.2
/ File description:
/ MochiAds game feeds reader 
/
/*******************************************************************/
require( '../include/json.php' );

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


// import games
function importMochiGames( $db, $settings, $bCron=false )
{
	// Base URI
	$baseURI = 'http://www.mochimedia.com/feeds/games/%id%/%slashes%?format=json%params%';

	$timeStart = time();
	$timeLimit = ini_get('max_execution_time');
	
	// read settings first
	if( empty($settings['publisher_id']) )
	{
		log_error("Error: Publisher ID is not specified. Can't import games. Please check Mochi feed settings in your admin cp.");
		return;
	}
	
	// assemble the feed URI 
	$feedURI = str_replace( '%id%', $settings['publisher_id'], $baseURI );
	$slashes = '';
	if( !empty($settings['category']) )
		$slashes .= $settings['category'].'/';
	if( !empty($settings['rating']) )
		$slashes .= $settings['rating'].'/';  
	$feedURI = str_replace( '%slashes%', $slashes, $feedURI );
	$params = '';
	if( !empty($settings['import_games']) )
		$params = '&limit=' . $settings['import_games'];
	if( !empty($settings['import_skip']) )
		$params .= '&offset=' . $settings['import_skip'];
	$feedURI = str_replace( '%params%', $params, $feedURI );
	
	// get the feed
	$feedText = file_get_contents( $feedURI );
	$feedDecoded = json_decode( $feedText, true );
	$games = $feedDecoded['games'];
	$gamesNumber = count($games);
	if( empty($gamesNumber) )
			return array( null, null, 'Error: the feed feed does not contain any game. '  );
	
	// get the categories existing on my server (and convert them to lowercase)
	$tempCategories = $db->super_query( 'SELECT id, latin_title FROM '.$GLOBALS['cMain']['dbPrefix'].'categories', true );
	$myCategoriesNumber = count($tempCategories);
	$myCategories = array();
	if( empty($tempCategories) )
	{
	   log_error( 'Error: can\'t get categories existing in our database' );
	   die( 'Error, see PAS-ERRORS.log for details.' );
	} 
	foreach( $tempCategories as $item )
		$myCategories[$item['id']] = strtolower( $item['latin_title'] );
		 
	
	// Process the games
	//----------------------------------------------------------------------------
	$addedGames = array();
	$skippedGames = array();
	$usedCategories = array(); // will be used later, for cache cleaning
	$i = 0;	
	$haveTime = true;
	while( ($i<$gamesNumber) && $haveTime )
	{
		$game = $games[$i];
		$name = mysql_escape_string($game['name']);
		$keywords = mysql_escape_string( implode(', ', $game['tags']) );
	 	$game_tag = $game['game_tag'];

		$skip = false;  
		if( !empty($settings['tags']) )
		{
			$tagsArray = explode( ',', $settings['tags'] );
			$intersection = array_intersect( $tagsArray, $game['tags'] );
			if( empty($intersection) )
			$skip = true;
			$skipReason = 'Filtered';
		}

		// Check for game's existence in the games table
		if( !$skip )
		{
			$existing = $db->query( 'SELECT game_tag FROM '.$GLOBALS['cMain']['dbPrefix'].'games WHERE game_tag = "'.$game_tag.'" LIMIT 1', false );
			if( $db->num_rows($existing) > 0 )
			{
				$skip = true;
				$skipReason = 'found by game_tag';
			}
		}
		if( !$skip )
		{
			$existing = $db->query( 'SELECT latin_title FROM '.$GLOBALS['cMain']['dbPrefix'].'games WHERE latin_title LIKE "'.$name.'" LIMIT 1', false );
			if( $db->num_rows($existing) > 0 )
			{
				$skip = true;
				$skipReason = 'found by title';
			}
		}

		// Proceed if the game does not exist there
		if( $skip )
		{
			$skippedGames[] = $bCron ? $game['name'].',       '.$skipReason : 
					'<span class="Cell W70">'.$game['name'] . '</span> 	<span class="Cell">'.$skipReason.'</span>';
		}
		else
		{
			$slug = $game['slug'];
			$swf_url = $game['swf_url'];
			$thumbnail_url = $game['thumbnail_url'];
	 		$large_img_url = empty( $game['thumbnail_large_url'] ) ? $game['thumbnail_url'] : $game['thumbnail_large_url'];

			// copy game file to my server
			$my_swf_url = "../content/swf/$slug.swf";
			if( $swf = fopen( $my_swf_url, "wb" ) )
		  {	
		    fwrite( $swf, file_get_contents($swf_url) );
			  fclose( $swf );
			}
			else
			{
			   log_error( 'Error: can\'t open '.$my_swf_url. 'for writing. Please check swf folder\'s attributes' );
			   die( 'Error, see PAS-ERRORS.log for details.' );
		  }
 			$my_game_url = $slug.".swf";

			// copy thumbnail to my server
			$ext = substr($thumbnail_url, -3) == 'gif' ? '.gif' : '.jpg'; 
			$my_thumbnail_url = "../content/thumbs/$slug".$ext;
			if( $thumb = fopen( $my_thumbnail_url, "wb" ) )
			{
			   fwrite( $thumb, file_get_contents($thumbnail_url) );
			   fclose( $thumb );
			}
			else
			{
			   log_error( 'Error: can\'t open '.$my_thumbnail_url. 'for writing. Please check thumbs folder\'s attributes' );
			   die( 'Error, see PAS-ERRORS.log for details.' );
		  }
 			$my_thumb_url = $slug.$ext;
		
			// also screenshot (the same image actually)
			$ext = substr($large_img_url, -3) == 'gif' ? '.gif' : '.jpg'; 
			$my_screenshot_url = "../content/screenshots/$slug".$ext;
			if( $shot = fopen( $my_screenshot_url, "wb" ) )
			{
		    fwrite( $shot, file_get_contents($thumbnail_url) );
			  fclose( $shot );
			}
			else
			{
			   mochi_log( 'Error: can\'t open '.$my_screenshot_url. 'for writing. Please check thumbs folder\'s attributes' );
			   die( 'Error, see PAS-ERRORS.log for details.' );
		  }
 			$my_screenshot_url = $slug.$ext;
		
			// The rest of game's parameters
	 		$added = time();
			$screen1_thumb = mysql_escape_string($game['$screen1_thumb']);
			$screen1_url = mysql_escape_string($game['$screen1_url']);
			$screen2_thumb = mysql_escape_string($game['$screen2_thumb']);
			$screen2_url = mysql_escape_string($game['$screen2_url']);
			$screen3_thumb = mysql_escape_string($game['$screen3_thumb']);
			$screen3_url = mysql_escape_string($game['$screen3_url']);
			$screen4_url = mysql_escape_string($game['$screen4_url']); 	
			$video_url = mysql_escape_string($game['video_url']);
			$description = mysql_escape_string($game['description']);
			$width = $game['width'];
			$height = $game['height'];
			$tempCategories = strtolower( mysql_escape_string((implode(',', $game['categories']))) );  // originally it's an array
			$categories = explode(',', $tempCategories); 			
			$leaderboards = $game['leaderboard_enabled'];
			$zip_url = $game['zip_url'];
		 	$coins_revshare_enabled = $game['coins_revshare_enabled'] ? 1 : 0;
		 	$author = mysql_escape_string($game['author']);
		 	$updated = convert_datetime($game['updated']);
		 	$coins_enabled = $game['$coins_enabled'] ? 1 : 0;  
			$leaderboard_enabled = $game['$leaderboard_enabled'] ? 1 : 0;
			$instructions = mysql_escape_string($game['$instructions']); 
	
			// find appropriate category for the game, generate latin_title
			$categoryFound = false;
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
			// if an appropriate category is still not found, ask user to manage categories
			if( $categoryFound )
				$usedCategories[] = $myCategoryTitle;
			else
			{
				$missingCategory = !empty($game['category']) ? $game['category'] : $categories[0]; 
				$usedCategories[]	= "Category '$missingCategory' is not found on the site. Please consider adding it."; 			
			}

			// insert the record into my games table.
			$query = "INSERT INTO ".$GLOBALS['cMain']['dbPrefix']."games(
					category_id,	added,			active,			title,				latin_title,	file, 				
					thumbnail, 		large_img,	keywords, 	description,	width, 				height,
					coins_revshare_enabled, 	coins_enabled, 		screen1_thumb,	screen1_url, 
					screen2_thumb, screen2_url, screen3_thumb, screen3_url, screen4_url, 
					video_url, author, game_tag, updated,	leaderboard_enabled, instructions) 
					VALUES(";
			$query .= "$myCategory, '$added', 1, '$name', '$slug', '$my_game_url', 
					'$my_thumb_url', '$my_screenshot_url', '$keywords', '$description', $width, $height,
					$coins_revshare_enabled, $coins_enabled, '$screen1_thumb',	'$screen1_url', 
					'$screen2_thumb', '$screen2_url', '$screen3_thumb', '$screen3_url', '$screen4_url', 
					'$video_url', '$author', '$game_tag', $updated,	$leaderboard_enabled, '$instructions')";  					
		
			if( !$db->query( $query, false ) )
			{
			   log_error( 'Error: can\'t insert game '.$name.' into database. Query was: '. $query . '; error returned: '.$db->last_error() );
			   die( 'Error, see PAS-ERRORS.log for details.' );
			}
			
			$addedGames[] = $bCron ? "$name   --    $myCategoryTitle" : 
					"<span class='Cell W70'>$name</span> <span class='Cell'>$myCategoryTitle</span>";
		} // else (the game is not found in my database)
		$i++;
		$haveTime = (time() - $timeStart < ($timeLimit-2));
	} // for $i < gamesNumber

	// if we've ran out of time, inform user about that.
	if( !$haveTime )
		$message = 'Sorry, can\'t process all games because have not enough time for script\'s execution. 
			Consider changing the max_execution_time parameter in your PHP.INI.';
	else
		$message = 'Done.';

 	// clear cache
 	array_unique( $usedCategories );
	$cache = new CCache();
	$cache->delete( 'home', 0, 0 );
	foreach( $usedCategories as $category )
		$cache->delete( 'cat', 0, $category );

	// the result 
	return array( $addedGames, $skippedGames, $message );
} 




function getMochiCategories( $db )
{
	// Base URI
	$baseURI = 'http://www.mochimedia.com/feeds/games/%id%/?format=json&limit=500';

	// read settings first
	$settings = $db->super_query( 'SELECT * FROM '.$GLOBALS['cMain']['dbPrefix'].'mochisettings LIMIT 1' );
	if( empty($settings['publisher_id']) )
	{
		log_error("Error: Publisher ID is not specified. Please check Mochi feed settings in your admin cp.");
		return;
	}
	
	// assemble the feed URI 
	$feedURI = str_replace( '%id%', $settings['publisher_id'], $baseURI );

	// get feed contents
	$feedText = file_get_contents( $feedURI );
	$feedDecoded = json_decode( $feedText, true );
	$games = $feedDecoded['games'];
	$gamesNumber = count($games);
	if( $gamesNumber > 0 )
	{
		// Process the games
		$categories = array();
		foreach( $games as $game )
		{
			if( !empty($game['category']) )
				$categories[] = $game['category'];
			else
			{ 					
				$feedCategories = $game['categories'];
				foreach( $feedCategories as $newCategory )
					$categories[] = $newCategory;
			}
		}			 
	  $result = array_unique($categories);
	}
	else
		$result = "No games in the feed";
  return $result;
}


?>