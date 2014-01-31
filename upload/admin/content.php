<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ show the content-related pages and process the given data
/
/*******************************************************************/
require_once( "../include/config.php" );
require_once( "../include/helpers.php" );
require_once( "../include/class.FastTemplate.php" );
require_once( "../include/class.Database.php" );
require( "../include/class.Cache.php" );

// few local constants
$cGamesOnPage = 20;

$tpl = new FastTemplate("templates");

require( "checklogin.php" );

$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );
$cache = new CCache();

$tpl->define( array(
	"tplHeader"	=> "header.html",
	"tplFooter"	=> "footer.html",
	"tplTop"	=> "block_contenttop.html"
));

$tpl->assign( array( 
	"SITEROOT"	=> $cSite["sSiteRoot"],
	"TITLE"		=> $cSite["sSiteTitle"] . " Administration - Manage Content",
	"SITETITLE"	=> $cSite["sSiteTitle"],
	"ATGAMES"	=> "Active" 
));

$arSubNav = Array( "SUBCATEGORIES", "SUBGAMES", "SUBNEWCAT", "SUBNEWGAME" );
foreach( $arSubNav as $BtnStyle )
	$tpl->assign( $BtnStyle, "" );

switch( $_REQUEST["action"] )
{
//-----------------------------------------------------------------------------
// Utility: Revise games number for each category
//-----------------------------------------------------------------------------
	case "revisecategories":
		$rescat = $db->super_query( "SELECT * FROM ".$cMain["dbPrefix"]."categories", true );
		foreach( $rescat as $category )
		{
			$res = $db->super_query( "SELECT category_id, COUNT(category_id) as count FROM ".$cMain["dbPrefix"]."games WHERE category_id=".$category["id"]." GROUP BY category_id" );
		   if( $res["category_id"] )
		   {
				$query = "UPDATE ".$cMain["dbPrefix"]."categories SET games=".$res["count"]." WHERE id=".$res["category_id"];
				$db->query( $query );
			}
		}
   	header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;

//-----------------------------------------------------------------------------
// Utility: Correct games' dimentions in the DB
//-----------------------------------------------------------------------------
	case "moviesizes":
		$res = $db->super_query( "SELECT id, width, height, file FROM ".$cMain["dbPrefix"]."games", true );
		foreach( $res as $game )
		{
			$sFile = "../content/swf/" . $game['file'];
			if( is_file($sFile) )
			{
				$arGameInfo = getimagesize( $sFile );
				if( $arGameInfo[0] > 0 )
					$db->query( "UPDATE LOW_PRIORITY ".$cMain["dbPrefix"]."games SET width=".$arGameInfo[0].", height=".$arGameInfo[1]." WHERE id=".$game["id"]." LIMIT 1" );
			} 
		}
			header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;

//-----------------------------------------------------------------------------
// Delete category: ask to confirm the action, select a category in which 
// the script should move games from the deleted category; Delete
//-----------------------------------------------------------------------------
	case "delcat":
		$tpl->define( "tplMain", "page_deletecat.html" );
		$tpl->assign( "SUBTITLE", "Delete Category" );
		// list categories for the "position" combobox
		$res = $db->super_query( "SELECT id, title, position FROM ".$cMain["dbPrefix"]."categories ORDER BY position ASC", true );
		$str = "";
		foreach( $res as $i => $cat )
			if( $_REQUEST["cat"] != $cat["id"] )
			{	
				if( $i == 0 )
					$str .= "<option selected value=\"".$cat["id"]."\">".$cat["title"]."</option>";
				else
					$str .= "<option value=\"".$cat["id"]."\">".$cat["title"]."</option>";
			}
		$tpl->assign( "CATEGORIES", $str );
		$tpl->assign( "CATTODELETE", $_REQUEST["cat"] );
		break;
	case "dodelcat":
		if( $_POST["submit"] == "Delete" )
		{
			// move games to one of the remaining categories
			$query = "UPDATE " . $cMain["dbPrefix"] . "games SET category_id=" . $_POST["moveto"] . " WHERE category_id=" . $_POST["cat_to_delete"];
			$db->query( $query );
			
			// clear cache for each of those games
			$res = $db->super_query( "SELECT id, latin_title, category_id FROM ".$cMain["dbPrefix"]."games WHERE category_id=" . $_POST["cat_to_delete"], true );
         foreach( $res as $game_rec )
            $cache->delete( 'game', $game_rec['id'], $game_rec['latin_title'] );

			// clear category's page cache
         $res = $db->super_query( "SELECT id, latin_title FROM ".$cMain["dbPrefix"]."categories WHERE id=".$_POST["cat_to_delete"]." LIMIT 1", false );
 			$cache->delete( 'cat', 0, $res['latin_title'] );
 			
 			// clear homepage cache
 			$cache->delete( 'home', 0, 0 );

			$query = "DELETE FROM ".$cMain["dbPrefix"]."categories WHERE id=" . $_POST["cat_to_delete"] . " LIMIT 1";
			$db->query( $query );
			header ("Location:content.php");			
		}
		else
			header ("Location:content.php");			
		break;
//-----------------------------------------------------------------------------
// Edit category
//-----------------------------------------------------------------------------
	case "editcat":
		$tpl->define( "tplMain", "page_addcat.html" );
		$tpl->define_dynamic( "dynPosition", "tplMain" );
		$tpl->clear_dynamic( "dynPosition" );
		
		$res = $db->super_query( "SELECT * FROM ".$cMain["dbPrefix"]."categories WHERE id=" . $_REQUEST["cat"] . " LIMIT 1" );
		$tpl->assign( array(
			"SUBTITLE"		=> "Edit Category",
			"BTNTITLE"		=> "Save Changes",
			"EDITCAT"		=> $_REQUEST["cat"],
			"ACTION"		=> "doeditcat",
			"CATTITLE"		=> $res["title"],
			"CATLATINTITLE"=> $res["latin_title"],
			"CATDESC"		=> $res["description"],
			"CATKEYWORDS"	=> $res["keywords"],
			"TITLEERROR"	=> "",
			"LATINTITLEERROR"	=> "",
			"DESCERROR"			=> "",
			"KEYWORDSERROR"	=> ""
		));
		if( $res["show_on_main"] == 1 )
			$tpl->assign( "ONMAINCHECKED", "checked" );
		else
			$tpl->assign( "ONMAINCHECKED", "" );
		break;
	case "doeditcat":
		$tpl->define( "tplMain", "page_addcat.html" );
		$tpl->define_dynamic( "dynPosition", "tplMain" );
		$tpl->clear_dynamic( "dynPosition" );
		$tpl->assign( array(
			"SUBTITLE"		=> "Edit Category",
			"BTNTITLE"		=> "Save Changes",
			"EDITCAT"		=> $_REQUEST["cat"],
			"ACTION"			=> "doeditcat",
			"CATTITLE"		=> $_POST["title"],
			"CATLATINTITLE"=> $_POST["latintitle"],
			"CATDESC"		=> $_POST["description"],
			"CATKEYWORDS"	=> $_POST["keywords"],
			"TITLEERROR"		=> "",
			"LATINTITLEERROR"	=> "",
			"DESCERROR"			=> "",
			"KEYWORDSERROR"	=> ""
		));
		if( empty($_POST["title"]) )
			$tpl->assign( "TITLEERROR", $cLang["errAEmpty"] );
		elseif( empty($_POST["description"]) )
			$tpl->assign( "DESCERROR", $cLang["errAEmpty"] );
		elseif( empty($_POST["keywords"]) )
			$tpl->assign( "KEYWORDSERROR", $cLang["errAEmpty"] );
		elseif( !empty($_POST['latintitle']) && Normalize($_POST['latintitle'])!=$_POST['latintitle'] )
         $tpl->assign( "LATINTITLEERROR", $cLang["errValue"] );
		else
		{
			$nOnMain = (empty($_POST["onmain"])) ? 0 : 1;
			$sTitle = get_magic_quotes_gpc() ? $_POST["title"] : mysql_escape_string($_POST["title"]);
			$sDescr = get_magic_quotes_gpc() ? $_POST["description"] : mysql_escape_string($_POST["description"]);
			$sKeywords = get_magic_quotes_gpc() ? $_POST["keywords"] : mysql_escape_string($_POST["keywords"]);
			$query = 'UPDATE ' . $cMain['dbPrefix'] . 'categories SET title="'.$sTitle.'"';
			if(!empty($_POST['latintitle']) )
				$query .= ', latin_title="'. $_POST['latintitle'].'"';
			$query .= ', show_on_main="'.$nOnMain.'", description="'.$sDescr.'", keywords="'.$sKeywords.'" WHERE id=' . $_POST["cat"] . ' LIMIT 1';
			$db->query( $query );
			
         $res = $db->super_query( "SELECT id, latin_title FROM ".$cMain["dbPrefix"]."categories WHERE id=".$_POST["cat"]." LIMIT 1", false );
 			$cache->delete( 'cat', 0, $res['latin_title'] );
			header ("Location:content.php");
		}
		break;
//-----------------------------------------------------------------------------
// Add category: show page, check for errors and do add a new category
//-----------------------------------------------------------------------------
	case "addcat":
	case "doaddcat":
		$tpl->define( "tplMain", "page_addcat.html" );
		// list categories for the "position" combobox
		$res = $db->super_query( "SELECT title, position FROM ".$cMain["dbPrefix"]."categories ORDER BY position ASC", true );
		$str = "";
		foreach( $res as $cat )
			$str .= "<option value=\"".$cat["position"]."\">".$cat["title"]."</option>";
		$tpl->assign( array(
			"SUBTITLE"		=> "Add Category",
			"SUBNEWCAT"		=> "Active",
			"BTNTITLE"		=> "Create Category",
			"EDITCAT"		=> "",
			"ACTION"			=> "doaddcat",
			"ONMAINCHECKED"=> "",
			"CATTITLE"		=> $_POST["title"],
			"CATLATINTITLE"=> $_POST['latintitle'],
			"CATDESC"		=> $_POST["description"],
			"CATKEYWORDS"	=> $_POST["keywords"],
			"ONMAINCHECKED"	=> empty($_POST["onmain"]) ? "" : "checked",
			"TITLEERROR"		=> "",
			"LATINTITLEERROR" => "",
			"DESCERROR"			=> "",
			"KEYWORDSERROR"	=> "",
			"POSITIONS"			=> $str
		));

		if( $_REQUEST["action"] == "doaddcat" )
		{
			$latinOK = empty($_POST['latintitle']) || Normalize($_POST['latintitle'])==$_POST['latintitle'];
			$tpl->assign( array(
				"TITLEERROR"		=>	empty($_POST["title"]) ? $cLang["errAEmpty"] : "",
				"LATINTITLEERROR" => !$latinOK ? $cLang["errValue"] : '',
				"DESCERROR"			=>	empty($_POST["description"]) ? $cLang["errAEmpty"] : "",
				"KEYWORDSERROR"	=>	empty($_POST["keywords"]) ? $cLang["errAEmpty"] : ""
			));
			if( !empty($_POST["title"]) && !empty($_POST["description"]) && !empty($_POST["keywords"]) && $latinOK )
			{
				// Everything's ok, create new record in the categories table
				$nOnMain = (empty($_POST["onmain"])) ? 0 : 1;
				$latintitle = empty($_POST['latintitle']) ? Normalize($_POST['title']) : $_POST['latintitle'];
				$sTitle = get_magic_quotes_gpc() ? $_POST["title"] : mysql_escape_string($_POST["title"]);
				$sDescr = get_magic_quotes_gpc() ? $_POST["description"] : mysql_escape_string($_POST["description"]);
				$sKeywords = get_magic_quotes_gpc() ? $_POST["keywords"] : mysql_escape_string($_POST["keywords"]);
				if( $_POST["position"] < 1000000 )
				{
					$db->query( "UPDATE " . $cMain["dbPrefix"]."categories SET position=position+1 WHERE position>".$_POST["position"] );
					$position = $_POST['position']+1;
				}
				else
				{
					$max = $db->super_query( "SELECT MAX(position) AS max FROM " . $cMain["dbPrefix"] . "categories", false );
					$position = $max["max"]+1;
				}
				$query = 'INSERT INTO ' . $cMain['dbPrefix']
					. 'categories (title, latin_title, show_on_main, description, keywords, position) VALUES ("'
					. $sTitle . '", "'. $latintitle . '", "' . $nOnMain . '", "' . $sDescr . '", "'
					. $sKeywords . '", ' . $position .')';
				$db->query( $query );

		 		// clear homepage cache
 				$cache->delete( 'home', 0, 0 );

				header ("Location:content.php");
			}
		}
		break;
//-----------------------------------------------------------------------------
// Add game
//-----------------------------------------------------------------------------
	case "addgame":
	case "doaddgame":
		$tpl->define( "tplMain", "page_addgame.html" );
		$tpl->define_dynamic( "dynCatList", "tplMain" );
		$tpl->define_dynamic( "dynStats", "tplMain" );
		$tpl->clear_dynamic( "dynStats" );
		foreach($cExt as $ext => $ext_tpl)
			$sExtensions .= ".$ext ";
		$tpl->assign( array( 
			"ACTION"				=>	"doaddgame",
			"SUBNEWGAME"		=>	"Active",
			"SUBTITLE"			=>	"Add Game",
			"GAMETITLE"			=>	$_REQUEST["title"],
			"GAMELATINTITLE"	=>	$_REQUEST["latintitle"],
			"GAMEDESC"			=>	$_REQUEST["description"],
			"GAMEKEYWORDS"		=>	$_REQUEST["keywords"],
			'FILEPATH'        => $_REQUEST['filepath'],
			"ACTIVECHECKED"	=>	empty($_REQUEST["active"]) ? "" : "Checked",
			"FEATUREDCHECKED"	=>	empty($_REQUEST["featured"]) ? "" : "Checked",
			"ERROR"				=>	"",
			"MESSAGE"			=>	empty($_REQUEST["result"]) ? "" : $cLang["msgAGameAdded"],
			"TITLEERROR"		=>	"",
			"LATINTITLEERROR"	=>	"",
			'FILEPATHERROR'   => '',
			"DESCERROR"			=>	"",
			"KEYWORDSERROR"	=>	"",
			"FILEERROR"			=>	"",
			"THUMBERROR"		=>	"",
			'SSHOTERROR'      => '',
			"DISABLEFILE"		=>	"",
			"DISABLETHUMBNAIL"	=>	"",
			"DISABLESCREENSHOT"	=>	"",
			"WIDTH"				=> empty($_POST["width"]) ? $_POST["width"] : "",
			"HEIGHT"				=> empty($_POST["height"]) ? $_POST["height"] : "",
			"EXTENSIONS"		=> $sExtensions
		));

		$res = $db->super_query( "SELECT * FROM ".$cMain["dbPrefix"]."categories ORDER BY position ASC", true );
		foreach( $res as $cat )
		{
			$tpl->assign( array(
				"CATTITLE"		=>	$cat["title"],
				"CATSELECTED"	=>	"",
				"CATID"			=>	$cat["id"]
			));
			$tpl->parse( "CATLIST", ".dynCatList" );
		}

		if( $_REQUEST["action"] == "doaddgame" )
		{
			$filename = $_FILES['file']['name'];
			$ext = substr( strrchr($filename, '.'), 1 );
   		$latinOK = empty($_POST["latintitle"]) || $_POST["latintitle"] == Normalize($_POST["latintitle"]);
			$tpl->assign( array( 
				"TITLEERROR"		=>	empty($_POST["title"]) ? $cLang["errAEmpty"] : "",
				'LATINTITLEERROR' => !$latinOK ? $cLang['errValue'] : '',
				"DESCERROR"			=>	empty($_POST["description"]) ? $cLang["errAEmpty"] : "",
				"KEYWORDSERROR"	=>	empty($_POST["keywords"]) ? $cLang["errAEmpty"] : "",
				"FILEPATHERROR"	=>	empty($filename) && empty($_POST['filepath']) ? $cLang["errAFile"] : "",
				"THUMBERROR"		=>	empty($_FILES['thumbnail']['name']) ? $cLang["errAEmpty"] : ""
			));
			$bOK = !empty($_POST["title"]) && $latinOK && !empty($_POST["description"]) && !empty($_POST["keywords"]);
			$bOK &= !empty($filename) || !empty($_POST['filepath']);
			$bOK &= !empty($_FILES['thumbnail']['name']);
			if( $bOK )
			{
			   // check the main file's extension (uploaded only)
			   if( !empty($filename) )
					$bOK &= array_key_exists( strtolower(substr(strrchr($filename, '.'), 1)), $cExt );
				if( !$bOK )
					$tpl->assign( "FILEPATHERROR", $cLang["errAWrongFormat"] );
				$str = strtolower( substr(strrchr($_FILES['thumbnail']['name'], '.'), 1) );
				if( !in_array($str, $cImgExt) )
				{
					$bOK = false;
					$tpl->assign( "THUMBERROR", $cLang["errAWrongFormat"] );
				}
				if( !empty($_FILES['screenshot']['name']) )
				{
				   $str = strtolower( substr(strrchr($_FILES['screenshot']['name'], '.'), 1) );
					if( !in_array($str, $cImgExt) )
					{
						$bOK = false;
                  $tpl->assign( 'SSHOTERROR', $cLang['errAWrongFormat'] );
					}
				}
			}
			if( $bOK )
			{
				// upload files
				$bOK &= move_uploaded_file($_FILES['thumbnail']['tmp_name'], "../content/thumbs/".$_FILES['thumbnail']['name']);
				if( !$bOK )
					$tpl->assign( "ERROR", $cLang["errAUpload"]."../content/thumbs/".$_FILES['thumbnail']['name'] . ". Error code: " . $_FILES['thumbnail']['error'] );
				else
				{
				   if( !empty($filename) ) // only if we have an uploaded game
						$bOK &= move_uploaded_file( $_FILES['file']['tmp_name'], "../content/swf/" . $filename );
					if( !$bOK )
					{
						$tpl->assign( "ERROR", $cLang["errAUpload"]."../content/swf/" . $filename . ". Error code: " . $_FILES['file']['error'] );
						unlink( "../content/thumbs/".$_FILES['thumbnail']['name'] );
					}
					else
					{	
						if( !empty($_FILES['screenshot']['name']) )
						$bOK &= move_uploaded_file( $_FILES['screenshot']['tmp_name'], "../content/screenshots/".$_FILES['screenshot']['name'] );
						if( !$bOK )
						{
							unlink( "../content/thumbs/".$_FILES['thumbnail']['name'] );
            			if( !empty($filename) ) // only if we have an uploaded game
								unlink( "../content/swf/" . $filename );
							$tpl->assign( "ERROR", $cLang["errAUpload"]."../content/screenshots/".$_FILES['screenshot']['name'] . ". Error code: " . $_FILES['screenshot']['error'] );
						} // screenshot
					} // game file
				} // thumbnail
			} 
			// upload done, make entry in the database
			if( $bOK )
			{
				$nActive = empty($_POST["active"]) ? 0 : 1;
				$nFeatured = empty($_POST["featured"]) ? 0 : 1;
				if( (empty($_POST["width"]) || empty($_POST["height"])) && ($ext == 'swf') )
				{
					$arGameInfo = getimagesize( "../content/swf/".$_FILES['file']['name'] );
					$width = $arGameInfo[0];
					$height = $arGameInfo[1];
				}
				else
				{
					$width = empty($_POST["width"]) ? 300 : $_POST["width"];
					$height = empty($_POST["height"]) ? 300 : $_POST["height"];
				}
				$sTitle = get_magic_quotes_gpc() ? $_POST["title"] : mysql_escape_string($_POST["title"]);
				$sLatinTitle = empty($_POST["latintitle"]) ? Normalize($_POST["title"]) : $_POST["latintitle"];
				$sDescr = get_magic_quotes_gpc() ? $_POST["description"] : mysql_escape_string($_POST["description"]);
				$sKeywords = get_magic_quotes_gpc() ? $_POST["keywords"] : mysql_escape_string($_POST["keywords"]);
				if( empty($filename) )
					$sFile = get_magic_quotes_gpc() ? $_POST['filepath'] : mysql_escape_string($_POST['filepath']);
				else
					$sFile = $_FILES['file']['name'];
				$query = 'INSERT DELAYED INTO '.$cMain['dbPrefix'].'games SET category_id='.$_POST['cat'].', added='.time()
					.', active='.$nActive.', title="'.$sTitle.'", latin_title="'.$sLatinTitle.'", file="'.$sFile
					.'", thumbnail="'.$_FILES['thumbnail']['name'].'", keywords="'.$sKeywords.'", description="'
					.$sDescr.'", width='.$width.', height='.$height.', featured='.$nFeatured;
				if( !empty($_FILES['screenshot']['name']) )
					$query .= ', large_img="' . $_FILES['screenshot']['name'] . '"';
				$db->query( $query );
				// update number of games in the categories table
				$db->query( "UPDATE " . $cMain["dbPrefix"] . "categories SET games=games+1 WHERE id=" . $_POST["cat"] . " LIMIT 1" );

				// clear category's page cache
   	      $res = $db->super_query( "SELECT id, latin_title FROM ".$cMain["dbPrefix"]."categories WHERE id=".$_POST["cat"]." LIMIT 1", false );
 				$cache->delete( 'cat', 0, $res['latin_title'] );

			 	// clear homepage cache
 				$cache->delete( 'home', 0, 0 );

				header ("Location:content.php?action=addgame&result=ok");
			}
		} // action=doaddgame
		break;
		
//-----------------------------------------------------------------------------
// List games
//-----------------------------------------------------------------------------
	case "listgames":
		$tpl->assign( "SUBGAMES", "Active" );
		$tpl->assign( "SUBTITLE", "Games" );
		$tpl->define( "tplMain", "page_games.html" );
		$tpl->define_dynamic( "dynList", "tplMain" );
		$tpl->define_dynamic( "dynPages", "tplMain" );
		$tpl->define_dynamic( "dynPagesTop", "tplMain" );
		$tpl->define_dynamic( "dynPagesTitle", "tplMain" );
	
		// checkboxes
		$tpl->assign( "FEATUREDCHECKED", empty($_REQUEST["featured"])? "" : "Checked" );
		$tpl->assign( "INACTIVECHECKED", empty($_REQUEST["inactive"])? "" : "Checked" );
		
		// Categories combobox
		$tpl->define_dynamic( "dynCatList", "tplMain" );
		$res = $db->super_query( "SELECT * FROM ".$cMain["dbPrefix"]."categories ORDER BY position ASC", true );
		foreach( $res as $cat )
		{
			$tpl->assign( array(
				"CATTITLE"		=>	$cat["title"],
				"GAMESNUMBER"	=>	$cat["games"],
				"CATID"			=>	$cat["id"],
				"CATSELECTED"	=>	($cat["id"] == $_REQUEST["cat"]) ? "selected" : ""
			));
			$tpl->parse( "CATLIST", ".dynCatList" );
		}

		// Column headers (highlight the column by which we sort the games)
		$arCurrent = Array( "id" => "SORTEDID", "title" => "SORTEDTITLE", "date" => "SORTEDDATE", "plays" => "SORTEDPLAYS", "rating" => "SORTEDRATING", "comments" => "SORTEDCOMMENTS" );
		foreach( $arCurrent as $col )
			$tpl->assign( $col, "" );
		if( !empty($_REQUEST["sort"]) )
			$tpl->assign( $arCurrent[$_REQUEST["sort"]], "Current" );
		else
			$tpl->assign( $arCurrent["id"], "Current" );
		
		// setup pagination and list sorting links
		$tpl->assign( array( 
			"ORDERLINK"		=>	"&cat=".$_REQUEST["cat"]."&title=".$_REQUEST["title"]."&featured=".$_REQUEST["featured"]."&inactive=".$_REQUEST["inactive"]."&page=".$_REQUEST["page"],
			"FILTERTITLE"	=>	$_REQUEST["title"],
			"FILTERACTION"	=>	$_SERVER["PHP_SELF"],
			"FILTERPAGE"	=>	$_REQUEST["page"]
		));
		$query = "SELECT g.*, COUNT(c.id) as comments FROM " . $cMain["dbPrefix"]."games as g LEFT JOIN " . $cMain["dbPrefix"]."comments as c ON g.id=c.game_id WHERE 1";
		if( !empty($_REQUEST["cat"]) )		$query .= " AND g.category_id=" . $_REQUEST["cat"]; 
		if( !empty($_REQUEST["title"]) )	$query .= " AND g.title LIKE \"" . $_REQUEST["title"] . "%\"";
		if( !empty($_REQUEST["featured"]) )	$query .= " AND g.featured=1";
		if( !empty($_REQUEST["inactive"]) )	$query .= " AND g.active=0";
		$query .= " GROUP BY g.id";
		if( !empty($_REQUEST["sort"]) )	
		{
			$arOrder = Array( "desc" => " DESC", "asc" => " ASC" );
			$arSort = Array( "id" =>" ORDER BY g.id", "title" => " ORDER BY g.title", "date" => " ORDER BY g.added", "plays" => " ORDER BY g.plays_total", "rating" => " ORDER BY g.rating", "comments" => " ORDER BY comments" );
			$query .= $arSort[$_REQUEST["sort"]] . $arOrder[$_REQUEST["order"]];
		}
		else
			$query .= " ORDER BY g.id DESC";
		$res = $db->super_query( $query, true );
		$nGames = count( $res );
		if( $nGames > 0 )
		{
			// Page links
			if( $nGames - $cGamesOnPage > $cGamesOnPage / 2 )
				for( $i=0; $i<(int)$nGames/$cGamesOnPage; $i++ )
				{
					$tpl->assign( array( 
						"PAGE"			=> $i+1,
						"PAGEACTIVE"	=> (($i+1==$_REQUEST["page"]) || (empty($_REQUEST["page"])&&$i==0)) ? "PageActive" : "",
						"PAGELINK"		=> "page=".($i+1)."&cat=".$_REQUEST["cat"]."&title=".$_REQUEST["title"]."&featured=".$_REQUEST["featured"]."&inactive=".$_REQUEST["inactive"]."&sort=".$_REQUEST["sort"]."&order=".$_REQUEST["order"]
					)); 
					$tpl->parse( "PAGES", ".dynPages" );
					$tpl->parse( "TOPPAGES", ".dynPagesTop" );
				}
			else
			{
				$tpl->clear_dynamic( "dynPages" );
				$tpl->clear_dynamic( "dynPagesTop" );
				$tpl->clear_dynamic( "dynPagesTitle" );
			}
			$nPage = empty($_REQUEST["page"]) ? 0 : $_REQUEST["page"]-1;
			if( $nPage*$cGamesOnPage > $nGames )
				$nPage = 0;

			// games for the curent page
			for( $i=$nPage*$cGamesOnPage; ($i<($nPage+1)*$cGamesOnPage) && ($i<$nGames); $i++ )
			{
				$game = $res[$i];
				$newState = $game["active"] ? "deactivate" : "activate";
				$newFState = $game["featured"] ? "defeature" : "feature";
				$tpl->assign(array(
					"GAMEID"	=>	$game["id"],
					"GAMETITLE"	=>	$game["title"],
					"GAMEADDED"	=>	date("M d, y", $game["added"]),
					"GAMEPLAYS"	=>	$game["plays_total"]>0 ? $game["plays_total"]."/".$game["plays_today"] : "-",
					"RATING"	=>	$game["rating"]>0 ? $game["rating"]/100 : "-",
					"STATE"		=>	$newState,
					"FSTATE"	=>	$newFState,
					"COMMENTS"	=>	$game["comments"]>0 ? $game["comments"] : "-"
				));
				$tpl->parse( "LIST", ".dynList" );
			}
		}
		else
		{
			$tpl->clear_dynamic( "dynList" );
			$tpl->clear_dynamic( "dynPages" );
			$tpl->clear_dynamic( "dynPagesTop" );
			$tpl->clear_dynamic( "dynPagesTitle" );
		}
		break;

//-----------------------------------------------------------------------------
// Change the "Featured" status (on/off)
//-----------------------------------------------------------------------------
	case "feature":
		$db->query( "UPDATE ".$cMain["dbPrefix"]."games SET featured=MOD(featured+1, 2) WHERE id=".$_REQUEST["id"] ); //set 1 if 0 and vice versa
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;

//-----------------------------------------------------------------------------
// Activate / Deactivate the game
//-----------------------------------------------------------------------------
	case "switchstate":
		$db->query( "UPDATE ".$cMain["dbPrefix"]."games SET active=MOD(active+1, 2) WHERE id=".$_REQUEST["id"]." LIMIT 1" );
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;

//-----------------------------------------------------------------------------
// Edit game
//-----------------------------------------------------------------------------
	case "editgame":
	case "doeditgame":
		$tpl->define( "tplMain", "page_addgame.html" );
		$tpl->define_dynamic( "dynCatList", "tplMain" );
		// retrieve game data in the database 
		$res = $db->super_query( "SELECT * FROM ".$cMain["dbPrefix"]."games WHERE id=".$_REQUEST["id"]." LIMIT 1", false );
		foreach($cExt as $ext => $ext_tpl)
			$sExtensions .= ".$ext ";
		$tpl->assign( array(
			"ACTION"				=>	"doeditgame",
			"SUBTITLE"			=>	"Edit Game",
			"GAMETITLE"			=>	$res["title"],
			"GAMELATINTITLE"	=>	$res["latin_title"],
			"GAMEID"				=>	$res["id"],
			"REFERRER"			=>	$_SERVER['HTTP_REFERER'],
			"ACTION"				=>	"doeditgame",
			"GAMEDESC"			=>	$res["description"],
			"GAMEKEYWORDS"		=>	$res["keywords"],
			'FILEPATH'        => htmlspecialchars($res['file']),
			"ACTIVECHECKED"	=>	empty($res["active"]) ? "" : "Checked",
			"FEATUREDCHECKED"	=>	empty($res["featured"]) ? "" : "Checked",
			"RATING"				=>	$res["rating"]/100,
			"VOTES"				=>	$res["votes"],
			"PLAYS"				=>	$res["plays_total"],
			"PLAYSTODAY"		=>	$res["plays_today"],
			"ERROR"				=>	"",
			"MESSAGE"			=>	"",
			"TITLEERROR"		=>	"",
			"FILEPATHERROR"	=>	"",
			"LATINTITLEERROR"	=>	"",
			"DESCERROR"			=>	"",
			"KEYWORDSERROR"	=>	"",
			"FILEERROR"			=>	"",
			"THUMBERROR"		=>	"",
			'SSHOTERROR'      => '',
			"DISABLEFILE"			=>	"disabled",
			"DISABLETHUMBNAIL"	=>	"disabled",
			"DISABLESCREENSHOT"	=>	"disabled",
			"WIDTH"					=>	$res["width"],
			"HEIGHT"					=>	$res["height"],
			"EXTENSIONS"			=> $sExtensions
		));
		// fill up the "categories" combobox
		$rescat = $db->super_query( "SELECT * FROM ".$cMain["dbPrefix"]."categories ORDER BY position ASC", true );
		foreach( $rescat as $cat )
		{
			$tpl->assign( array(
				"CATTITLE"		=>	$cat["title"],
				"CATSELECTED"	=>	($res["category_id"] == $cat["id"]) ? "Selected" : "",
				"CATID"			=>	$cat["id"]
			));
			$tpl->parse( "CATLIST", ".dynCatList" );
		}
		
		// process the form data
		if( $_REQUEST["action"] == "doeditgame" )
		{
		   $latinOK = empty($_POST["latintitle"]) || $_POST["latintitle"] == Normalize($_POST["latintitle"]);
			$tpl->assign( array( 
				"TITLEERROR"		=>	empty($_POST["title"]) ? $cLang["errAEmpty"] : "",
				"LATINTITLEERROR" => !$latinOK ? $cLang["errValue"] : '',
				"DESCERROR"			=>	empty($_POST["description"]) ? $cLang["errAEmpty"] : "",
				"KEYWORDSERROR"	=>	empty($_POST["keywords"]) ? $cLang["errAEmpty"] : ""
			));
			$bOK = !empty($_POST["title"]) && $latinOK && !empty($_POST["description"]) && !empty($_POST["keywords"]);
			if( $bOK )
			{
				$nActive = empty($_POST["active"]) ? 0 : 1;
				$nFeatured = empty($_POST["featured"]) ? 0 : 1;
				$sTitle = get_magic_quotes_gpc() ? $_POST["title"] : mysql_escape_string($_POST["title"]);
				$sDescr = get_magic_quotes_gpc() ? $_POST["description"] : mysql_escape_string($_POST["description"]);
				$sKeywords = get_magic_quotes_gpc() ? $_POST["keywords"] : mysql_escape_string($_POST["keywords"]);
				$query = 'UPDATE '.$cMain['dbPrefix'].'games SET category_id=' . $_POST['cat'] . ', active='
					. $nActive . ', title="' . $sTitle . '"';
				if( $_POST['latintitle'] )
				   $query .= ', latin_title="' . $_POST['latintitle'] . '"';
				if( !empty($_POST['filepath']) )
				   $query .= ', file="' . trim($_POST['filepath']) . '"';
				$query .= ', keywords="' . $sKeywords . '", description="' . $sDescr . '", featured=' . $nFeatured;
				if( !empty($_POST['width']) && ($_POST['width'] != $res['width']) )
					$query .= ', width=' . $_POST['width'];
				if( !empty($_POST['height']) && ($_POST['height'] != $res['height']) )
					$query .= ', height=' . $_POST['height'];
				$query .= '  WHERE id=' . $_POST['id'] . ' LIMIT 1';
				$db->query( $query );

            $res = $db->super_query( "SELECT * FROM ".$cMain["dbPrefix"]."games WHERE id=".$_POST["id"]." LIMIT 1", false );
    			$cache->delete( 'game', $_POST['id'], $res['latin_title'] );
				
				header( "Location:".$_POST["referrer"] );			
			}
		}
		break;

//-----------------------------------------------------------------------------
// Reset statistics 
//-----------------------------------------------------------------------------
	case "resetplays":
		$query = "UPDATE ".$cMain["dbPrefix"]."games SET plays_total=0, plays_today=0 WHERE id=".$_REQUEST["id"]." LIMIT 1";
		$db->query( $query );
		header( "Location:".$_SERVER['HTTP_REFERER'] );
		break;
	case "resetvotes":
		$query = "UPDATE ".$cMain["dbPrefix"]."games SET votes=0, votes_value=0, rating=0 WHERE id=".$_REQUEST["id"]." LIMIT 1";
		$db->query( $query );
		header( "Location:".$_SERVER['HTTP_REFERER'] );
		break;

//-----------------------------------------------------------------------------
// Delete the game
//-----------------------------------------------------------------------------
	case "deletegame":
		$tpl->define( "tplMain", "page_confirm.html" );
		$tpl->assign( array( 
			"SUBGAMES"	=>	"Active",
			"SUBTITLE"	=>	"Delete Game",
			"MESSAGE"	=>	$cLang["msgADeleteGame"],
			"FORMACTION"=>	"content.php",
			"ACTION"	=>	"dodelgame",
			"ID"		=>	$_REQUEST["id"],
			"REFERRER"	=>	$_SERVER['HTTP_REFERER']
		));
		break;
	case "dodelgame":
		if( $_POST["confirm"] == "Yes" )
		{
			// find the game's files and delete them
			$res = $db->super_query( "SELECT * FROM ".$cMain["dbPrefix"]."games WHERE id=".$_REQUEST["id"]." LIMIT 1", false );
			if( file_exists('../content/thumbs/'.$res['thumbnail']) )
				unlink( '../content/thumbs/'.$res['thumbnail'] );
			if( file_exists('../content/swf/'.$res['file']) )
				unlink( '../content/swf/'.$res['file'] );
			if( !empty($res['large_img']) )
				if( file_exists('../content/screenshots/'.$res['large_img']) )
					unlink( '../content/screenshots/'.$res['large_img'] );
			// update database
			$db->query( "DELETE FROM ".$cMain["dbPrefix"]."games WHERE id=".$_REQUEST["id"]." LIMIT 1" );
			// and delete cache for this game
			$cache->delete( 'game', $res['id'], $res['latin_title'] );
			
		}
		header( "Location:".$_POST["referrer"] );			
		break;

//-----------------------------------------------------------------------------
// "Manage content" home page: list categories
//-----------------------------------------------------------------------------
	case "listcategories":
	default:
		$tpl->define( "tplMain", "page_content.html" );
		$tpl->define_dynamic( "dynCatList", "tplMain" );
		$tpl->assign( "SUBTITLE", "Categories" );
		$tpl->assign( "SUBCATEGORIES", "Active" );
		$res = $db->super_query( "SELECT * FROM ".$cMain["dbPrefix"]."categories ORDER BY position ASC", true );
		foreach( $res as $cat )
		{
			$tpl->assign( array(
				"CATTITLE"		=>	$cat["title"],
				"GAMESNUMBER"	=>	$cat["games"],
				"CATID"			=>	$cat["id"]
			));
			$tpl->parse( "CATLIST", ".dynCatList" );
		}
		break;
}

$tpl->parse( "CONTENTTOP", "tplTop" );
$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");
?>
