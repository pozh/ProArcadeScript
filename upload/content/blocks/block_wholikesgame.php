<?php
/*******************************************************************
/ ProArcadeScript version: 1.3
/ File description:
/ shows list of the users who have added current game to their favorites.
/
/ © 2007-2008, ProArcadeScript. All rights reserved.
/*******************************************************************/

// find out the name of the script which calles the block
// If it's not game page, cancel block
$sScript = substr(strrchr($_SERVER['SCRIPT_NAME'], "/"), 1);
if( $sScript != "game.php" )
	return;

$tpl->define( 'tpl'.$id, 'block_default_center.html' );

$query = 'SELECT f.*, u.id, u.username FROM ' . $cMain['dbPrefix'] . 'favorites as f LEFT JOIN '
	. $cMain['dbPrefix'] . 'users as u ON f.user_id=u.id WHERE game_id='.$gameID.' LIMIT ' . $cBlock['max'];

$res = $db->super_query( $query, true );
$tpl->assign( array(
	'BLOCKID'			=>	$id,
	'BLOCKTITLE'		=> $cBlock['title']
));

if( count($res) )
{
	$blockText = '<p>';
	foreach( $res as $values )
		$blockText .= '<a href="'.$cSite['sSiteRoot'].'user/'.$values['username'].'/">'.$values['username'].'</a>, ';
   $blockText = substr_replace( $blockText, '', -2 );  //remove last ', '
	$blockText .= '</p>';
	$tpl->assign( 'BLOCKTEXT', $blockText );
}
else
	$tpl->assign( 'BLOCKTEXT', '<p>&nbsp;</p>' );
$tpl->parse( $id, 'tpl'.$id );

?>