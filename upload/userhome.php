<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ User's personal page, registration and login box.
/ IN: page - a page to show(register, login) show CP if undefined
/
/*******************************************************************/
require_once( "include/config.php" );
require_once( "templates/".$cSite["sTemplate"]."/config.php" );
require_once( "include/class.FastTemplate.php" );
require_once( "include/class.Database.php" );
require_once( "include/helpers.php" );
require_once( "include/class.Log.php" );
require_once( "include/class.usercp.php" );

session_start();
// Initialise templates
$tpl = new FastTemplate("templates/".$cSite["sTemplate"]);

// Connect to the database
$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );

$log = new CLog( $db );

// Integrated (permanent) blocks
$tpl->define( "tplBHead", "block_head.html" );
$tpl->define( "tplMain", "page_user.html" );

$tpl->assign( array(
	"SITEURL"			=> $cSite["sURL"],
	"SITEROOT"			=> $cSite["sSiteRoot"],
	"TITLE"				=> $cSite["sSiteTitle"]
	));

// Proceed if visitor is registered user only
$guestpages = array( 'login', 'lostpassword', 'register', 'activate', 'newemail' );
if ( !empty($_SESSION['user']) || in_array($_REQUEST['page'], $guestpages) )
{
	switch( $_REQUEST['page'] )
	{
//---------------------------------------------------------------------
// registration
//---------------------------------------------------------------------
	case "register":
	   //hide user navigation
		$tpl->define_dynamic( 'dynOwner', 'tplMain' );
		$tpl->clear_dynamic( 'dynOwner' );

		$tpl->define( "tplText", "user_register.html" );
		$page_path = $cLang['sRegistration'];

		$tpl->assign( array(
			'NAMEERROR'			=>	'',
			'EMAILERROR'		=>	'',
			'PASSWORDERROR'	=>	'',
			'PASSWORD2ERROR'	=>	'',
			'EMAIL'				=>	'',
			'NAME'				=>	'',
			'LOCATION'			=>	'',
			'GENDER0'			=>	'Selected',
			'GENDER1'			=>	'',
			'GENDER2'			=>	'',
			'SUBSCRIBE'			=>	'checked'
			));

		if( empty($_POST['submit']) )
		{
		   // Enter the registration page
			$log->write_event( 'register user page' );
		}
		else
		{
   		// Check entered data and register if everything is ok
		   $bOK = true;

			// Username
			$forbidden = str_replace( ' ', '', $cUser['forbidden'] );
			$reservednames = explode( ',', $forbidden );
   		if( empty($_POST['name']) )
   		{
   		   $bOK = false;
   		   $tpl->assign( 'NAMEERROR', $cLang['errAEmpty'] );
			}
			elseif( !NameValid($_POST['name']) || in_array($_POST['name'], $guestpages) || in_array($_POST['name'], $reservednames) )
			{
   		   $bOK = false;
   		   $tpl->assign( 'NAMEERROR', $cLang['errNewUsername'] );
			}
			else
			{
			   // check for availability (other users)
			   $duplicate = $db->super_query( 'SELECT username FROM '.$cMain['dbPrefix'].'users WHERE username="'.$_POST['name'].'" LIMIT 1', false );
			   if( !empty($duplicate) )
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
			   if( !empty($duplicate) )
			   {
			      $bOK = false;
			      $tpl->assign( 'EMAILERROR', $cLang['errEmailExists'] );
				}
			}

			// Password
   		if( empty($_POST['password']) )
   		{
   		   $bOK = false;
   		   $tpl->assign( 'PASSWORDERROR', $cLang['errAEmpty'] );
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
			{
			   $bOK = false;
			}

			// Fill up the form by our data (for the case if we'll need to show the form and errors
			$tpl->assign( array(
				"NAME"			=>	$_POST['name'],
				"EMAIL"			=>	$_POST['email'],
				"LOCATION"		=>	htmlspecialchars($_POST['location']),
				"GENDER0"		=>	$_POST['gender'] == 0 ? 'Selected' : '',
				"GENDER1"		=>	$_POST['gender'] == 1 ? 'Selected' : '',
				"GENDER2"		=>	$_POST['gender'] == 2 ? 'Selected' : '',
				"SUBSCRIBE"		=>	empty($_POST['subscribe']) ? '' : "checked"
			));

			// If everything's OK, register him/her
			if( !$bOK )
				$log->write_event( 'registration is failed' );
			else
			{
				$nSubscribe = empty( $_POST['subscribe'] ) ? 0 : 1;
				$nActivation = RandomCode();
				$location = get_magic_quotes_gpc() ? $_POST['location'] : mysql_escape_string($_POST['location']);
				$db->query( 'INSERT INTO ' . $cMain['dbPrefix'] .
					'users (username, password, email, subscribed, location, gender, joined, ip, activation_code)' .
					' VALUES("' . $_POST['name'] . '", "' . md5($_POST['password']) . '", "' . $_POST['email'] . '", ' .
					$nSubscribe . ', "' . htmlspecialchars($location) . '", ' . $_POST['gender'] .
					', ' . time() . ', "' . $_SERVER['REMOTE_ADDR'] . '", ' . $nActivation . ')' );
					
				// Create and send a confirmation email to the user
				$mailtpl = new FastTemplate( "emailtemplates" );
				$mailtpl->define( "tplBody", "activation.tpl" );
				$mailtpl->assign(array(
					"SITETITLE"		=>	$cSite['sSiteTitle'],
					"SITEROOT"		=> $cSite["sSiteRoot"],
					"NAME"			=>	$_POST['name'],
					"USEREMAIL"		=> $_POST['email'],
					"PASSWORD"		=>	$_POST['password'],
					"ACTIVATELINK"	=>	$cSite['sURL'].'/user/activate/?email='.$_POST['email'].'&code='.$nActivation,
					"SIGNATURE"		=>	$cSite['MailSignature']
				));
				$mailtpl->parse( BODY, "tplBody" );
				$sBody = $mailtpl->GetText( "BODY" );
				$sTo = $_POST['email'];
				$sFrom = $cSite['sContactEmail'];
				$header = "From: $sFrom\r\n" .
     						 "Reply-To: $sFrom\r\n" .
     						 "X-Mailer: PHP/" . phpversion();
				$subj = str_replace( 'SITENAME', $cSite['sSiteTitle'], $cLang['sActivationSubj'] );
				if( @mail( $sTo, $subj, $sBody, $header ) )
					$tpl->assign( "MESSAGE", $cLang['msgAccountCreated'] );
				else
					$tpl->assign( "MESSAGE", $cLang['errSendEmail'] );
				$tpl->define( "tplText", "user_msg.html" );
			}
		}

		$log->write_event( $_POST['name'] .' registered successfully' );
		break;
//---------------------------------------------------------------------
// login page
//---------------------------------------------------------------------
	case "login":
		$tpl->define( "tplText", "user_login.html" );

		//hide user navigation
		$tpl->define_dynamic( 'dynOwner', 'tplMain' );
		$tpl->clear_dynamic( 'dynOwner' );

		$page_path = $cLang['sLogin'];
		$tpl->assign( array(
			'LOGINERROR'		=>	'',
			'PASSWORDERROR'	=>	'',
			'LOGIN'				=>	''
		));
		if( empty($_POST['submit']) )
			$log->write_event( 'login page' );
		else	
		{
			// trying to login
			$sLogin = trim($_POST['login']);
			$sPassword = trim($_POST['password']);
			$bOK = NameValid( $sLogin ) && PasswordValid( $sPassword );

			if( $bOK )
			{
				// Check database
				$res = $db->super_query( "SELECT * FROM ".$cMain['dbPrefix']."users WHERE username=\"$sLogin\" AND password=\"".md5($sPassword)."\" LIMIT 1" );
				if( !empty($res) )
				{
					$tpl->define( "tplText", "user_msg.html" );
					if( $res['verified'] == 1 )	
					{
						$_SESSION['user'] = $res['id'];
						$_SESSION['username'] = $res['username'];
						$_SESSION['last_login'] = $res['last_login'];
						$db->query( 'UPDATE '.$cMain['dbPrefix'].'users SET last_login='.time().' WHERE id='.$res['id'].' LIMIT 1' );
						
						// delete temp records from userdata table
						$db->query( 'DELETE FROM '.$cMain['dbPrefix'].'userdata WHERE user_id='.$res['id'].' AND param=3' );
						
						$log->write_event( $res['username'].' logged in' );
					   header( 'Location:'.$cSite['sSiteRoot'].'user/'.$res['username'] );
					}
					else
						$tpl->assign( "MESSAGE", str_replace("!NAME!", $res['username'], $cLang['msgNotActive']) );
				}
				else
					$tpl->assign( "LOGINERROR", $cLang['errALogin'] );
			} // bOK
			else
			{
				$tpl->assign( "LOGINERROR", $cLang['errALogin'] );
				$log->write_event( "login is failed" );
			}
		}
		break;

//--------------------------------------------------------------------
// User logout
//--------------------------------------------------------------------
	case 'logout':
		unset( $_SESSION["user"] );
		unset( $_SESSION["username"] );
		$log->write_event( $_SESSION["username"] . ' logged out' );
		header( "Location:" . $cSite['sSiteRoot'] );
		break;

//--------------------------------------------------------------------
// Lost password form
//--------------------------------------------------------------------
	case 'lostpassword':
		$tpl->define( "tplText", "user_lostpassword.html" );
		$log->write_event( 'lost password page' );

		//hide user navigation
		$tpl->define_dynamic( 'dynOwner', 'tplMain' );
		$tpl->clear_dynamic( 'dynOwner' );

		$page_path = $cLang['sLogin'];
		$tpl->assign( array(
			'USERNAME'			=>	'',
			'USERNAMEERROR'	=>	'',
			'EMAIL'           => '',
			'EMAILERROR'      => ''
		));
		if( !empty($_POST['submit']) )
		{
		   // Process the form
			$username = trim($_POST['username']);
			$email = trim($_POST['email']);
			if( NameValid($username) )
			{
			   $tpl->assign( 'USERNAME', $username );
			   if( EmailValid($email) )
			   {
			      $tpl->assign( 'EMAIL', $email );
			      // find this user in the database
			      $user = $db->super_query( 'SELECT * FROM '.$cMain['dbPrefix']
						.'users WHERE username="'.$username.'" AND email="'.$email.'" LIMIT 1', false );
					$log->write_event( 'finding user ' . $username . ' (' . $email . ')' );
					if( !empty($user) )
					{
				      // generate new password
				      $newpassword = RandomStr( 5 );

					   if( $user['verified']==1 )
					   {
					      // Assenble new message using template and send it to the user
	      				$mailtpl = new FastTemplate( 'emailtemplates' );
							$mailtpl->define( 'tplBody', 'lostpassword.tpl' );
							$mailtpl->assign(array(
								'SITETITLE'		=>	$cSite['sSiteTitle'],
								'SITEROOT'		=> $cSite['sSiteRoot'],
							   'LOGINURL'     => $cSite['sURL'].$cSite['sSiteRoot'].'user/login/',
								'NAME'			=>	$username,
								'PASSWORD'		=>	$newpassword,
								'SIGNATURE'		=>	$cSite['MailSignature']
							));
							$mailtpl->parse( BODY, 'tplBody' );
							$sBody = $mailtpl->GetText( 'BODY' );
							$sTo = $username . ' <'.$email.'>';
							$sFrom = $cSite['sSiteTitle'].' <'.$cSite['sContactEmail'].'>';
							$header = "From: $sFrom\r\n X-Mailer: Content Manager - PHP/" . phpversion();
							if( @mail($sTo, $cLang['sNewPassSubj'], $sBody, $header) )
							{
						      // change the record in the database
						      $db->query( 'UPDATE '.$cMain['dbPrefix'].'users SET password="'.md5($newpassword)
									.'" WHERE username="'.$username.'" LIMIT 1' );
								// Generate and show the "done" message to the user
								$tpl->define( 'tplText', 'user_msg.html' );
								$tpl->assign( 'MESSAGE', $cLang['msgPasswordSent'] );
								
								$log->write_event( 'new password is sent' );
							}
							else
							{
								$tpl->assign( 'EMAILERROR', $cLang['errSendEmail'] );
								$log->write_event( 'can\'t send new password' );
							}

						} //verified
						else
						{
						   //if user is found, but not verified yet, inform him about that
							$mailtpl = new FastTemplate( "emailtemplates" );
							$mailtpl->define( "tplBody", "activation.tpl" );
							$mailtpl->assign(array(
								"SITETITLE"		=>	$cSite['sSiteTitle'],
								"SITEROOT"		=> $cSite['sSiteRoot'],
								"NAME"			=>	$user['username'],
								"USEREMAIL"		=> $user['email'],
								"PASSWORD"		=>	$newpassword,
								"ACTIVATELINK"	=>	$cSite['sURL'].'/user/activate/?email='.$user['email'].'&code='.$user['activation_code'],
								"SIGNATURE"		=>	$cSite['MailSignature']
							));
							$mailtpl->parse( BODY, "tplBody" );
							$sBody = $mailtpl->GetText( "BODY" );
							$sTo = $user['username'] . ' <'.$user['email'].'>';
							$sFrom = $cSite['sSiteTitle'].' <'.$cSite['sContactEmail'].'>';
							$header = "From: $sFrom\r\n X-Mailer: Content Manager - PHP/" . phpversion();
							$subj = str_replace( 'SITENAME', $cSite['sSiteTitle'], $cLang['sActivationSubj'] );
							if( @mail($sTo, $subj, $sBody, $header) )
							{
							   $log->write_event( 'resent verification message' );
								// Generate and show the "done" message to the user
								$tpl->define( 'tplText', 'user_msg.html' );
								$tpl->assign( 'MESSAGE', $cLang['msgUnconfirmed'] );
								
								// save new password in the database
								$db->query( 'UPDATE '.$cMain['dbPrefix'].'users SET password="'.md5($newpassword).'" WHERE id='.$user['id'].' LIMIT 1' );
							}
							else
								$tpl->assign( 'EMAILERROR', $cLang['errSendEmail'] );
						}
					}
					else
					   $tpl->assign( 'EMAILERROR', $cLang['errNoUser'] );
			   } // valid email
			   else
					$tpl->assign( 'EMAILERROR', $cLang['errEmail'] );
			} // valid name
		   else
				$tpl->assign( 'USERNAMEERROR', $cLang['errNewUsername'] );
		}
		break;

		
//---------------------------------------------------------------------
// Activate user's account
//---------------------------------------------------------------------
	case "activate":
	   //hide user navigation
		$tpl->define_dynamic( 'dynOwner', 'tplMain' );
		$tpl->clear_dynamic( 'dynOwner' );
		$log->write_event( 'new user activation' );

		$page_path = $cLang['sActivation'];
		$bOK = EmailValid($_REQUEST['email']) && ($_REQUEST['code']>0);
		if( $bOK )
		{
			$res = $db->super_query( "SELECT * FROM ".$cMain['dbPrefix']."users WHERE email='" . 
				$_REQUEST['email']."' AND activation_code=".$_REQUEST['code']." LIMIT 1", false );
			$bOK = !empty( $res );
		}
		if( $bOK )
		{
			if( $res['verified'] )
			{
				$tpl->define( "tplText", "user_msg.html" );
				$tpl->assign( "MESSAGE", $cLang['msgActivated'] );
			}
			else
			{
				$db->query( "UPDATE	".$cMain['dbPrefix']."users SET verified=1 WHERE email='".$_REQUEST['email']."' AND activation_code=".$_REQUEST['code']." LIMIT 1" );
				$res = $db->super_query( "SELECT * FROM ".$cMain['dbPrefix']."users WHERE email='" . 
					$_REQUEST['email']."' AND activation_code=".$_REQUEST['code']." AND verified=1 LIMIT 1", false );
				if( !empty($res) )
				{
					$tpl->define( "tplText", "user_msg.html" );
					$tpl->assign( "MESSAGE", $cLang['msgActivateSuccess'] );
					$log->write_event( 'user with email '.$_REQUEST['email'].' activated' );
				}
				else		
				{
					$tpl->define( "tplText", "user_msg.html" );
					$tpl->assign( "MESSAGE", $cLang['errActivate'] );
				}
			}
			$log->write_event( 'activated' );
		}
		else		
		{
			$tpl->define( "tplText", "user_msg.html" );
			$tpl->assign( "MESSAGE", $cLang['errActivate'] );
			$log->write_event( 'activation_failed' );
		}
		break;
		
//---------------------------------------------------------------------
// List all members along with their rating
//---------------------------------------------------------------------
	case 'list':
		$tpl->assign( 'USERNAME', $_SESSION['username'] );
   	$usercp = new CUsercp( $db, $tpl, $_SESSION['user'], $_SESSION['username'], $_REQUEST['page'] );
		$page_path = $cLang['sUserList'];
   	$usercp->show_usernav( 'list' );
		$usercp->list_users();
		$log->write_event( $_SESSION['username'] . ' lists users' );
		break;

//---------------------------------------------------------------------
// Confirm new email address
//---------------------------------------------------------------------
	case 'newemail':
		$tpl->define( "tplText", "user_msg.html" );
		$log->write_event( 'change email' );
		if( ($_GET['id']>0) && EmailValid($_GET['newemail']) )
		{
		   // find the new email address in the temp record
			$temprec = $db->super_query( 'SELECT * FROM '.$cMain['dbPrefix'].'userdata WHERE user_id=' .
				$_GET['id'].' AND param=3 and value="'.$_GET['newemail'].'" LIMIT 1', false );
			// if found, move it into the user's record and delete all temp emails for this user
			if( !empty($temprec) )
			{
				$db->query( 'UPDATE '.$cMain['dbPrefix'].'users SET email="'.$_GET['newemail'].'" WHERE id='.$_GET['id'].' LIMIT 1' );
				$db->query( 'DELETE FROM '.$cMain['dbPrefix'].'userdata WHERE user_id='.$_GET['id'].' AND param=3' );
				$tpl->assign( 'MESSAGE', $cLang['msgEmailConfirmed'] );
			}
			else
			   $tpl->assign( 'MESSAGE', $cLang['errNoNewEmail'] );
		}
		else
			$tpl->assign( 'MESSAGE', $cLang['errBadRequest'] );

	   //if user is not logged in, hide user navigation
	   if( empty($SESSION['user']) )
	   {
			$tpl->define_dynamic( 'dynOwner', 'tplMain' );
			$tpl->clear_dynamic( 'dynOwner' );
		}
		else
		{
		   // else show it.
	   	$usercp = new CUsercp( $db, $tpl, $_SESSION['user'], $_SESSION['username'], $_REQUEST['page'] );
			$page_path = $cLang['sUser'];
	   	$usercp->show_usernav();
		}


	   break;
		
//---------------------------------------------------------------------
// A user's page
//---------------------------------------------------------------------
	default:
		$tpl->assign( 'USER_NAME', $_REQUEST['page'] );
   	$usercp = new CUsercp( $db, $tpl, $_SESSION['user'], $_SESSION['username'], $_REQUEST['page'] );
   	$usercp->show_usernav( $_GET['show'] );
   	switch( $_GET['show'] )
   	{
   	   case 'favorites':
   	      $log->write_event( $_SESSION['username'] . ' tries to look at ' . $_REQUEST['page'] . ' favorites' );
	         if( $_SESSION['username'] == $_REQUEST['page'] )
	         {
					$page_path = $cLang['sUserFavorites'];
		         $usercp->show_favorites();
				}
				else
				   $usercp->show_info();
   	      break;
			case 'submit':
			   $log->write_event( 'submit game page' );
				$page_path = $cLang['sUserSubmit'];
//		      $usercp->process_submit( $_REQUEST['action'] );
			   break;
			case 'mail':
			   $log->write_event( 'user mail' );
			   break;
			case 'settings':
			   $log->write_event( 'user settings' );
	         if( $_SESSION['username'] == $_REQUEST['page'] )
	         {
					$page_path = $cLang['sUserSettings'];
		         $usercp->process_settings( $_REQUEST['action'] );
				}
				else
				   $usercp->show_info();
			   break;
			case 'addtofriends':
			   $log->write_event( 'user_addfriend' );
			   // find the new friends ID first
			   $friend = $db->super_query( 'SELECT id, username, verified FROM '.$cMain['dbPrefix'].'users WHERE username="'.$_REQUEST['page'].'" AND verified=1 LIMIT 1', false );
			   if( !empty($friend) )
			   {
			      // check if this user is already a friend
			      $duplicate = $db->super_query( 'SELECT * FROM '.$cMain['dbPrefix'].'userdata WHERE user_id='.$_SESSION['user'].' AND param=1 AND value='.$friend['id'].' LIMIT 1', false );
			      if( empty( $duplicate ) )
			   		$db->query( 'INSERT INTO '.$cMain['dbPrefix'].'userdata VALUES ('.time().', '.$_SESSION['user'].', 1, '.$friend['id'].')' );
				}
				header( "Location:" . $_SERVER['HTTP_REFERER'] );
			   break;
			case 'removefriend':
			   $log->write_event( 'remove '.$_REQUEST['page'].' from friendlist of '.$_SESSION['user'] );
			   // find the new friends ID first
			   $friend = $db->super_query( 'SELECT id, username FROM '.$cMain['dbPrefix'].'users WHERE username="'.$_REQUEST['page'].'" LIMIT 1', false );
			   if( !empty($friend) )
				   $db->query( 'DELETE FROM '.$cMain['dbPrefix'].'userdata WHERE user_id='.$_SESSION['user'].' AND param=1 AND value='.$friend['id'].' LIMIT 1' );
				header( "Location:" . $_SERVER['HTTP_REFERER'] );
			   break;
	      default:
	         $log->write_event( $_SESSION['username'] . ' looks at profile of '.$_REQUEST['page'] );
	         // Show user's profile
	         if( $_SESSION['username'] == $_REQUEST['page'] )
					$page_path = $cLang['sUserHome'];
				else
					$page_path = $_REQUEST['page'] . $cLang['sUserPath'];
	         $usercp->show_info();
	         break;
	   }
		break;
	}// switch
}
elseif( !in_array($_REQUEST['page'], $guestpages) )
{
	// access prohibited

	//hide user navigation
	$tpl->define_dynamic( 'dynOwner', 'tplMain' );
	$tpl->clear_dynamic( 'dynOwner' );

	$page_path = $cLang['sLogin'];
	$tpl->define( "tplText", "user_login.html" );
	$tpl->assign( array(
		"LOGINERROR"	=>	"",
		"PASSWORDERROR"	=>	"",
		"LOGIN"			=>	""
	));
	$log->write_event( 'guest tried to access '.$_REQUEST['page'] );
}


$tpl->assign( array(
	"SITEURL"			=> $cSite["sURL"],
	"SITEROOT"			=> $cSite["sSiteRoot"],
	"METATITLE"			=> $cSite["sSiteTitle"],
	"METADESCRIPTION"	=> $cSite["sSiteDesc"],
	"METAKEYWORDS"		=> $cSite["sSiteKeywords"],
	"TITLE"				=> $cSite["sSiteTitle"]
	));

// Page blocks
foreach( $cB as $id => $cBlock )
{
	if( $cBlock["user"] )
	{
		if( $cBlock["script"] )
			include( "content/blocks/" . $cBlock["file"] . ".php" );
		else
		{	
			$tpl->set_root( "content/blocks" );
			$tpl->define( $cBlock["file"], $cBlock["file"].".html" );
			$tpl->parse( $id, $cBlock["file"] );
			$tpl->set_root( "templates/".$cSite["sTemplate"] );
		}
	}
	else
		$tpl->assign( $id, "" );
}

//	parse and make the page visible
$tpl->parse( "TEXT", "tplText" );
$tpl->parse( "HEAD", "tplBHead" );
$tpl->parse( "MAIN", "tplMain" );
$tpl->FastPrint( "MAIN");
?>
