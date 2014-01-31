<?php
/*******************************************************************
/ ProArcadeScript version: 1.3
/ File description:
/ shows the code to publish current game on a blog or website
/
/ © 2007-2008, ProArcadeScript. All rights reserved.
/*******************************************************************/

// find out the name of the script which calles the block
// If it's not game page, cancel block
$sScript = substr(strrchr($_SERVER['PHP_SELF'], "/"), 1);
if( $sScript != "game.php" )
	return;

$tpl->define( 'tpl'.$id, 'block_default_center.html' );

// define the code
$pubcode = '';
if( !empty($gameres) )
{
	$ext = substr( strrchr($gameres['file'], '.'), 1);
	// if this is an embedded game with html,
	// just copy the html code for default case in the switch below.
	if( stristr($gameres['file'], "<") && stristr($gameres['file'], "</") )
	{
	   $pubfile  = $gameres['file'];
		$ext = 'embedded_html';
	}
	else
		$pubfile = (substr($gameres['file'], 0, 5) == 'http:') ? $gameres['file'] : $cSite['sURL'].$cSite['sSiteRoot'].'content/swf/'.$gameres['file'];
	
	switch( $ext )
	{
	   case 'swf':
	      $pubcode = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab"
width="' . $gameres['width'] . '" height="' . $gameres['height'] . '">
<param name="movie" value="' . $pubfile . '" />
<param name="quality" value="high" /><embed src="' . $pubfile . '" quality="high"
pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"
width="' . $gameres['width'] . '" height="' . $gameres['height'] . '"></embed></object></object>
';
	      break;
		case 'dcr':
			$pubcode = '<object classid="clsid:166B1BCA-3F9C-11CF-8075-444553540000"
codebase="http://download.macromedia.com/pub/shockwave/cabs/director/sw.cab"
width="' . $gameres['width'] . '" height="' . $gameres['height'] . '">
<param name="src" value="' . $pubfile . '" />
<embed src="' . $pubfile . '" pluginspage="http://www.macromedia.com/shockwave/download/" width="' . $gameres['width'] . '" height="' . $gameres['height'] . '">
</embed></object>
';
		   break;
		case 'ccn':
		   $pubcode = '<OBJECT ID="Vitalize1" WIDTH=' . $gameres['width'] . ' HEIGHT=' . $gameres['height'] . '
CLASSID="CLSID:EB6D7E70-AAA9-40D9-BA05-F214089F2275" CODEBASE="http://www.clickteam.com/vitalize3/vitalize.cab#Version=3,5,119,0">
<PARAM NAME="URL" VALUE="' . $pubfile . '">
<PARAM NAME="TaskPriority" VALUE="50">
<PARAM NAME="BackColor" VALUE="0,0,0">
<PARAM NAME="ProgressBar" VALUE="53,181,222,5">
<PARAM NAME="ProgressBarColor" VALUE="255,0,0">
<PARAM NAME="ProgressBarBorderColor" VALUE="255,255,0">
<PARAM NAME="YourParam" VALUE="ValueOfYourParam">
<EMBED type="application/x-cnc" width=' . $gameres['width'] . ' height=' . $gameres['height'] . '
Pluginspage="http://www.clickteam.com/vitalize3/plugin.html"
CheckVersion=3,5,119,0
TaskPriority=50
BackColor=0,0,0
ProgressBar=53,181,222,5
ProgressBarColor=255,0,0
ProgressBarBorderColor=128,0,0
YourParam="ValueOfYourParam"
src="' . $pubfile . '">
</EMBED>
</OBJECT>
';
		   break;
      case 'embedded_html':
         $pubcode = htmlspecialchars( $pubfile );
         // we already have $pubcode.
         break;
	   default:
			$pubcode = '';
			break;
	}
}
$landingtext = '<a href="'.$cSite['sURL'].'">' . str_replace('!SITENAME!', $cSite['sSiteTitle'], $cLang['sLanding']) . '</a>
';
$tpl->assign( array(
	'BLOCKID'		=>	$id,
	'BLOCKTITLE'	=> $cBlock['title'],
	'BLOCKTEXT'    => '<textarea onclick="this.select();" class="code">'.$pubcode.'<br />'.$landingtext.'</textarea>'
));

$tpl->parse( $id, 'tpl'.$id );
?>