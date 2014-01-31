<?php
/*******************************************************************
/ ProArcadeScript
/ File description:
/ Import games data from other databases
/
/*******************************************************************/
require_once( "../include/config.php" );
require_once( "../include/helpers.php" );
require_once( "../include/class.FastTemplate.php" );
require_once( "../include/class.Database.php" );

$tpl = new FastTemplate( "templates" );

include( "checklogin.php" );

// Connect to the database
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

$tpl->define( array(
	"tplHeader"	=> "header.html",
	"tplFooter"	=> "footer.html",
	"tplMain"	=> "page_install.html"
));

if( isset($_POST["nextstep"]) )
{
	$tpl->define( "tplMain", "import".$_POST["nextstep"].".html" );
	if( isset($_POST["games"]) ) $tpl->assign( "GAMES", $_POST["games"] );
	if( isset($_POST["categories"]) ) $tpl->assign( "CATEGORIES", $_POST["categories"] );
	if( isset($_POST["cat_id"]) ) $tpl->assign( "CATID", $_POST["cat_id"] );
	if( isset($_POST["cat_title"]) ) $tpl->assign( "CATTITLE", $_POST["cat_title"] );
	if( isset($_POST["cat_size"]) ) $tpl->assign( "CATSIZE", $_POST["cat_size"] );
	switch( $_POST["nextstep"] )
	{
		case 2:
			// fill up the list of source categories
			$str = "";
			$result = $db->query( "SELECT * FROM " . $_POST["categories"], true );
			$fields = $db->get_result_fields( $result );
			foreach( $fields as $i => $value ) 
				$str .= "<option value=\"".$value->name."\">".$value->name."</option>";
			$tpl->assign( "FIELDLIST", $str );
			break;
		case 3:
			// List source table
			$result = $db->super_query( "SELECT * FROM " . $_POST["categories"], true );
			$str = "";
			foreach( $result as $i => $values )
			{
				$strsize = (!empty($_POST["cat_size"]) ) ? " (" . $values[$_POST["cat_size"]] . ")" : "";
				$str .= "<p>".$values[$_POST["cat_title"]] . $strsize . " <input name=\"srccat" . 
				$values[$_POST["cat_id"]] . "\"  type=\"text\" size=\"3\"></p>" . 
				"<input name=\"cattitle" . $values[$_POST["cat_id"]] . "\" type=\"hidden\" value=\"" . 
				$values[$_POST["cat_title"]] . "\">\n";
			}
			$tpl->assign( "SOURCECATEGORYLIST", $str );
			// List destination table
			$result = $db->super_query( "SELECT title, id FROM " . $cS["dbPrefix"] . "categories", true );
			$str = "";
			foreach( $result as $i => $values )
				$str .= "<p>".$values["title"].", ID = ".$values["id"]."</p>";
			$tpl->assign( "DESTCATEGORYLIST", $str );
			break;
		case 4:
			// Create new categories in our cat. table using data from the previous step
			$str = "";
			foreach( $_POST as $var => $value )
			{
				if( strstr($var, "srccat") )
				{
					$nSrcCat = (int)str_replace("srccat", "", $var);
					$sCatTitle = $_POST["cattitle".$nSrcCat];
					if( !empty($value) )
					{
						// if this is a new category to our table, add it!
						if( count($db->super_query( "SELECT id FROM " . $cS["dbPrefix"] . "categories WHERE id=\"$value\"", true)) == 0)
							$db->query( "INSERT INTO " . $cS["dbPrefix"] . "categories (id, title) VALUES (\"$value\", \"$sCatTitle\")", true );
						// and anyway store the info about the categories in hidden fields
						$str .= "<input name=\"" . $var . "\" type=\"hidden\" value=\"" . $value . "\">\n";
					}
				}
			}
			$tpl->assign( "CATEGORYIDS", $str );
			
			// Show the list of fields in the "games" table to choose the "ID", "Title", etc. like we did for categories
			$result = $db->super_query( "SELECT * FROM " . $_POST["games"], true );

			// fill up the list of source categories
			$str = "";
			$result = $db->query( "SELECT * FROM " . $_POST["games"], true );
			$fields = $db->get_result_fields( $result );
			foreach( $fields as $i => $value ) 
				$str .= "<option value=\"".$value->name."\">".$value->name."</option>";
			$tpl->assign( "FIELDLIST", $str );
			break;
		case 5:
			// create an array of category ID associations: arCatID [oldID] = newID
			$arIndex = Array();
			foreach( $_POST as $var => $value )
				if( strstr($var, "srccat") )
				{
					$nSrcCat = (int)str_replace("srccat", "", $var);
					$arIndex[$nSrcCat] = $value;
				}
			
			// Now copy games from the donor table to our one 
			$src = $db->super_query( "SELECT * FROM " . $_POST["games"], true );
			foreach( $src as $i => $game )
			{
				$query = "INSERT INTO " . $cS["dbPrefix"] . "games (category_id, added, active, title, file, thumbnail, width, height";
				if( !empty($_POST["game_keywords"]) ) $query .= ", keywords";
				if( !empty($_POST["game_description"]) ) $query .= ", description";
				if( !empty($_POST["game_plays"]) ) $query .= ", plays_total";
				if( !empty($_POST["game_playstoday"]) ) $query .= ", plays_today";
				$catID = $arIndex[$game[$_POST["game_catid"]]];

				// proceed only if we do import the category which the current game belongs
				if( !empty($catID) )
				{
					$date = Today();
					$title = addslashes( $game[$_POST["game_title"]] );
					$file = $game[$_POST["game_file"]];
					$thumb = $game[$_POST["game_thumb"]];
					$width = $game[$_POST["game_width"]];
					$height = $game[$_POST["game_height"]];
					$query .= ") VALUES ('$catID', '$date', 1, '$title', '$file', '$thumb', '$width', '$height'";
					if( !empty($_POST["game_keywords"]) ) $query .= ", '" . addslashes( $game[$_POST["game_keywords"]] ) . "'";
					if( !empty($_POST["game_description"]) ) $query .= ", '" . addslashes( $game[$_POST["game_description"]] ) . "'";
					if( !empty($_POST["game_plays"]) ) $query .= ", '" . $game[$_POST["game_plays"]] . "'";
					if( !empty($_POST["game_playstoday"]) ) $query .= ", '" . $game[$_POST["game_playstaday"]] . "'";
					$query .= ")";
					$db->query( $query, true );
					echo( $title . "<br>" );
					flush();
				}
			}
			break;
		default:
			header( "Location:import.php" );
			
	}
}
else
	$tpl->define( "tplMain", "import1.html" );

$tpl->assign( "ACTION", $_SERVER['PHP_SELF'] );
$tpl->parse( "MAIN", "tplMain" );
$tpl->FastPrint( "MAIN");
?>