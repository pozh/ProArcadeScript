<?php
/*******************************************************************
/ ProArcadeScript
/ File description:
/ Implementation of the CLog class
/
/*******************************************************************/

class CLog
{
	var $db;
	var $dbPrefix;
	var $cMaxEventInterval = 900;	// 15 minutes

//-----------------------------------------------------------------------------------------
function CLog( $_db )
{
	$this->db = $_db;
	$this->dbPrefix = $GLOBALS["cMain"]["dbPrefix"];
}

//-----------------------------------------------------------------------------------------	
function clear_log()
{
	$minTime = time() - $this->cMaxEventInterval;
	$this->db->query( "DELETE FROM ".$this->dbPrefix."log WHERE time<$minTime" );
}
	
//-----------------------------------------------------------------------------------------
function write_event( $sEvent, $gameID = 0 )
{
	$user = empty($_SESSION['user']) ? 0 : $_SESSION['user'];
	$ip = $_SERVER['REMOTE_ADDR'];
	$time = time();
	$str = mysql_escape_string($sEvent);
	$query = "INSERT INTO ".$this->dbPrefix."log (user_id, ip, time, action, game_id) VALUES($user, '$ip', $time, '$str', $gameID)";
	$this->db->query( $query );
}

}//class


?>