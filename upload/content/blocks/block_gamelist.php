<?php
/*******************************************************************
/ ProArcadeScript
/ File version: 1.2 released: Oct.3, 2009
/ File description:
/ Generates the list of games for the front or category page 
/ (few games from each category with thumbnails), depends of 
/ $_SERVER['SCRIPT_NAME'] - index.php for main, cat.php for category
/
/ © 2007-2009, ProArcadeScript. All rights reserved.
/*******************************************************************/

// find out the name of the script which called this block
$sScript = substr(strrchr($_SERVER['PHP_SELF'], "/"), 1);
if( $sScript == "index.php" )
{
	$nColumns = $cTpl["nColumnsMain"];
	$nMaxGames = $cB["GAMELIST"]["max"];
	$nOrder = $cB["GAMELIST"]["sort"];
	$tpl->define( "tplBCategory", "block_frontcategory.html" );
	$tpl->define( "tplBGame", "block_frontgame.html" );
	$res = $db->super_query( "SELECT id, title, latin_title, show_on_main, position FROM " . $cMain["dbPrefix"] . "categories WHERE show_on_main = 1 ORDER BY position ASC", true );
	$res_count = count( $res );
}
elseif( $sScript == 'cat.php' )
{
	$nColumns = $cTpl["nColumnsCat"];
	$nMaxGames = $cB["GAMELISTCAT"]["max"];
	$nOrder = $cB["GAMELISTCAT"]["sort"];
	$tpl->define( "tplBCategory", "block_category.html" );
	$tpl->define( "tplBGame", "block_catgame.html" );
	$res = $db->super_query( "SELECT id, title, latin_title FROM " . $cMain["dbPrefix"] . "categories WHERE id = $nCategory", true );
	$res_count = count( $res );
}
else
	$res_count = 0;
	
$tpl->define( 'tplBPages', 'helper_pagination.html' );
$tpl->define_dynamic( 'dynPagination', 'tplBPages' );

// which page to show?  (1-based)
$page = !empty( $_GET['page'] ) ? $_GET['page'] : 1;
if( !preg_match('([0-9]*)', $page) )
	$page = 1;

$tpl->define_dynamic( "dynGamesRow", "tplBGame" );

// if there is anything to show...
if( $res_count > 0 )
{
	$bNoGames = true;	// set false if there is at least 1 game in the DB
	// Process categories
	foreach( $res as $i => $values ) 
	{
		$tpl->assign( "CATEGORY", $values["title"] );
		$tpl->assign( "SORTSTR", $cSortStr[$nOrder] );
		$query = "SELECT * FROM " . $cMain["dbPrefix"] . "games WHERE active=1 AND category_id=" .
			$values["id"] . " ORDER BY " . $cSort[$nOrder] . ' LIMIT ' . ($page-1) * $nMaxGames . ',' . $nMaxGames;

		$resGames = $db->super_query( $query, true );

		// Process games
		if( count($resGames) > 0 )
		{
			$tpl->clear( "GAMES" );
			$bNoGames = false;
			$nResGame = 0;
			while( $nResGame < count($resGames) )
			{
				for( $nCol=0; $nCol<$nColumns; $nCol++ )
				{
					$nCurGame = $nResGame+$nCol;
					if( !empty($resGames[$nCurGame]) )
					{
						$sThumb = "content/thumbs/".$resGames[$nCurGame]["thumbnail"];
						if( file_exists($sThumb) )
							$sThumb = $cSite["sSiteRoot"].$sThumb;
						else
							$sThumb = $cSite["sSiteRoot"]."templates/".$cSite["sTemplate"]."/images/no_image.gif";
						$tpl->assign(array(
							"ID"			=> $resGames[$nCurGame]["id"],
							"GAMEURL"		=> GameURL( $resGames[$nCurGame]["id"], $resGames[$nCurGame]["latin_title"] ),
							"GAMETITLE" 	=> $resGames[$nCurGame]["title"],
							"IMG"			=> $sThumb,
							"IMGRATING"		=> StarImg($resGames[$nCurGame]['votes'],$resGames[$nCurGame]['rating']),
							"DESCRIPTION"	=> $resGames[$nCurGame]["description"]
						));
						$tpl->parse( "DYNGAMES", ".dynGamesRow" );
					}
					else
						$tpl->clear( "DYNGAMES" );
				}
				$tpl->parse( "GAMES", ".tplBGame" );
				$tpl->clear_parse( "DYNGAMES" );
				$nResGame += $nColumns;
			}
			$tpl->assign( "CATEGORYURL", CategoryURL($values["latin_title"]) );
			$tpl->parse( "GAMELIST", ".tplBCategory" );
		}		
		else
			$tpl->assign( "GAMES", "" );
	}
	
		// output the pagination "block"
		// find out the category contains max number of games
		if( $sScript == "index.php" )
			$tempRes = $db->super_query( 'SELECT * FROM '.$cMain["dbPrefix"].'categories ORDER BY games DESC LIMIT 1', false );
		else
		   $tempRes = $db->super_query( 'SELECT * FROM '.$cMain["dbPrefix"].'categories WHERE id=' . $nCategory . ' LIMIT 1', false );
		$games = $tempRes['games'];
		// if only we do need pagination
		if( $games > $nMaxGames )
		{
			for( $i=0; $i<(int)$games/$nMaxGames; $i++ )
			{
				$tpl->assign( array(
					'PAGE'			=> $i+1,
					'PAGEACTIVE'	=> ($i+1 == $page) ? 'active' : '',
					'PAGELINK'		=> 'page'.($i+1)
				));
				$tpl->parse( 'PAGES', '.dynPagination' );
			}
         $tpl->parse( "GAMELIST", ".tplBPages" );
		}
		else
			$tpl->clear_dynamic( "dynPagination" );

	if( $bNoGames )
		if( $sScript == "index.php" )
			$tpl->assign( "GAMELIST", "" );
		else
		{
			$tpl->define( "tplBError", "block_errormsg.html" );
			$tpl->assign( "ERROR", $cLang["errEmptyCat"] );
			$tpl->parse( "GAMELIST", "tplBError" );
		}
}	// 1 or more categories
else
	$tpl->assign( "GAMELIST", "" );

?>