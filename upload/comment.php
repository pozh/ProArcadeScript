<?php
/*******************************************************************
/ File version: 1.3.1
/ File description:
/ Adds the given comment to the database and return to the same page 
/ IN: comment (text), private (checkbox, 1 if is checked), 
/ IN(STOP): email - spammer if not empty
/ IN(SSPAMCHECK): timer - spammer if time()-timer < some min interval
/
/*******************************************************************/
require_once( "include/config.php" );
require_once( "include/class.Database.php" );
require_once( "include/helpers.php" );
require_once( "include/class.Log.php" );

session_start();

$db = new CDatabase( $cMain["dbUser"], $cMain["dbPassword"], $cMain["dbName"], $cMain["dbHost"], 0 );
$log = new CLog( $db );

$sIP = $_SERVER['REMOTE_ADDR'];
$res = $db->super_query( 'SELECT * FROM '.$cMain['dbPrefix'].'ban WHERE ip="'.$sIP.'" LIMIT 1' );

if( empty($res) && !empty($_POST['comment']) && empty($_REQUEST['email']) && (time()-$_REQUEST['timer'] > $cC['mindelay']) )
{
	$res = $db->super_query( "SELECT * FROM ".$cMain["dbPrefix"]."stoplist", true );
	$textOK = true;
	foreach( $res as $stoprec )
		if( substr_count($_POST['comment'], $stoprec['string']) >= $stoprec['count'] )
			$textOK = false;
	if( $textOK === true )
	{
		$sText = get_magic_quotes_gpc() ? $_POST['comment'] : mysql_escape_string( $_POST['comment'] );
		$sText = htmlspecialchars( $sText );

		// Send a copy of the comment to the admin's email
		if( !empty($_POST['private']) || ($cC["sendcopy"]==1) )
		{
			$sBody = $sText . "\n\nReferer: " . $_SERVER['HTTP_REFERER'] . "\nIP: " . $sIP;
			if( !empty($_POST['private']) )
				$sSubj = empty($_POST['game']) ? str_replace( "!URL!", $cSite["sURL"], $cLang["msgPrivateFeedback"] ) : str_replace( "!URL!", $cSite["sURL"], $cLang["msgPrivateComment"] );
			else
				$sSubj = empty($_POST['game']) ? str_replace( "!URL!", $cSite["sURL"], $cLang["msgFeedback"] ) : str_replace( "!URL!", $cSite["sURL"], $cLang["msgComment"] );
			$sFrom = $cSite['sSiteTitle'].' <'.$cSite['sAdminEmail'].'>';
			$header = "From: $sFrom\r\n X-Mailer: Content Manager - PHP/" . phpversion();
			@mail( $cSite["sAdminEmail"], $sSubj, $sBody, $header );
		}

		// Add public comment to the database
		if( empty($_POST['private']) )
		{
			$nGame = empty($_POST['game']) ? 0 : $_POST['game'];
			$nUser = empty($_SESSION['user']) ? 0 : $_SESSION['user'];
			$nPermit = ($nGame == 0) ? $cC['sitepermit'] : $cC['contentpermit'];
			$nPremod = ($nGame == 0) ? $cC['sitepremod'] : $cC['contentpremod'];
			if( (($nPermit == 1) && ($nUser > 0)) || ($nPermit == 2) )
			{
				$nActive = ( (($nPremod == 1) && ($nUser > 0)) || ($nPremod == 0) ) ? 1 : 0;
				$nDate = time();
				$db->query( "INSERT INTO ".$cMain['dbPrefix']."comments VALUES( '', $nGame, $nUser, $nDate, '$sIP', '$sText', $nActive )" );
				
				// Update user's rating (for registered only)
				if( $nUser > 0 )
				{
					$logdata = $db->super_query( 'SELECT * FROM ' . $cMain['dbPrefix'] . 'log WHERE user_id=' . $nUser . ' AND action="comment" ORDER BY time DESC LIMIT 1', false );
					if( empty($logdata) || (time()-$logdata['time']>$cUser['minCommentPause']) )
						$db->query( 'UPDATE LOW_PRIORITY ' . $cMain['dbPrefix'] . 'users SET rating=rating+' . $cUser['ptComment'] . ' WHERE id=' . $nUser . ' LIMIT 1' );
					$gameID = empty($_POST['game']) ? 0 : $_POST['game'];
					$log->write_event( 'comment', $gameID );
				}
			}
		}
	}
}
header( "Location:" . $_SERVER['HTTP_REFERER'] );

