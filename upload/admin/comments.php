<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ Manage comments and site feedback
/
/*******************************************************************/
require_once( "../include/config.php" );
require_once( "../include/helpers.php" );
require_once( "../include/class.FastTemplate.php" );
require_once( "../include/class.Database.php" );

// Local constants
$cCommentsPerPage = 20;

$tpl = new FastTemplate("templates");

require( "checklogin.php" );

$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

$tpl->define( array(
	"tplHeader"	=> "header.html",
	"tplFooter"	=> "footer.html",
	"tplTop"	=> "block_commentstop.html",
	"tplMain"	=> "page_comments.html"
));
$tpl->define_dynamic( "dynList", "tplMain" );
$tpl->define_dynamic( "dynPages", "tplMain" );
$tpl->define_dynamic( "dynPagesTitle", "tplMain" );

$tpl->assign( array( 
	"SITEROOT"	=> $cSite["sSiteRoot"],
	"TITLE"		=> $cSite["sSiteTitle"] . " Administration - Manage Comments",
	"SITETITLE"	=> $cSite["sSiteTitle"],
	"SUBTITLE"	=> "",
	"ATCOMMENTS"=> "Active",
	"REFERRER"	=> "",
	"CURSHOW"	=> "",
	"CURFILTER"	=> "",
	"CURVALUE"	=> "",
	"PAGELINK"	=> "",
	"EDITFILTER"=> "",
	"EDITIP"	=> "",
	"FILTERNUM"	=> ""
));

$arEmptyThings = Array( 
	"SUBCOMMENTS", "SUBSETTINGS", "SUBFILTERS", "SUBALL", "SUBACTIVE", "SUBINACTIVE",
	"GAMECOM0","GAMECOM1","GAMECOM2", "GAMEPREMOD0","GAMEPREMOD1","GAMEPREMOD2",
	"SITECOM0","SITECOM1","SITECOM2","SITEPREMOD0","SITEPREMOD1","SITEPREMOD2"
 );
foreach( $arEmptyThings as $rec )
	$tpl->assign( $rec, "" );

switch( $_REQUEST["action"] )
{
//-----------------------------------------------------------------------------
// add new spam filter
//-----------------------------------------------------------------------------
	case "addfilter":
		$sFilter = get_magic_quotes_gpc() ? $_POST['newfilter'] : mysql_escape_string( $_POST['newfilter'] );
		$nFilter = $_POST['newfilternum'];
		if( !empty($sFilter) && ($nFilter > 0) )
			$db->query( "INSERT INTO ".$cMain['dbPrefix']."stoplist VALUES( '', '$sFilter', $nFilter, 1 )" );
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;

//-----------------------------------------------------------------------------
// ban new ip
//-----------------------------------------------------------------------------
	case "banip":
		$sIP = $_POST['banip'];
		$bIsIP = ( preg_match("/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", $sIP) > 0 );
		if( $bIsIP )
		{
			$nTime = time();
			$db->query( "INSERT INTO ".$cMain['dbPrefix']."ban VALUES( '$sIP', $nTime, 0 )" );
		}
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;

//-----------------------------------------------------------------------------
// unban ip
//-----------------------------------------------------------------------------
	case "unban":
		$db->query( "DELETE FROM ".$cMain["dbPrefix"]."ban WHERE ip=\"".$_REQUEST["ip"]."\" LIMIT 1" );
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;
		
//-----------------------------------------------------------------------------
// activate / deactivate filter
//-----------------------------------------------------------------------------
	case "switchfilter":
		$db->query( "UPDATE ".$cMain["dbPrefix"]."stoplist SET active=MOD(active+1, 2) WHERE id=".$_REQUEST["id"]." LIMIT 1" );
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;
		
//-----------------------------------------------------------------------------
// change setings 
//-----------------------------------------------------------------------------
	case "newsettings":
		$tplConfig = new FastTemplate("templates");
		$tplConfig->define( "tplConfig", "comments_config.tpl" );
		$tplConfig->assign( array(
			"CONTENT"		=>	$_POST['gamecomments'],
			"CONTENTPREMOD"	=>	$_POST['gamepremod'],
			"SITE"			=>	$_POST['sitecomments'],
			"SITEPREMOD"	=>	$_POST['sitepremod'],
			"FILTERS"		=>	empty($_POST['bfilters']) ? 0 : 1,
			"DELETE"		=>	empty($_POST['bdelete']) ? 0 : 1,
			"SENDCOPY"		=>	empty($_POST['bsendcopy']) ? 0 : 1,
			"BAN"			=>	empty($_POST['bban']) ? 0 : 1,
			"DELAY"			=>	$_POST['delay']
		));
		$tplConfig->parse( "CONFIG", "tplConfig" );
		$sNewConfig = $tplConfig->GetText( "CONFIG" );
		$fConfig = fopen( "../include/comments_config.php", "w" );
		fwrite( $fConfig, $sNewConfig );
		fclose( $fConfig );
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;		

//-----------------------------------------------------------------------------
// show setings page
//-----------------------------------------------------------------------------
	case "settings":
		$tpl->define( "tplMain", "page_commentsettings.html" );
		$tpl->assign( array(
			"SUBTITLE"		=>	"Settings",
			"SUBSETTINGS"	=>	"Active",
			"ACTION"		=>	"newsettings",
			"GAMECOM".$cC['contentpermit']		=>	"Selected",
			"GAMEPREMOD".$cC['contentpremod']	=>	"Selected",
			"SITECOM".$cC['sitepermit']		=>	"Selected",
			"SITEPREMOD".$cC['sitepremod']		=>	"Selected",
			"DELAY"			=>	$cC['mindelay'],
			"FILTERCHECKED"	=>	$cC['filters'] ? "Checked" : "",
			"BANCHECKED"	=>	$cC['ban'] ? "Checked" : "",
			"DELETECHECKED"	=>	$cC['instantdelete'] ? "Checked" : "",
			"SENDCOPYCHECKED"	=>	$cC['sendcopy'] ? "Checked" : "",
		));
		break;

//-----------------------------------------------------------------------------
// save filter changes
//-----------------------------------------------------------------------------
	case "modifyfilter":
		$sFilter = get_magic_quotes_gpc() ? $_POST['newfilter'] : mysql_escape_string( $_POST['newfilter'] );
		$nFilter = $_POST['newfilternum'];
		if( !empty($sFilter) && ($nFilter > 0) )
			$db->query( "UPDATE ".$cMain['dbPrefix']."stoplist SET string='$sFilter', count=$nFilter WHERE id=".$_POST['id']." LIMIT 1" );
		header( "Location:comments.php?action=filters" );
		break;

//-----------------------------------------------------------------------------
// save banned ip changes
//-----------------------------------------------------------------------------
	case "modifyban":
		$sIP = $_POST['banip'];
		$bIsIP = ( preg_match("/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", $sIP) > 0 );
		if( $bIsIP )
			$db->query( "UPDATE ".$cMain['dbPrefix']."ban SET ip='$sIP' WHERE ip='".$_REQUEST['oldip']."' LIMIT 1" );
		header( "Location:comments.php?action=filters" );
		break;
		
//-----------------------------------------------------------------------------
// show filters page
//-----------------------------------------------------------------------------
	case "filters":
	case "editfilter":
	case "editban":
		$tpl->assign( array(
			"SUBTITLE"		=>	"Antispam filters",
			"SUBFILTERS"	=>	"Active",
			"FILTERACTION"	=>	"addfilter",
			"FILTERCAPTION"	=>	"Add",
			"IPCAPTION"		=>	"Ban",
			"IPACTION"		=>	"banip",
			"OLDIP"			=>	""
		));
		$tpl->define( "tplMain", "page_filters.html" );
		$tpl->define_dynamic( "dynFilters", "tplMain" );
		$tpl->define_dynamic( "dynIPs", "tplMain" );
		// List filters
		$res = $db->super_query( "SELECT * FROM ".$cMain['dbPrefix']."stoplist", true );
		if( count($res) > 0 )
			foreach( $res as $rec )
			{
				$tpl->assign( array(
					"STOPID"		=>	$rec['id'],
					"STOPSTRING"	=>	htmlspecialchars( $rec['string'] ),
					"STOPNUMBER"	=>	$rec['count'],
					"STOPSTATE"		=>	$rec['active'] ? "deactivate" : "activate"
				));
				$tpl->parse( "STOPPERS", ".dynFilters" );
			}
		else
			$tpl->clear_dynamic( "dynFilters" );
		// List banned IPs
		$res = $db->super_query( "SELECT ip, time FROM ".$cMain['dbPrefix']."ban ORDER BY time DESC", true );
		if( count($res) > 0 )
			foreach( $res as $rec )
			{
				$tpl->assign( "IP", $rec['ip'] );
				$tpl->parse( "IPLIST", ".dynIPs" );
			}
		else
			$tpl->clear_dynamic( "dynIPs" );

		// Edit stuff
		if( $_REQUEST["action"] == "editfilter" )
		{
			$rec = $db->super_query( "SELECT * FROM ".$cMain['dbPrefix']."stoplist WHERE id=".$_REQUEST['id']." LIMIT 1" );
			$tpl->assign( array(
				"FILTERACTION"	=>	"modifyfilter",
				"FILTERCAPTION"	=>	"Edit",
				"EDITFILTER"	=>	htmlspecialchars( $rec['string'] ),
				"FILTERNUM"		=>	$rec['count'],
				"FILTERID"		=>	$_REQUEST['id']
			));
		}
		else if( $_REQUEST["action"] == "editban" )
		{
			$tpl->assign( array(
				"IPACTION"	=>	"modifyban",
				"EDITIP"	=>	$_REQUEST['ip'],
				"OLDIP"		=>	$_REQUEST['ip'],
				"IPCAPTION"	=>	"Modify"
			));
			
		}
		break;
		
//-----------------------------------------------------------------------------
// activate / deactivate comment
//-----------------------------------------------------------------------------
	case "switchstate":
		$db->query( "UPDATE ".$cMain["dbPrefix"]."comments SET active=MOD(active+1, 2) WHERE id=".$_REQUEST["id"]." LIMIT 1" );
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;
// Bulk activate / deactivate

	case 'Activate/Deactivate selected':
	   $sList = '';
	   $nList = 0;
	   foreach( $_POST as $postvar => $postvalue )
         if (substr($postvar, 0, 7)=='comment')
         {
            $sList .= $postvalue . ',';
            $nList ++;
			}
		$sList = substr_replace( $sList, '', -1 );  //remove last ','
	   if( !empty($sList) )
	      $db->query( 'UPDATE '.$cMain['dbPrefix'].'comments SET active=MOD(active+1, 2) WHERE id IN ('.$sList.') LIMIT ' . $nList );
		header( "Location:".$_SERVER['HTTP_REFERER'] );


//-----------------------------------------------------------------------------
// Edit comment
//-----------------------------------------------------------------------------
	case "edit":
		$tpl->define_dynamic( "dynList", "tplMain" );
		$tpl->clear_dynamic( "dynList" );
		$tpl->clear_dynamic( "dynPages" );
		$tpl->clear_dynamic( "dynPagesTitle" );
		$rec = $db->super_query( "SELECT * FROM ".$cMain['dbPrefix']."comments WHERE id=".$_REQUEST['id']." LIMIT 1", false );
		$tpl->assign( array(
			"SUBTITLE"		=>	"Edit comment",
			"SUBCOMMENTS"	=>	"Active",
			"ACTION"		=>	"edit",
			"EDITID"		=>	$_REQUEST['id'],
			"GAMEID"		=>	$rec['game_id'],
			"NEWCOMMENT"	=>	empty($_POST['submit']) ? $rec['text'] : $_POST['comment'],
			"COMMENTERROR"	=>	""
		));
		// if the form is submitted with a modigied record, update the database
		if( !empty($_POST['submit']) )
		{
			$tpl->assign( "REFERRER", $_POST['referrer'] );
			if( !empty($_POST['comment']) )
			{
				$sComment = get_magic_quotes_gpc() ? $_POST['comment'] : mysql_escape_string( $_POST['comment'] );
				$db->query( "UPDATE ".$cMain["dbPrefix"]."comments SET text='$sComment' WHERE id=".$_REQUEST["id"]." LIMIT 1" );
				header( "Location:" . $_POST['referrer'] );
			}
			else
				$tpl->assign( "COMMENTERROR", $cLang["errAEmpty"] );
		}
		else
			$tpl->assign( "REFERRER", $_SERVER['HTTP_REFERER'] );
		break;

//-----------------------------------------------------------------------------
// Delete filter
//-----------------------------------------------------------------------------
	case "delfilter":
		$db->query( "DELETE FROM ".$cMain["dbPrefix"]."stoplist WHERE id=".$_REQUEST["id"]." LIMIT 1" );
		header( "Location:" . $_SERVER['HTTP_REFERER'] );
		break;
	
//-----------------------------------------------------------------------------
// Delete comment
//-----------------------------------------------------------------------------
	case "del":
		if( $cC["instantdelete"] )
		{
			$db->query( "DELETE FROM ".$cMain["dbPrefix"]."comments WHERE id=".$_REQUEST["id"]." LIMIT 1" );
			header( "Location:" . $_SERVER['HTTP_REFERER'] );
		}
		else
		{
			$tpl->define( "tplMain", "page_confirm.html" );
			$tpl->assign( array( 
				"SUBCOMMENTS"	=>	"Active",
				"SUBTITLE"	=>	"Delete comment",
				"MESSAGE"	=>	$cLang["msgADeleteComment"],
				"FORMACTION"=>	"comments.php",
				"ACTION"	=>	"dodel",
				"ID"		=>	$_REQUEST["id"],
				"REFERRER"	=>	$_SERVER['HTTP_REFERER']
			));
		}
		break;

	case "dodel":
		if( $_POST["confirm"] == "Yes" )
			$db->query( "DELETE FROM ".$cMain["dbPrefix"]."comments WHERE id=".$_REQUEST["id"]." LIMIT 1" );
		header( "Location:".$_POST["referrer"] );			
		break;
		
//-----------------------------------------------------------------------------
// Bulk delete comments
//-----------------------------------------------------------------------------
	case 'Delete selected':
	   $sList = '';
	   $nList = 0;
	   foreach( $_POST as $postvar => $postvalue )
         if (substr($postvar, 0, 7)=='comment')
         {
            $sList .= $postvalue . ',';
            $nList ++;
			}
		$sList = substr_replace( $sList, '', -1 );  //remove last ','
	   if( !empty($sList) )
			$db->query( 'DELETE FROM '.$cMain['dbPrefix'].'comments WHERE id IN ('.$sList.') LIMIT ' . $nList );
		header( "Location:".$_SERVER['HTTP_REFERER'] );
	   break;

//-----------------------------------------------------------------------------
// Add comment
//-----------------------------------------------------------------------------
	case "add":
		$nGame = $_POST['gameid'];
		$newText = $_POST['comment'];
		$nDate = time();
		$sIP = $_SERVER['REMOTE_ADDR'];
		$sText = get_magic_quotes_gpc() ? $_POST['comment'] : mysql_escape_string( $_POST['comment'] );
		if( !empty($_POST['comment']) )
		{
			$db->query( "INSERT INTO ".$cMain['dbPrefix']."comments VALUES( '', $nGame, 1, $nDate, '$sIP', '$sText', 1 )" );
            header( "Location:" . $_SERVER['HTTP_REFERER'] );
		}
		else
		{
			$tpl->assign( array(
				"SUBTITLE"		=>	"Add comment",
				"SUBCOMMENTS"	=>	"Active",
				"ACTION"		=>	"add",
				"EDITID"		=>	"",
				"GAMEID"		=>	$_POST['gameid'],
				"NEWCOMMENT"	=>	"",
				"COMMENTERROR"	=>	$cLang["errAEmpty"]
			));
			$tpl->define_dynamic( "dynList", "tplMain" );
			$tpl->clear_dynamic( "dynList" );
		}		
		break;

//-----------------------------------------------------------------------------
// List comments
//-----------------------------------------------------------------------------
	case "list":
	default:
		$tpl->assign( array(
			"SUBTITLE"		=>	"List comments",
			"SUBCOMMENTS"	=>	"Active",
			"ACTION"		=>	"add",
			"EDITID"		=>	"",
			"GAMEID"		=>	"0",
			"NEWCOMMENT"	=>	"",
			"COMMENTERROR"	=>	""
		));
		$sWhere = "1";
		if( !empty($_REQUEST['filter']) )
			switch($_REQUEST['filter'])
			{
				case "user":	$tpl->assign( "CURFILTER", "user" ); $tpl->assign( "CURVALUE", $_REQUEST["value"] ); $sWhere .= " AND c.user_id=".$_REQUEST["value"]; break;
				case "object":	$tpl->assign( "CURFILTER", "object" ); $tpl->assign( "CURVALUE", $_REQUEST["value"] ); $sWhere .= " AND c.game_id=".$_REQUEST["value"]; $tpl->assign( "GAMEID", $_REQUEST["value"] ); break;
				case "ip":		$tpl->assign( "CURFILTER", "ip" ); $tpl->assign( "CURVALUE", $_REQUEST["value"] ); $sWhere .= " AND c.ip=\"".$_REQUEST["value"]."\""; break;
			}
		if( !empty($_REQUEST['show']) )
			switch($_REQUEST['show'])
			{
				case "all":			$tpl->assign( "CURSHOW", "all" ); $tpl->assign( "SUBALL", "Active" ); break;
				case "active":		$tpl->assign( "CURSHOW", "active" ); $tpl->assign( "SUBACTIVE", "Active" ); $sWhere .= " AND c.active=1"; break;
				case "inactive":	$tpl->assign( "CURSHOW", "inactive" ); $tpl->assign( "SUBINACTIVE", "Active" ); $sWhere .= " AND c.active=0"; break;
			}
		else
			$tpl->assign( "SUBALL", "Active" );
		$query = "SELECT c.id as c_id, c.game_id as c_gameid, c.user_id as c_userid, c.added as c_added, 
					c.ip as c_ip, c.text as c_text, c.active as c_active, g.id as g_id, g.title as g_title, 
					u.id as u_id, u.username as u_username FROM " . $cMain["dbPrefix"]."comments as c LEFT JOIN ". 
					$cMain["dbPrefix"]."games as g ON g.id=c.game_id LEFT JOIN ".$cMain["dbPrefix"]."users as u ON 
					c.user_id=u.id WHERE $sWhere ORDER BY c_added DESC";
		$res = $db->super_query( $query, true );
		$nCount = count( $res );
		if( $nCount > 0 )
		{
			// Page links
			if( $nCount - $cCommentsPerPage > $cCommentsPerPage / 2 )
				for( $i=0; $i<(int)$nCount/$cCommentsPerPage; $i++ )
				{
					$tpl->assign( array( 
						"PAGE"			=> $i+1,
						"PAGEACTIVE"	=> (($i+1==$_REQUEST["page"]) || (empty($_REQUEST["page"])&&$i==0)) ? "PageActive" : "",
						"PAGELINK"		=> "show=".$_REQUEST["show"]."&page=".($i+1)."&filter=".$_REQUEST["filter"]."&value=".$_REQUEST["value"]
					)); 
					$tpl->parse( "PAGES", ".dynPages" );
				}
			else
			{
				$tpl->clear_dynamic( "dynPages" );
				$tpl->clear_dynamic( "dynPagesTitle" );
			}
			$nPage = empty($_REQUEST["page"]) ? 0 : $_REQUEST["page"]-1;
			if( $nPage*$cGamesOnPage > $nCount )
				$nPage = 0;

			// Comments for the current page
			for( $i=$nPage*$cCommentsPerPage; ($i<($nPage+1)*$cCommentsPerPage) && ($i<$nCount); $i++ )
			{
				$rec = $res[$i];
				if( $rec['c_gameid'] == 0 )
					$sWhat = "Site";
				else
					$sWhat = empty($rec['g_title']) ? "Deleted game" : $rec['g_title'];
				if( $rec['c_userid'] == 1 )
					$sUser = "Admin";
				else if( $rec['c_userid'] == 0 )
					$sUser = "Guest";
				else 
					$sUser = empty($rec['u_username']) ? "Unknown" : $rec['u_username'];
				$tpl->assign( array(
					"LISTWHAT"		=>	$sWhat,
					"LISTGAMEID"	=>	$rec['c_gameid'],
					"LISTID"		=>	$rec['c_id'],
					"LISTDATE"		=>	date( "M d, y", $rec['c_added'] ),
					"USERID"		=>	$rec['c_userid'],
					"LISTUSER"		=>	$sUser,
					"LISTCOMMENT"	=>	$rec['c_text'],
					"LISTIP"		=>	$rec['c_ip'],
					"STATE"			=>	$rec['c_active'] ? "deactivate" : "activate"
					
				));
				$tpl->parse( "LIST", ".dynList" );
			}
		} //if count > 0
		else
		{
			$tpl->clear_dynamic( "dynList" );
			$tpl->clear_dynamic( "dynPages" );
			$tpl->clear_dynamic( "dynPagesTitle" );
		}
		break;
}

$tpl->parse( "CONTENTTOP", "tplTop" );
$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");
?>