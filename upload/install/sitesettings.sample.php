<?php
/*******************************************************************
/ ProArcadeScript version: 1.4
/ File description:
/ Site configuration. 
/*******************************************************************/

$cSite = array(

//  General Settings
	"sSiteRoot"		=>	"!BASEPATH!",
	"sURL"			=>	"http://www.yourarcade.com",
	"sSiteTitle"	=>	"!SITENAME!",
	"sCopyright"	=>	"© !SITENAME!",
	"sSiteDesc"		=>	"Flash arcade",
	"sSiteKeywords"	=>	"Flash, Arcade, Games, Free, Online",
	"sTemplate"		=>	"Toys",
	"bSeo"			=>	true,		// search engine friendly links
	"bCache"       => false,
	"cacheTTL"     => 3600,
	
//  Site Admin information
	"sAdminName"		=>	"!ADMIN!",
	"sAdminEmail"		=>	"!ADMINEMAIL!",
	"sContactEmail"		=>	"!ADMINEMAIL!",
	"sAdminPassword"	=>	"!ADMINPASSWORD!",

// Email templates
	"MailSignature"		=>	"Regards, \r\n!SITENAME! Support \r\n!ADMINEMAIL!"

);
?>