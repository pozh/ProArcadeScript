<?php
$tpl->define(array(	"tplBGamesNav"	=> "block_gamesnav.html" ) );
$tpl->define_dynamic( "dynGenre" , "tplBGamesNav" );
$res = $db->super_query( "SELECT * FROM " . $cMain["dbPrefix"] . "categories ORDER by position ASC", true );
foreach( $res as $i => $values )
{
	$tpl->assign( "GENREURL", CategoryURL($values["latin_title"]) );
	$tpl->assign( "GENRETITLE", $values["title"] );
	$tpl->parse( "GENRES", ".dynGenre" );
}
$tpl->parse( "GAMESNAV", "tplBGamesNav" );
?>