<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ do logout
/
/*******************************************************************/
session_start();

unset( $_SESSION["admin"] );
header ("Location:index.php");
	
?>