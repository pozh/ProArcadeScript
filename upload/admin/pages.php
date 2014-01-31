<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ Add / Delete / Edit aditional pages (about, terms of use, etc.)
/
/*******************************************************************/
require_once( "../include/config.php" );
require_once( "../include/helpers.php" );
require_once( "../include/class.FastTemplate.php" );
require_once( "../include/class.Database.php" );
require( "../include/class.Cache.php" );

$tpl = new FastTemplate("templates");

require( "checklogin.php" );

$cache = new CCache();

//-----------------------------------------------------------------------------
// Helper function which write the updated config data to the file
// $deleteID - ID of the record to delete; 
// $addRec - a record to add 
//-----------------------------------------------------------------------------
function WriteNewConfig( $_cP, $deleteID, $addRec, $modifyRec )
{
	$tplConfig = new FastTemplate("templates");
	$tplConfig->define( "tplConfig", "pages.tpl" );
	$tplConfig->define_dynamic( "dynList", "tplConfig" );
	$modifyID = $modifyRec ? $modifyRec["id"] : 0;
	//are there any pages at all?
	$nPages = 0;  
	foreach( $_cP as $pageID => $page )
	{
		if( !$deleteID || ($deleteID != $pageID) )
		{
			if( !$modifyID || ($modifyID != $pageID) )
				$tplConfig->assign( array( 
					"ID" => $pageID, 
					"TITLE" => htmlspecialchars($page["title"]), 
					"DESCRIPTION" => htmlspecialchars($page["description"]), 
					"KEYWORDS" => htmlspecialchars($page["keywords"]), 
					"ACTIVE" => $page["active"], 
					"MENUTITLE" => htmlspecialchars($page["menutitle"]), 
					"MAINMENU" => $page["mainmenu"],
					"FOOTERMENU" => $page["footermenu"],
					"COMMENTS" => htmlspecialchars($page["comments"]) 
				));
			else
				$tplConfig->assign( array( 
					"ID" => $pageID, 
					"TITLE" => htmlspecialchars($modifyRec["title"]), 
					"DESCRIPTION" => htmlspecialchars($modifyRec["description"]), 
					"KEYWORDS" => htmlspecialchars($modifyRec["keywords"]), 
					"ACTIVE" => $modifyRec["active"], 
					"MENUTITLE" => htmlspecialchars($modifyRec["menutitle"]), 
					"MAINMENU" => $modifyRec["mainmenu"],
					"FOOTERMENU" => $modifyRec["footermenu"],
					"COMMENTS" => htmlspecialchars($modifyRec["comments"]) 
				));
			$tplConfig->parse( "LIST", ".dynList" );
			$nPages++;
		}
	}
	if( $addRec )
	{
		$tplConfig->assign( array( 
			"ID" => $addRec['id'], 
			"TITLE" => htmlspecialchars($addRec["title"]), 
			"DESCRIPTION" => htmlspecialchars($addRec["description"]), 
			"KEYWORDS" => htmlspecialchars($addRec["keywords"]), 
			"ACTIVE" => $addRec["active"], 
			"MENUTITLE" => htmlspecialchars($addRec["menutitle"]), 
			"MAINMENU" => $addRec["mainmenu"],
			"FOOTERMENU" => $addRec["footermenu"],
			"COMMENTS" => htmlspecialchars($addRec["comments"]) 
		));
		$tplConfig->parse( "LIST", ".dynList" );
		$nPages++;
	}
	if( $nPages == 0 )
		$tplConfig->clear_dynamic( "dynList" );
	$tplConfig->parse( "CONFIG", "tplConfig" );
	$sNewConfig = $tplConfig->GetText( "CONFIG" );
	$fConfig = fopen( "../include/pages_config.php", "w" );
	fwrite( $fConfig, $sNewConfig );
	fclose( $fConfig );
} //WriteNewConfig
//------------------------------------------------------------------------------

$tpl->define( array(
	"tplTop"	=> "block_pagestop.html",
	"tplHeader"	=> "header.html",
	"tplFooter"	=> "footer.html"
));

$tpl->assign( array( 
	"SITEROOT"	=> $cSite["sSiteRoot"],
	"TITLE"		=> $cSite["sSiteTitle"] . " Administration - Manage Pages",
	"SITETITLE"	=> $cSite["sSiteTitle"],
	"ATPAGES"	=> "Active",
	"SUBPAGES"	=> "",
	"SUBADDPAGE"=> "" 
));

switch( $_REQUEST["action"] )
{
//-----------------------------------------------------------------------------
// Edit one of main pages
//-----------------------------------------------------------------------------
	case "editcode":
		$tpl->define( "tplMain", "page_blockcode.html" );
		$sFile = "../templates/".$cSite["sTemplate"]."/page_".$_REQUEST["page"];
		$sCode = file_exists($sFile) ? file_get_contents( $sFile ) : "Error! Can't open file $sFile";
		$tpl->assign( array( 
			"ACTION"		=>	"pages.php",
			"SUBTITLE"		=>	"Edit page_".$_REQUEST["page"],
			"CODEHINT"		=>	"Important: All block IDs must be enclosed by ! signs. <br/><br/>Please note, any code changes will affect on the current site template only. Other templates will not be changed.",
			"BLOCKID"		=>	$_REQUEST["page"],	// we edit page, not a block, so have to store page name somewhere
			"CODE"			=>	$sCode, 
			"CODEERROR"		=>	"",
			"ERROR"			=>	""
		));
		break;

	case "doeditcode":
		$tpl->define( "tplMain", "page_blockcode.html" );
		$sFile = "../templates/".$cSite["sTemplate"]."/page_".$_REQUEST["id"];
		$fFile = fopen( $sFile, "w+" );
		if( $fFile )
		{
			$sCode = get_magic_quotes_gpc() ? stripslashes($_POST["code"]) : $_POST["code"];
			fwrite( $fFile, $sCode );
			fclose( $fFile );
			header( "Location:pages.php" );
		}
		else
		{
			$tpl->assign( array(
				"ACTION"		=>	"pages.php",
				"SUBTITLE"		=>	"Edit page_".$_POST["id"],
				"BLOCKID"		=>	$_POST["id"],					
				"CODE"			=>	$sCode, 
				"ERROR"			=>	$cLang["errAFileWrite"],
				"CODEERROR"		=>	""
			));
		}
		break;
		
//-----------------------------------------------------------------------------
// Edit custom page
//-----------------------------------------------------------------------------
	case "edit":
		$tpl->define( "tplMain", "page_addpage.html" );
		$id = $_REQUEST['id'];
		$sFile = "../docs/$id";
		$tpl->assign( array( 
			"SUBTITLE"			=>	"Edit page '" . $cP[$id]['title'] . "'",
			"ERROR"				=>	"",
			"ACTION"			=>	"doedit",
			"IDSTATIC"			=>	$id,
			"IDINPUT"			=>	"Hidden",
			"PAGEID"			=>	$id,
			"IDERROR"			=>	"",
			"PAGETITLE"			=>	$cP[$id]['title'],
			"TITLEERROR"		=>	"",
			"PAGEDESC"			=>	$cP[$id]['description'],
			"PAGEKEYWORDS"		=>	$cP[$id]['keywords'],
			"MENUTITLE"			=>	$cP[$id]['menutitle'],
			"MENUTITLEERROR"	=>	"",
			"MAINCHECKED"		=>	$cP[$id]['mainmenu'] ? "Checked" : "",
			"FOOTERCHECKED"		=>	$cP[$id]['footermenu'] ? "Checked" : "",
			"ACTIVECHECKED"		=>	$cP[$id]['active'] ? "Checked" : "",
			"COMMENTS"			=>	$cP[$id]['comments'],
			"CODE"				=>	file_exists($sFile) ? file_get_contents( $sFile ) : "Error! Can't open file $sFile",
			"CODEERROR"			=>	""
		));
		break;

	case "doedit":
		$tpl->define( "tplMain", "page_addpage.html" );
		$id = $_REQUEST['id'];
		$sCode = get_magic_quotes_gpc() ? stripslashes($_POST["code"]) : $_POST["code"];
		$tpl->assign( array(
			"SUBTITLE"			=>	"Edit page '" . $_POST['title'] . "'",
			"ERROR"				=>	"",
			"ACTION"			=>	"doedit",
			"IDSTATIC"			=>	$id,
			"IDINPUT"			=>	"Hidden",
			"PAGEID"			=>	$id,
			"IDERROR"			=>	"",
			"PAGETITLE"			=>	$_POST['title'],
			"TITLEERROR"		=>	empty( $_POST['title'] ) ? $cLang['errAEmpty'] : "",
			"PAGEDESC"			=>	$_POST['description'],
			"PAGEKEYWORDS"		=>	$_POST['keywords'],
			"MENUTITLE"			=>	$_POST['menutitle'],
			"MENUTITLEERROR"	=>	empty( $_POST['menutitle'] ) ? $cLang['errAEmpty'] : "",
			"MAINCHECKED"		=>	empty( $_POST['mainmenu'] ) ? "" : "Checked",
			"FOOTERCHECKED"		=>	empty( $_POST['footermenu'] ) ? "" : "Checked",
			"ACTIVECHECKED"		=>	empty( $_POST['active'] ) ? "" : "Checked",
			"COMMENTS"			=>	$_POST['comments'],
			"CODE"				=>	$sCode,
			"CODEERROR"			=>	empty($sCode) ? $cLang['errAEmpty'] : ""
		));

		// Check for errors
		$bOK = true;
		$bOK &= !empty( $_POST['title'] );
		$bOK &= !empty( $_POST['menutitle'] );
		// Try to create a file
		if( $bOK )
		{
			$sFile = "../docs/$id";
			$fFile = fopen( $sFile, "w+" );
			if( $fFile )
			{
				$bOK = fwrite( $fFile, $sCode );
				fclose( $fFile );
			}
			else
			{
				$bOK = false;
				$tpl->assign( "ERROR", $cLang["errAFileWrite"] );
			}
		}
		// Now if the file is updated, modify the record in the pages' config.
		if( $bOK )
		{
			$pageRec = array( 
				"id"			=>	$id, 
				"title"			=>	$_POST['title'], 
				"description"	=>	$_POST['description'], 
				"keywords"		=>	$_POST['keywords'], 
				"active"		=>	empty($_POST['active']) ? 0 : 1, 
				"menutitle"		=>	$_POST['menutitle'], 
				"mainmenu"		=>	empty($_POST['mainmenu']) ? 0 : 1,
				"footermenu"	=>	empty($_POST['footermenu']) ? 0 : 1,
				"comments"		=>	$_POST['comments']
			); 
			WriteNewConfig( $cP, 0, 0, $pageRec );
			
			// clear page's cache
			$cache->delete( 'page', $id, 0 );

			header( "Location:pages.php" );
		}
		break;
	
//-----------------------------------------------------------------------------
// Delete page
//-----------------------------------------------------------------------------
	case "delete":
		$tpl->define( "tplMain", "page_confirm.html" );
		$tpl->assign( array( 
			"CONTENTTOP"=>	"",
			"SUBTITLE"	=>	"Delete Page " . $_REQUEST["id"],
			"MESSAGE"	=>	$cLang["msgADeletePage"],
			"FORMACTION"=>	"pages.php",
			"ACTION"	=>	"dodelete",
			"ID"		=>	$_REQUEST["id"],
			"REFERRER"	=>	$_SERVER['HTTP_REFERER']
		));
		break;
	case "dodelete":
		if( $_POST["confirm"] == "Yes" )
		{
			$sFile = "../docs/" . $_REQUEST["id"];
			unlink( $sFile );
			WriteNewConfig( $cP, $_REQUEST["id"], 0, 0 );
		}
		header( "Location:pages.php" );
		break;
		

//-----------------------------------------------------------------------------
// Activate / deactivate page
//-----------------------------------------------------------------------------
	case "switch":
		// prepare an "Edit" record for the settings table
		$editRec = $cP[$_REQUEST["id"]];
		$editRec["id"] = $_REQUEST["id"];
		if( !empty($_REQUEST['menu']) )
			$editRec[$_REQUEST["menu"]] = $editRec[$_REQUEST["menu"]] ? 0 : 1;
		else
			$editRec['active'] = $editRec['active'] ? 0 : 1;
		WriteNewConfig( $cP, 0, 0, $editRec );
		header( "Location:pages.php" );
		break;		

//-----------------------------------------------------------------------------
// Move up, move down in the list
//-----------------------------------------------------------------------------
	case "moveup":
	case "movedown":
		$id = $_REQUEST['id'];
		$arKeys = array_keys( $cP );
		$arPos = array_flip( $arKeys );
		if( $_REQUEST['action'] == "moveup" )
		{
			if( $arKeys[0] != $id )
			{
				$arKeys[$arPos[$id]] = $arKeys[$arPos[$id]-1];
				$arKeys[$arPos[$id]-1] = $id;
			}
			else
				header( "Location:pages.php" );
		}
		else
			if( $arPos[$id] < count($arKeys)-1 )
			{
				$arKeys[$arPos[$id]] = $arKeys[$arPos[$id]+1];
				$arKeys[$arPos[$id]+1] = $id;
			}
			else
				header( "Location:pages.php" );
		
		$tplConfig = new FastTemplate("templates");
		$tplConfig->define( "tplConfig", "pages.tpl" );
		$tplConfig->define_dynamic( "dynList", "tplConfig" );
		foreach( $arKeys as $key )
		{
			$tplConfig->assign( array( 
				"ID" => $key, 
				"TITLE" => $cP[$key]["title"], 
				"DESCRIPTION" => $cP[$key]["description"], 
				"KEYWORDS" => $cP[$key]["keywords"], 
				"ACTIVE" => $cP[$key]["active"], 
				"MENUTITLE" => $cP[$key]["menutitle"], 
				"MAINMENU" => $cP[$key]["mainmenu"],
				"FOOTERMENU" => $cP[$key]["footermenu"],
				"COMMENTS" => $cP[$key]["comments"] 
			));
			$tplConfig->parse( "LIST", ".dynList" );
		}
		$tplConfig->parse( "CONFIG", "tplConfig" );
		$sNewConfig = $tplConfig->GetText( "CONFIG" );
		$fConfig = fopen( "../include/pages_config.php", "w" );
		fwrite( $fConfig, $sNewConfig );
		fclose( $fConfig );
		header( "Location:pages.php" );
		break;
		
//-----------------------------------------------------------------------------
// List pages
//-----------------------------------------------------------------------------
	case "add":
		$tpl->define( "tplMain", "page_addpage.html" );
		$tpl->assign( array( 
			"SUBTITLE"			=>	"Add page",
			"SUBADDPAGE"		=>	"Active",
			"ERROR"				=>	"",
			"ACTION"			=>	"doadd",
			"IDSTATIC"			=>	"",
			"IDINPUT"			=>	"Text",
			"PAGEID"			=>	"",
			"IDERROR"			=>	"",
			"PAGETITLE"			=>	"",
			"TITLEERROR"		=>	"",
			"PAGEDESC"			=>	"",
			"PAGEKEYWORDS"		=>	"",
			"MENUTITLE"			=>	"",
			"MENUTITLEERROR"	=>	"",
			"MAINCHECKED"		=>	"Checked",
			"FOOTERCHECKED"		=>	"Checked",
			"ACTIVECHECKED"		=>	"Checked",
			"COMMENTS"			=>	"",
			"CODE"				=>	"",
			"CODEERROR"			=>	""
		));
		break;
	case "doadd":
		$tpl->define( "tplMain", "page_addpage.html" );
		$bOK = true;
		$tpl->assign( array( 
			"SUBTITLE"			=>	"Add page '" . $_POST['title'] . "'",
			"SUBADDPAGE"		=>	"Active",
			"ERROR"				=>	"",
			"ACTION"			=>	"doadd",
			"IDSTATIC"			=>	"",
			"IDINPUT"			=>	"Text",
			"PAGEID"			=>	$_POST['id'],
			"IDERROR"			=>	"",
			"PAGETITLE"			=>	$_POST['title'],
			"TITLEERROR"		=>	empty($_POST['title']) ? $cLang['errAEmpty'] : "",
			"PAGEDESC"			=>	$_POST['description'],
			"PAGEKEYWORDS"		=>	$_POST['keywords'],
			"MENUTITLE"			=>	$_POST['menutitle'],
			"MENUTITLEERROR"	=>	empty($_POST['menutitle']) ? $cLang['errAEmpty'] : "",
			"MAINCHECKED"		=>	empty($_POST['mainmenu']) ? "" : "Checked",
			"FOOTERCHECKED"		=>	empty($_POST['footermenu']) ? "" : "Checked",
			"ACTIVECHECKED"		=>	empty($_POST['active']) ? "" : "Checked",
			"COMMENTS"			=>	$_POST['comments'],
			"CODE"				=>	$_POST['code'],
			"CODEERROR"			=>	empty($_POST['code']) ? $cLang['errAEmpty'] : ""
		));
		// Check for errors
		$bOK &= !empty( $_POST['id'] );
		if( !$bOK )
			$tpl->assign( "IDERROR", $cLang['errAEmpty'] );
		else
		{
			$bOK &= !file_exists("../docs/".$_POST['id']);
			if( !$bOK )
				$tpl->assign( "IDERROR", $cLang['errAPageExists'] );
		}
		$bOK &= !empty( $_POST['title'] );
		$bOK &= !empty( $_POST['menutitle'] );
		// Try to create a file
		if( $bOK )
		{
			$sFile = "../docs/".$_POST["id"];
			$fFile = fopen( $sFile, "w+" );
			if( $fFile )
			{
				$sCode = get_magic_quotes_gpc() ? stripslashes($_POST["code"]) : $_POST["code"];
				$bOK = fwrite( $fFile, $sCode );
				fclose( $fFile );
			}
			else
			{
				if( file_exists($sFile) )
					unlink( $sFile );
				$bOK = false;
				$tpl->assign( "ERROR", $cLang["errAFileWrite"] );
			}
		}
		// Now if the file is created, add a new record to the pages' config.
		if( $bOK )
		{
			$pageRec = array( 
				"id"			=>	$_POST['id'], 
				"title"			=>	$_POST['title'], 
				"description"	=>	$_POST['description'], 
				"keywords"		=>	$_POST['keywords'], 
				"active"		=>	empty($_POST['active']) ? 0 : 1, 
				"menutitle"		=>	$_POST['menutitle'], 
				"mainmenu"		=>	empty($_POST['mainmenu']) ? 0 : 1,
				"footermenu"	=>	empty($_POST['footermenu']) ? 0 : 1,
				"comments"		=>	$_POST['comments']
			); 
			WriteNewConfig( $cP, 0, $pageRec, 0 );
			header( "Location:pages.php" );
		}
		break;
				
	case "list":
	default:
		$tpl->define( "tplMain", "page_pages.html" );
		$tpl->define_dynamic( "dynList", "tplMain" );
		$tpl->assign( "SUBTITLE", "List pages" );
		$tpl->assign( "SUBPAGES", "Active" );
		if( count($cP) )
			foreach( $cP as $pageID => $page )
			{
				$tpl->assign( array( 
					"ID"			=>	$pageID,
					"PAGETITLE"		=>	$page["title"],
					"COMMENTS"		=>	$page["comments"],
					"MAINSTATE"		=>	$page["mainmenu"] ? "deactivate" : "activate",
					"FOOTERSTATE"	=>	$page["footermenu"] ? "deactivate" : "activate",
					"STATE"			=>	$page["active"] ? "deactivate" : "activate",
				));
				$tpl->parse( "PAGESLIST", ".dynList" );
			}
		else
			$tpl->clear_dynamic( "dynList" );
		break;
}

$tpl->parse( "CONTENTTOP", "tplTop" );
$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");
?>