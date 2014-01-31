<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ process html file from this folder and generate a page based on it
/
/*******************************************************************/
require_once( "include/config.php" );
require_once( "templates/".$cSite["sTemplate"]."/config.php" );
require_once( "include/class.FastTemplate.php" );
require_once( "include/class.Database.php" );
require_once( "include/helpers.php" );
require_once( "include/class.Log.php" );

session_start();

$cNewsID = $_REQUEST['id'];

// Initialise templates
$tpl = new FastTemplate("templates/".$cSite["sTemplate"]);

// Connect to the database and read the news stuff
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );
$news = $db->super_query( "SELECT * FROM ".$cMain["dbPrefix"]."news WHERE id=$cNewsID LIMIT 1" );

// Write an event to the log and remove the aout of date records from it.
$log = new CLog( $db );
$log->clear_log();
$log->write_event( "news" );

// Integrated (permanent) blocks
$tpl->define( "tplBHead", "block_head.html" );
$tpl->define( "tplMain", "page_custom.html" );
$tpl->define( "tplPageContents", "block_newsfull.html" );

$tpl->assign( array(
	"TITLE"				=> $cSite["sSiteTitle"],
	"SITEURL"			=> $cSite["sURL"],
	"SITEROOT"			=> $cSite["sSiteRoot"],
	"METATITLE"			=> $news["title"],
	"METADESCRIPTION"	=> $news["summary"],
	"METAKEYWORDS"		=> $news["summary"],
	"FULLNEWSTITLE"		=> $news["title"],	
	"FULLNEWSTEXT"		=> nl2br($news["text"])
	));

// Page blocks
foreach( $cB as $id => $cBlock )
{
	if( $cBlock["custom"] )
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


//	parse and make the page visible
$tpl->parse( "PAGECONTENTS", "tplPageContents" );
$tpl->parse( "HEAD", "tplBHead" );
$tpl->parse( "MAIN", "tplMain" );
$tpl->FastPrint( "MAIN");
?>