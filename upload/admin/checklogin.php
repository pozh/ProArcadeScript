<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ helper file for the checking the admin's login 
/
/*******************************************************************/
session_start();

if ( !isset($_SESSION['admin']) )
{
	header ("Location:login.php");
	exit();
}
else
{
	$tpl->assign( "TOPINFO", "You are logged in as " . $cSite["sAdminName"] . ". <a href=\"logout.php\">Logout</a>" );

	$arNavButtons = Array( "ATHOME", "ATSETTINGS", "ATGAMES", "ATNEWS", "ATBLOCKS", "ATPAGES", "ATCOMMENTS", "ATUSERS" );
	foreach( $arNavButtons as $BtnStyle )
		$tpl->assign( $BtnStyle, "" );
}
?>