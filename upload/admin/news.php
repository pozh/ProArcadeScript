<?php
/*******************************************************************
/ ProArcadeScript 
/ File description: show the stats page and process related commands
/ 
/*******************************************************************/
require( "../include/config.php" );
require( "../include/helpers.php" );
require( "../include/class.FastTemplate.php" );
require( "../include/class.Database.php" );
require( "../include/class.Cache.php" );

$tpl = new FastTemplate("templates");

require( "checklogin.php" );

$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );
$cache = new CCache();

$tpl->define( array(
	"tplHeader"	=> "header.html",
	"tplMain"	=> "page_news.html",
	"tplFooter"	=> "footer.html"
));

$tpl->assign( array( 
	"SITEROOT"	=> $cSite["sSiteRoot"],
	"TITLE"		=> $cSite["sSiteTitle"] . " Administration - Manage News",
	"SITETITLE"	=> $cSite["sSiteTitle"],
	"ATNEWS"	=> "Active"
));

switch( $_REQUEST["action"] )
{
//-----------------------------------------------------------------------------
// Add news
//-----------------------------------------------------------------------------
	case "add":
		$sTitle = get_magic_quotes_gpc() ? $_POST["title"] : mysql_escape_string($_POST["title"]);
		$sText = get_magic_quotes_gpc() ? $_POST["text"] : mysql_escape_string($_POST["text"]);
		$sSummary = get_magic_quotes_gpc() ? $_POST["summary"] : mysql_escape_string($_POST["summary"]);
		$active = empty($_POST["active"]) ? 0 : 1;
		$tpl->assign( array( 
			"TITLEERROR"	=>	empty($sTitle) ? $cLang["errAEmpty"] : "",
			"TEXTERROR"		=>	empty($sText) ? $cLang["errAEmpty"] : "",
			"NEWSTITLE"		=>	$sTitle,
			"NEWSSUMMARY"	=>	$sSummary,
			"NEWSTEXT"		=>	$sText,
			"FORMTITLE"		=>	"Add News",
			"ACTIVECHECKED"	=>	($active) ? "Checked" : ""
		));
		if( !empty($_POST["text"]) && !empty($_POST["title"]) )
		{
			$db->query( "INSERT INTO ".$cMain["dbPrefix"]."news (title, summary, text, date, active) VALUES ('$sTitle', '$sSummary', '$sText', ".time().", $active)" );

			// clear home page cache to show the news there
			$cache->delete( 'home', 0, 0 );

			header( "Location:" . $_SERVER['HTTP_REFERER'] );
		}
		else
			$tpl->assign( "ACTION", "add" );
		break;		

//-----------------------------------------------------------------------------
// Activate / Deactivate the news item
//-----------------------------------------------------------------------------
	case "switchstate":
		$db->query( "UPDATE ".$cMain["dbPrefix"]."news SET active=MOD(active+1, 2) WHERE id=".$_REQUEST["id"]." LIMIT 1" );
		// clear home page cache to show the news there
		$cache->delete( 'home', 0, 0 );
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;

//-----------------------------------------------------------------------------
// Delete news item
//-----------------------------------------------------------------------------
	case "delete":
		$db->query( "DELETE FROM ".$cMain["dbPrefix"]."news WHERE id=".$_REQUEST["id"]." LIMIT 1" );
		// clear home page cache to show the news there
		$cache->delete( 'home', 0, 0 );
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;

//-----------------------------------------------------------------------------
// Edit news item
//-----------------------------------------------------------------------------
	case "doedit":
		$tpl->assign( array( 
			"TEXTERROR"		=>	empty($_POST["text"]) ? $cLang["errAEmpty"] : "",
			"TITLEERROR"	=>	empty($_POST["title"]) ? $cLang["errAEmpty"] : "",
			"NEWSTEXT"		=>	$_POST["text"],
			"NEWSSUMMARY"	=>	$_POST["summary"],
			"NEWSTITLE"		=>	$_POST["title"],
			"FORMTITLE"		=>	"Edit News",
			"ACTIVECHECKED"	=>	($_POST["text"] == 1) ? "Checked" : ""
		));
		if( !empty($_POST["text"]) && !empty($_POST["title"]) )
		{
			$active = empty($_POST["active"]) ? 0 : 1;
			$sTitle = get_magic_quotes_gpc() ? $_POST["title"] : mysql_escape_string($_POST["title"]);
			$sText = get_magic_quotes_gpc() ? $_POST["text"] : mysql_escape_string($_POST["text"]);
			$sSummary = get_magic_quotes_gpc() ? $_POST["summary"] : mysql_escape_string($_POST["summary"]);
			$db->query( "UPDATE ".$cMain["dbPrefix"]."news SET title='". $sTitle .
			"', summary='$sSummary' ,text='$sText', active=$active WHERE id=".$_POST["id"]." LIMIT 1" );
			// clear home page cache to show the news there
			$cache->delete( 'home', 0, 0 );
			header( "Location: news.php" );
		}
		{
			$tpl->assign( "ACTION", "edit" );
			$tpl->assign( "EDITID", $_POST["id"] );
		}
		break;		

//-----------------------------------------------------------------------------
// List news
//-----------------------------------------------------------------------------
	case "edit":
	case "list":
	default:
		if( $_REQUEST["action"]=="edit" )
		{
			$newsrecord = $db->super_query( "SELECT * FROM " . $cMain["dbPrefix"] . "news WHERE id=".$_REQUEST["id"]." LIMIT 1", false );
			$sText = $newsrecord["text"];
			$sTitle = $newsrecord["title"];
			$sSummary = $newsrecord["summary"];
			$bActive = $newsrecord["active"];
			$tpl->assign( "EDITID", $_REQUEST["id"] );
			$tpl->assign( "FORMTITLE", "Edit News" );
		}
		else
		{
			$sText = "";
			$sTitle = "";
			$sSummary = "";
			$bActive = 1;
			$tpl->assign( "EDITID", 0 );
			$tpl->assign( "FORMTITLE", "Add News" );
		}
		$tpl->assign( array( 
			"NEWSTITLE"		=>	$sTitle,
			"TITLEERROR"	=>	"",
			"TEXTERROR"		=>	"",
			"NEWSSUMMARY"	=>	$sSummary,
			"NEWSTEXT"		=>	$sText,
			"ACTIVECHECKED"	=>	$bActive ? "Checked" : "",
			"ACTION"		=>	($_REQUEST["action"]=="edit") ? "doedit" : "add"
		));
		break;
}

//-------------------------------------------------------------------------------------------------
// List existing news
//-------------------------------------------------------------------------------------------------
$tpl->define_dynamic( "dynList", "tplMain" );
$res = $db->super_query( "SELECT * FROM " . $cMain["dbPrefix"] . "news ORDER BY date DESC", true );
if( count($res) > 0 )
	foreach( $res as $news )
	{
		$tpl->assign( array(
			"DATE"		=>	date("F d, Y", $news["date"]),
			"TEXT"		=>	$news["title"],
			"STATE"		=>	$news["active"] ? "deactivate" : "activate",
			"NEWSID"	=>	$news["id"]
		));
		$tpl->parse( "LIST", ".dynList" );
	}
else
	$tpl->clear_dynamic( "dynList" );

$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");

?>