<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ Page blocks' manipulation functions
/
/*******************************************************************/
require_once( "../include/config.php" );
require_once( "../include/helpers.php" );
require_once( "../include/class.FastTemplate.php" );
require_once( "../include/class.Database.php" );

$tpl = new FastTemplate("templates");

require( "checklogin.php" );

//-----------------------------------------------------------------------------
// Helper function which write the updated blocks config data to the file
// $deleteID - ID of the record to delete; 
// $addRec - a record to add to the array / config file
//-----------------------------------------------------------------------------
function WriteNewConfig( $_cB, $deleteID, $addRec, $modifyRec )
{
	$tplConfig = new FastTemplate("templates");
	$tplConfig->define( "tplConfig", "blocks.tpl" );
	$tplConfig->define_dynamic( "dynList", "tplConfig" );
	$modifyID = $modifyRec ? $modifyRec["id"] : 0;
	foreach( $_cB as $blockID => $block )
	{
		if( !$deleteID || ($deleteID != $blockID) )
		{
			if( !$modifyID || ($modifyID != $blockID) )
				$tplConfig->assign( array( "ID" => $blockID, "TITLE" => $block["title"], "MAX" => empty($block["max"]) ? 0 : $block["max"], "SORT" => empty($block["sort"]) ? "rand" : $block["sort"], "SCRIPT" => $block["script"], "FILE" => $block["file"], "COMMENTS" => htmlspecialchars($block["comments"]), "FRONT" => $block["front"], "CAT" => $block["cat"], "GAME" => $block["game"], "USER" => $block["user"], "CUSTOM" => $block["custom"] ));
			else
				$tplConfig->assign( array( "ID" => $blockID, "TITLE" => $modifyRec["title"], "MAX" => empty($modifyRec["max"]) ? 0 : $modifyRec["max"], "SORT" => empty($modifyRec["sort"]) ? "rand" : $modifyRec["sort"], "SCRIPT" => $modifyRec["script"], "FILE" => $modifyRec["file"], "COMMENTS" => htmlspecialchars($modifyRec["comments"]), "FRONT" => $modifyRec["front"], "CAT" => $modifyRec["cat"], "GAME" => $modifyRec["game"], "USER" => $modifyRec["user"], "CUSTOM" => $modifyRec["custom"] ));
			$tplConfig->parse( "LIST", ".dynList" );
		}
	}
	if( $addRec )
	{
		$tplConfig->assign( array( 
			"ID"		=>	$addRec["id"],
			"TITLE"		=>	$addRec["title"],
			"MAX"		=>	empty($addRec["max"]) ? 0 : $addRec["max"],
			"SORT"		=>	$addRec["sort"],
			"SCRIPT"	=>	$addRec["script"],
			"FILE"		=>	$addRec["file"],
			"COMMENTS"	=>	empty($addRec["comments"]) ? $addRec["title"] : htmlspecialchars($addRec["comments"]),
			"FRONT"		=>	$addRec["front"],	
			"CAT"		=>	$addRec["cat"],
			"GAME"		=>	$addRec["game"],
			"USER"		=>	$addRec["user"],
			"CUSTOM"	=>	$addRec["custom"]
		));
		$tplConfig->parse( "LIST", ".dynList" );
	}
	$tplConfig->parse( "CONFIG", "tplConfig" );
	$sNewConfig = $tplConfig->GetText( "CONFIG" );
	$fConfig = fopen( "../include/blocks_config.php", "w" );
	fwrite( $fConfig, $sNewConfig );
	fclose( $fConfig );
}
//------------------------------------------------------------------------------

$tpl->define( array(
	"tplHeader"	=> "header.html",
	"tplFooter"	=> "footer.html",
	"tplTop"	=> "block_blockstop.html"
));

$tpl->assign( array( 
	"SITEROOT"	=> $cSite["sSiteRoot"],
	"TITLE"		=> $cSite["sSiteTitle"] . " Administration - Manage Page Blocks",
	"SITETITLE"	=> $cSite["sSiteTitle"],
	"ATBLOCKS"	=> "Active" 
));

switch( $_REQUEST["action"] )
{
//-----------------------------------------------------------------------------
// Edit code for the given block
//-----------------------------------------------------------------------------
	case "editcode":
		$tpl->define( "tplMain", "page_blockcode.html" );
		$id = $_REQUEST["id"];
		$sFile = "../content/blocks/".$cB[$id]["file"];
		$sFile .= $cB[$id]["script"] ? ".php" : ".html";
		$sCode = file_exists($sFile) ? file_get_contents( $sFile ) : "Error! Can't open file $sFile";
		$tpl->assign( array( 
			"ACTION"		=>	"blocks.php",
			"SUBTITLE"		=>	"Edit code for " . $cB[$id]["title"],
			"CODEHINT"		=>	"Enter html code here for your static block or PHP (please be careful!) - for dynamic block.",
			"BLOCKID"		=>	$id,					
			"CODE"			=>	$sCode, 
			"CODEERROR"		=>	"",
			"ERROR"			=>	""
		));
		break;
	case "doeditcode":
		$tpl->define( "tplMain", "page_blockcode.html" );
		$id = $_REQUEST["id"];
		$sFile = "../content/blocks/" . $cB[$id]["file"];
		$sFile .= $cB[$id]["script"] ? ".php" : ".html";
		$fBlock = fopen( $sFile, "w+" );
		if( $fBlock )
		{
			$sCodeToWrite = get_magic_quotes_gpc() ? stripslashes ($_POST["code"]) : $_POST["code"];
			fwrite( $fBlock, $sCodeToWrite );
			fclose( $fBlock );
			header( "Location:blocks.php" );
		}
		else
		{
			$tpl->assign( array(
				"ACTION"		=>	"blocks.php",
				"SUBTITLE"		=>	"Edit code for " . $cB[$id]["title"],
				"BLOCKID"		=>	$id,					
				"CODE"			=>	$_POST["code"], 
				"ERROR"			=>	$cLang["errAFileWrite"],
				"CODEERROR"		=>	""
			));
		}
		break;
		
//-----------------------------------------------------------------------------
// Edit block
//-----------------------------------------------------------------------------
	case "edit":
		$tpl->define( "tplMain", "page_addblock.html" );
		$tpl->define_dynamic( "dynList", "tplMain" );
		$id = $_REQUEST["id"];
		$tpl->assign( array( 
			"SUBTITLE"		=>	"Edit Block",
			"ERROR"			=>	"",
			"ACTION"		=>	"doedit",
			"STATICCHECKED"	=>	$cB[$id]["script"] ? "" : "checked",
			"SCRIPTCHECKED"	=>	$cB[$id]["script"] ? "checked" : "",
			"FRONTCHECKED"	=>	$cB[$id]["front"] ? "Checked" : "",
			"CATCHECKED"	=>	$cB[$id]["cat"] ? "Checked" : "",
			"GAMECHECKED"	=>	$cB[$id]["game"] ? "Checked" : "",
			"USERCHECKED"	=>	$cB[$id]["user"] ? "Checked" : "",
			"CUSTOMCHECKED"	=>	$cB[$id]["custom"] ? "Checked" : "",
			"BLOCKID"		=>	$id,					
			"IDINPUT"		=>	"Hidden",
			"IDSTATIC"		=>	$id,
			"BLOCKTITLE"	=>	$cB[$id]["title"],			
			"MAX"			=>	$cB[$id]["max"],		
			"FILE"			=>	$cB[$id]["file"],			
			"COMMENTS"		=>	$cB[$id]["comments"],	
			"CODE"			=>	"",	"IDERROR" => "", "TITLEERROR" => "", "FILEERROR" => "", "CODEERROR" => ""
		));
		$tpl->define_dynamic( "dynCode", "tplMain" );
		$tpl->clear_dynamic( "dynCode" );
		foreach( $cSort as $sortID => $sortStr )
		{
			$tpl->assign( "SORTID", $sortID );
			$tpl->assign( "SORTSELECTED", $cB[$id]["sort"] == $sortID ? "selected" : "" );
			$tpl->assign( "SORTTITLE", $cSortTitle[$sortID] );
			$tpl->parse( "LIST", ".dynList" );
		}
		break;
	case "doedit":
		$tpl->define( "tplMain", "page_addblock.html" );
		$tpl->define_dynamic( "dynList", "tplMain" );
		$tpl->define_dynamic( "dynCode", "tplMain" );
		$tpl->clear_dynamic( "dynCode" );
		$tpl->assign( array( 
			"SUBTITLE"		=>	"Edit Blocks",
			"ERROR"			=>	"",
			"ACTION"		=>	"doedit",
			"STATICCHECKED"	=>	$_POST["type"] != "script" ? "checked" : "",
			"SCRIPTCHECKED"	=>	$_POST["type"] == "script" ? "checked" : "",
			"FRONTCHECKED"	=>	$_POST["front"] ? "" : "Checked",
			"CATCHECKED"	=>	$_POST["cat"] ? "" : "Checked",
			"GAMECHECKED"	=>	$_POST["game"] ? "" : "Checked",
			"USERCHECKED"	=>	$_POST["user"] ? "" : "Checked",
			"CUSTOMCHECKED"	=>	$_POST["custom"] ? "" : "Checked",
			"BLOCKID"		=>	$_POST["id"],
			"IDINPUT"		=>	"Hidden",
			"IDSTATIC"		=>	$_POST["id"],
			"BLOCKTITLE"	=>	$_POST["title"],
			"MAX"			=>	$_POST["max"],
			"FILE"			=>	$_POST["file"],
			"COMMENTS"		=>	$cB[$id]["comments"],
			"IDERROR"		=>	($_POST["action"] == "doedit") && (empty($_POST["id"])) ? $cLang["errAEmpty"] : "",
			"TITLEERROR"	=>	($_POST["action"] == "doedit") && (empty($_POST["title"])) ? $cLang["errAEmpty"] : "",
			"FILEERROR"		=>	($_POST["action"] == "doedit") && (empty($_POST["file"])) ? $cLang["errAEmpty"] : ""
		));
		foreach( $cSort as $sortID => $sortStr )
		{
			$tpl->assign( "SORTID", $sortID );
			$tpl->assign( "SORTSELECTED", $_POST["sort"] == $sortID ? "selected" : "" );
			$tpl->assign( "SORTTITLE", $cSortTitle[$sortID] );
			$tpl->parse( "LIST", ".dynList" );
		}
		$bOK = !empty($_POST["id"]) && !empty($_POST["title"]) && !empty($_POST["file"]);
		if( $bOK )
		{
			$editRec = array(
				"id"		=>	$_POST["id"],	
				"title"		=>	$_POST["title"],
				"max"		=>	empty($_POST["max"]) ? 0 : $_POST["max"],
				"sort"		=>	$_POST["sort"],
				"script"	=>	$_POST["type"] == "script" ? 1 : 0,
				"file"		=>	$_POST["file"],
				"comments"	=>	$_POST["comments"],
				"front"		=>	empty($_POST["front"]) ? 0 : 1,	
				"cat"		=>	empty($_POST["cat"]) ? 0 : 1,
				"game"		=>	empty($_POST["game"]) ? 0 : 1,
				"user"		=>	empty($_POST["user"]) ? 0 : 1,
				"custom"	=>	empty($_POST["custom"]) ? 0 : 1,
			);
			WriteNewConfig( $cB, 0, 0, $editRec );
			header( "Location:blocks.php" );			
		}
		break;
	
//-----------------------------------------------------------------------------
// Delete block
//-----------------------------------------------------------------------------
	case "delete":
		$tpl->define( "tplMain", "page_confirm.html" );
		$tpl->assign( array( 
			"CONTENTTOP"=>	"",
			"SUBTITLE"	=>	"Delete Block " . $_REQUEST["id"],
			"MESSAGE"	=>	$cLang["msgADeleteBlock"],
			"FORMACTION"=>	"blocks.php",
			"ACTION"	=>	"dodelete",
			"ID"		=>	$_REQUEST["id"],
			"REFERRER"	=>	$_SERVER['HTTP_REFERER']
		));
		break;
	case "dodelete":
		if( $_POST["confirm"] == "Yes" )
		{
			$sFile = "../content/blocks/" . $cB[$_REQUEST["id"]]["file"];
			$sFile .= $cB[$_REQUEST["id"]]["script"] ? ".php" : ".html";
			if( file_exists($sFile) )
				unlink( $sFile );
			WriteNewConfig( $cB, $_REQUEST["id"], 0, 0 );
		}
		header( "Location:blocks.php" );
		break;
		
//-----------------------------------------------------------------------------
// Add block
//-----------------------------------------------------------------------------
	case "addblock":
	case "doadd":
		$tpl->define( "tplMain", "page_addblock.html" );
		$tpl->define_dynamic( "dynList", "tplMain" );
		$tpl->define_dynamic( "dynCodeLink", "tplMain" );
		$tpl->clear_dynamic( "dynCodeLink" );
		$tpl->assign( array( 
			"SUBTITLE"		=>	"Blocks",
			"SUBADDBLOCK"	=>	"Active",
			"ERROR"			=>	"",
			"ACTION"		=>	"doadd",
			"STATICCHECKED"	=>	$_POST["type"] != "script" ? "checked" : "",
			"SCRIPTCHECKED"	=>	$_POST["type"] == "script" ? "checked" : "",
			"FRONTCHECKED"	=>	empty($_POST["front"]) ? "" : "Checked",
			"CATCHECKED"	=>	empty($_POST["cat"]) ? "" : "Checked",
			"GAMECHECKED"	=>	empty($_POST["game"]) ? "" : "Checked",
			"USERCHECKED"	=>	empty($_POST["user"]) ? "" : "Checked",
			"CUSTOMCHECKED"	=>	empty($_POST["custom"]) ? "" : "Checked",
			"BLOCKID"		=>	empty($_POST["id"]) ? "" : $_POST["id"],
			"IDINPUT"		=>	"Text",
			"IDSTATIC"		=>	"",
			"BLOCKTITLE"	=>	empty($_POST["title"]) ? "" : $_POST["title"],
			"MAX"			=>	$_POST["max"],
			"FILE"			=>	empty($_POST["file"]) ? "" : $_POST["file"],
			"COMMENTS"		=>	empty($_POST["comments"]) ? "" : $_POST["comments"],
			"CODE"			=>	empty($_POST["code"]) ? "" : $_POST["code"],
			"IDERROR"		=>	($_REQUEST["action"] == "doadd") && (empty($_POST["id"])) ? $cLang["errAEmpty"] : "",
			"TITLEERROR"	=>	($_REQUEST["action"] == "doadd") && (empty($_POST["title"])) ? $cLang["errAEmpty"] : "",
			"FILEERROR"		=>	($_REQUEST["action"] == "doadd") && (empty($_POST["file"])) ? $cLang["errAEmpty"] : "",
			"CODEERROR"		=>	($_REQUEST["action"] == "doadd") && (empty($_POST["code"])) ? $cLang["errAEmpty"] : ""
		));
		foreach( $cSort as $sortID => $sortStr )
		{
			$tpl->assign( "SORTID", $sortID );
			$tpl->assign( "SORTSELECTED", $_POST["sort"] == $sortID ? "selected" : "" );
			$tpl->assign( "SORTTITLE", $cSortTitle[$sortID] );
			$tpl->parse( "LIST", ".dynList" );
		}
		
		if( $_REQUEST["action"] == "doadd" )
		{
			$bCanAdd = !empty($_POST["id"]) && !empty($_POST["title"]) && !empty($_POST["file"]) && !empty($_POST["code"]);
		
			// create a new file and write the block's code there first
			if( $bCanAdd )
			{
				$sFile = "../content/blocks/".$_POST["file"];
				$sFile .= ($_POST["type"]=="script") ? ".php" : ".html";
				$fBlock = fopen( $sFile, "w+" );
				if( $fBlock )
				{
					$sCodeToWrite = get_magic_quotes_gpc() ? stripslashes ($_POST["code"]) : $_POST["code"];
					fwrite( $fBlock, $sCodeToWrite );
					fclose( $fBlock );
				}
				else
				{
					$bCanAdd = false;
					$tpl->assign( "ERROR", $cLang["errAFileWrite"] );
				}
			}
			// add new line to the config file
			if( $bCanAdd )
			{
				$newRec = array( 
					"id"		=>	$_POST["id"],	
					"title"		=>	$_POST["title"],
					"max"		=>	empty($_POST["max"]) ? 0 : $_POST["max"],
					"sort"		=>	$_POST["sort"],
					"script"	=>	$_POST["type"] == "script" ? 1 : 0,
					"file"		=>	$_POST["file"],
					"comments"	=>	$_POST["comments"],
					"front"		=>	empty($_POST["front"]) ? 0 : 1,	
					"cat"		=>	empty($_POST["cat"]) ? 0 : 1,
					"game"		=>	empty($_POST["game"]) ? 0 : 1,
					"user"		=>	empty($_POST["user"]) ? 0 : 1,
					"custom"	=>	empty($_POST["custom"]) ? 0 : 1,
				);
				WriteNewConfig( $cB, 0, $newRec, 0 );		
				header( "Location:blocks.php" );
			}
		} // doadd
		break;

//-----------------------------------------------------------------------------
// Activate / deactivate block
//-----------------------------------------------------------------------------
	case "switch":
		// prepare an "Edit" record for the settings table
		$editRec = $cB[$_REQUEST["id"]];
		$editRec["id"] = $_REQUEST["id"];
		if( $_REQUEST["page"] == "front" )
			$editRec["front"] = $editRec["front"] ? 0 : 1;
		if( $_REQUEST["page"] == "cat" )
			$editRec["cat"] = $editRec["cat"] ? 0 : 1;
		if( $_REQUEST["page"] == "game" )
			$editRec["game"] = $editRec["game"] ? 0 : 1;
		if( $_REQUEST["page"] == "user" )
			$editRec["user"] = $editRec["user"] ? 0 : 1;
		if( $_REQUEST["page"] == "custom" )
			$editRec["custom"] = $editRec["custom"] ? 0 : 1;

		WriteNewConfig( $cB, 0, 0, $editRec );
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;		
		
//-----------------------------------------------------------------------------
// "Manage blocks" home page: list categories
//-----------------------------------------------------------------------------
	case "listblocks":
	default:
		$tpl->define( "tplMain", "page_blocks.html" );
		$tpl->assign( "SUBTITLE", "Blocks" );
		$tpl->assign( "SUBBLOCKS", "Active" );
		$tpl->define_dynamic( "dynList", "tplMain" );
		foreach( $cB as $blockID => $block )
		{
			$tpl->assign( array( 
				"ID"			=>	$blockID,
				"BLOCKTITLE"	=>	$block["title"],
				"COMMENTS"		=>	$block["comments"],
				"FRONTSTATE"	=>	$block["front"] ? "deactivate" : "activate",
				"CATSTATE"		=>	$block["cat"] ? "deactivate" : "activate",
				"GAMESTATE"		=>	$block["game"] ? "deactivate" : "activate",
				"USERSTATE"		=>	$block["user"] ? "deactivate" : "activate",
				"CUSTOMSTATE"	=>	$block["custom"] ? "deactivate" : "activate",
			));
			$tpl->parse( "BLOCKLIST", ".dynList" );
		}
		break;
}

$tpl->parse( "CONTENTTOP", "tplTop" );
$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");
?>