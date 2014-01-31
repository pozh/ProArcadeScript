<?php
/********************************************************************************
/ ProArcadeScript 
/ File description:
/ admin panel: site settings
/ IN: "action" - the action admin wants to run
/
/********************************************************************************/
require_once( "../include/config.php" );
require_once( "../include/class.FastTemplate.php" );
require( "../include/class.Cache.php" );

$tpl = new FastTemplate("templates");

require( "checklogin.php" );

$tpl->define( array(
	"tplHeader"	=> "header.html",
	"tplFooter"	=> "footer.html",
	"tplMain"	=> "page_settings.html"
));

// Assign the constant parameters for the page
$tpl->assign( array(
	"SITEROOT"			=> $cSite["sSiteRoot"],
	"TITLE"				=> $cSite["sSiteTitle"] . " Administration - Site Settings",
	"ATSETTINGS"		=> "Active",
	"BASEPATHERROR"	=> "",
	"URLERROR"			=> "",
	"TEMPLATEERROR"	=> "",
	"SITETITLEERROR"	=> "",
	"ADMINEMAILERROR"	=> "",
	"EMAILERROR"		=> "",
	"ADMINERROR"		=> "",
	'CACHETTLERROR'   => '',
	"PASSWORDERROR"	=> ""
));


// If the form is submitted process the given data 	
if( isset($_REQUEST["action"]) )
{
	$bEverythingOk = true;
	if( $_REQUEST["action"] == "Submit" )
	{
		if( empty($_POST["base_path"]) )	{	$bEverythingOk = false;	$tpl->assign( "BASEPATHERROR", $cLang["errAEmpty"] );	}
		if( empty($_POST["site_url"]) )		{	$bEverythingOk = false;	$tpl->assign( "URLERROR", $cLang["errAEmpty"] );	}
		if( empty($_POST["site_title"]) )	{	$bEverythingOk = false;	$tpl->assign( "SITETITLEERROR", $cLang["errAEmpty"] );	}
		if( empty($_POST["template"]) )		{	$bEverythingOk = false;	$tpl->assign( "TEMPLATEERROR", $cLang["errAEmpty"] );	}
		if( empty($_POST["admin"]) )		{	$bEverythingOk = false;	$tpl->assign( "ADMIN", $cLang["errAEmpty"] );	}
		if( empty($_POST["admin_email"]) )	{	$bEverythingOk = false;	$tpl->assign( "ADMINEMAILERROR", $cLang["errAEmpty"] );	}
		if( empty($_POST["contact_email"]) ){	$bEverythingOk = false;	$tpl->assign( "EMAILERROR", $cLang["errAEmpty"] );	}
		if( empty($_POST["cache_ttl"]) ){	$bEverythingOk = false;	$tpl->assign( "CACHETTLERROR", $cLang["errAEmpty"] );	}
		if( $_POST["password"] != $_POST["password_copy"] ){	$bEverythingOk = false;	$tpl->assign( "PASSWORDERROR", $cLang["errAPassword"] );	}
	}
	// 
	if( $bEverythingOk == true )
	{	
		$tplConfig = new FastTemplate("templates");
		$tplConfig->define( "tplConfig", "sitesettings.tpl" );
		if( $_REQUEST["action"] == "Submit" )
		{
			$tplConfig->assign( array(
				"SITEROOT"		=>	$_POST["base_path"],
				"SITEURL"		=>	$_POST["site_url"],
				"SITETITLE"		=>	htmlspecialchars($_POST["site_title"]),
				"COPYRIGHT"		=>	htmlspecialchars($_POST["copyright"]),
				"DESCRIPTION"	=>	htmlspecialchars($_POST["site_desc"]),
				"KEYWORDS"		=>	htmlspecialchars($_POST["site_keywords"]),
				"TEMPLATE"		=>	$_POST["template"],
				"ADMIN"			=>	$_POST["admin"],
				"ADMINEMAIL"	=>	$_POST["admin_email"],
				"CONTACTEMAIL"	=>	$_POST["contact_email"],
				"CACHETTL"     => $_POST['cache_ttl'],
				"MAILSIGNATURE"	=>	get_magic_quotes_gpc() ? $_POST["signature"] : mysql_escape_string($_POST["signature"])
			));

			if( $cSite["bSeo"] )
				$tplConfig->assign( "SEO", "true" );
			else
				$tplConfig->assign( "SEO", "false" );

			if( $cSite["bCache"] )
				$tplConfig->assign( "CACHE", "true" );
			else
			   $tplConfig->assign( "CACHE", "false" );
		} // action = Submit
		else
		{	
			$tplConfig->assign( array(
				"SITEROOT"		=> $cSite["sSiteRoot"],
				"SITEURL"		=> $cSite["sURL"],
				"SITETITLE"		=> $cSite["sSiteTitle"],
				"DESCRIPTION"	=> $cSite["sSiteDesc"],
				"KEYWORDS"		=> $cSite["sSiteKeywords"],
				"TEMPLATE"		=> $cSite["sTemplate"],
				"COPYRIGHT"		=> $cSite["sCopyright"],
				"ADMIN"			=> $cSite["sAdminName"],
				"ADMINEMAIL"	=> $cSite["sAdminEmail"],
				"CONTACTEMAIL"	=> $cSite["sContactEmail"],
				'SEO'          => $cSite['bSeo'] ? 'true' : 'false',
				'CACHE'        => $cSite['bCache'] ? 'true' : 'false',
				'CACHETTL'     => $cSite['cacheTTL'],
				"MAILSIGNATURE"	=> get_magic_quotes_gpc() ? $cSite["MailSignature"] : mysql_escape_string($cSite["MailSignature"])
			));

			if( $_REQUEST["action"] == "disable_seo" )
			{
				$tplConfig->assign( "SEO", "false" );
				$cache = new CCache();
				$cache->clear_cache();
			}
			else if( $_REQUEST["action"] == "enable_seo" )
			{
				$tplConfig->assign( "SEO", "true" );
				$cache = new CCache();
				$cache->clear_cache();
			}

			if( $_REQUEST["action"] == "disable_cache" )
				$tplConfig->assign( "CACHE", "false" );
			else if( $_REQUEST["action"] == "enable_cache" )
				$tplConfig->assign( "CACHE", "true" );

		}
		
		if( !empty($_POST["password"]) )
			$tplConfig->assign( "ADMINPASSWORD", md5($_POST["password"]) );
		else
			$tplConfig->assign( "ADMINPASSWORD", $cSite["sAdminPassword"] );
			
		$tplConfig->parse( "CONFIG", "tplConfig" );
		$sNewConfig = $tplConfig->GetText( "CONFIG" );
		$fConfig = fopen( "../include/sitesettings.php", "w" );
		fwrite( $fConfig, $sNewConfig );
		fclose( $fConfig );
		header ("Location:settings.php");
	}
	else
	{
		$tpl->assign( array(
			"BASEPATH"		=>	$_POST["base_path"],
			"SITEURL"		=>	$_POST["site_url"],
			"SITETITLE"		=>	$_POST["site_title"],
			"COPYRIGHT"		=>	$_POST["copyright"],
			"SITEDESC"		=>	$_POST["site_desc"],
			"SITEKEYWORDS"	=>	$_POST["site_keywords"],
			"TEMPLATE"		=>	$_POST["template"],
			"ADMIN"			=>	$_POST["admin"],
			"ADMINEMAIL"	=>	$_POST["admin_email"],
			"CONTACTEMAIL"	=>	$_POST["contact_email"],
			"CACHETTL"     => $_POST['cache_ttl'],
			"MAILSIGNATURE"	=>	$_POST["signature"]
		));

		if( $cSite["bSeo"] == 0 )
			$tpl->assign( "SEOBUTTON", "<a class=\"Btn Off\" href=\"settings.php?action=enable_seo\"><b>Disabled</b>, click to enable</a>" );
		else
			$tpl->assign( "SEOBUTTON", "<a class=\"Btn On\" href=\"settings.php?action=disable_seo\"><b>Enabled</b>, click to disable</a>" );

		if( $cSite['bCache'] == 0 )
			$tpl->assign( "CACHEBUTTON", "<a class=\"Btn Off\" href=\"settings.php?action=enable_cache\"><b>Disabled</b></a>" );
		else
			$tpl->assign( "CACHEBUTTON", "<a class=\"Btn On\" href=\"settings.php?action=disable_cache\"><b>Enabled</b></a>" );
	}
}
else	//if submit is not set
{
	// Setup controls' values
	$tpl->assign( array(
		"BASEPATH"		=> $cSite["sSiteRoot"],
		"SITEURL"		=> $cSite["sURL"],
		"SITETITLE"		=> $cSite["sSiteTitle"],
		"SITEDESC"		=> $cSite["sSiteDesc"],
		"SITEKEYWORDS"	=> $cSite["sSiteKeywords"],
		"TEMPLATE"		=> $cSite["sTemplate"],
		"COPYRIGHT"		=> $cSite["sCopyright"],
		"ADMIN"			=> $cSite["sAdminName"],
		"ADMINEMAIL"	=> $cSite["sAdminEmail"],
		"CONTACTEMAIL"	=> $cSite["sContactEmail"],
		'CACHETTL'     => $cSite['cacheTTL'],
		"MAILSIGNATURE"	=> $cSite["MailSignature"]
	));

	if( $cSite["bSeo"] == 0 )
		$tpl->assign( "SEOBUTTON", "<a class=\"Btn Off\" href=\"settings.php?action=enable_seo\"><b>Disabled</b>, click to enable</a>" );
	else
		$tpl->assign( "SEOBUTTON", "<a class=\"Btn On\" href=\"settings.php?action=disable_seo\"><b>Enabled</b>, click to disable</a>" );

	if( $cSite['bCache'] == 0 )
		$tpl->assign( "CACHEBUTTON", "<a class=\"Btn Off\" href=\"settings.php?action=enable_cache\"><b>Disabled</b></a>" );
	else
		$tpl->assign( "CACHEBUTTON", "<a class=\"Btn On\" href=\"settings.php?action=disable_cache\"><b>Enabled</b></a>" );
}

$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");
