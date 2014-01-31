//******************************************************************
// ProArcadeScript 
// File description: Javascript helper functionality
// 
//******************************************************************
	
var xmlHttp = false;
/*@cc_on @*/
/*@if (@_jscript_version >= 5)
try {
	xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
} catch (e) {
	try {
		xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
	} catch (e2) {
		xmlHttp = false;
	}
}
@end @*/

if (!xmlHttp && typeof XMLHttpRequest != "undefined") 
{
	xmlHttp = new XMLHttpRequest();
}

function updateGameRate() 
{
	if (xmlHttp.readyState == 4) 
	{
		var response = xmlHttp.responseText;
		alert( response );
	}
}	

function rateGame( siteroot, gameid, rate )
{
	var url = siteroot + "rategame.php?id=" + gameid + "&rate=" + rate;
	xmlHttp.open("GET", url, true);
	xmlHttp.onreadystatechange = updateGameRate;
	xmlHttp.send(null);
}
