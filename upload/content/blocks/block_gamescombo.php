<?php
/*******************************************************************
/ ProArcadeScript version: 1.5
/ File description:
/ Games Combobox
/
/ © 2007, ProArcadeScript. All rights reserved. 
/*******************************************************************/
$tpl->define( "tplBCombo", "block_gamescombo.html" );
$tpl->define_dynamic( "dynCombo", "tplBCombo" );
$res = $db->super_query( "SELECT id, title, latin_title, file FROM " . $cMain["dbPrefix"] . "games ORDER BY ".$cSort[$cB["COMBO"]["sort"]]." LIMIT " . $cB["COMBO"]["max"], true );
foreach( $res as $i => $game ) 
{
	$tpl->assign(array(
		"COMBOLINK" => GameURL( $game["id"], $game["latin_title"] ),
		"COMBOTITLE" => $game["title"],
		));
	$tpl->parse( "COMBO", ".dynCombo" );
}
$tpl->parse( "COMBO", "tplBCombo" );
?>