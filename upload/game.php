<?php
/*******************************************************************
/ File description:
/ Generate and show html page for the given game
/ IN: id - the game's id
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
$log = new CLog( $db );
$cache = new CCache();

// Find the game's record in DB
$gameID = 0;
$query = 'SELECT * FROM ' . $cMain['dbPrefix'] . 'games WHERE ';
if( $cSite['bSeo'] )
	$query .= 'latin_title = "' . $_GET['game'] . '" AND active=1 LIMIT 1';
else
	$query .= 'id='.$_GET['id'] . ' AND active=1 LIMIT 1';
$gameres = $db->super_query( $query, false );
if( !empty($gameres) )
	$gameID = $gameres['id'];

// if the game is found in the database and is active, update what's should be updated and show the content
if( $gameID != 0 )
{
	$db->query( 'UPDATE LOW_PRIORITY ' . $cMain['dbPrefix'] . 'games SET plays_total=plays_total+1, plays_today=plays_today+1 WHERE id='.$gameID.' AND active=1 LIMIT 1' );
	if( !empty($_SESSION['user']) )
	{
		// Prevent possible fraud clicks (for rating increasing)
		$therule = $_SESSION['user'] ? ' user_id=' . $_SESSION['user'] : ' ip="' . $_SERVER['REMOTE_ADDR'] . '"';
		$logdata = $db->super_query( 'SELECT * FROM ' . $cMain['dbPrefix'] . 'log WHERE' . $therule .
			' AND action="game" ORDER BY time DESC LIMIT 1', false );
		if( empty($logdata) || (time()-$logdata['time']>$cUser['minPlayPause']) )
			$db->query( 'UPDATE LOW_PRIORITY ' . $cMain['dbPrefix']
			. 'users SET gameplays=gameplays+1, rating=rating+' . $cUser['ptPlay'] . ' WHERE id='.$_SESSION['user'].' LIMIT 1' );
	}
	$log->write_event( 'game', $gameID );

	// If this page exists in cache, just show it and exit.
	if( $data = $cache->get() )
	{
		echo( $data );
		exit();
	}
	

	//If we are still here (no cache for this game), setup some template vars for future parsing
	$thisPage = "game";
	$tpl->define( 'tplMain', 'page_game.html' );
	$tpl->assign( array(
		'CURRENTGAMETITLE'	=> $gameres['title'],
		'ID'						=>	$gameID
	));
}
else
{
	// if the game is not found, we are showing the "error" page
	$tpl->define( "tplMain", "page_custom.html" );
	$thisPage = "custom";
	$tpl->assign( 'PAGECONTENTS', $cLang['errNoGame'] );
	$tpl->assign( 'METATITLE', $cSite['sSiteTitle'] . ' - ' . $_GET['game'] );
}

// permanent blocks and constants (we need them in any case)
$tpl->define( array(
	'tplBHead'	=>	'block_head.html',
	'tplBError'	=>	'block_errormsg.html'
));
$tpl->assign( array(
	'SITEURL'			=> $cSite['sURL'],
	'SITEROOT'			=> $cSite['sSiteRoot'],
	'TITLE'				=> $cSite['sSiteTitle'],
));


// Page blocks
foreach( $cB as $id => $cBlock )
{
	if( $cBlock[$thisPage] )
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

if( $gameID != 0 )
{
	$ext = substr( strrchr($gameres['file'], '.'), 1);
	if( $cExt[$ext] )
 		$tpl->define( 'tplBGame', $cExt[$ext] );
	else
   	$tpl->define( 'tplBGame', 'block_game.html' );

	// Find out dimentions, update DB if they do not exist there
	$nWidth = $gameres["width"];
	$nHeight = $gameres["height"];
	if( $nWidth == 0 )
	{
		$sFile =  "content/swf/" . $gameres["file"];
		if( is_file($sFile) )
		{
			$arGameInfo = getimagesize( $sFile );
			if( $arGameInfo[0] > 0 )
			{
				$db->query( 'UPDATE LOW_PRIORITY '.$cMain['dbPrefix'].'games SET width='.$arGameInfo[0].', height='.$arGameInfo[1].' WHERE id='.$gameID.' LIMIT 1' );
				$nWidth = $arGameInfo[0];
				$nHeight = $arGameInfo[1];
			}
		}
		else
		{
			$nWidth = $cTpl['nMaxGameW'];
			$nHeight = $cTpl['nMaxGameH'];
		}
	}

	$nWidthFactor = $cTpl['nMaxGameW'] / $nWidth;
	$nHeightFactor = $cTpl['nMaxGameH'] / $nHeight;
	$nSizeFactor = min( $nWidthFactor, $nHeightFactor );
	$tpl->assign( array(
		"METATITLE"		=> $gameres["title"] . " - " . $cSite["sSiteTitle"],
		"METADESCRIPTION"	=> empty($gameres["description"]) ? $cSite["sSiteDesc"] : $gameres["description"],
		"METAKEYWORDS"		=> empty($gameres["keywords"]) ? $cSite["sSiteKeywords"] : $gameres["keywords"],
		"GAMETITLE"		=> $gameres["title"],
		'INSTRUCTIONS'	=> empty($gameres["instructions"]) ? '' : $gameres["instructions"]
	));

	// warning: html codes for embedded and uploaded games are different!

	// if this is ane embedded game and we have an html code instead of direct URL,
	// don't use template. Just assign the given HTML code to the variable instead.
	if( stristr($gameres['file'], "<") && stristr($gameres['file'], "</") )
	   $tpl->assign( 'GAME', $gameres['file'] );
	else
	{
	   // In case we have an uploaded game or a direct URL to the game's file, use template.
	   $tpl->assign( array(
			'GAMEFILE'    	=> substr($gameres['file'], 0, 7) == 'http://' ? $gameres['file'] : $cSite['sSiteRoot'] . 'content/swf/' . $gameres['file'],
			"GAMEWIDTH"		=> $nSizeFactor < 1 ? round( $nWidth * $nSizeFactor ) : $nWidth,
			"GAMEHEIGHT"	=> $nSizeFactor < 1 ? round( $nHeight * $nSizeFactor ) : $nHeight,
			"GAMERATING"	=> round( $gameres["rating"]*$cTpl["nStarSize"]/100 )
		));
		$tpl->parse( "GAME", "tplBGame" );
	}
}

//	parse and make the page visible
$tpl->parse( "HEAD", "tplBHead" );
$tpl->parse( "MAIN", "tplMain" );
$data = $tpl->GetText('MAIN');

// if game is found and active, write cache
if( $gameID != 0 )
	$cache->write( $data );

$tpl->FastPrint( "MAIN");
?>
