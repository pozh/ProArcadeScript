<?php
$tpl->define("tplBPath", "block_path.html" );
$tpl->define_dynamic( "dynPath" , "tplBPath" );

// find out the name of the script which called this block
$sScript = substr(strrchr($_SERVER['SCRIPT_NAME'], "/"), 1);

if( $sScript != "index.php" )
{
	// Parce "Home" link
	$tpl->assign( "PATHURL", $cSite["sSiteRoot"] );
	$tpl->assign( "PATHTITLE", $cLang["sHome"] );
	$tpl->parse( "PATH", ".dynPath" );
}

switch( $sScript )
{
	case "cat.php":
		$tpl->clear_dynamic( "dynPath" );
		$tpl->assign( "CURRENTPATH", $sCategory );
		break;
	case "game.php":
	   if( $gameID != 0 )
	   {
			$query = "SELECT id, title, latin_title FROM " . $cMain["dbPrefix"] . "categories WHERE id=".$gameres['category_id']." LIMIT 1";
			$res = $db->super_query( $query, false );
			$tpl->assign( "PATHURL", CategoryURL($res["latin_title"]) );
			$tpl->assign( "PATHTITLE", $res["title"] );
			$tpl->parse( "PATH", ".dynPath" );
			$tpl->assign( "CURRENTPATH", $gameres['title'] );
		}
		else
		{
			$tpl->clear_dynamic( "dynPath" );
		   $tpl->assign( 'CURRENTPATH', $cLang['errUnknown'] );
		}
		break;
	case "userhome.php":
		$tpl->assign( "CURRENTPATH", $page_path );
		$tpl->clear_dynamic( "dynPath" );
		break;
	case "doc.php":
		$tpl->clear_dynamic( "dynPath" );
		$tpl->assign( "CURRENTPATH", $cP[$cPage]["title"] );
		break;
	case "shownews.php":
		$tpl->clear_dynamic( "dynPath" );
		$tpl->assign( "CURRENTPATH", $news["title"] );
		break;
	case "search.php":
		$tpl->clear_dynamic( "dynPath" );
		$tpl->assign( "CURRENTPATH", 'Search Results' );
		break;
	case "index.php":
	default:
		$tpl->assign( "CURRENTPATH", $cLang["sHome"] );
		$tpl->clear_dynamic( "dynPath" );
		break;
}
$tpl->parse( "PATH", "tplBPath" );
?>