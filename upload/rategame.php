<?php
/*******************************************************************
/ File version: 1.3.1
/ File description:
/ Process the new game rating added by a visitor
/ IN: id - game ID
/ IN: rate - game rating (1-5)
/
/*******************************************************************/
require_once( "include/config.php" );
require_once( "templates/".$cSite["sTemplate"]."/config.php" );
require_once( "include/helpers.php" );
require_once( "include/class.Database.php" );
require_once( "include/class.Log.php" );

session_start();

if( !(($_REQUEST['rate']>0) && ($_REQUEST['id']>0) && ($_REQUEST['rate']<6)) )
{
	print "Error: wrong ID or rating value";
	return;
}

// Connect to the database
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

$res = $db->query( "SELECT * FROM " . $cMain['dbPrefix'] . "log WHERE ip='" . $_SERVER['REMOTE_ADDR'] . "' AND game_id=" . $_REQUEST["id"] . " AND action='rate' LIMIT 1" );
if( $db->num_rows($res) == 0 )
{
	$db->query( "UPDATE " . $cMain["dbPrefix"] . 
		"games SET votes=votes+1, votes_value=votes_value+" . 
		$_REQUEST['rate']*100 . ", rating=votes_value/votes WHERE id=" . 
		$_REQUEST["id"] . " LIMIT 1" );
	$log = new CLog( $db );
	$log->write_event( "rate", $_REQUEST['id'] );
	print $cLang['msgVoteThanks'];
}
else
	print $cLang['errVoteCheat'];
?>