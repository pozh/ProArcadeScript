<?php
/*******************************************************************
/ ProArcadeScript 
/ File description: manage registered users
/ 
/*******************************************************************/
require_once( "../include/config.php" );
require_once( "../include/helpers.php" );
require_once( "../include/class.FastTemplate.php" );
require_once( "../include/class.Database.php" );

// few local constants
$cUsersOnPage = 10;

$tpl = new FastTemplate("templates");

require( "checklogin.php" );

$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

$tpl->define( array(
	"tplHeader"	=> "header.html",
	"tplTop"		=> "block_userstop.html",
	"tplFooter"	=> "footer.html"
));

$tpl->assign( array( 
	"SITEROOT"	=> $cSite["sSiteRoot"],
	"TITLE"		=> $cSite["sSiteTitle"] . " Administration - Registered Users",
	"SITETITLE"	=> $cSite["sSiteTitle"],
	"ATUSERS"	=> "Active"
));


$arSubNav = Array( 'SUBLIST', 'SUBNEWUSER', 'SUBUSERSETTINGS' );
foreach( $arSubNav as $BtnStyle )
	$tpl->assign( $BtnStyle, '' );

switch( $_REQUEST['action'] )
{
//-----------------------------------------------------------------------------
// Add or edit user
//-----------------------------------------------------------------------------
	case 'new':
	case 'edit':
	case 'doedit':
		$tpl->define( 'tplMain', 'page_adduser.html' );
		$tpl->assign( array(
		   'ERROR'           => '',
  			'NAMEERROR'    	=> '',
			'EMAILERROR'   	=> '',
			'PASSWORDERROR'	=> '',
			'PASSWORD2ERROR'  => '',
			'REFERRER'        => $_SERVER['HTTP_REFERER'],
			'USERID'          => '',
			'DAYSINACTIVE'    => $cUser['daysUnverified']
		));

		// for the "Edit" form we'll get the data from our database first
		if( $_REQUEST['action'] == 'edit' )
		{
			$dbdata = $db->super_query( 'SELECT * FROM '.$cMain['dbPrefix'].'users WHERE id='.$_GET['id'].' LIMIT 1', false );
			
			// if the user is found, show the data
			if( $dbdata['id']==$_GET['id'] )
			{
				$tpl->assign( array(
				   'ACTION'       => 'doedit',
					'SUBTITLE'     => 'Edit User '.$dbdata['username'],
					'USERID'       => $dbdata['id'],
					'NAME'         => $dbdata['username'],
					'EMAIL'        => $dbdata['email'],
					'LOCATION'     => $dbdata['location'],
					'GENDER0'		=>	$dbdata['gender'] == 0 ? 'Selected' : '',
					'GENDER1'		=>	$dbdata['gender'] == 1 ? 'Selected' : '',
					'GENDER2'		=>	$dbdata['gender'] == 2 ? 'Selected' : '',
					'GAMEPLAYS'    => $dbdata['gameplays'],
					'RATING'       => $dbdata['rating'],
					'SUBSCRIBE'		=>	$dbdata['subscribed']==0 ? '' : 'checked',
					'ACTIVE'			=>	$dbdata['verified']==0 ? '' : 'checked',
				));
			}
			// if not found, show en error and clear the form
			else
			{
				$tpl->assign( array(
				   'ACTION'       => 'doedit',
				   'ERROR'        => $cLang['errNoUser'],
					'SUBTITLE'     => 'New User',
					'NAME'         => '',
					'EMAIL'        => '',
					'LOCATION'     => '',
					'GENDER0'		=>	'',
					'GENDER1'		=>	'',
					'GENDER2'		=>	'',
					'GAMEPLAYS'    => '0',
					'RATING'       => '0',
					'SUBSCRIBE'		=>	'',
					'ACTIVE'			=>	'',
				));

			}
		} // edit
		else
			$tpl->assign( array(
			   'ACTION'       => $_POST['action'] == 'doedit' ? 'doedit' : 'addnew',
				'SUBNEWUSER'   => 'Active',
				'SUBTITLE'     => 'New User',
				'NAME'         => $_POST['name'],
				'EMAIL'        => $_POST['email'],
				'LOCATION'     => $_POST['location'],
				'GAMEPLAYS'    => $_POST['gameplays'] ? $_POST['gameplays'] : 0,
				'RATING'       => $_POST['rating'] ? $_POST['rating'] : 0,
				'GENDER0'		=>	$_POST['gender'] == 0 ? 'Selected' : '',
				'GENDER1'		=>	$_POST['gender'] == 1 ? 'Selected' : '',
				'GENDER2'		=>	$_POST['gender'] == 2 ? 'Selected' : '',
				'SUBSCRIBE'		=>	empty($_POST['subscribe']) ? '' : 'checked',
				'ACTIVE'			=>	empty($_POST['active']) ? '' : 'checked',
			));

		// Process the form data
		// no matter is id specified (editing) or not (new user)
		if( $_REQUEST['action'] == 'doedit' )
		{
			// check the data first
		   $bOK = true;

			// Username
   		if( empty($_POST['name']) )
   		{
   		   $bOK = false;
   		   $tpl->assign( 'NAMEERROR', $cLang['errAEmpty'] );
			}
			elseif( !NameValid($_POST['name']) )
			{
   		   $bOK = false;
   		   $tpl->assign( 'NAMEERROR', $cLang['errNewUsername'] );
			}
			else
			{
			   // check for availability (other users)
			   $duplicate = $db->super_query( 'SELECT username FROM '.$cMain['dbPrefix'].'users WHERE username="'.$_POST['name'].'" LIMIT 1', false );
			   if( !empty($duplicate) && !($dbdata[id]==$duplicate['id']) )   // found and this is ANOTHER user (if dbdata exists)
			   {
			      $bOK = false;
			      $tpl->assign( 'NAMEERROR', $cLang['errUserExists'] );
				}
			}

			// Email
   		if( empty($_POST['email']) )
   		{
   		   $bOK = false;
   		   $tpl->assign( 'EMAILERROR', $cLang['errAEmpty'] );
			}
			elseif( !EmailValid($_POST['email']) )
			{
   		   $bOK = false;
   		   $tpl->assign( 'EMAILERROR', $cLang['errEmail'] );
			}
			else
			{
			   // check the email for its existence in the database (another user)
			   $duplicate = $db->super_query( 'SELECT email FROM '.$cMain['dbPrefix'].'users WHERE email="'.$_POST['email'].'" LIMIT 1', false );
			   if( !empty($duplicate) && !($dbdata['id']==$duplicate['id']) )
			   {
			      $bOK = false;
			      $tpl->assign( 'EMAILERROR', $cLang['errEmailExists'] );
				}
			}

			// Password
   		if( empty($_POST['password']) )
   		{
   		   if( empty($_POST['id']) )
   		   {
					$bOK = false;
   		   	$tpl->assign( 'PASSWORDERROR', $cLang['errAEmpty'] );
				}
			}
			elseif( !PasswordValid($_POST['password']) )
			{
   		   $bOK = false;
   		   $tpl->assign( 'PASSWORDERROR', $cLang['errPassword'] );
			}
			elseif( $_POST['password'] != $_POST['password2'] )
			{
   		   $bOK = false;
				$tpl->assign( "PASSWORD2ERROR", $cLang['errAPassword'] );
			}

			// Check gender
			if( ($_POST['gender']!=0) && ($_POST['gender']!=1) && ($_POST['gender']!=2) )
			   $bOK = false;

			// Check rating and gameplays (must be numbers)
			if( !ctype_digit($_POST['rating']) || !ctype_digit($_POST['gameplays']) )
			   $bOK = false;

			// if everything's ok, add this user to the database
			if( $bOK )
			{
				$location = get_magic_quotes_gpc() ? $_POST['location'] : mysql_escape_string($_POST['location']);
				// Add new user
            if( empty($_POST['id']) )
					$db->query( 'INSERT INTO ' . $cMain['dbPrefix'] .
						'users (username, password, email, verified, subscribed, location, gender, joined, ip, activation_code)'
						. ' VALUES("' . $_POST['name'] . '", "' . md5($_POST['password']) . '", "' . $_POST['email'] . '", '
						. ($_POST['active'] ? 1 : 0) . ', '
						. ($_POST['subscribe'] ? 0 : 1) . ', "' . htmlspecialchars($location) . '", ' . $_POST['gender']
						. ', ' . time() . ', "' . $_SERVER['REMOTE_ADDR'] . '", ' . RandomCode() . ')' );
				// Or change existing one
				else
				{
					$query = 'UPDATE ' . $cMain['dbPrefix'] .	'users SET username="' . $_POST['name'] . '"';
               if( !empty($_POST['password']) )
                  $query .= ', password="'. md5( $_POST['password'] ).'"';
					$query .= ', email="'.$_POST['email'].'", verified='.($_POST['active'] ? 1 : 0).', subscribed='.($_POST['subscribe'] ? 1 : 0)
						.', location="'.htmlspecialchars($location).'", gender='.$_POST['gender'].', gameplays='
						. $_POST['gameplays'] . ', rating=' . $_POST['rating'] . ' WHERE id='.$_POST['id'].' LIMIT 1';
					$db->query( $query );
				}
				if( !$_POST['referrer'] )
	           	header( 'Location:users.php' );
				else
				   header( 'Location:'.$_POST['referrer'] );
			}
		}
		break;

//-----------------------------------------------------------------------------
// Activate / Deactivate user (change the 'verified' state
//-----------------------------------------------------------------------------
	case 'switchstate':
		$db->query( 'UPDATE '.$cMain['dbPrefix'].'users SET verified=MOD(verified+1, 2) WHERE id='.$_REQUEST['id'].' LIMIT 1' );
		header( 'Location:' . $_SERVER['HTTP_REFERER'] );
		break;

//-----------------------------------------------------------------------------
// Delete user
//-----------------------------------------------------------------------------
	case 'delete':
		$tpl->define( 'tplMain', 'page_confirm.html' );
		$tpl->assign( array(
			'MESSAGE'		=>	$cLang['msgADeleteUsert'],
			'FORMACTION'   => 'users.php',
			'ACTION'       => 'dodelete',
			'ID'           => $_REQUEST['id'],
			'SUBTITLE'     => 'Delete',
			'REFERRER'		=>	$_SERVER['HTTP_REFERER']
		));
			
	case 'dodelete':
		if( $_POST['confirm'] == 'Yes' )
		{
		   // delete user's data - favorites, friends, and this user from others' friend lists
		   $db->query( 'DELETE FROM '.$cMain['dbPrefix'].'userdata WHERE user_id='.$_REQUEST['id'] );
		   $db->query( 'DELETE FROM '.$cMain['dbPrefix'].'userdata WHERE param IN(1,2) AND value='.$_REQUEST['id'] );
		   $db->query( 'DELETE FROM '.$cMain['dbPrefix'].'favorites WHERE user_id='.$_REQUEST['id'] );
		   // delete user
		   $db->query( 'DELETE FROM '.$cMain['dbPrefix'].'users WHERE id='.$_REQUEST['id'].' LIMIT 1' );
		}
		header( 'Location:'.$_POST['referrer'] );
		break;

//-----------------------------------------------------------------------------
// User management settings
//-----------------------------------------------------------------------------
	case 'settings':
	case 'savesettings':
	   // setup form controls
		$tpl->define( 'tplMain', 'page_usersettings.html' );
		$tpl->assign( array(
		   'SUBUSERSETTINGS'	=>	'Active',
		   'SUBTITLE'			=>	'Settings',
		   'PLAYPAUSE'  		=> $cUser['minPlayPause'],
		   'PLAYPAUSEERROR'  => '',
			'COMMENTPAUSE'    => $cUser['minCommentPause'],
		   'COMMENTPAUSEERROR'  => '',
		   'MAXNAME'	  		=> $cUser['maxNameLength'],
		   'MAXNAMEERROR'  	=> '',
		   'PERPAGE'	  		=> $cUser['listSize'],
		   'PERPAGEERROR'  	=> '',
		   'AWIDTH'		  		=> $cUser['maxAvatarW'],
		   'AWIDTHERROR'		=> '',
		   'AHEIGHT'		  	=> $cUser['maxAvatarH'],
		   'AHEIGHTERROR'		=> '',
		   'AFILE'			  	=> $cUser['maxAvatar'],
		   'AFILEERROR' 		=> '',
		   'UNVERIFIED'	  	=> $cUser['daysUnverified'],
		   'UNVERIFIEDERROR'	=> '',
		   'PTPLAY'	  			=> $cUser['ptPlay'],
		   'PTCOMMENT'			=> $cUser['ptComment'],
		   'PTRATING'			=> $cUser['ptRating'],
		   'PTSUBMIT'			=> $cUser['ptSubmit'],
		   'PTPLAYERROR'		=> '',
		   'PTRATINGERROR'	=> '',
		   'PTCOMMENTERROR'	=> '',
		   'PTSUBMITERROR'	=> '',
		   'FORBIDDEN'       => $cUser['forbidden']
		));

		// process the form
		if( $_REQUEST['action'] == 'savesettings' )
		{
			$posts = array(
		   	'PLAYPAUSE'  		=> $_POST['playpause'],
				'COMMENTPAUSE'    => $_POST['commentpause'],
			   'MAXNAME'	  		=> $_POST['maxname'],
			   'PERPAGE'	  		=> $_POST['perpage'],
			   'AWIDTH'		  		=> $_POST['awidth'],
			   'AHEIGHT'		  	=> $_POST['aheight'],
			   'AFILE'			  	=> $_POST['afilesize'],
			   'UNVERIFIED'	  	=> $_POST['unverified'],
			   'PTPLAY'	  			=> $_POST['ptplay'],
			   'PTCOMMENT'			=> $_POST['ptcomment'],
			   'PTRATING'			=> $_POST['ptrating'],
			   'PTSUBMIT'			=> $_POST['ptsubmit']
			);
			$tpl->assign( $posts );
			$tpl->assign( 'FORBIDDEN', htmlspecialchars($_POST['forbidden']) );

			// Check every field for errors
			$bOK = true;
			foreach( $posts as $index => $value )
			{
			   if( empty($value) )
			   {
			      $bOK = false;
			      $tpl->assign( $index.'ERROR', $cLang['errAEmpty'] );
			   }
				elseif( !ctype_digit($value) )
				{
				   $bOK = false;
				   $tpl->assign( $index.'ERROR', $cLang['errValue'] );
				}
			}
			
			// if everything's ok, save settings.
			if( $bOK )
			{
				$tplConfig = new FastTemplate( 'templates' );
				$tplConfig->define( 'tplConfig', 'usersettings.tpl' );
				$tplConfig->assign( array(
			   	'PLAYPAUSE'		=> $_POST['playpause'],
					'COMMENTPAUSE' => $_POST['commentpause'],
				   'MAXNAME'  		=> $_POST['maxname'],
				   'PERPAGE'  		=> $_POST['perpage'],
				   'AWIDTH'	  		=> $_POST['awidth'],
				   'AHEIGHT'	  	=> $_POST['aheight'],
				   'AFILE'		  	=> $_POST['afilesize'],
				   'UNVERIFIED'  	=> $_POST['unverified'],
				   'PTPLAY'	  		=> $_POST['ptplay'],
				   'PTCOMMENT'		=> $_POST['ptcomment'],
				   'PTRATING'		=> $_POST['ptrating'],
				   'PTSUBMIT'		=> $_POST['ptsubmit'],
				   'FORBIDDEN'    => $_POST['forbidden']
				));
				$tplConfig->parse( 'CONFIG', 'tplConfig' );
				$sNewConfig = $tplConfig->GetText( 'CONFIG' );
				$fConfig = fopen( '../include/usersettings.php', 'w' );
				fwrite( $fConfig, $sNewConfig );
				fclose( $fConfig );
				header( 'Location:users.php?action=settings');
			} // if bOK
		} // if action == savesettings
	   break;


//-----------------------------------------------------------------------------
// List users
//-----------------------------------------------------------------------------
	case 'list':
	default:
		$tpl->define( 'tplMain', 'page_users.html' );
		$tpl->define_dynamic( 'dynList', 'tplMain' );
		$tpl->define_dynamic( 'dynPages', 'tplMain' );
		$tpl->define_dynamic( 'dynPagesTop', 'tplMain' );

		$tpl->assign( array(
		   'SUBLIST'		=>	'Active',
		   'SUBTITLE'		=>	'List',
			'ORDERLINK'		=>	'&username='.$_REQUEST['username'].'&inactive='.$_REQUEST['inactive'].'&page='.$_REQUEST['page'],
			'FINDUSER'		=>	$_REQUEST['username'],
			'FINDACTION'	=>	$_SERVER['PHP_SELF'],
			'FILTERPAGE'	=>	$_REQUEST['page'],
			'INACTIVECHECKED'	=>	empty($_REQUEST['inactive'])? '' : 'Checked'
		));

		// Highlight the column by which the users are sorted
		$arCurrent = Array(
			'id' 			=> 'SORTEDID',
			'username'	=> 'SORTEDUSERNAME',
			'joined'		=>	'SORTEDJOINED',
			'plays'		=> 'SORTEDPLAYS',
			'rating'		=> 'SORTEDRATING',
			'comments'	=> 'SORTEDCOMMENTS'
		);
		foreach( $arCurrent as $col )
			$tpl->assign( $col, '' );
		if( !empty($_REQUEST['sort']) )
			$tpl->assign( $arCurrent[$_REQUEST['sort']], 'Current' );
		else
			$tpl->assign( $arCurrent['id'], 'Current' );

		// count the user in our database
		$query = 'SELECT COUNT(id) AS count FROM '.$cMain['dbPrefix'].'users WHERE 1';
		if( !empty($_REQUEST['username']) )
			$query .= ' AND username LIKE "' . $_REQUEST['username'] . '%"';
		if( !empty($_REQUEST['inactive']) )
			$query .= ' AND verified=0';
		$res = $db->super_query( $query, false );
		$nusers = $res['count'];

		// List the users (if there is at least one)
		if( $nusers > 0 )
		{
			$query = 'SELECT u.*, COUNT(c.id) as comments FROM '.$cMain['dbPrefix'].'users as u LEFT JOIN '
				.$cMain['dbPrefix'].'comments as c ON u.id=c.user_id WHERE 1';
		  	if( !empty($_REQUEST['username']) )
				$query .= ' AND u.username LIKE "' . $_REQUEST['username'] . '%"';
			if( !empty($_REQUEST['inactive']) )
				$query .= ' AND u.verified=0';
			$query .= ' GROUP BY u.id';
			if( !empty($_REQUEST['sort']) )
			{
				$arOrder = Array( 'desc' => ' DESC', 'asc' => ' ASC' );
				$arSort = Array(
					'id'		=>' ORDER BY u.id',
					'username'	=> ' ORDER BY u.username',
					'joined'	=> ' ORDER BY u.joined',
					'plays' 	=> ' ORDER BY u.gameplays',
					'rating'	=>	' ORDER BY u.rating',
					'comments'	=> ' ORDER BY comments' );
				$query .= $arSort[$_REQUEST['sort']] . $arOrder[$_REQUEST['order']];
			}

			// if users do not fit in 1 page...
			if( $nusers - $cUsersOnPage > $cUsersOnPage / 2 )
			{
				// ...output page numbers
				for( $i=0; $i<(int)$nusers/$cUsersOnPage; $i++ )
				{
					$tpl->assign( array(
						'PAGE'			=> $i+1,
						'PAGEACTIVE'	=> (($i+1==$_REQUEST['page']) || (empty($_REQUEST['page'])&&$i==0)) ? 'PageActive' : '',
						'PAGELINK'		=> 'page='.($i+1).'&username='.$_REQUEST['username']
							.'&inactive='.$_REQUEST['inactive'].'&sort='.$_REQUEST['sort'].'&order='.$_REQUEST['order']
					));
					$tpl->parse( 'PAGES', '.dynPages' );
					$tpl->parse( 'TOPPAGES', '.dynPagesTop' );
				}
            // ...add limits to the quesry
			   $query .= $_REQUEST['page'] ? ' LIMIT '.$cUsersOnPage*($_REQUEST['page']-1).', '.$cUsersOnPage : ' LIMIT '.$cUsersOnPage;
			}
			else
			{
				$tpl->clear_dynamic( 'dynPages' );
				$tpl->clear_dynamic( 'dynPagesTop' );
			}

			// users on the curent page
			$res = $db->super_query( $query, true );
			foreach( $res as $i => $auser )
			{
				$tpl->assign(array(
					'USERID'		=>	$auser['id'],
					'USERNAME'	=>	$auser['username'],
					'USERJOINED'=>	date('M d, y', $auser['joined']),
					'GAMEPLAYS'	=>	$auser['gameplays']>0 ? $auser['gameplays'] : '-',
					'RATING'		=>	$auser['rating']>0 ? $auser['rating'] : '-',
					'STATE'		=> $auser['verified'] ? 'deactivate' : 'activate',
					'COMMENTS'	=>	$auser['comments']>0 ? $auser['comments'] : '-'
				));
				$tpl->parse( 'LIST', '.dynList' );
			}
		}
		else
		{
			$tpl->clear_dynamic( 'dynList' );
			$tpl->clear_dynamic( 'dynPages' );
			$tpl->clear_dynamic( 'dynPagesTop' );
		}
		break;
}

$tpl->parse( "CONTENTTOP", "tplTop" );
$tpl->parse( "ADMINHEADER", "tplHeader" );
$tpl->parse( "ADMINFOOTER", "tplFooter" );
$tpl->parse( "ADMINMAIN", "tplMain" );
$tpl->FastPrint( "ADMINMAIN");
?>