<?php
/*******************************************************************
/ ProArcadeScript
/ File description:
/ custom and integrated blocks configuration file for ProArcadeScript
/
/*******************************************************************/
$cB = array (
	"MAINNAV" => array("title"=>"Main Navigation", "front"=>1, "max"=>0, "sort"=>"rand", "cat"=>1, "game"=>1, "user"=>1, "custom"=>1, "script"=>1,"file"=>"block_mainnav", "comments"=>"Main navigation bar... Shows the nav bar in the header"),
	"GAMESNAV" => array("title"=>"Cat. Navigation", "front"=>1, "max"=>0, "sort"=>"rand", "cat"=>1, "game"=>1, "user"=>1, "custom"=>1, "script"=>1,"file"=>"block_gamesnav", "comments"=>"Generates the categories list"),
	"COMBO" => array("title"=>"Games Combobox", "front"=>1, "max"=>10, "sort"=>"plays_today", "cat"=>1, "game"=>1, "user"=>1, "custom"=>0, "script"=>1,"file"=>"block_gamescombo", "comments"=>"Games combobox"),
	"PATH" => array("title"=>"Path to current page", "front"=>1, "max"=>0, "sort"=>"rand", "cat"=>1, "game"=>1, "user"=>1, "custom"=>1, "script"=>1,"file"=>"block_path", "comments"=>"Path to the current page or site section"),
	"FAVORITES" => array("title"=>"Favorites", "front"=>1, "max"=>0, "sort"=>"rand", "cat"=>1, "game"=>1, "user"=>0, "custom"=>1, "script"=>1,"file"=>"block_favorites", "comments"=>"Games selected by registered user"),
	"FEATURED" => array("title"=>"Featured Game", "front"=>1, "max"=>0, "sort"=>"rand", "cat"=>0, "game"=>0, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_featured", "comments"=>"Featured game on the front page"),
	"GAMELIST" => array("title"=>"Frontpage Games", "front"=>1, "max"=>4, "sort"=>"date", "cat"=>0, "game"=>0, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_gamelist", "comments"=>"List of games to show with thumbnails on the front page"),
	"GAMELISTCAT" => array("title"=>"Category Games", "front"=>0, "max"=>8, "sort"=>"plays_total", "cat"=>1, "game"=>0, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_gamelist", "comments"=>"Category list of games with thumbnails. For the category page"),
	"STATS" => array("title"=>"Stats", "front"=>0, "max"=>0, "sort"=>"rand", "cat"=>0, "game"=>0, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_stats", "comments"=>"Site statistics showed on the front page"),
	"SEARCH" => array("title"=>"Search", "front"=>1, "max"=>20, "sort"=>"rand", "cat"=>0, "game"=>1, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_search", "comments"=>"Games search box"),
	"MOSTPLAYED" => array("title"=>"MostPlayed", "front"=>1, "max"=>6, "sort"=>"rand", "cat"=>1, "game"=>0, "user"=>1, "custom"=>1, "script"=>1,"file"=>"block_mostplayed", "comments"=>"The most played games"),
	"MOSTPLAYEDTODAY" => array("title"=>"MostPlayedToday", "front"=>1, "max"=>6, "sort"=>"rand", "cat"=>1, "game"=>1, "user"=>1, "custom"=>1, "script"=>1,"file"=>"block_mostplayedtoday", "comments"=>"The today's most played games"),
	"TOPRATED" => array("title"=>"TopRated", "front"=>1, "max"=>6, "sort"=>"rand", "cat"=>1, "game"=>0, "user"=>1, "custom"=>1, "script"=>1,"file"=>"block_toprated", "comments"=>"The top rated games"),
	"NEWS" => array("title"=>"News Block", "front"=>1, "max"=>5, "sort"=>"date", "cat"=>1, "game"=>0, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_news", "comments"=>"Site News block"),
	"FOOTER" => array("title"=>"Site Footer", "front"=>1, "max"=>0, "sort"=>"rand", "cat"=>1, "game"=>0, "user"=>1, "custom"=>1, "script"=>1,"file"=>"block_footer", "comments"=>"Universal site footer"),
	"TOPAD" => array("title"=>"Top Google AD", "front"=>0, "max"=>0, "sort"=>"rand", "cat"=>0, "game"=>0, "user"=>0, "custom"=>0, "script"=>0,"file"=>"adblock_top", "comments"=>"728x90px google AD"),
	"WELCOMETEXT" => array("title"=>"Welcome text for the front page", "front"=>1, "max"=>0, "sort"=>"rand", "cat"=>0, "game"=>0, "user"=>0, "custom"=>0, "script"=>0,"file"=>"welcome_text", "comments"=>"Nothing special, just few paragraphs of intro text"),
	"CATGAMES" => array("title"=>"All Category's Games", "front"=>0, "max"=>0, "sort"=>"title", "cat"=>1, "game"=>0, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_catgames", "comments"=>"Show all games exist in the category."),
	"SITEFEEDBACK" => array("title"=>"Site Feedback", "front"=>1, "max"=>7, "sort"=>"rand", "cat"=>0, "game"=>0, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_sitefeedback", "comments"=>"Allows visitors to comment the site in whole."),
	"GAMECOMMENTS" => array("title"=>"Game Comments", "front"=>0, "max"=>5, "sort"=>"date", "cat"=>0, "game"=>1, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_gamecomments", "comments"=>"Block shows the comments allready posted   for a particular game and the form "),
	"SIDEAD1" => array("title"=>"Side AD Spot 120x240", "front"=>0, "max"=>0, "sort"=>"rand", "cat"=>0, "game"=>0, "user"=>0, "custom"=>0, "script"=>0,"file"=>"side_ad_1", "comments"=>"Google ADSENSE"),
	"RELATED" => array("title"=>"Related Games", "front"=>0, "max"=>6, "sort"=>"rand", "cat"=>0, "game"=>1, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_related", "comments"=>"Related Games"),
	"RANDOM" => array("title"=>"Random Game", "front"=>1, "max"=>0, "sort"=>"rand", "cat"=>0, "game"=>0, "user"=>1, "custom"=>0, "script"=>1,"file"=>"block_random", "comments"=>""),
	"RECENT" => array("title"=>"Added Recently", "front"=>1, "max"=>6, "sort"=>"date", "cat"=>0, "game"=>0, "user"=>1, "custom"=>0, "script"=>1,"file"=>"sidelist_sorted", "comments"=>"List of the games added recently"),
	"TOPUSERS" => array("title"=>"Top Users", "front"=>1, "max"=>9, "sort"=>"rand", "cat"=>1, "game"=>0, "user"=>1, "custom"=>0, "script"=>1,"file"=>"block_topusers", "comments"=>"Sorting does not matter anything for this block"),
	"WHOLIKESGAME" => array("title"=>"Users who enjoy playing this game", "front"=>0, "max"=>200, "sort"=>"rand", "cat"=>0, "game"=>1, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_wholikesgame", "comments"=>"A simple list of users who have current game in their favorites"),
	"PUBLISH" => array("title"=>"Publish on blog or website", "front"=>0, "max"=>0, "sort"=>"rand", "cat"=>0, "game"=>1, "user"=>0, "custom"=>0, "script"=>1,"file"=>"block_publish", "comments"=>"Publish on blog or website"),

);

return;
?>

