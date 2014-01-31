<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ MochiAds game feeds administration 
/ (a part of the content management tab)
/
/*******************************************************************/
require( '../include/config.php' );
require( '../include/helpers.php' );
require( '../include/class.FastTemplate.php' );
require( '../include/class.Database.php' );
require( '../include/class.Cache.php' );
require( 'mochiimporter.php' );
		
$tpl = new FastTemplate("templates");
require( "checklogin.php" );
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );
if( !$db )
	die( 'can\'t connect to the database' );

// create necessary database tables if they do not exist
$query = "CREATE TABLE IF NOT EXISTS `".$cMain["dbPrefix"]."mochisettings` (
  `publisher_id` varchar(32) NOT NULL default '',
  `rating` varchar(32) NOT NULL default 'all',
  `category` varchar(128) NOT NULL default '',
  `tags` varchar(255) NOT NULL default '',
  `import_games` int(5) NOT NULL default 0,
  `import_skip` int(5) NOT NULL default 0
) TYPE=MyISAM;";
$db->query( $query );

// add missed fields in the games table
$res = $db->query( 'SELECT * FROM '.$cMain['dbPrefix'].'games LIMIT 1' );
$fields = $db->get_result_fields( $res );
$fieldNames = array();
foreach($fields as $field )
	$fieldNames[] = $field->name;
if( !in_array('coins_revshare_enabled', $fieldNames) )
{	
	$query = "ALTER TABLE ".$cMain['dbPrefix']."games 
		ADD coins_revshare_enabled INT(1) NOT NULL default 0,
		ADD coins_enabled INT(1) NOT NULL default 0,
		ADD screen1_thumb VARCHAR(255) NOT NULL default '',
		ADD screen1_url VARCHAR(255) NOT NULL default '',
		ADD screen2_thumb VARCHAR(255) NOT NULL default '',
		ADD screen2_url VARCHAR(255) NOT NULL default '',
		ADD screen3_thumb VARCHAR(255) NOT NULL default '',
		ADD screen3_url VARCHAR(255) NOT NULL default '',
		ADD screen4_url VARCHAR(255) NOT NULL default '',
		ADD video_url VARCHAR(255) NOT NULL default '',
		ADD author VARCHAR(255) NOT NULL default '',
		ADD game_tag VARCHAR(255) NOT NULL default '',
		ADD updated INT(10) NOT NULL default 0, 
		ADD leaderboard_enabled INT(1) NOT NULL default 0,
		ADD instructions TEXT NOT NULL default ''";
	$db->query( $query, false );
}                                                   

// read settings, if not exist, assign zero values
$settings = $db->super_query('SELECT * FROM '.$cMain['dbPrefix'].'mochisettings LIMIT 1', false);
if( !$settings )
	$settings = array(
		'publisher_id'	=>	'',
		'rating'				=>	'',
		'category'			=>	'',
		'tags'					=>	'',
		'import_games'	=>	'',
		'import_skip'		=>	'',
	);


// template-related stuff
$tpl->define( array(
	"tplHeader"	=> "header.html",
	"tplMain"		=> "page_mochiadmin.html",
	"tplFooter"	=> "footer.html",
	"tplTop"	=> "block_contenttop.html"
));
$tpl->define_dynamic( 'dynForm', 'tplMain' );
$tpl->define_dynamic( 'dynText', 'tplMain' );

$arSubNav = Array( "SUBCATEGORIES", "SUBGAMES", "SUBNEWCAT", "SUBNEWGAME", "SUBMOCHI" );
foreach( $arSubNav as $BtnStyle )
	$tpl->assign( $BtnStyle, '' );

$tpl->assign( array(
	'SUBTITLE'					=>	'Mochi Games Import',
	'SUBMOCHI'					=>	'Active',
 	'SITEROOT'					=>	$cSite["sSiteRoot"],
	'TITLE'							=>	$cSite["sSiteTitle"] . " Administration - Import games from Mochimedia",
	'SITETITLE'					=>	$cSite["sSiteTitle"],
	'ATGAMES'						=>	'Active',
	'PUBLISHERID'				=>  $settings['publisher_id'],
	'PUBLISHERIDERROR'	=>  '',
  'GAMESNUMBER'				=>  empty($settings['import_games']) ? '' : $settings['import_games'],
  'SKIPNUMBER'				=>	empty($settings['import_skip']) ? '' : $settings['import_skip'],
  'CATEGORY'					=>	$settings['category'],
  'TAGS'							=>	$settings['tags'],
  'RATINGALL'					=>	'',
  'RATINGTEEN'				=>	'',
  'RATINGMATURE'			=>	'',
  'RATINGEVERYONE'		=>	''
));


// Deal with the form, no utility
if( empty( $_REQUEST['utility']) )
{
	if( $settings['rating'] != '' )
		$tpl->assign('RATING'.strtoupper($settings['rating']), 'selected="selected"');
	
	// Save settings
	if( !empty($_POST['btn_save']) || !empty($_POST['btn_import']) )
	{
		$tpl->assign( array(
			'PUBLISHERID'				=>  $_POST['publisher_id'],
			'PUBLISHERIDERROR'	=>  empty($_POST['publisher_id'])? $cLang['errAEmpty'] : '',
	  	'GAMESNUMBER'				=>  empty($_POST['games_number']) ? '' : $_POST['games_number'],
	  	'SKIPNUMBER'				=>	empty($_POST['skip_number']) ? '' : $_POST['skip_number'],
	  	'CATEGORY'					=>	$_POST['category'],
	  	'TAGS'							=>	$_POST['tags'],
		  'RATINGALL'					=>	'',
		  'RATINGTEEN'				=>	'',
	 	 	'RATINGMATURE'			=>	'',
	 	 	'RATINGEVERYONE'		=>	''
		));	
		if( $_POST['rating'] != '' )
			$tpl->assign('RATING'.strtoupper($_POST['rating']), 'selected="selected"');
	
		// Check for errors first
		$bOK = true;
		if( empty($_POST['publisher_id']) )
			$bOK = false;
		
		// if everything's ok, save the settings
		if( $bOK )
		{
			$res = $db->query( 'SELECT * FROM '.$cMain['dbPrefix'].'mochisettings LIMIT 1' );
			if( $db->num_rows($res) == 1 )
				$query = 'UPDATE '.$cMain['dbPrefix']. 'mochisettings '.
					'SET publisher_id="'.$_POST['publisher_id'].
					'", rating="'. $_POST['rating'].
					'", category="'.mysql_escape_string($_POST['category']).
					'", tags="'.mysql_escape_string($_POST['tags']).
					'", import_games="'.$_POST['games_number'].
					'", import_skip="'.$_POST['skip_number'].'"';
			else
				$query = 'INSERT INTO '.$cMain['dbPrefix']. 'mochisettings '.
					'(publisher_id, rating, category, tags, import_games, import_skip) '.
					'VALUES( "'.$_POST['publisher_id'].'", "'.$_POST['rating'].'", "'.mysql_escape_string($_POST['category']).
					'", "'.mysql_escape_string($_POST['tags']).'", "'.$_POST['games_number'].'", "'.$_POST['skip_number'].
					'")';
			$db->query( $query );
		}
	}
	
	// Now import games           
	if( !empty($_POST['btn_import']) )
	{
		$timeStart = time();
		$newSettings = array(
			'publisher_id'	=>	$_POST['publisher_id'],
			'rating'				=>	$_POST['rating'],
			'category'			=>	mysql_escape_string($_POST['category']),
			'tags'					=>	mysql_escape_string($_POST['tags']),
			'import_games'	=>	$_POST['games_number'],
			'import_skip'		=>	$_POST['skip_number']
		);

		
		list( $addedGames, $skippedGames, $message ) = importMochiGames( $db, $newSettings );
		$timeResult = time() - $timeStart;
		
		if( is_array($addedGames) )
			$message .= ' ' . count($addedGames) . ' games added; '; 
		if( is_array($skippedGames) )
			$message .= count($skippedGames) . ' games skipped; ';
		$message .= "Spent $timeResult seconds.";
		
		$tpl->clear_dynamic( 'dynForm' );
		$timeLimit = ini_get('max_execution_time');
		$output = "<h3>Import Results: </h3> 
			<p>$message</p>";
		
		$gotGames = false;
		if( count($addedGames) > 0 )
		{
			$output .= '<ul class="List"> <li class="Caption"><span class="Cell W70">Added games:</span><span class="Cell">Category</span></li> ';
			$gotGames = true;				
			foreach( $addedGames as $game )
				$output .= "<li>$game</li>";
			$output .= '</ul>';
		}
		if( count($skippedGames) > 0 )
		{
			$output .= '<ul class="List"> <li class="Caption"><span class="Cell W70">Skipped games:</span> <span class="Cell">Reason </span></li> ';				
			$gotGames = true;				
			foreach( $skippedGames as $game )
				$output .= "<li>$game</li>";
			$output .= '</ul>';
		}
		if( !$gotGames )
			$output .= '<p>No games processed... check PAS-ERRORS.log for possible reason.</p>';

		$tpl->assign( 'TEXT', $output );			 			
	}
	else
	{
		$tpl->clear_dynamic( 'dynText' );
	}

} // Form
// Plain text output (util result, etc.)
else
{
	$tpl->clear_dynamic( 'dynForm' );
	if( $_REQUEST['utility'] == 'categories' )
	{
		$categories = getMochiCategories( $db );
		$output = '';
		if( is_array($categories) )
			foreach( $categories as $category )
				$output .= $category . '<br>';
		else
			$output = $categories;
		$tpl->assign( 'TEXT', $output );			 			
	}
} // Text

$tpl->parse( "CONTENTTOP", "tplTop" );
$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");
?>
