<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Bla chat</title>
</head>
<body>
	<div id="topBar">Bla - chat with friends</div>
	<div id="tabBar" class="xScrollPane"><noscript>Javascript required</noscript></div>
	<div id="listPane" class="yScrollPane"><noscript>Javascript required</noscript></div>
	<div id="container"><div id='mediaContainer'></div><div id='chatContainer' class='yScrollablePane'></div><textarea rows="5" cols="60" id='msgBox' onkeypress='handleKeyPress(event,this.form)' /></textarea></div>
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
<link rel="stylesheet" type="text/css" href="'.$serverAddress.'/style.css">';
?>
</html>
