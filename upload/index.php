<?php
/*******************************************************************
/ File description:
/ main page visualisation
/
/*******************************************************************/
require_once( "include/config.php" );
require_once( "templates/".$cSite["sTemplate"]."/config.php" );
require_once( "include/class.FastTemplate.php" );
require_once( "include/class.Database.php" );
require_once( "include/helpers.php" );
require_once( "include/class.Log.php" );
require( "include/class.Cache.php" );

session_start();

// Initialise templates
$tpl = new FastTemplate("templates/".$cSite["sTemplate"]);

// Connect to the database
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

// Check the date of the last cron't execution and execute the cron tasks if necessary
if( Today() != $cLastRun )
	RunCron( $db );

// Write an event to the log and remove the aout of date records from it.
$log = new CLog( $db );
$log->clear_log();
$log->write_event( "index" );

// Try to get our page from cache
$cache = new CCache();
if( $data = $cache->get() )
{
	echo $data;
	exit();
}
else
{
// Integrated (permanent) blocks
$tpl->define( "tplBHead", "block_head.html" );
$tpl->define( "tplMain", "page_index.html" );

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

//	parse and make the page visible
$tpl->parse( "HEAD", "tplBHead" );
$tpl->parse( "MAIN", "tplMain" );
$data = $tpl->GetText('MAIN');
$cache->write( $data );
$tpl->FastPrint( "MAIN");
} // no copy in cache
?>