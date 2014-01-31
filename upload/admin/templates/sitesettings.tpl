<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ Site configuration. Edit it manually or using admin's control panel
/
/*******************************************************************/

$cSite = array(

//  General Settings
	"sSiteRoot"		=>	"!SITEROOT!",	// if the script will be installed in a folder, the root must be "/folder/"
	"sURL"			=>	"!SITEURL!",
	"sSiteTitle"	=>	"!SITETITLE!",
	"sCopyright"	=>	"!COPYRIGHT!",
	"sSiteDesc"		=>	"!DESCRIPTION!",
	"sSiteKeywords"	=>	"!KEYWORDS!",
	"sTemplate"		=>	"!TEMPLATE!",
	"bSeo"			=>	!SEO!,		// search engine friendly links
	"bCache"       => !CACHE!,
	"cacheTTL"     => !CACHETTL!,

//  Site Admin information
	"sAdminName"		=>	"!ADMIN!",
	"sAdminEmail"		=>	"!ADMINEMAIL!",
	"sContactEmail"		=>	"!CONTACTEMAIL!",
	"sAdminPassword"	=>	"!ADMINPASSWORD!",

// Email templates
	"MailSignature"		=>	"!MAILSIGNATURE!"

);
?>