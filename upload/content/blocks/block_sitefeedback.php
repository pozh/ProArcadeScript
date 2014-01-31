<?php
/*******************************************************************
/ ProArcadeScript version: 1.1
/ File description:
/ Show visitors' comments about the site.
/
/ © 2007, ProArcadeScript. All rights reserved. 
/*******************************************************************/
$tpl->define( "tplSiteFeedback", "block_sitefeedback.html" );
$tpl->define_dynamic( "dynSiteFeedback", "tplSiteFeedback" );
$tpl->define_dynamic( "dynSiteFeedbackEmpty", "tplSiteFeedback" );

$res = $db->super_query( "SELECT c.*, u.id, u.username FROM " . $cMain["dbPrefix"] . "comments AS c LEFT JOIN " . 
		$cMain["dbPrefix"] . "users AS u ON u.id=c.user_id WHERE c.game_id=0 AND c.active=1 ORDER BY c.added DESC LIMIT ".$cB['SITEFEEDBACK']['max'], true );

if( count($res) > 0 )
{
	$tpl->clear_dynamic( "dynSiteFeedbackEmpty" );
	foreach( $res as $comment )
	{
		$tpl->assign(array(
			"SITEFEEDBACKDATE"	=>	date( "M-d", $comment['added'] ),
			"SITEFEEDBACKUSER"	=>	empty($comment["username"]) ? $cLang['sGuest'] : '<a href="'.$cMain['sSiteRoot'].'user/'.$comment['username'].'/">'.$comment['username'].'</a>',
			"SITEFEEDBACK"		=>	$comment['text']
		));
		$tpl->parse( "SITEFEEDBACKLIST", ".dynSiteFeedback" );
	}
}
else
{
	$tpl->clear_dynamic( "dynSiteFeedback" );
}
$tpl->assign( "SITEFEEDBACKTIMER", time() );
$tpl->parse( "SITEFEEDBACK", "tplSiteFeedback" );
?>