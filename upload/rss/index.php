<?php
/*******************************************************************
/ ProArcadeScript 
/ File version 1.1, released Sept.21, 2009
/ File description:
/ Generate Media RSS feed containing only most recent games.
/
/*******************************************************************/
require_once( "../include/config.php" );
require_once( "../include/class.FastTemplate.php" );
require_once( "../include/class.Database.php" );
require_once( "../include/helpers.php" );

// Set the limit
$cMaxNews = 100;

// Connect to the database
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

// Initialise templates
$tpl = new FastTemplate("../templates");
$tpl->define( "tplMain", "rss.tpl" );
$tpl->define_dynamic( "dynItem", "tplMain" );

$tpl->assign( array(
	"SITEURL"			=> $cSite["sURL"],
	"SITEROOT"		=> $cSite["sSiteRoot"],
	"TITLE"				=> $cSite["sSiteTitle"],
	'SITEDESCRIPTION'	=>	$cSite['sSiteDesc']
	));

$query = "SELECT * FROM " . $cMain['dbPrefix'] . "games WHERE active=1 ORDER BY added DESC LIMIT $cMaxNews";
$res = $db->super_query( $query, true );

if( !is_array($res) )
	return;

// assign the most recent game's date of addition to the "lastBuildDate" parameter.
$tpl->assign( 'DATE', date("D, d M Y H:i:s T", $res[0]['added']) );

$thumbWidth = 100;
$thumbHeight = 100;

foreach( $res as $item )
{
	$arThumbInfo = @getimagesize( "../content/thumbs/".$item['thumbnail'] );
	if( is_array($arThumbInfo) )
	{
		$thumbWidth = $arGameInfo[0];
		$thumbHeight = $arGameInfo[1];
	}
	// hosted swf or embedded?
	$embedded = ( stristr($item['file'], "<") || stristr($item['file'], "http:") );
	
	if( !$embedded )	
		$swfFileSize = filesize( "../content/swf/".$item['file'] );

	$tpl->assign( array(
		'GAMETITLE'		=>	htmlspecialchars( $item['title'] ),
		'GAMEURL'			=>	$cSite["sURL"] . GameURL($item['id'], $item['latin_title']),
		'GAMEDESCRIPTION'	=>	htmlspecialchars( $item['description'] ),
		'SWF'					=>	$embedded ? $item['file'] : $cSite['sURL'].$cSite['sSiteRoot'].'content/swf/'.$item['file'],
		'FILESIZE'		=>	$embedded ? '' : 'fileSize="'.$swfFileSize.'"',	
		'GAMEWIDTH'		=>  $item['width'],
		'GAMEHEIGHT'	=>	$item['height'],
		'THUMB'				=>	$cSite['sURL'].$cSite['sSiteRoot'].'content/thumbs/'.$item['thumbnail'],
		'THUMBWIDTH'	=>	$thumbWidth,
		'THUMBHEIGHT' =>  $thumbHeight,
	));
	$tpl->parse( 'MAIN', '.dynItem' );
}
//	parse and output the content
$tpl->parse( "MAIN", "tplMain" );
$sText = $tpl->GetText( "MAIN" );
echo( $sText );
?>