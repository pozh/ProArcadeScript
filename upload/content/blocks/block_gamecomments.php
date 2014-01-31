<?php
/*******************************************************************
/ ProArcadeScript version: 1.1
/ File description:
/ Show visitors' comments about a game.
/
/ © 2007, ProArcadeScript. All rights reserved. 
/*******************************************************************/
$tpl->define( "tplGameComments", "block_gamecomments.html" );
$tpl->define_dynamic( "dynGameComments", "tplGameComments" );
$tpl->define_dynamic( "dynGameNoComments", "tplGameComments" );

$sOrder = "c.added  DESC";
if( $cB['GAMECOMMENTS']['sort'] == "rand" )
	$sOrder = "RAND()";
$res = $db->super_query( "SELECT c.*, u.id, u.username FROM " . $cMain["dbPrefix"] . "comments AS c LEFT JOIN " . 
		$cMain["dbPrefix"] . "users AS u ON u.id=c.user_id WHERE c.game_id=$gameID AND c.active=1 ORDER BY $sOrder LIMIT ".$cB['GAMECOMMENTS']['max'], true );

if( count($res) > 0 )
{
	$tpl->clear_dynamic( "dynGameNoComments" );
	foreach( $res as $comment )
	{
		$tpl->assign(array(
			"GAMECOMMENTDATE"	=>	date( "M d", $comment['added'] ),
			"GAMECOMMENTUSER"	=>	empty($comment['username']) ? $cLang['sGuest'] : $comment['username'],
			"GAMECOMMENT"		=>	$comment['text']
		));
		$tpl->parse( "GAMECOMMENTSLIST", ".dynGameComments" );
	}
}
else
{
	$tpl->clear_dynamic( "dynGameComments" );
}
$tpl->assign( "GAMECOMMENTSTIMER", time() );
$tpl->assign( "GAMECOMMENTSGAME", $gameID );
$tpl->parse( "GAMECOMMENTS", "tplGameComments" );
?>