<?php
/*******************************************************************
/ ProArcadeScript version: 1.4
/ File description:
/ Show the games that are favorite to people who plays the current one 
/
/ © 2007-2008, ProArcadeScript. All rights reserved. 
/*******************************************************************/

// how old (days) must be the data to be considered as obsolete
$cRelatedDays = 1;

// proceed only if the game is found
if( $gameID > 0 )
{
	// log current game first
	$query = 'INSERT INTO ' . $cMain['dbPrefix'] . 'related (game_id, ip, time) VALUES('.$gameID.', "'.$_SERVER['REMOTE_ADDR'].'", '.time().')';
	$db->query( $query );
	
	// Remove obsolete records
	$delta = time() - $cRelatedDays*24*3600;
	$query = 'DELETE FROM ' . $cMain['dbPrefix'] . 'related WHERE time < ' . $delta;
	$db->query( $query );
	
	// Now find the "related" games 
	$query = 'SELECT g.*, r.*, COUNT(*) AS cnt FROM ' . $cMain['dbPrefix'] . 'games AS g, ' . $cMain['dbPrefix'] . 
		'related AS r WHERE r.ip IN (SELECT DISTINCT ip FROM ' . $cMain['dbPrefix'] . 'related WHERE g.active=1 AND game_id=' . $gameID .
		') AND g.id=r.game_id AND g.id<>' . $gameID . ' GROUP BY r.game_id ORDER BY cnt DESC LIMIT ' . $cB["RELATED"]["max"];
	$res = $db->super_query( $query, true );
	if( count($res) > 0 )
	{
		$tpl->define( "tplBRelated", "block_related.html" );
		$tpl->define_dynamic( "dynGame", "tplBRelated" );
		foreach( $res as $i => $values )
		{
			$sThumb = "content/thumbs/".$values['thumbnail'];
			if( file_exists($sThumb) )
				$sThumb = $cSite["sSiteRoot"].$sThumb;
			else
				$sThumb = $cSite["sSiteRoot"]."templates/".$cSite["sTemplate"]."/images/no_image.gif";
			$tpl->assign(array(
				'GAMEURL'		=> GameURL( $values["id"], $values["latin_title"] ),
				'GAMETITLE'		=> $values['title'],
				'DESCRIPTION'	=> $values['description'],
				'IMG'				=> $sThumb
			));
			$tpl->parse( 'RELATED', '.dynGame' );
			$even = $even ? '' : 'even';  // mark even games by this css class
		}
		$tpl->parse( 'RELATED', 'tplBRelated' );
	}
	else	
		$tpl->assign( 'RELATED', '' );
}
?>