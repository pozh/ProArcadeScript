<?php
/*******************************************************************
/ ProArcadeScript version: 1.1
/ File description:
/ 
/
/ © 2007, ProArcadeScript. All rights reserved. 
/*******************************************************************/

$nColumns = $cTpl["nColumnsListCat"];
$nOrder = $cB["CATGAMES"]["sort"];
$tpl->define( "tplBList", "block_catgameslist.html" );
for( $i=0; $i<$nColumns; $i++ )
	$tpl->define_dynamic( "dynListCol$i", "tplBList" );
$tpl->assign( "LISTSORTSTR", $cSortStr[$nOrder] );
$res = $db->super_query( "SELECT id, category_id, title, latin_title, active FROM " . $cMain["dbPrefix"] . "games WHERE category_id=". $nCategory ." and active=1 ORDER BY ".$cSort[$nOrder], true );

$col=0;
if( count($res) )
{
	foreach( $res as $game )
	{
		$tpl->assign( "LISTURL", GameURL($game["id"], $game["latin_title"]) );
		$tpl->assign( "LISTTITLE", $game["title"] );
		$tpl->parse( "DYNLISTGAMES$col", ".dynListCol$col" );
		$col++;
		if( $col == $nColumns)
			$col = 0;
	}
	$tpl->parse( "CATGAMES", "tplBList" );
}
else
	$tpl->assign( 'CATGAMES', '' );
?>