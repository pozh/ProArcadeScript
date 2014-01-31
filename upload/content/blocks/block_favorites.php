<?php
/*******************************************************************
/ ProArcadeScript version: 1.1
/ File description:
/ Block "Favorites" shows the favorite games of the current player
/ If current user is not logged in, show block caption and message
/ like "you have to login to use this feature"
/
/ © 2007, ProArcadeScript. All rights reserved. 
/*******************************************************************/

// who's calling?
$sScript = substr(strrchr($_SERVER['SCRIPT_NAME'], "/"), 1);

if ( isset($_SESSION["user"]) && !empty($_SESSION["user"]) )
{
	$tpl->define( "tplBFavorites", "block_favorites.html" );
	$query = "SELECT F.user_id, F.game_id, U.id, G.id, G.title, G.latin_title, G.active FROM ".$cMain["dbPrefix"]."games AS G, ".
		$cMain["dbPrefix"]."users AS U, ".$cMain["dbPrefix"].
		"favorites AS F WHERE U.id='".$_SESSION["user"]."' AND U.id=F.user_id AND G.id=F.game_id";
	$arFavGames = $db->super_query( $query, true );
	$tpl->define_dynamic( "dynFavorites", "tplBFavorites" );
	$tpl->define_dynamic( "dynAddFavorite", "tplBFavorites" );
	$tpl->define_dynamic( "dynLogout", "tplBFavorites" );
	
	// List existing Favorites for this user, if any
	if( count($arFavGames) > 0 )
	{
		$tpl->assign( "FAVMESSAGE", "" );
		foreach( $arFavGames as $i => $FavGame )
		{
			$tpl->assign(array(
				'FAVURL'	=> GameURL( $FavGame["game_id"], $FavGame["latin_title"] ),
				'FAVNAME'	=> $FavGame["title"],
				'REMFAVURL'	=> $cSite['sSiteRoot'].'favorites.php?id=' . $FavGame["game_id"] . '&action=remove'
			));
			$tpl->parse( "FAVGAMES", ".dynFavorites" );
		}
	}
	else	
	{
		// if there are no favorite games for this user yet
		$tpl->clear_dynamic( "dynFavorites" );
		$tpl->assign( "FAVMESSAGE", $cLang["msgNoFavorites"] );
	}

	// Setup the "Add favorite" link if we are on the game's page
	if( $sScript == "game.php" )
		$tpl->assign( "ADDFAVURL", $cSite['sSiteRoot'].'favorites.php?id='.$gameID );
	else
		$tpl->clear_dynamic( "dynAddFavorite" );
}
else	// anonimous user
{
	$tpl->assign( "NEEDACCOUNTFOR", "My Favorites" );
	$tpl->assign( "LOGOUT", "" );
	$tpl->define( "tplBFavorites", "block_needaccount.html" );
	$tpl->define_dynamic( "dynAddFavorite", "tplBFavorites" );
	$tpl->clear_dynamic( "dynAddFavorite" );
	$tpl->define_dynamic( "dynLogout", "tplBFavorites" );
	$tpl->clear_dynamic( "dynLogout" );
}

$tpl->parse( "FAVORITES", "tplBFavorites" );	

?>