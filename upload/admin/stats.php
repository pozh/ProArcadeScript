<?php
/*******************************************************************
/ ProArcadeScript 
/ File description: show the stats page and process related commands
/ 
/*******************************************************************/
require_once( "../include/config.php" );
require_once( "../include/helpers.php" );
require_once( "../include/class.FastTemplate.php" );
require_once( "../include/class.Database.php" );

$cStatsHeight = 200;
$cStatsMinHeight = 20;

$tpl = new FastTemplate("templates");

require( "checklogin.php" );

$tpl->define( array(
	"tplHeader"	=> "header.html",
	"tplMain"	=> "page_stats.html",
	"tplFooter"	=> "footer.html"
));
$tpl->assign( array( 
	"SITEROOT"	=> $cSite["sSiteRoot"],
	"TITLE"		=> $cSite["sSiteTitle"] . " Administration - Site Statistics",
	"SITETITLE"	=> $cSite["sSiteTitle"],
	"ATSTATS"	=> "Active",
	"MESSAGE"	=> "",
	"YEAR"		=> $nYear
));

$tpl->assign( "SUBMONTHLY", "Active" );

// setup month links
$tpl->define_dynamic( "dynMonths", "tplMain" );
$nMonth = empty($_REQUEST["m"]) ? (int)date("m",mktime()) : $_REQUEST["m"];
for( $i=0; $i<12; $i++ )
{
	$tpl->assign( array(
		"MONTH"			=> ($i+1),
		"MONTHACTIVE"	=> ($i+1)==$nMonth ? "PageActive" : "",
		"MONTHNAME"		=> date( "M", mktime(0,0,0,($i+1), 1, 2000) )
	));
	$tpl->parse( "MONTHS", ".dynMonths" );
}

// year links
$nYear = empty($_REQUEST["y"]) ? (int)date("Y",mktime()) : $_REQUEST["y"];
$tpl->assign( "YEARPREV", $nYear-1 );
$tpl->assign( "YEARNEXT", $nYear+1 );

// process the stats data
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );
$nFirstDate = MyDate( mktime(0, 0, 0, $nMonth, 1,   $nYear) );
$nLastDate = MyDate( mktime(0, 0, 0, $nMonth+1, 1,   $nYear) );
$nDates = $nLastDate - $nFirstDate;
$tpl->define_dynamic( "dynBar", "tplMain" );
	
$resmax = $db->super_query( "SELECT MAX(plays) AS plays FROM " . $cMain["dbPrefix"]."stats WHERE date>=$nFirstDate AND date < $nLastDate LIMIT $nDates", false );
$res = $db->super_query( "SELECT * FROM " . $cMain["dbPrefix"]."stats WHERE date>=$nFirstDate AND date < $nLastDate LIMIT $nDates", true );
$nHFactor = $resmax["plays"] > 0 ? $cStatsHeight / $resmax["plays"] : 1;
$nLeft = 10;
if( count($res) > 0 )
{
	$day = 1;
	foreach( $res as $record )
	{
		while( $nFirstDate+$day-1 < $record["date"] )
		{
			$nLeft += 24;
			$day ++;
		}
		$nHeight = round( $nHFactor * $record["plays"] );
		$tpl->assign( array( 
			"BARHEIGHT"	=> $nHeight,
			"BARLEFT"	=> $nLeft,
			"BARDISPLAY"=> $record["plays"] > 0 ? "block" : "none",
			"BARVALUE"	=> $record["plays"]
		));
		if( $record["plays"] > 999 )
			$tpl->assign( "BARFONT", 8 );
		else
			$tpl->assign( "BARFONT", $nHeight > 20 ? 9 : $nHeight/2 );
		$tpl->parse( "MAIN", ".dynBar" );
	}	
}
else
{
	$tpl->assign( "MESSAGE", $cLang["msgANoDataForPeriod"] );
	$tpl->clear_dynamic( "dynBar" );
}
	
$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");

?>