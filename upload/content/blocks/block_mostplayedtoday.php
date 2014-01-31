<?php
/*******************************************************************
/ ProArcadeScript version: 1.3.2
/ File description:
/ The "Most Played" shows the game that were played more times 
/ than the others
/
/ © 2007, ProArcadeScript. All rights reserved. 
/*******************************************************************/

// find out which script call this block
$sScript = substr(strrchr($_SERVER['SCRIPT_NAME'], "/"), 1);

$tpl->define( "tplBMostPlayedToday", "block_mostplayedtoday.html" );
$tpl->define_dynamic( "dynTodayGame", "tplBMostPlayedToday" );

if( $sScript == "cat.php" )
	$query = "SELECT id, category_id, title, latin_title, plays_today, active FROM " . $cMain["dbPrefix"] . "games WHERE active=1 AND category_id=". $nCategory ." ORDER BY plays_today DESC LIMIT " . $cB["MOSTPLAYEDTODAY"]["max"];
else
	$query = "SELECT id, title, latin_title, plays_today, active FROM " . $cMain["dbPrefix"] . "games WHERE active=1  ORDER BY plays_today DESC LIMIT " . $cB["MOSTPLAYEDTODAY"]["max"];
$res = $db->super_query( $query, true );

if( $res[0]["plays_today"] > 0 )
{
	foreach( $res as $i => $values )
		if( $values["plays_today"] > 0 )
		{
			$tpl->assign(array(
				"GAMEURL" => GameURL( $values["id"], $values["latin_title"] ),
				"GAMETITLE" => $values["title"],
				"PLAYS" => $values["plays_today"]
			));
			$tpl->parse( "MOSTPLAYEDTODAY", ".dynTodayGame" );
		}
		$tpl->parse( "MOSTPLAYEDTODAY", "tplBMostPlayedToday" );
}
else
	$tpl->assign( "MOSTPLAYEDTODAY", "" );
?>