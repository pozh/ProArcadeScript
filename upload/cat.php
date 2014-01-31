<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ Generate and show main page for the given category (arcades, etc.)
/ IN: id - category ID
/
/*******************************************************************/
require_once( "include/config.php" );
require_once( "templates/".$cSite["sTemplate"]."/config.php" );
require_once( "include/helpers.php" );
require_once( "include/class.FastTemplate.php" );
require_once( "include/class.Database.php" );
require_once( "include/class.Log.php" );
require( "include/class.Cache.php" );

session_start();

// Initialise templates
$tpl = new FastTemplate("templates/".$cSite["sTemplate"]);

// Connect to the database
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

// Write an event to the log 
$log = new CLog( $db );
$log->write_event( "category" );

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
$tpl->define(array(	"tplBHead"	=> "block_head.html" ));

$tpl->assign( array( 
	"SITEROOT"	=> $cSite["sSiteRoot"],
	"TITLE"		=> $cSite["sSiteTitle"],
	"SITEURL"	=> $cSite["sURL"]
));

// Read category-related information
$query = 'SELECT * FROM ' . $cMain['dbPrefix'] . 'categories WHERE latin_title = "'	. $_REQUEST['cat'] . '" LIMIT 1';
$res = $db->super_query( $query, false );
if( !empty($res) )
{
	$tpl->define( "tplMain", "page_category.html" );
	$tpl->assign( array(
		"METATITLE"			=> $res["title"] . " - " . $cSite["sSiteTitle"],
		"METADESCRIPTION"	=> $res["description"],
		"METAKEYWORDS"		=> $res["keywords"]
	));
	$nCategory = $res['id'];
	$sCategory = $res['title'];
}
else
{
	header( "Location:".$cSite['sSiteRoot'] );
}

// Page blocks
foreach( $cB as $id => $cBlock )
{
	if( $cBlock["cat"] )
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