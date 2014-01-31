<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ User Control Panel. Contains all necessary functions to visualise
/ user's data and process user-related actions
/
/*******************************************************************/
	class CUsercp
	{
	   var $tpl = NULL;
	   var $db = NULL;
		var $username = '';     // from SESSION, i.e. the logged in user
		var $userid = 0;
		var $ownername = 0;    // from $_GET, i.e. the owner of the user page we are looking at
		var $siteroot = '';
		var $dbprefix = '';

	   //--------------------------------------------------------------------------------------------
		// Constructor
		//--------------------------------------------------------------------------------------------
		function CUsercp( &$db, &$tpl, $userid, $username, $ownername )
		{
		   $this->db = &$db;
		   $this->tpl = &$tpl;
		   $this->userid = $userid;
		   $this->username = $username;
			$this->ownername = $ownername;
			$this->siteroot = $GLOBALS['cSite']['sSiteRoot'];
			$this->dbprefix = $GLOBALS['cMain']['dbPrefix'];
		}

	   //--------------------------------------------------------------------------------------------
	   // Output user navigation
	   //--------------------------------------------------------------------------------------------
		function show_usernav( $activetab )
		{
			$this->tpl->define_dynamic( 'dynUsernav', 'tplMain' );
			$links = Array(
				'sUserFavorites'  => $this->username . '/favorites.html',
//				'sUserSubmit'     => $this->username . '/submit.html',
				'sUserList'       => 'list/',
//				'sUserMail'       => $this->username . '/mail.html',
				'sUserSettings'   => $this->username . '/settings.html',
				'sUserLogout'   	=> 'logout/' );

			// place user home link
			$this->tpl->assign( array(
			   'UNAVACTIVE'	=> (empty($activetab) && ($this->username==$this->ownername)) ? 'active' : '',
				'UNAVLINK'		=>	$GLOBALS['cSite']['sSiteRoot'] . 'user/' . $this->username . '/',
				'UNAVTITLE'		=> $GLOBALS['cLang']['sUserHome']
			));
			$this->tpl->parse( 'USERNAV', '.dynUsernav' );

			// other links
			foreach( $links as $id => $link )
			{
			   if( $id == 'sUserHome' ) continue;
			   $sactive = '';
			   if( $link && $activetab )
			   	if( stristr($link, $activetab) )
			      	$sactive = 'active';
			   $this->tpl->assign( array(
			      'UNAVACTIVE'	=> $sactive,
					'UNAVLINK'		=>	$GLOBALS['cSite']['sSiteRoot'] . 'user/' . $link,
					'UNAVTITLE'		=> $GLOBALS['cLang'][$id]
				));
				$this->tpl->parse( 'USERNAV', '.dynUsernav' );
			}
		}
		
	   //--------------------------------------------------------------------------------------------
	   // Show information about the user
	   //--------------------------------------------------------------------------------------------
		function show_info()
		{
		   $tblusers = $this->dbprefix . 'users';
		   $tblfavs = $this->dbprefix . 'favorites';
		   $tbldata = $this->dbprefix . 'userdata';

			// Read user's data from DB...
 			$userdata = $this->db->super_query( 'SELECT * FROM ' . $tblusers . ' WHERE username="' .
			 	$this->ownername . '" AND verified=1 LIMIT 1' );

			// ...And check whether or not this user exists...
         if( !$userdata )
			{
			   // User does not exists, show the warning and exit
	  			$this->tpl->define( 'tplText', 'user_msg.html' );
	  			$this->tpl->assign( 'MESSAGE', $GLOBALS['cLang']['msgNoUser'] );
				return 0;
         }

			// User exists, continue
  			$this->tpl->define( "tplText", "user_home.html" );

			// Remove private user's blocks if visitor is not the owner of this user CP
		   $this->tpl->define_dynamic( 'dynOwner', 'tplText' );
		   if( $this->ownername != $this->username )
				$this->tpl->clear_dynamic( 'dynOwner' );

			// Other data -------------------------------------------------
			$this->tpl->assign( array(
				'USER_SEX'        => $GLOBALS['cLang']['sex'.$userdata['gender']],
				'USER_AVATAR'     => empty($userdata['avatar']) ? 'default'.$userdata['gender'].'.gif' : $userdata['avatar'],
				'USER_LOCATION'   => $userdata['location'],
				'USER_JOINED'     => date( 'M.jS, Y', $userdata['joined'] ),
				'USER_STATUSNAME'	=> $GLOBALS['cLang']['status'.GetStatus($userdata['rating'])],
				'USER_STATUS'  	=> GetStatus($userdata['rating']),
				'USER_LASTLOGIN'  => empty($_SESSION['last_login']) ? $GLOBALS['cLang']['na'] : date( 'M.jS, Y', $_SESSION['last_login'] ),
				'USER_GAMEPLAYS'  => $userdata['gameplays'],
				'USER_SUBMITS'    => 0,  //TODO: write the actual number once submit subsystem is ready.
				'USER_RATING'     => empty($userdata['rating']) ? 0 : $userdata['rating']
			));

			// Add to friends stuff (inactive while user browses his/her own profile)
         $this->tpl->define_dynamic( 'dynIsFriend', 'tplText' );
         $this->tpl->define_dynamic( 'dynAddFriend', 'tplText' );
		   if( $this->ownername == $this->username )
			{
				// Remove the "Add to friends" stuff
			   $this->tpl->clear_dynamic( 'dynIsFriend' );
			   $this->tpl->clear_dynamic( 'dynAddFriend' );
			}
			else
			{
			   // If this user is already my friend, hide the "add friend" link
			   $res = $this->db->super_query( 'SELECT * FROM '.$this->dbprefix.
					'userdata WHERE user_id='.$this->userid.' AND param=1 AND value="'.$userdata['id'].'" LIMIT 1', false );
				if( empty($res) )
				   $this->tpl->clear_dynamic( 'dynIsFriend' );
				else
				   $this->tpl->clear_dynamic( 'dynAddFriend' );
   		}

			// Favorites --------------------------------------------------
			$this->tpl->define_dynamic( "dynUserFavorites", "tplText" );
			$query = 'SELECT F.user_id, F.game_id, U.id, G.id, G.title, G.latin_title, G.active, G.thumbnail FROM '.$this->dbprefix.'games AS G, '.
				$tblusers.' AS U, '.$tblfavs.' AS F WHERE U.id='.$userdata['id'].' AND U.id=F.user_id AND G.id=F.game_id';
         if( $this->ownername == $this->username )
            $query .= ' LIMIT 8';     //TODO limit must be taken from user's settings
			$favorites = $this->db->super_query( $query, true );
			if( count($favorites) > 0 )
			{
				$this->tpl->assign( 'USERFAVMESSAGE', '' );
				$n = 0;
				foreach( $favorites as $i => $fav )
				{
					$sThumb = $this->siteroot.'content/thumbs/'.$fav['thumbnail'];
					if( !file_exists($_SERVER['DOCUMENT_ROOT'] . $sThumb) )
						$sThumb = $this->siteroot.'templates/'.$GLOBALS['cSite']['sTemplate'].'/images/no_image.gif';
					$this->tpl->assign(array(
						'GAMEURL'	=> GameURL( $fav['game_id'], $fav['latin_title'] ),
						'GAMETITLE'	=> $fav['title'],
						'IMG'       => $sThumb
					));
					$n++;
					$this->tpl->parse( "USERFAVGAMES", ".dynUserFavorites" );
				}
			}
			else
			{
				// if there are no favorite games for this user yet
				$this->tpl->clear_dynamic( "dynUserFavorites" );
				$this->tpl->assign( 'USERFAVMESSAGE', $GLOBALS['cLang']['msgNoData'] );
			}
			// Friends --------------------------------------------------
			$this->tpl->define_dynamic( 'dynFriends', 'tplText' );
			$query = 'SELECT u.*, d.*, COUNT(f.game_id) AS favs FROM '.$tblusers.' AS u LEFT JOIN '
				. $tblfavs . ' AS f ON u.id=f.user_id, ' . $tbldata .
				' AS d WHERE d.param=1 AND u.id=d.value AND d.user_id='.$this->userid.' GROUP BY u.id';
			$friends = $this->db->super_query( $query, true );
			if( count($friends) > 0 )
			{
			   $this->tpl->assign( 'USERFRIENDSMESSAGE', '' );
				$even_odd = 'odd';
			   foreach( $friends as $i => $user )
			   {
			   	$this->tpl->assign( array(
				      'EVENODD'   => $even_odd,
						'USERNAME'	=>	$user['username'],
						'URL'       => $GLOBALS['cSite']['sSiteRoot'] . 'user/' . $user['username'] . '/',
						'AVATAR'    => empty($user['avatar']) ? 'default'.$user['gender'].'.gif' : $user['avatar'],
						'STATUSNAME'=> $GLOBALS['cLang']['status'.GetStatus($user['rating'])],
						'STATUS'  	=> GetStatus($user['rating']),
						'SEX'       => $user['gender'],
						'PLAYS'		=> $user['gameplays'],
						'FAVS'		=> $user['favs'],
						'LOCATION'	=> empty($user['location']) ? $GLOBALS['cLang']['sNoLocation'] : $user['location']
					));
					$this->tpl->parse( 'FRIENDSLIST', '.dynFriends' );
					$even_odd = ($even_odd == 'odd') ? 'even' : 'odd';
			   }
			}
			else
			{
			   // there ar no friends for this user
				$this->tpl->assign( 'USERFRIENDSMESSAGE', $GLOBALS['cLang']['msgNoFriends'] );
				$this->tpl->clear_dynamic( 'dynFriends' );
			}
			// Fans --------------------------------------------------
			$this->tpl->define_dynamic( 'dynUserFans', 'tplText' );
			$query = 'SELECT u.*, d.*, COUNT(f.game_id) AS favs FROM '.$tblusers.' AS u LEFT JOIN ' . $tblfavs
				. ' AS f ON u.id=f.user_id, ' . $tbldata .
				' AS d WHERE d.param=1 AND u.id=d.user_id AND d.value='.$this->userid.' GROUP BY u.id';
			$fans = $this->db->super_query( $query, true );
			if( count($fans) > 0 )
			{
				$even_odd = 'odd';
			   $this->tpl->assign( 'USERFANSMESSAGE', '' );
			   foreach( $fans as $i => $user )
			   {
			   	$this->tpl->assign( array(
				      'EVENODD'   => $even_odd,
						'USERNAME'	=>	$user['username'],
						'URL'       => $GLOBALS['cSite']['sSiteRoot'] . 'user/' . $user['username'] . '/',
						'AVATAR'    => empty($user['avatar']) ? 'default'.$user['gender'].'.gif' : $user['avatar'],
						'STATUSNAME'=> $GLOBALS['cLang']['status'.GetStatus($user['rating'])],
						'STATUS'  	=> GetStatus($user['rating']),
						'SEX'       => $user['gender'],
						'PLAYS'		=> $user['gameplays'],
						'FAVS'		=> $user['favs'],
						'LOCATION'	=> empty($user['location']) ? $GLOBALS['cLang']['sNoLocation'] : $user['location']
					));
					$this->tpl->parse( 'USERFANS', '.dynUserFans' );
					$even_odd = ($even_odd == 'odd') ? 'even' : 'odd';

			   }
			}
			else
			{
			   // there ar no friends for this user
				$this->tpl->assign( 'USERFANSMESSAGE', $GLOBALS['cLang']['msgNoFans'] );
				$this->tpl->clear_dynamic( 'dynUserFans' );
			}
		} // function show_info
		

	   //--------------------------------------------------------------------------------------------
	   // Show user's favorites
	   //--------------------------------------------------------------------------------------------
		function show_favorites()
		{
		   $tblusers = $this->dbprefix . 'users';
		   $tblfavs = $this->dbprefix . 'favorites';
		   $tbldata = $this->dbprefix . 'userdata';
			$this->tpl->define( "tplText", "user_favorites.html" );

			// My Favorites --------------------------------------------------
			$this->tpl->define_dynamic( "dynUserFavorites", "tplText" );
			$query = 'SELECT F.user_id, F.game_id, U.id, G.id, G.title, G.latin_title, G.active, G.thumbnail FROM '.$this->dbprefix.'games AS G, '.
				$tblusers.' AS U, '.$tblfavs.' AS F WHERE U.id='.$this->userid.' AND U.id=F.user_id AND G.id=F.game_id  ORDER BY G.title';
			$favorites = $this->db->super_query( $query, true );
			if( count($favorites) > 0 )
			{
				$this->tpl->assign( 'USERFAVMESSAGE', '' );
				$n = 0;
				foreach( $favorites as $i => $fav )
				{
					$sThumb = $this->siteroot.'content/thumbs/'.$fav['thumbnail'];
					if( !file_exists($_SERVER['DOCUMENT_ROOT'] . $sThumb) )
						$sThumb = $this->siteroot.'templates/'.$GLOBALS['cSite']['sTemplate'].'/images/no_image.gif';
					$this->tpl->assign(array(
						'GAMEURL'	=> GameURL( $fav['game_id'], $fav['latin_title'] ),
						'GAMETITLE'	=> $fav['title'],
						'IMG'       => $sThumb
					));
					$n++;
					$this->tpl->parse( "USERFAVGAMES", ".dynUserFavorites" );
				}
			}
			else
			{
				// if there are no favorite games for this user yet
				$this->tpl->clear_dynamic( "dynUserFavorites" );
				$this->tpl->assign( 'USERFAVMESSAGE', $GLOBALS['cLang']['msgNoData'] );
			}
			// Friends' Favorites --------------------------------------------------
			$this->tpl->define_dynamic( "dynFriendFavorites", "tplText" );
			$query = 'SELECT f.user_id, f.game_id, u.id, g.id, g.title, g.latin_title, g.active, g.thumbnail FROM ' .
				$this->dbprefix.'games AS g, ' . $tblusers.' AS u, ' . $tblfavs .
				' AS f WHERE u.id IN (SELECT value FROM ' . $tbldata . ' WHERE user_id=' .
				$this->userid.' AND param=1) AND u.id=f.user_id AND g.id=f.game_id ORDER BY g.title';
			$favorites = $this->db->super_query( $query, true );
			if( count($favorites) > 0 )
			{
				$this->tpl->assign( 'FRIENDFAVMESSAGE', '' );
				$n = 0;
				foreach( $favorites as $i => $fav )
				{
					$sThumb = $this->siteroot.'content/thumbs/'.$fav['thumbnail'];
					if( !file_exists($_SERVER['DOCUMENT_ROOT'] . $sThumb) )
						$sThumb = $this->siteroot.'templates/'.$GLOBALS['cSite']['sTemplate'].'/images/no_image.gif';
					$this->tpl->assign(array(
						'GAMEURL'	=> GameURL( $fav['game_id'], $fav['latin_title'] ),
						'GAMETITLE'	=> $fav['title'],
						'IMG'       => $sThumb
					));
					$n++;
					$this->tpl->parse( "FRIENDFAVGAMES", ".dynFriendFavorites" );
				}
			}
			else
			{
				// if there are no favorite games for this user yet
				$this->tpl->clear_dynamic( 'dynFriendFavorites' );
				$this->tpl->assign( 'FRIENDFAVMESSAGE', $GLOBALS['cLang']['msgNoData'] );
			}
		} // function show_favorites
		
	   //--------------------------------------------------------------------------------------------
	   // Handle submit
	   //--------------------------------------------------------------------------------------------
		function process_submit( $action )
		{
		   $tblusers = $this->dbprefix . 'users';
			$tblsubmit = $this->dbprefix . 'submit';
			$this->tpl->define( 'tplText', 'user_submitform.html' );
			if( $action == 'dosubmit' )
			{
				$this->tpl->assign( array(
				   'SUBMITTITLE'  => $_POST['title'],
				   'SUBMITDESC'  	=> $_POST['description'],
				   'SUBMITKEYWORDS'  => $_POST['keywords'],
			   	'TITLEERROR'  => empty($_POST['title']) ? $GLOBALS['cLang']['errAEmpty'] : '',
					'DESCERROR'  => empty($_POST['description']) ? $GLOBALS['cLang']['errAEmpty'] : '',
					'KEYWORDSERROR'  => empty($_POST['keywords']) ? $GLOBALS['cLang']['errAEmpty'] : '',
					'FILEERROR'  => $_POST[''],
					'SCREENSHOTERROR'  => $_POST[''],
					'THUMBERROR'  => $_POST[''],
				));
			}
			else
			{
			}
		} // process_submit
		
	   //--------------------------------------------------------------------------------------------
	   // List registered users
	   //--------------------------------------------------------------------------------------------
		function list_users()
		{
			$this->tpl->define( 'tplText', 'user_list.html' );
			$this->tpl->define_dynamic( 'dynUsers', 'tplText' );
		   $tblusers = $this->dbprefix . 'users';
		   $tbldata = $this->dbprefix . 'userdata';
		   $tblfavs = $this->dbprefix . 'favorites';
		   $tblsubmit = $this->dbprefix . 'submit';

			// count them first and find out which page is active
		   $count = $this->db->super_query( 'SELECT COUNT(id) as users FROM ' . $tblusers
				. ' WHERE verified=1', false );
     		$list_offset = empty($_GET['show']) ? 0 : ($_GET['show']-1)*$GLOBALS['cUser']['listSize']; 	// 0 or page*number_per_page

     		// generate the list
     		$query = 'SELECT u.*, COUNT(f.game_id) AS favs, COUNT(s.id) AS submits FROM ' . $tblusers
				. ' AS u LEFT JOIN ' . $tblfavs . ' AS f ON u.id=f.user_id LEFT JOIN ' . $tblsubmit
				. ' AS s ON u.id=s.user_id WHERE u.verified=1 GROUP BY u.id ORDER by u.username LIMIT '
				. $list_offset . ', ' . $GLOBALS['cUser']['listSize'];
         $res = $this->db->super_query( $query, true );
			$even_odd = 'odd';
			foreach( $res as $i => $user )
			{
			   $this->tpl->assign( array(
			      'EVENODD'   => $even_odd,
					'USERNAME'	=>	$user['username'],
					'URL'       => $GLOBALS['cSite']['sSiteRoot'] . 'user/' . $user['username'] . '/',
					'AVATAR'    => empty($user['avatar']) ? 'default'.$user['gender'].'.gif' : $user['avatar'],
					'STATUSNAME'=> $GLOBALS['cLang']['status'.GetStatus($user['rating'])],
					'STATUS'  	=> GetStatus($user['rating']),
					'PLAYS'		=> $user['gameplays'],
					'SEX'       => $user['gender'],
					'FAVS'		=> $user['favs'],
					'SUBMITS'	=> $user['submits'],
					'LOCATION'	=> empty($user['location']) ? $GLOBALS['cLang']['sNoLocation'] : $user['location']
				));
				$this->tpl->parse( 'USERLIST', '.dynUsers' );
				$even_odd = ($even_odd == 'odd') ? 'even' : 'odd';
			}

			// generate the pages navigation (if necessary)
			if( $count['users'] > $GLOBALS['cUser']['listSize'] )
			{
			   $this->tpl->define_dynamic( 'dynPages', 'tplText' );
				$page_count = ceil( $count['users'] / $GLOBALS['cUser']['listSize'] );
				for( $i=0; $i<$page_count; $i++ )
				{
				   $this->tpl->assign( 'PAGE', $i+1 );
					$active = ( empty($_GET['show']) && ($i==0) ) || ( $_GET['show']==$i+1 ) ? 'active' : '';
					$this->tpl->assign( 'ACTIVE', $active );
				   $this->tpl->parse( 'LISTPAGES', '.dynPages' );
				}
			}
			else
			{
			   $this->tpl->define_dynamic( 'dynPageNav', 'tplText' );
			   $this->tpl->clear_dynamic( 'dynPageNav' );
			}
		} // list_users
		
	   //--------------------------------------------------------------------------------------------
	   // Handle user settings
	   //--------------------------------------------------------------------------------------------
		function process_settings( $action )
		{
		   $tblusers = $this->dbprefix . 'users';
		   $tbldata = $this->dbprefix . 'userdata';
			$this->tpl->define( 'tplText', 'user_settings.html' );
  			$userdata = $this->db->super_query( 'SELECT * FROM ' . $tblusers . ' WHERE id=' . $this->userid . ' LIMIT 1' );
  			$this->tpl->assign( array(
  			   'USER_NAME'		=> $this->username,
  			   'USER_AVATAR' 	=> empty($userdata['avatar']) ? 'default'.$userdata['gender'].'.gif' : $userdata['avatar'],
  			   'EMAIL'        => $userdata['email'],
  			   'EMAILERROR'   => '',
  			   'LOCATION'     => $userdata['location'],
				'GENDER0'		=>	$userdata['gender'] == 0 ? 'selected' : '',
				'GENDER1'		=>	$userdata['gender'] == 1 ? 'selected' : '',
				'GENDER2'		=>	$userdata['gender'] == 2 ? 'selected' : '',
				'PASSWORDERROR'   => '',
				'PASSWORD2ERROR'  => '',
				'SUBSCRIBE'    	=> $userdata['subscribed'] == 1 ? 'checked' : '',
				'RESULTS'			=> '',
				'RESULTSNB'			=> '',
				'AVATAR_MAXSIZE'  => number_format( $GLOBALS['cUser']['maxAvatar']/1024 ),
				'AVATAR_MAXW'     => $GLOBALS['cUser']['maxAvatarW'],
				'AVATAR_MAXH'  	=> $GLOBALS['cUser']['maxAvatarH'],
				'AVATARRESULT'    => ''
			));

			if( $action == 'save' )
			{
	   		// Check entered data and process changes if everything is ok
			   $bOK = true;

				// Email
   			if( empty($_POST['email']) )
   			{
   		  		$bOK = false;
   		   	$this->tpl->assign( 'EMAILERROR', $GLOBALS['cLang']['errAEmpty'] );
				}
				elseif( !EmailValid($_POST['email']) )
				{
   		   	$bOK = false;
   		   	$this->tpl->assign( 'EMAILERROR', $GLOBALS['cLang']['errEmail'] );
				}
				else
				{
				   $duplicate = $this->db->super_query( 'SELECT id, email FROM '.$tblusers.
						' WHERE email="'.$userdata['email'].'" AND id!='.$userdata['id'].' LIMIT 1', false );
					if( !empty($duplicate) )
					{
   			   	$bOK = false;
   			   	$this->tpl->assign( 'EMAILERROR', $GLOBALS['cLang']['errEmailBusy'] );
					}
				}

				// Password
				if( $_POST['changepassword'] == 1)
				{
   				if( empty($_POST['password']) )
   				{
   		   		$bOK = false;
   		   		$this->tpl->assign( 'PASSWORDERROR', $GLOBALS['cLang']['errAEmpty'] );
					}
					elseif( !preg_match('/^[a-zA-Z0-9_-]*$/', $_POST['password']) )
					{
   		   		$bOK = false;
   		   		$this->tpl->assign( 'PASSWORDERROR', $GLOBALS['cLang']['errPassword'] );
					}
					elseif( $_POST['password'] != $_POST['password2'] )
					{
   		   		$bOK = false;
						$this->tpl->assign( "PASSWORD2ERROR", $GLOBALS['cLang']['errAPassword'] );
					}
				}

				// Check gender
				if( ($_POST['gender']!=0) && ($_POST['gender']!=1) && ($_POST['gender']!=2) )
				{
				   $bOK = false;
				}

				// Fill up the form by our data (for the case if we'll need to show the form and errors
				$this->tpl->assign( array(
					'EMAIL'			=>	$_POST['email'],
					'LOCATION'		=>	htmlspecialchars($_POST['location']),
					'GENDER0'		=>	$_POST['gender'] == 0 ? 'Selected' : '',
					'GENDER1'		=>	$_POST['gender'] == 1 ? 'Selected' : '',
					'GENDER2'		=>	$_POST['gender'] == 2 ? 'Selected' : '',
					'SUBSCRIBE'		=>	empty($_POST['subscribe']) ? '' : "checked"
				));

				// If everything's OK, submit changes to the database
				if( $bOK )
				{
				   $subscribed = empty($_POST['subscribe']) ? 0 : 1;
				   $query = 'UPDATE '.$tblusers.' SET location="'.
				   	htmlspecialchars($_POST['location']).'", gender='.$_POST['gender'].', subscribed='.$subscribed;
					if( $_POST['changepassword'] == 1 )
					   $query .= ', password="'.md5($_POST['password']).'"';
					$query .= ' WHERE id='.$this->userid.' LIMIT 1';
					$this->db->query( $query );

					// If user changed email address
					if( $_POST['email'] != $userdata['email'] )
					{
						// move the new one to the userdata table
						$this->db->query( 'INSERT INTO '.$tbldata.' VALUES('.time().', '.$userdata['id'].', 3, "'.$_POST['email'].'")' );
						// and send confirmation message to the user
						$mailtpl = new FastTemplate( 'emailtemplates' );
						$mailtpl->define( 'tplBody', 'newemail.tpl' );
						$mailtpl->assign(array(
							'SITETITLE'		=>	$cSite['sSiteTitle'],
							'SITEROOT'		=> $cSite["sSiteRoot"],
       					'SITENAME'		=>	$GLOBALS['cSite']['sURL'],
       					'NAME'			=>	$this->username,
							'CONFIRMLINK'	=>	$GLOBALS['cSite']['sURL'].'/user/newemail/?id='.$userdata['id'].'&newemail='.$_POST['email'],
							"SIGNATURE"		=>	$GLOBALS['cSite']['MailSignature']
						));
						$mailtpl->parse( 'BODY', 'tplBody' );
						$sBody = $mailtpl->GetText( 'BODY' );
						$sTo = $this->username.' <'.$_POST['email'].'>';
						mail( $sTo, $GLOBALS['cLang']['sNewMailSubj'], $sBody, "From: ".$GLOBALS['cSite']['sContactEmail'] );

						//NB. New email must be stored in the temp record in the database untill
						// next login. Once it's confirmed, the new email address must be moved to the
						// user's record. If it's not confirmed before next login, delete the temp record
						
						$this->tpl->assign( 'RESULTS', $GLOBALS['cLang']['msgChangesSaved'] );
						$this->tpl->assign( 'RESULTSNB', $GLOBALS['cLang']['msgNewEmailNote'] );
					}
					else
					{
					   $this->tpl->assign( 'RESULTS', $GLOBALS['cLang']['msgChangesSaved'] );
						$this->tpl->assign( 'RESULTSNB', '' );
					}
				}
			}
         elseif( $action == 'upload')
			{
				// Check the transferred file - its format and dimensions
				$types = array( 'image/gif', 'image/jpeg', 'image/png' );
				if( isset($_FILES['avatar']) && ($_FILES['avatar']['error']==0) )
               if( in_array($_FILES['avatar']['type'], $types) )
						if( $_FILES['avatar']['size'] < $GLOBALS['cUser']['maxAvatar'] )
						{
						   $gameinfo = getimagesize( $_FILES['avatar']['tmp_name'] );
							if( ($gameinfo[0]>0) && ($gameinfo[0]<=$GLOBALS['cUser']['maxAvatarW'])
								&& ($gameinfo[1]>0) && ($gameinfo[1]<=$GLOBALS['cUser']['maxAvatarH']) )
							{
				  				// Rename and move the file to its proper location
				  				$filename = $this->username.'_'.RandomCode(5).$_FILES['avatar']['name'];
				  				if( move_uploaded_file($_FILES['avatar']['tmp_name'], 'content/avatars/'.$filename) )
				  				{
									// Modify user's record in the database
									$this->db->query( 'UPDATE '.$tblusers.' SET avatar="'.$filename.'" WHERE id='.$this->userid.' LIMIT 1' );
									// Delete old avatar (if exists)
									if( !empty($userdata['avatar']))
										if( is_file('content/avatars/'.$userdata['avatar']) )
											unlink( 'content/avatars/'.$userdata['avatar'] );
									$this->tpl->assign( 'AVATARRESULT', $GLOBALS['cLang']['msgDone'] );
									$this->tpl->assign( 'USER_AVATAR', $filename );

								}
								else
  									$this->tpl->assign( 'AVATARRESULT', $GLOBALS['cLang']['msgDone'] );
							}
       					else  $this->tpl->assign( 'AVATARRESULT', $GLOBALS['cLang']['errDimensions'] );
               	}
						else  $this->tpl->assign( 'AVATARRESULT', $GLOBALS['cLang']['errLargeFile'] );
					else $this->tpl->assign( 'AVATARRESULT', $GLOBALS['cLang']['errAWrongFormat'] . ': ' . $_FILES['avatar']['type'] );
				else  $this->tpl->assign( 'AVATARRESULT', $GLOBALS['cLang']['errUpload'] );
			}// upload
		} // function process_settings


	} // Class
?>
