<?php
/*******************************************************************
/ ProArcadeScript version: 1.3.2
/ File description:
/ Generate the sorted list of games
/ IN: $id - block's id; $cBlock - array containing block's params
/
/ © 2007-2008, ProArcadeScript. All rights reserved.
/*******************************************************************/

// find out which script call this block
$sScript = substr(strrchr($_SERVER['SCRIPT_NAME'], "/"), 1);

$tpl->define( 'tpl'.$id, 'block_default.html' );

if( $sScript == "cat.php" )
	$query = 'SELECT * FROM ' . $cMain['dbPrefix'] . 'games WHERE active=1 AND category_id='. $nCategory . ' ORDER BY ' . $cSort[$cBlock['sort']] . ' LIMIT ' . $cBlock['max'];
else
	$query = 'SELECT * FROM ' . $cMain['dbPrefix'] . 'games WHERE active=1 ORDER BY ' . $cSort[$cBlock['sort']] . ' LIMIT ' . $cBlock['max'];
$res = $db->super_query( $query, true );

$tpl->assign( array(
	'BLOCKID'			=>	$id,
	'BLOCKTITLE'		=>	$cBlock['title']
));

if( count($res) )
{
	$blockText = '<ul>';
	foreach( $res as $i => $values )
		$blockText .= '<li><a href="'.GameURL( $values['id'], $values['latin_title'] ).'">'.$values['title'].'</a></li>';
	$blockText .= '</ul>';
	$tpl->assign( 'BLOCKTEXT', $blockText );
}
else
	$tpl->assign( 'BLOCKTEXT', '<p>&nbsp;</p>' );

$tpl->parse( $id, 'tpl'.$id );
?>