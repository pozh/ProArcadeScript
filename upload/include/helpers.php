<?php
/*******************************************************************
/ ProArcadeScript
/ File description:
/ All helper functions are here
/
/*******************************************************************/

//-------------------------------------------------------------------
// remplace Euro chars with the Latin equiv
//-------------------------------------------------------------------
function Normalize( $str='' )
{
   $result = @html_entity_decode( $str, ENT_NOQUOTES, 'UTF-8');
   if( !$result ) $result = $str;
	$replace = array('à','À','á','Á','â','Â','ã','Ã','ä','Ä','å','Å','æ',
		'Æ','ç','Ç','è','È','é','É','ê','Ê','ë','Ë','ì','Ì','í','Í','î','Î','ï','Ï','ñ',
		'Ñ','ò','Ò','ó','Ó','ô','Ô','õ','Õ','ö','Ö','ø','Ø','ù','Ù','ü','Ü','ú','Ú','ÿ', ' ');
	$by = array('a','A','a','A','a','A','a','A','a','A','a','A','a',
		'A','c','C','e','E','e','E','e','E','e','E','i','I','i','I','i','I','i','I','n',
		'N','o','O','o','O','o','O','o','O','o','O','o','O','u','U','u','U','u','U','y', '_');
	$result =  str_replace( $replace, $by, $result );

	// Replace any other crappy chars with nothing
	$terrible_chars = array('$', '?', '*', '+', '(', ')', '&', '^', '%', '#', '@', '!', '~', ';', '"', ',', '}', '{', '`', '|', '<', '>', '/', '\'' );
	return str_replace($terrible_chars, '', $result);
}

//-------------------------------------------------------------------
// Generate the "play game" url
//-------------------------------------------------------------------
function GameURL($_id, $_title)
{
	if( $GLOBALS["cSite"]["bSeo"] )
		return $GLOBALS["cSite"]["sSiteRoot"] . "play/" . $_title . "/";
	else
		return $GLOBALS["cSite"]["sSiteRoot"] . "game.php?id=" . $_id;
}

//-------------------------------------------------------------------
// Generate a category URL depending on the current seo settings
//-------------------------------------------------------------------
function CategoryURL( $_link )
{
	if( $GLOBALS["cSite"]["bSeo"] )
		return $GLOBALS["cSite"]["sSiteRoot"] . "category/" . $_link . "/";
	else
		return $GLOBALS["cSite"]["sSiteRoot"] . "cat.php?cat=" . $_link;
}

//-------------------------------------------------------------------
// Returns the current day
//-------------------------------------------------------------------
function Today() 
{ 
   return floor ( time() / 86400 ); 
} 

//-------------------------------------------------------------------
// Converts time to days
//-------------------------------------------------------------------
function MyDate( $time )
{
	return floor( $time / 86400 ); 
}

//-------------------------------------------------------------------
// Converts days back to time
//-------------------------------------------------------------------
function MyTime( $days )
{
	return $days * 86400;
}

//-------------------------------------------------------------------
// create a random string of the specified length
//-------------------------------------------------------------------
function RandomStr( $length )
{
	$newpass = '';
	// Init random num generator
 	mt_srand ((double) microtime() * 1000000);

	while($length > 0)
	{
		// Create a new password using numbers and upper case letters ASCII 48-57
    	// and 65 to 90 I have no idea how this will affect non ASCII users
    	$newchar = mt_rand(48, 90);
    	if( ($newchar > 57) && ($newchar < 65) )
    		continue;
		$newpass .= sprintf("%c",$newchar);
		$length--;
	}
	return( $newpass );
}

//-------------------------------------------------------------------
// return a random number which consists of $digits digits.
//-------------------------------------------------------------------
function RandomCode( $digits = 9 )
{
	for ($i=0; $i<$digits; $i++)
		$result = $result . rand(0,9);
	return $result;
}

//-------------------------------------------------------------------
// Calculate user's status (beginner, veteran, etc.) from his/her rating
//-------------------------------------------------------------------
function GetStatus( $rating )
{
	if( $rating > 4000 )
	   return 5;
	elseif( $rating > 2000 )
	   return 4;
	elseif( $rating > 900 )
	   return 3;
	elseif( $rating > 300 )
	   return 2;
	elseif( $rating > 100 )
	   return 1;
	else return 0;
}

//-------------------------------------------------------------------
// Check email address
//-------------------------------------------------------------------
function EmailValid( $email )
{
   return ( strlen($email) <= 50 )
		&& preg_match( '/^(([^<>()[\]\\.,;:\s@"\']+(\.[^<>()[\]\\.,;:\s@"\']+)*)|("[^"\']+"))@((\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\])|(([a-zA-Z\d\-]+\.)+[a-zA-Z]{2,}))$/', $email );
}

//-------------------------------------------------------------------
// Check login/password strings
//-------------------------------------------------------------------
function NameValid( $str )
{
  	return ( strlen($str) <= $GLOBALS['cUser']['maxNameLength'] )
	  && preg_match( '/^[a-zA-Z][a-zA-Z0-9_-]*$/', $str );
}

function PasswordValid( $str )
{
   return preg_match( '/^[a-zA-Z0-9_-]*$/', $str );
}

//-------------------------------------------------------------------
// Daily cron tasks
//-------------------------------------------------------------------
function RunCron($db)
{
	// find out number of plays today first
	$plays = $db->super_query( "SELECT SUM(plays_today) as sum FROM ".$GLOBALS["cMain"]["dbPrefix"]."games", false );
	$nPlays = empty($plays['sum']) ? 0 : $plays['sum'];
	$joined = $db->super_query( "SELECT COUNT(joined) as sum FROM ".$GLOBALS["cMain"]["dbPrefix"]."users WHERE joined=".(Today()-1), false );
	$nPlays = empty($plays['sum']) ? 0 : $plays['sum'];
	$nJoined = empty($joined['sum']) ? 0 : $joined['sum'];
	
	$db->query( "UPDATE ".$GLOBALS["cMain"]["dbPrefix"]."games SET plays_today=0" );

	// delete obsolete unverified users
	$query = 'DELETE FROM '.$GLOBALS['cMain']['dbPrefix']
		.'users WHERE verified=0 AND joined<'.(time()-$GLOBALS['cUser']['daysUnverified']*86400);
	$db->query( $query );
	$fCron = fopen( "include/cron.php", "w" );
	if( $fCron )
	{
		fwrite( $fCron, '<?php $cLastRun = ' . Today() . '; ?' . '>' );
		$query = "INSERT INTO ".$GLOBALS["cMain"]["dbPrefix"]."stats SET date=".(Today()-1).", plays=$nPlays".", new_users=$nJoined";
		$db->query( $query, false );
	}
}

//-------------------------------------------------------------------
// Generate the image name (star00 - star50) using the given data
//-------------------------------------------------------------------
function StarImg( $votes, $rating )
{
	if( $votes == 0 )
		return "starna";
	else
	{	
		$r = floor( round( $rating/50 ) * 5 );
		return "star".$r;
	}
}

function log_error($error, $stopScript=false)
{
	error_log( "\n$error", 3, "PAS-ERRORS.log" );
	if( $stopScript )
		exit();
}

//----------------------------------------------------------
// Recursively Remove directories
//----------------------------------------------------------
function delete($path = null)
{
	if( substr($path, -1) != '/' )
	   $path .= '/';
	if (is_dir($path) === true)
	{
		$files = glob($path . "*");
		if (is_array($files))
		{
			foreach ($files as $file)
			{
				if (preg_match("/(\.|\.\.)$/", $file))
					continue;
				if (is_file($file) === true)
					@unlink($file);
				elseif (is_dir($file) === true)
				{
					if( delete($file) === false )
						return false;
     			}
			}
		}
		$path = substr($path, 0, strlen($path) - 1);
		if (rmdir($path) === false)
			return false;
	}
	return true;
}

?>