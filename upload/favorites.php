<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ Add/Remove game in the player's favorites and returns to the 
/ page where user was previously
/ IN: id - the game's id; action = remove to remove
/
/*******************************************************************/
require_once( "include/config.php" );
require_once( "include/helpers.php" );
require_once( "include/class.Database.php" );
require_once( "include/class.Log.php" );

session_start();

if ( isset($_SESSION["user"]) && !empty($_SESSION["user"]) )
{
	// Connect to the database
	$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

	// Write an event to the log 
	$log = new CLog( $db );
	
	switch( $_REQUEST['action'] )
	{
		case 'remove':
			$db->query( "DELETE FROM " . $cMain["dbPrefix"] . "favorites WHERE game_id=".$_REQUEST['id']." AND user_id=".$_SESSION["user"]." LIMIT 1", false );
			$log->write_event( "rem fav", $_REQUEST['id'] );
			break;
		default:
			$log->write_event( "add fav", $_REQUEST['id'] );
			// Find the game's record in DB
			$res = $db->query( "SELECT * FROM " . $cMain["dbPrefix"] . "games WHERE id=".$_REQUEST['id']." LIMIT 1", false );
			if( $db->num_rows($res) > 0 )
			{
				$res = $db->query( "SELECT * FROM " . $cMain["dbPrefix"] . "favorites WHERE game_id=".$_REQUEST['id']." AND user_id=".$_SESSION["user"]." LIMIT 1", false );
				if( $db->num_rows($res) < 1 )
					$db->query( "INSERT LOW_PRIORITY INTO " . $cMain["dbPrefix"] . "favorites VALUES(".$_SESSION["user"].", ".$_REQUEST['id'].")" );
			}
			break;
	}

}	
header( "Location:" . $_SERVER['HTTP_REFERER'] );
?>