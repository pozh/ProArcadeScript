<?php
/********************************************************************************
/ ProArcadeScript 
/ File description:
/ admin panel
/ IN: "action" - the action admin wants to run or the page (s)he wants to access
/
/********************************************************************************/
require( "../include/config.php" );
require( "../include/helpers.php" );
require( "../include/class.FastTemplate.php" );
require( "../include/class.Database.php" );
require( "../include/class.Cache.php" );

$tpl = new FastTemplate("templates");

include( "checklogin.php" );

if( isset($_REQUEST['action']) )
{
	// Clear cache
	if( $_REQUEST['action'] == 'clear_cache' )
	{
	   $cache = new CCache();
		$cache->clear_cache();
  		header( 'Location:' . $_SERVER['HTTP_REFERER'] );
		exit();
	}
}

// Show the page
$tpl->define( "tplHeader", "header.html" );
$tpl->define( "tplFooter", "footer.html" );
$tpl->define( "tplMain", "page_home.html" );

$tpl->assign( array( 
	"SITEROOT"	=>	$cSite["sSiteRoot"],
	"TITLE"		=>	$cSite["sSiteTitle"] . " Administration - Home",
	"ATHOME"	=>	"Active",
	"SCRIPTVERSION"	=> $version
));

	
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

$res = $db->super_query( "SELECT COUNT(*) as number FROM ".$cMain["dbPrefix"]."games");
$tpl->assign( "GAMES", $res["number"] );

$res = $db->super_query( "SELECT COUNT(*) as number FROM ".$cMain["dbPrefix"]."games WHERE active=0");
$tpl->assign( "INACTIVEGAMES", $res["number"] > 0 ? "<a href=\"content.php?action=listgames&inactive=1\">".$res["number"]." inactive</a>" : $res["number"]." inactive" );

$res = $db->super_query( "SELECT SUM(plays_total) as number FROM ".$cMain["dbPrefix"]."games");
$tpl->assign( "GAMEPLAYS", $res["number"] );

$res = $db->super_query( "SELECT SUM(plays_today) as number FROM ".$cMain["dbPrefix"]."games");
$tpl->assign( "TODAYPLAYS", $res["number"] );

$res = $db->super_query( "SELECT COUNT(*) as number FROM ".$cMain["dbPrefix"]."users" );
$tpl->assign( "USERS", $res["number"] );

$res = $db->super_query( "SELECT COUNT(*) as number FROM ".$cMain["dbPrefix"]."users WHERE joined = NOW()" );
$tpl->assign( "TODAYUSERS", $res["number"] );

$res = $db->super_query( "SELECT COUNT(*) as number FROM ".$cMain["dbPrefix"]."users WHERE verified = 0" );
$tpl->assign( "PENDINGUSERS", ($res['number'] > 0) ? '(<a href="users.php?action=listusers&inactive=1">'.$res['number'].' pending verification</a>)' : '' );

$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");
