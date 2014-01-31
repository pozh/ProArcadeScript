<?php
/*******************************************************************
/ ProArcadeScript version: 1.1
/ File description:
/ shows the main site stats
/
/ © 2007, ProArcadeScript. All rights reserved. 
/*******************************************************************/
$tpl->define( "tplBSiteStats", "block_stats.html" );

$res = $db->super_query( "SELECT COUNT(id) as games, SUM(plays_total) as total, SUM(plays_today) as today FROM ".$cMain["dbPrefix"]."games", false );
$stats = $db->super_query( "SELECT COUNT(DISTINCT ip) as ip, COUNT(DISTINCT user_id) as user_id FROM ".$cMain["dbPrefix"]."log", false );

$tpl->assign( array(
	"STAT-GAMES"		=>	$res["games"],
	"STAT-PLAYSTOTAL"	=>	$res["total"],
	"STAT-PLAYSTODAY"	=>	$res["today"],
	"STAT-GUESTS"		=>	$stats["ip"]-$stats["user_id"]+1,	//+1 because there is always 1 'user' with zero id
	"STAT-MEMBERS"		=>	$stats["user_id"]-1					//-1 for zero-id 'user'
));

$tpl->parse( "STATS", "tplBSiteStats" );
?>