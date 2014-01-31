<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ User CP configuration. Edit it manually or using admin's control panel
/
/*******************************************************************/

$cUser = array(
	'minPlayPause'	=>	'60', 	// if the pause between 2 clicks of a user is shorter, the gameplay will not be counted
	'minCommentPause'	=>	'45', // if the pause between 2 comments of a user is shorter, the comment will not add any value to the user's rating
	'maxNameLength'=> '25',   // maximum length of a username
	'listSize'     => '10',   // users per page on the userlist
	'maxAvatarW'   => '100',  // max width
	'maxAvatarH'   => '100',  // max height
	'maxAvatar'    => '102400',	// max filesize in bytes (100Kb)
	'daysUnverified'  => '5',  	//  how many days should we keep the unverified users in our database
	// usernames reserved by system
	'forbidden'    => 'admin, list',
	'ptPlay'     	=> '1',    // add this poitns to the user's rating
	'ptSubmit'     => '30',
	'ptComment'    => '5',
	'ptRating'     => '2'
);
?>