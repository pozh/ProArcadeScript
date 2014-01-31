<?php
/*******************************************************************
/ ProArcadeScript version: 1.3
/ File description:
/ shows the list of most active users
/
/ © 2007-2008, ProArcadeScript. All rights reserved.
/*******************************************************************/

$tpl->define( 'tpl'.$id, 'block_default.html' );

$query = 'SELECT * FROM ' . $cMain['dbPrefix'] . 'users ORDER BY rating DESC  LIMIT ' . $cBlock['max'];
$res = $db->super_query( $query, true );
$tpl->assign( array(
	'BLOCKID'			=>	$id,
	'BLOCKTITLE'		=>	$cBlock['title']
));

if( count($res) )
{
	$blockText = '<ul>';
	foreach( $res as $i => $values )
	   if( $values['rating'] > 0 )
			$blockText .= '<li><a href="'.$cSite['sSiteRoot'].'user/'.$values['username'].'/">'.$values['username'].'</a></li>';
	$blockText .= '</ul>';
	$tpl->assign( 'BLOCKTEXT', $blockText );
}
else
	$tpl->assign( 'BLOCKTEXT', '<p>&nbsp;</p>' );

$tpl->parse( $id, 'tpl'.$id );

?>