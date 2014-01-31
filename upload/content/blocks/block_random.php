<?php
/*******************************************************************
/ ProArcadeScript version: 1.3.3
/ File description:
/ Forwards player to a random game; 
/
/ © 2007-2008, ProArcadeScript. All rights reserved. 
/*******************************************************************/
$c_block_caption = 'Random Game';
$c_random_absolute = '<b>ABSOLUTELY RANDOM</b>';
$c_block_id = 'RandomGame';


if( isset($_GET['random']) )
{
	require_once( "include/config.php" );
	require_once( "include/class.Database.php" );
	require_once( "include/helpers.php" );
	$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );
	if( ($_GET['random']== -1) || ( !preg_match( '/^[0-9\-]*$/', $_GET['random'] )) )
		// go to the absolutely random game
		$query = 'SELECT id, title, latin_title, active FROM ' . $cMain['dbPrefix'] . 'games WHERE active=1 ORDER BY RAND() LIMIT 1';
	else
		$query = 'SELECT id, category_id, title, latin_title, active FROM ' . $cMain['dbPrefix'] .
				'games WHERE category_id='.$_GET['random'].' AND active=1 ORDER BY RAND() LIMIT 1';
	$game = $db->super_query( $query );
	if( !empty($game) )
		header( 'Location:' . GameURL($game['id'], $game['latin_title']) );
}
else
{
	// generate list of links for the block's frontend
	$tpl->define( 'tplBRandom', 'block_default2.html' );
	$tpl->assign( array(
		'BLOCKID'			=>	$c_block_id,
		'BLOCKTITLE'		=>	$c_block_caption
	));
	$res = $db->super_query( "SELECT id, title FROM " . $cMain["dbPrefix"] . "categories", true );
	$blockText = '<ul>';
	foreach( $res as $i => $values )
		$blockText .= '<li><a href="'.$cSite["sSiteRoot"].'?random='.$values["id"].'">'.$values["title"].'</a></li>';
	$blockText .= '<li><br/><a href="'.$cSite["sSiteRoot"].'?random=-1">'.$c_random_absolute.'</a></li></ul>';
	$tpl->assign( 'BLOCKTEXT', $blockText );

	$tpl->parse( "RANDOM", "tplBRandom" );
}
?>