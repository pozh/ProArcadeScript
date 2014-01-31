<?php
$tpl->assign( 'COPYRIGHT', $cSite['sCopyright'] );
$tpl->assign( 'POWEREDBY', '<a href="http://www.proarcadescript.com"><img src="'.$cSite['sSiteRoot'].'templates/'.$cSite['sTemplate'].'/images/arcade_script.gif" alt="Powered by ProArcadeScript" /></a>' );

$tpl->define(array(	"tplFooter" => "block_footer.html" ) );
$tpl->define_dynamic( "dynFooter" , "tplFooter" );

if( count($cP) )
	foreach( $cP as $pageID => $page )
		if( $page["footermenu"]*$page["active"] == 1 )
		{
			$tpl->assign( "PAGEURL", $cSite["sSiteRoot"]."docs/$pageID" );
			$tpl->assign( "PAGETITLE", $page["menutitle"] );
			$tpl->parse( "FOOTERLINKS", ".dynFooter" );
		}
else
	$tpl->clear_dynamic( "dynFooter" );
$tpl->parse( "FOOTER", "tplFooter" );
?>