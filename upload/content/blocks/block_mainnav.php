<?php
$tpl->define( "tplMainNav", "block_mainnav.html" );
$tpl->define_dynamic( "dynMainNav", "tplMainNav" );
if( count($cP) )
{
	foreach( $cP as $pageID => $page )
		if( $page['mainmenu']*$page['active'] == 1 )
		{
			$tpl->assign( 'PAGEURL', $cSite['sSiteRoot']."docs/$pageID" );
			$tpl->assign( 'PAGETITLE', $page['menutitle'] );
			$tpl->parse( 'MAINNAVLINKS', '.dynMainNav' );
		}
}
// if user is not logged in, show the 'login' link
if( empty($_SESSION['username']) )
{
	$tpl->assign( 'PAGEURL', $cSite['sSiteRoot'].'user/login/' );
	$tpl->assign( 'PAGETITLE', $cLang['sLogin'] );
	$tpl->parse( 'MAINNAVLINKS', '.dynMainNav' );
}
// if user is logged in, show the link to his profile page
else
{
	$tpl->assign( 'PAGEURL', $cSite['sSiteRoot'].'user/'.$_SESSION['username'].'/' );
	$tpl->assign( 'PAGETITLE', $cLang['sUserHome'] );
	$tpl->parse( 'MAINNAVLINKS', '.dynMainNav' );
}
$tpl->parse( "MAINNAV", "tplMainNav" );
?>