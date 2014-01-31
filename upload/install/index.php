<?php 
/*******************************************************************
/
/ FILE install.php
/ The script installer
/
/*******************************************************************/
include( "../include/class.FastTemplate.php" );
include( "../include/class.Database.php" );

// SET PHP ENVIRONMENT 
error_reporting(E_ALL & ~E_NOTICE); 

// CREATE DATABASE TABLES
//------------------------------------------------------------------
function CreateTables( $request )
{
	$db = new CDatabase( $request["dbuser"],$request["dbpass"],$request["dbname"],$request["dbhost"], 0 );
	if( $db->last_error_num() != 0 )
		return "Can't initialise database. <br/>" . $db->last_error() . "<br/><br/>
			Please make sure your DB setings (Host, Name, Username and Password) are entered correctly. 
			Also check whether or not the database with that name already exists.";

	$sql_file = fopen( "db.sql","r" );
	$query_default = fread( $sql_file, 20000 );
	if( !$query_default )
		return "Can't find the file containing Database structure. Please make sure you have uploaded all files from the archive.";
	fclose( $sql_file );
	$query = str_replace( "arcade_", $request["tableprefix"], $query_default );
	$queries = explode( ";", $query );
	foreach( $queries as $query )
		if( strlen($query) > 4 )
		{
			$db->query($query, false);
			if( !stristr($query, 'INSERT') && ($db->last_error_num() != 0) )
				return "Can't create tables... you can do that manually using phpMyAdmin, see install/db.sql file for tables structure.<br/>" . $db->last_error()." - ".$db->last_error_num() . "<br/><br/>". $query;
		}
	return "";
}

// WRITE CONFIG FILE
//------------------------------------------------------------------
function WriteConfig( $request )
{
	// general site settings
	$file = fopen( "sitesettings.sample.php", "r" );
	$text = fread( $file, 20000 );
	fclose( $file );
	if( !$text )
		return "Can't find sample file. Please make sure you have uploaded all files from the archive.";
	$text = str_replace( "!BASEPATH!", $request["basepath"], $text );
	$text = str_replace( "!SITENAME!", $request["sitename"], $text );
	$text = str_replace( "!ADMIN!", $request["adminname"], $text );
	$text = str_replace( "!ADMINEMAIL!", $request["adminemail"], $text );
	$text = str_replace( "!ADMINPASSWORD!", md5($request["adminpass"]), $text );
	$file = fopen( "../include/sitesettings.php", "w" );
	if( !$file )
		return "Can't create a new sitesettings file... please make sure the \"includes\" folder is writable to the owner";
	if( !fwrite($file, $text) )
		return "Can't write new settings to the file... please make sure the \"includes\" folder is writable to the owner";
	
	// database settings
	$file = fopen( "dbsettings.sample.php", "r" );
	$text = fread( $file, 20000 );
	fclose( $file );
	if( !$text )
		return "Can't find sample file. Please make sure you have uploaded all files from the archive.";
	$text = str_replace( "!DBHOST!", $request["dbhost"], $text );
	$text = str_replace( "!DBNAME!", $request["dbname"], $text );
	$text = str_replace( "!DBUSER!", $request["dbuser"], $text );
	$text = str_replace( "!DBPASSWORD!", $request["dbpass"], $text );
	$text = str_replace( "!DBPREFIX!", $request["tableprefix"], $text );
	$file = fopen( "../include/dbsettings.php", "w" );
	if( !$file )
		return "Can't create a new db settings file... please make sure the \"includes\" folder's attributes value is '777'";
	if( !fwrite($file, $text) )
		return "Can't write new db settings to the file... please make sure the \"includes\" folder's attributes value is '777'";

	$file = fopen( "../include/cron.php", "w" );
	if( !$file )
		return "Can't create the 'cron.php' file... please make sure the \"includes\" folder's attributes value is '777'";
	if( !fwrite($file, '<?php $cLastRun = 0; ?'.'>') )
		return "Can't write to the 'cron.php' file... please make sure the \"includes\" folder's attributes value is '777'";
		
	return "";
}

//------------------------------------------------------------------

$tpl = new FastTemplate("templates");
$tpl->define( "tplMain", "install.html" );

switch( $_REQUEST['step'] )
{
	case 2:
		$tpl->define( "tplContent", "install_finish.html" );
		$tpl->assign( "STEP", ": Done!" );
		$tpl->assign( "ERROR", "" );
		break;
	default:
		$tpl->define( "tplContent", "install_step1.html" );
		$tpl->assign( array(
			"STEP"		=> ": Settings",
			"SITENAME"	=> $_REQUEST["sitename"],
			"BASEPATH"	=> $_REQUEST["basepath"] ? $_REQUEST["basepath"] : "/",
			"DBNAME"	=> $_REQUEST["dbname"],
			"DBUSER"	=> $_REQUEST["dbuser"],
			"DBPASS"	=> $_REQUEST["dbpass"],
			"DBHOST"	=> $_REQUEST["dbhost"] ? $_REQUEST["dbhost"] : "localhost",
			"TABLEPREFIX"	=> $_REQUEST["tableprefix"] ? $_REQUEST["tableprefix"] : "pas_",
			"ADMINNAME"		=> $_REQUEST["adminname"],
			"ADMINPASS"		=> $_REQUEST["adminpass"],
			"ADMINPASS2"	=> $_REQUEST["adminpass2"],
			"ADMINEMAIL"	=> $_REQUEST["adminemail"]
		));
		
		// Make sure all fields are filled
		$sError = "";
		if( $_REQUEST["submit"] == 1 )
		{
			if( empty($_REQUEST["adminemail"]) )
				$sError = "Please fill in the admin E-mail";
			if( $_REQUEST["adminpass2"] != $_REQUEST["adminpass"] )
				$sError = "The passwords you provided do not match";
			if( empty($_REQUEST["adminpass2"]) || !isset($_REQUEST["adminpass"]) )
				$sError = "Please fill in both password fields";
			if( empty($_REQUEST["adminname"]) )
				$sError = "Please fill in the admin username";
			if( empty($_REQUEST["dbhost"]) )
				$sError = "Please fill in the database host";
			if( empty($_REQUEST["dbpass"]) )
				$sError = "Please fill in the database password";
			if( empty($_REQUEST["dbuser"]) )
				$sError = "Please fill in the database username";
			if( empty($_REQUEST["dbname"]) )
				$sError = "Please fill in the database name";
			if( empty($_REQUEST["basepath"]) )
				$sError = "Please fill in the base path field";
			if( empty($_REQUEST["sitename"]) )
				$sError = "Please enter your site's name";
		}
		$tpl->assign( "ERROR", $sError );
		
		// if there is no error, proceed to the installation procedure
		if( ($_REQUEST["submit"] == 1) && ($sError == "") )
		{
			// Create tables in our database
			$sError = CreateTables( $_REQUEST );
			// Write settings to the "config.php" file
			if( $sError == "" )
				$sError = WriteConfig( $_REQUEST );
			else
				$tpl->assign( "ERROR", $sError );
			// proceed to the final step
			if( $sError == "" )
				header ("Location:index.php?step=2");
			else
				$tpl->assign( "ERROR", $sError );
		}
		break;
}

$tpl->parse( "CONTENT", "tplContent" );
$tpl->parse( "MAIN", "tplMain" );
$tpl->FastPrint( "MAIN" );
?>
