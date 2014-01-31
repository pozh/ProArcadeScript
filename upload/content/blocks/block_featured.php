<?php
$tpl->define( "tplFeatured", "block_featured.html" );
$res = $db->super_query( "SELECT * FROM " . $cMain["dbPrefix"] . "games WHERE featured=1 ORDER BY RAND() LIMIT 1", true );
if( count($res) > 0 )
{
	$game = $res[0];
	$tpl->assign(array(
			"MSGFEATURED"	=> $cLang["msgFeatured"],
			"GAMEURL" 		=> GameURL( $game["id"], $game["latin_title"] ),
			"GAMETITLE"		=> $game["title"],
			"IMG"			=> $cSite["sSiteRoot"]."content/screenshots/".$game["large_img"],
			"DESCRIPTION"	=> $game["description"]
		));
	$tpl->parse( "FEATURED", "tplFeatured" );
}
else
	$tpl->assign( "FEATURED", "" );
?>