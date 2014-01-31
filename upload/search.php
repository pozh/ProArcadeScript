<?php
/*******************************************************************
/ ProArcadeScript
/ File version: 1.2 
/ File description:
/ shows the search results
/
/*******************************************************************/
require_once( "include/config.php" );
require_once( "templates/".$cSite["sTemplate"]."/config.php" );
require_once( "include/class.FastTemplate.php" );
require_once( "include/class.Database.php" );
require_once( "include/helpers.php" );
require_once( "include/class.Log.php" );

session_start();

// Initialise templates
$tpl = new FastTemplate("templates/".$cSite["sTemplate"]);

// Connect to the database
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

// Write an event to the log and remove the out of date records from it.
$log = new CLog( $db );
$log->write_event( "search" );

// Integrated (permanent) blocks
$tpl->define( "tplBHead", "block_head.html" );
$tpl->define( "tplMain", "page_custom.html" );
$tpl->define( "tplSearchResults", "block_searchresults.html" );

$tpl->assign( array(
	"SITEURL"			=> $cSite["sURL"],
	"SITEROOT"			=> $cSite["sSiteRoot"],
	"METATITLE"			=> $cSite["sSiteTitle"],
	"METADESCRIPTION"	=> $cSite["sSiteDesc"],
	"METAKEYWORDS"		=> $cSite["sSiteKeywords"],
	"TITLE"				=> $cSite["sSiteTitle"]
	));

// Page blocks
foreach( $cB as $id => $cBlock )
{
	if( $cBlock["front"] )
	{
		if( $cBlock["script"] )
			include( "content/blocks/" . $cBlock["file"] . ".php" );
		else
		{	
			$tpl->set_root( "content/blocks" );
			$tpl->define( $cBlock["file"], $cBlock["file"].".html" );
			$tpl->parse( $id, $cBlock["file"] );
			$tpl->set_root( "templates/".$cSite["sTemplate"] );
		}
	}
	else
		$tpl->assign( $id, "" );
}
$searchstr = mysql_escape_string(strip_tags($_REQUEST['searchstr']));
$query = "SELECT * FROM " . $cMain['dbPrefix'] . "games WHERE title LIKE '%$searchstr%' OR MATCH(description) AGAINST ('$searchstr') OR keywords LIKE '%$searchstr%' LIMIT ".$cB["SEARCH"]["max"];
$arGames = $db->super_query( $query, true );
if( count($arGames) > 0 )
{
	// Something was found
	$tpl->define_dynamic( 'dynSearchRes', 'tplSearchResults' );
	foreach( $arGames as $game )
	{
		$tpl->assign( array(
		 	'GAMEURL'					=>	GameURL($game['id'], $game['latin_title']),
			'GAMETITLE'				=>	$game['title'],
			'GAMEDESCRIPTION'	=>	$game['description'],
			'GAMETHUMB'				=>	$game['thumbnail'],
			'GAMERATING' 			=> 	StarImg( $game['votes_value'], $game['rating'] ),
			'GAMEPLAYS' 			=> 	$game['plays_total']
		));
		$tpl->parse( "SEARCHRESULTS", ".dynSearchRes" );
	}
	$tpl->assign( 'MESSAGE', '' );
}
else
{
	// Nothing was found
	$tpl->define_dynamic( 'dynSearchRes', 'tplSearchResults' );
	$tpl->clear_dynamic( 'dynSearchRes' );
	$tpl->assign( 'MESSAGE', str_replace('!QUERY!',$searchstr, $cLang['msgNothingFound']) );
}

// Parse search results
$tpl->parse( "PAGECONTENTS", "tplSearchResults" );

//	parse and make the page visible
$tpl->parse( "HEAD", "tplBHead" );
$tpl->parse( "MAIN", "tplMain" );
$tpl->FastPrint( "MAIN");
?>