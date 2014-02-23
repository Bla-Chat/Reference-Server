<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<link rel="shortcut icon" href="normal.png" />
<?PHP
include "api/settings.php";

echo '		<link rel="stylesheet" type="text/css" href="'.$serverAddress.'/style.css">';
?>

		<title>Bla Chat</title>
	</head>
	<body>
		<div id="topBar">Bla - chat with friends <a id="invite" href='api/invite.php'>invite a friend</a></div>
		<div id="tabBar" class="xScrollPane"><noscript>Javascript required</noscript></div>
		<div id="listPane" class="yScrollPane"><noscript>Javascript required</noscript></div>
		<div id="container">
			<audio id="notifications">
				<source src="notification.mp3" type="audio/mpeg">
				<source src="notification.ogg" type="audio/ogg">
			</audio>
			<audio id="calls">
				<source src="call.mp3" type="audio/mpeg">
				<source src="call.ogg" type="audio/ogg">
			</audio>
			<b>Peer: </b>
			<button id="btn2" onclick="ui.chatManager.videoManager.start(true)">Peer</button>
			<button id="btn3" onclick="ui.chatManager.videoManager.stop()">Get lost</button>
			<b>Babble: </b>
			<button id="btn4" onclick="ui.chatManager.audioManager.start(true)">Babble</button>
			<button id="btn5" onclick="ui.chatManager.audioManager.stop()">Get lost</button>
			<i>(Chrome only)</i>
			<div id="incomingCallOverlay" class="incomingCallOverlay" align="center">
				<button id="accept" onclick="ui.chatManager.acceptCall()">Yay</button>
				<button id="decline" onclick="ui.chatManager.declineCall()">Get lost</button>
			</div>
			<div id="outgoingCallOverlay" class="outgoingCallOverlay" align="center">
				<button id="cancel" onclick="ui.chatManager.declineCall()">Nevermind</button>
			</div>
			<div id='mediaContainer'>
				<div id="audioOverlay" class="audioOverlay" align="left">
					<audio id="aud1" autoplay="true" muted="true"></audio>
					<audio id="aud2" autoplay></audio>
				</div>
				<div id="videoOverlay" class="videoOverlay" align="left">
					<video class="otherVideo" id="vid2" autoplay ></video>
					<video class="ownVideo" id="vid1" autoplay="true" muted="true"></video>
				</div>
			</div>
			<div id='chatContainer' class='yScrollablePane'></div>
			<textarea rows="5" cols="60" id='msgBox' onkeypress='handleKeyPress(event,this.form)'></textarea>
		</div>
<?PHP 
echo '
		<script type="text/javascript">window.mobilecheck = false; var serverlocation = "'.$serverAddress.'/api/"; var welcomeMessage = "'.$welcomeMessage.'";</script>
		<script src="'.$serverAddress.'/engine/adapter.js"></script>
		<script src="'.$serverAddress.'/engine/helper.js"></script>
		<script src="'.$serverAddress.'/engine/chat.js"></script>
		<script src="'.$serverAddress.'/engine/videocall.js"></script>
		<script src="'.$serverAddress.'/engine/voicecall.js"></script>
		<script src="'.$serverAddress.'/engine/uimanager.js"></script>';
?>

	</body>
</html>
