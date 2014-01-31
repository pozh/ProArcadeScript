<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ User CP configuration. Edit it manually or using admin's control panel
/
/*******************************************************************/

$cUser = array(
	'minPlayPause'	=>	'!PLAYPAUSE!', 	// if the pause between 2 clicks of a user is shorter, the gameplay will not be counted
	'minCommentPause'	=>	'!COMMENTPAUSE!', // if the pause between 2 comments of a user is shorter, the comment will not add any value to the user's rating
	'maxNameLength'=> '!MAXNAME!',   // maximum length of a username
	'listSize'     => '!PERPAGE!',   // users per page on the userlist
	'maxAvatarW'   => '!AWIDTH!',  // max width
	'maxAvatarH'   => '!AHEIGHT!',  // max height
	'maxAvatar'    => '!AFILE!',	// max filesize in bytes (100Kb)
	'daysUnverified'  => '!UNVERIFIED!',  	//  how many days should we keep the unverified users in our database
	// usernames reserved by system
	'forbidden'    => '!FORBIDDEN!',
	'ptPlay'     	=> '!PTPLAY!',    // add this poitns to the user's rating
	'ptSubmit'     => '!PTSUBMIT!',
	'ptComment'    => '!PTCOMMENT!',
	'ptRating'     => '!PTRATING!'
);
?>