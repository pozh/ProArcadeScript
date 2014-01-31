<?php
/*******************************************************************
/ ProArcadeScript version: 1.1
/ File description:
/ This block shows the news submitted by the site owner
/
/ © 2007, ProArcadeScript. All rights reserved. 
/*******************************************************************/
$tpl->define( "tplBNews", "block_news.html" );
$tpl->define_dynamic( "dynNews", "tplBNews" );
$sOrderBy = ( $cB["NEWS"]["sort"] == "date" ) ? "date DESC" : "RAND()";
$res = $db->super_query( "SELECT * FROM " . $cMain["dbPrefix"] . "news ORDER BY $sOrderBy LIMIT " . $cB["NEWS"]["max"], true );
if( count($res) > 0 )
{
	foreach( $res as $news )
	{
		$tpl->assign(array(
			"NEWSDATE" => date( "M d", $news["date"]),
			"NEWSTITLE" => $news["title"],
			"NEWSTEXT"	=> empty($news["summary"]) ? $news["text"] : $news["summary"],
			"MORELINK"	=> empty($news["summary"]) ? "" : "<a href=\"".$cSite["sSiteRoot"]."news/".$news["id"]."/\">".$cLang["msgMore"]."</a>"
		));
		$tpl->parse( "NEWSLINES", ".dynNews" );
	}
	$tpl->parse( "NEWS", "tplBNews" );
}
else
{
//	$tpl->clear_dynamic( "dynNews" );
	$tpl->assign(array(
		"NEWSDATE" => "",
		"NEWSTEXT" => "",
		"NEWSTITLE"	=> $cLang["msgNoNews"],
		"MORELINK"	=> ""
	));
	$tpl->parse( "NEWSLINES", ".dynNews" );
}
$tpl->parse( "NEWS", "tplBNews" );
?>