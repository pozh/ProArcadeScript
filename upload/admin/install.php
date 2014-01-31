<?php
/*******************************************************************
/ ProArcadeScript
/ File description:
/ utility - game pack installator. Moves the given sql to the database
/
/*******************************************************************/
require_once( "../include/config.php" );
require_once( "../include/helpers.php" );
require_once( "../include/class.FastTemplate.php" );
require_once( "../include/class.Database.php" );

$tpl = new FastTemplate("templates");

require( "checklogin.php" );

$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

$tpl->define( array(
	"tplHeader"	=> "header.html",
	"tplFooter"	=> "footer.html",
	"tplMain"	=> "page_install.html"
));

$tpl->assign( array( 
	"SITEROOT"	=> $cSite["sSiteRoot"],
	"TITLE"		=> $cSite["sSiteTitle"] . " Administration - Import games",
	"SITETITLE"	=> $cSite["sSiteTitle"],
	"ATGAMES"	=> "Active",
	"CATEGORIESSETUP"	=>	""
));

switch( $_REQUEST["action"] )
{
//-------------------------------------------------------------------------------
// Read the categories list from the installation text and prepare the controls
// for categories' setup
//-------------------------------------------------------------------------------
	case "run":
		$sCode = get_magic_quotes_gpc() ? stripslashes($_POST["code"]) : $_POST["code"];
		$tpl->assign( array( 
			"ERROR"		=>	empty($_POST['code']) ? $cLang['errAEmpty'] : "",
			"CODE"		=>	$sCode,
			"MESSAGE"	=>	'<p>Now please choose the categories where you want the games of certain types (<span class="marked">marked text</span>) to be installed in.</p>'
		));
		if( !empty($sCode) )
		{
		   // Check the pack's version
		   $pack_version = substr( $_POST['code'], strpos($_POST['code'], '===== GAMES v.')+14, 2 );
		   if( $pack_version )
		      settype($pack_version, "integer");
			else
			{
				if( strpos($_POST['code'], '===== GAMES =====') )
					$pack_version = 1;
         	else
					$pack_version = 0;
			}

			$sTypes = substr( $_POST['code'], 0, strpos($_POST['code'], '===== GAMES') );
			if( strlen($sTypes) < 5 )
			{
				// if category descriptions are not found, show error message and stop
				$tpl->assign( "ERROR", "Wrong list of categories, please make sure the pack's description has proper format" );
				$success = false;
				$tpl->assign( array(
					'BTN'				=>	'Cancel Installation',
					'ACTION'			=>	'clear'
				));
			}
			elseif( $pack_version != 2 )
			{
				// if the pack is not compatible with the current version of the script, error and stop
				$tpl->assign( "ERROR", "This pack is incompatible with ProArcadeScript $version. Please download the appropriate version of the pack." );
				$success = false;
				$tpl->assign( array(
					'BTN'				=>	'Cancel Installation',
					'ACTION'			=>	'clear'
				));
			}
			else
			{			
			   // everything ok, process the categories settings.
				$arTypes = explode( ',', $sTypes );
				$rescat = $db->super_query( "SELECT id, title FROM ".$cMain["dbPrefix"]."categories", true );
				$selected = "selected";
				foreach( $rescat as $category )
				{
					$sOptions .= "<option $selected value=\"".$category['id']."\">".$category['title'].'</option>';
					$selected = '';
				}
				foreach( $arTypes as $type )
				{	
					$correcttype = trim( $type );
					$sControls .= '<div class="left" style="margin-bottom:6px;"><span class="marked">' . $correcttype . '</span> <select name="' . $correcttype . '">' . 
									$sOptions . '</select> &nbsp;&nbsp;&nbsp;</div>';
				}
				$tpl->assign( array(
					'CATEGORIESSETUP'	=>	$sControls,
					'CODE'				=>	$sCode,
					'BTN'				=>	'Add games to the database',
					'ACTION'			=>	'install'
				));
			}				
		}		
		else  // empty code
			$tpl->assign( array(
				'BTN'				=>	'Cancel Installation',
				'ACTION'			=>	'clear'
			));
		break;
//-------------------------------------------------------------------------------
// Create new records in the "games" table, update information about games number 
// in the "categories" table.
//-------------------------------------------------------------------------------
	case "install":
		$tpl->assign( array( 
			"ERROR"		=>	empty($_POST['code']) ? $cLang['errAEmpty'] : "",
			"CODE"		=>	$_POST['code'],
			"ACTION"	=>	'finish',
			"BTN"		=>	'Done'
			
		));
		if( !empty($_POST['code']) )
		{
		   // Check the pack's version
		   $pack_version = substr( $_POST['code'], strpos($_POST['code'], '===== GAMES v.')+14, 3 );
		   if( $pack_version )
			{
			   ereg( '([0-9]*)', $pack_version, $regs );
				$pack_version = $regs[1];
			}
         elseif( strpos($_POST['code'], '===== GAMES =====') )
				$pack_version = '1';
         else
				$pack_version = '0';

			$sTypes = substr( $_POST['code'], 0, strpos($_POST['code'], '===== GAMES') );
			if( strlen($sTypes) < 5 )
			{	
				// if category descriptions are not found, show error message and stop
				$tpl->assign( "ERROR", "Wrong list of categories, please make sure the pack's description has proper format" );
				$success = false;
				$tpl->assign( array(
					'BTN'				=>	'Cancel Installation',
					'ACTION'			=>	'clear'
				));
			}
			elseif( $pack_version != 2 )
			{
				// if the pack is not compatible with the current version of the script, error and stop
				$tpl->assign( "ERROR", "This pack is incompatible with ProArcadeScript $version. Please download the appropriate version of the pack." );
				$success = false;
				$tpl->assign( array(
					'BTN'				=>	'Cancel Installation',
					'ACTION'			=>	'clear'
				));
			}
			else
			{
				$success = true;
				// replace category names by their order numbers taken from the user's database (like ARCADE to 1, and so on)
				$sCode = get_magic_quotes_gpc() ? stripslashes ($_POST["code"]) : $_POST["code"];
				$arTypes = explode( ',', $sTypes );
				foreach( $arTypes as $i => $type )
				{
					$type = trim( $type );
					$sCode = str_replace( "!$type!", $_POST[$type], $sCode );
				}
				$sCode = str_replace( '!DATE!', time(), $sCode );
				// make sql compatible with any field set
				$sCode = str_replace( '!TABLE!', '!TABLE! (id, category_id, added, active, title, latin_title, file, thumbnail, large_img, plays_total, plays_today, keywords, description, width, height, votes, votes_value, rating, featured)', $sCode );
				$sCode = str_replace( '!TABLE!', $cMain['dbPrefix'].'games', $sCode );
				$sCode = str_replace( '!ACTIVE!', "1", $sCode );
				
				// get the games lines (queries) only and execute them
				$squeries = substr( $sCode, strpos($sCode, 'INSERT') );
				$queries = explode( ";", $squeries );
				$ninstalled = 0;
				$falsequeries = array();
				foreach( $queries as $query )
            	{
               		if( strlen($query) > 10 )
                  		if( !$db->query($query, 0) )
                  		{
                     		$success = false;
                     		$falsequeries[] = $query;
                  		}
                  		else
							$ninstalled ++;
            	}
            	if( !$success )
				{
                  $failQs = implode('<br />',$falsequeries);
                  $tpl->assign( "ERROR", "An error occured while adding new records to the database.<br /><br />Un-installed queries:<br />$failQs" );
				}
				
			}
			// revise games number for each category
			$rescat = $db->super_query( "SELECT id FROM ".$cMain["dbPrefix"]."categories", true );
			foreach( $rescat as $category )
			{
				$res = $db->super_query( "SELECT category_id, COUNT(category_id) as count FROM ".$cMain["dbPrefix"]."games WHERE category_id=".$category["id"]." GROUP BY category_id" );
				$gamescount = empty($res) ? 0 : $res["count"];
				$db->query( "UPDATE ".$cMain["dbPrefix"]."categories SET games=".$gamescount." WHERE id=".$category["id"] );
			}
			$tpl->assign( "MESSAGE", "<b>Done. $ninstalled games have been installed.</b>" );
			$tpl->assign( "CODE", "" );
		}
		else  // empty code
			$tpl->assign( array(
				'BTN'				=>	'Cancel Installation',
				'ACTION'			=>	'clear'
			));
		break;
	case 'finish':
		header ("Location:index.php");
		break;
	default:
		$tpl->assign( array( 
			"ACTION"	=>	"run",
			"ERROR"		=>	"",
			"CODE"		=>	"",
			"BTN"		=>	"Run installation",
			"MESSAGE"	=>	'<p>To install a new game pack please upload all swf and image files from the pack\'s archive 
							into appropriate folders on your server.</p>
							<p><strong>Only when the files are uploaded</strong>, copy the entire text from the "games.sql.txt" file found 
							in the gamepack\'s archive, paste it in the text area below and click the "Run installation" button. 
							The games will be added to your database. That\'s it.</p>'
		));
		break;
}

$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");
?>
