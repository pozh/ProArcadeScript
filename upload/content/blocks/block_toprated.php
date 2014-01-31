<?php
/*******************************************************************
/ ProArcadeScript version: 1.1
/ File description:
/ Block "Top Rated" shows the list of the games that got the highest 
/ rating from players
/
/ © 2007, ProArcadeScript. All rights reserved. 
/*******************************************************************/

// find out which script call this block
$sScript = substr(strrchr($_SERVER['SCRIPT_NAME'], "/"), 1);

$tpl->define( "tplBTopRated", "block_toprated.html" );
$tpl->define_dynamic( "dynTopGame", "tplBTopRated" );

if( $sScript == "cat.php" )
	$query = "SELECT id, title, latin_title, rating FROM " . $cMain["dbPrefix"] . "games WHERE category_id=". $nCategory ." ORDER BY rating DESC LIMIT " . $cB["TOPRATED"]["max"];
else 
	$query = "SELECT id, title, latin_title, rating FROM " . $cMain["dbPrefix"] . "games ORDER BY rating DESC LIMIT " . $cB["TOPRATED"]["max"];
	
$res = $db->super_query( $query, true );

// if anyone ever rated a game 
if( $res[0]["rating"] > 0 )
{
	foreach( $res as $i => $values )
		if( $values["rating"] > 0 )
		{
			$tpl->assign(array(
				"TOPGAMEURL" => GameURL( $values["id"], $values["latin_title"] ),
				"TOPGAMETITLE" => $values["title"],
				"TOPRATING" => number_format( $values["rating"]/100, 1 )
			));
			$tpl->parse( "TOPRATEDGAMES", ".dynTopGame" );
		}
	$tpl->parse( "TOPRATED", "tplBTopRated" );
}
else
{
	$tpl->clear_dynamic( "dynTopGame" );
	$tpl->assign( "TOPRATED", "" );
}
?>