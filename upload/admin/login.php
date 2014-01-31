<?php
/*******************************************************************
/ ProArcadeScript
/ File description:
/ show the login form and process the given login information
/
/*******************************************************************/
require_once( "../include/config.php" );
require_once( "../include/class.FastTemplate.php" );

session_start();

$tpl = new FastTemplate("templates");
$tpl->define( "tplHeader", "header.html" );
$tpl->define( "tplFooter", "footer.html" );
$tpl->define( "tplMain", "page_login.html" );

$tpl->assign( "SITEROOT", $cSite["sSiteRoot"] );
$tpl->assign( "TITLE", $cSite["sSiteTitle"] );
$tpl->assign( "TOPINFO", $cLang["msgLogin"] );

$tpl->define_dynamic( "dynNav", "tplHeader" );
$tpl->clear_dynamic( "dynNav" );

if( isset($_POST["submit"]) )
{
	$tpl->assign( "LOGIN", $_POST["login"] );	
	$tpl->assign( "LOGINERROR", "" );	
	$tpl->assign( "PASSWORDERROR", "" );	
	
	if( empty($_POST["login"]) )
		$tpl->assign( "LOGINERROR", $cLang["errAEmpty"] );
	else if( empty($_POST["password"]) )
		$tpl->assign( "PASSWORDERROR", $cLang["errAEmpty"] );	
	else if( ($_POST["login"] != $cSite["sAdminName"]) || (md5($_POST["password"]) != $cSite["sAdminPassword"]) )
		$tpl->assign( "LOGINERROR", $cLang["errALogin"] );
	else
	{
		// Everything's ok, let admin enter
		$_SESSION["admin"] = $_POST["login"];
		header ("Location:index.php");
	}	
}	
else
{
	$tpl->assign( "LOGIN", "" );	
	$tpl->assign( "LOGINERROR", "" );	
	$tpl->assign( "PASSWORDERROR", "" );	
}

$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");
	
	
?>