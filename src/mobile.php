<!DOCTYPE html>
<html>
<head>
<meta name='viewport' content='initial-scale=1.0, user-scalable=no'>
<meta charset="UTF-8">
<title>Hangout</title>
</head>
<body>
	<div id="topBar">Hangout - chat with friends</div>
	<div id="tabBar" class="xScrollPane"><noscript>Javascript required</noscript></div>
	<div id="listPane" class="yScrollPane"><noscript>Javascript required</noscript></div>
	<div id="container"><div id='mediaContainer'></div><div id='chatContainer' class='yScrollablePane'></div><textarea rows="1" cols="80" id='msgBox' onkeypress='handleKeyPress(event,this.form)' /></textarea><a href='javascript:ui.chatManager.sendMsg(); class='button'><div class='SendButton'>GO</div></a></div>
</body>
<?PHP 
include "api/settings.php";
echo '
<script type="text/javascript">window.mobilecheck = false; var serverlocation = "'.$serverAddress.'/api/";</script>
<script src="'.$serverAddress.'/engine/adapter.js"></script>
<script src="'.$serverAddress.'/engine/helper.js"></script>
<script src="'.$serverAddress.'/engine/chat.js"></script>
<script src="'.$serverAddress.'/engine/videocall.js"></script>
<script src="'.$serverAddress.'/engine/voicecall.js"></script>
<script src="'.$serverAddress.'/engine/uimanager.js"></script>
<link rel="stylesheet" type="text/css" href="'.$serverAddress.'/mobile.css">';
?>
</html>
