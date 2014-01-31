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

$tpl->define( "tplBMostPlayed", "block_mostplayed.html" );
$tpl->define_dynamic( "dynGame", "tplBMostPlayed" );

if( $sScript == "cat.php" )
	$query = "SELECT id, category_id, title, latin_title, plays_total, active FROM " . $cMain["dbPrefix"] . "games WHERE active=1 AND category_id=". $nCategory ." ORDER BY plays_total DESC LIMIT " . $cB["MOSTPLAYED"]["max"];
else
	$query = "SELECT id, title, latin_title, plays_total, active FROM " . $cMain["dbPrefix"] . "games WHERE active=1 ORDER BY plays_total DESC LIMIT " . $cB["MOSTPLAYED"]["max"];
$res = $db->super_query( $query, true );

if( $res[0]["plays_total"] > 0 )
{
	foreach( $res as $i => $values )
		if( $values["plays_total"] > 0 )
		{
			$tpl->assign(array(
				"GAMEURL" => GameURL( $values["id"], $values["latin_title"] ),
				"GAMETITLE" => $values["title"],
				"PLAYS" => $values["plays_total"]
			));
			$tpl->parse( "MOSTPLAYED", ".dynGame" );
		}
		$tpl->parse( "MOSTPLAYED", "tplBMostPlayed" );
}
else
	$tpl->assign( "MOSTPLAYED", "" );
?>