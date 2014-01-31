<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ process html file from this folder and generate a page based on it
/
/*******************************************************************/
require( "../include/config.php" );
require( "../templates/".$cSite["sTemplate"]."/config.php" );
require( "../include/class.FastTemplate.php" );
require( "../include/class.Database.php" );
require( "../include/helpers.php" );
require( "../include/class.Log.php" );
require( "../include/class.Cache.php" );

session_start();

$cPage = $_GET['page'];
if( stristr($cPage, 'http') || stristr($cPage, '/') )
	die();

// Initialise templates
$tpl = new FastTemplate("../templates/".$cSite["sTemplate"]);

// Connect to the database
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

// Write an event to the log and remove the aout of date records from it.
$log = new CLog( $db );
$log->clear_log();
$log->write_event( "doc" );

// Try to get our page from cache
$cache = new CCache();
if( $data = $cache->get() )
{
	echo $data;
	exit();
}


// Integrated (permanent) blocks
$tpl->define( "tplBHead", "block_head.html" );
$tpl->define( "tplMain", "page_custom.html" );

$tpl->assign( array(
	"SITEURL"			=> $cSite["sURL"],
	"TITLE"				=> $cSite["sSiteTitle"],
	"SITEROOT"			=> $cSite["sSiteRoot"],
	"METATITLE"			=> $cP[$cPage]["title"],
	"METADESCRIPTION"	=> $cP[$cPage]["description"],
	"METAKEYWORDS"		=> $cP[$cPage]["keywords"]
	));

// Page blocks
foreach( $cB as $id => $cBlock )
{
	if( $cBlock["custom"] )
	{
		if( $cBlock["script"] )
			include( "../content/blocks/" . $cBlock["file"] . ".php" );
		else
		{	
			$tpl->set_root( "../content/blocks" );
			$tpl->define( $cBlock["file"], $cBlock["file"].".html" );
			$tpl->parse( $id, $cBlock["file"] );
			$tpl->set_root( "../templates/".$cSite["sTemplate"] );
		}
	}
	else
		$tpl->assign( $id, "" );
}

if( substr($cPage, -3) == 'php' )
{
	// Process custom PHP script/page
	ob_start();
	include( $cPage );
	$tpl->assign( 'PAGECONTENTS', ob_get_contents() );
	ob_end_clean();
}
elseif( substr($cPage, -4) == 'html' )
{
	// Parse custom page contents
	$tpl->set_root( "../docs" );
	$tpl->define( "tplPageContents", $cPage );
	$tpl->parse( "PAGECONTENTS", "tplPageContents" );
	$tpl->set_root( "../templates/".$cSite["sTemplate"] );
}

//	parse and make the page visible
$tpl->parse( "HEAD", "tplBHead" );
$tpl->parse( "MAIN", "tplMain" );
$data = $tpl->GetText('MAIN');
$cache->write( $data );
$tpl->FastPrint( "MAIN");
?>