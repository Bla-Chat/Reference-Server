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
			<div id="OpenCallOverlay">
			<a href="javascript:ui.chatManager.audioManager.start(true);"><img id="audioCall" class="tinyImg" src="images/Call.png" alt="Audio Call" /></a>
			<a href="javascript:ui.chatManager.videoManager.start(true);"><img id="videoCall" class="tinyImg" src="images/Video.png" alt="Video Call" /></a>
			</div>
			<div id="ExitCallOverlay">
			<a href="javascript:ui.chatManager.declineCall();"><img id="exitCall" class="tinyImg" src="images/Exit.png" alt="Exit Call" /></a>
			</div>
			<i><a onclick="enableNotification();" href='#'>notifications</a></i>
			<div id="incomingCallOverlay" class="incomingCallOverlay" align="center">
				<a href="javascript:ui.chatManager.acceptCall();"><img id="acceptCall" class="tinyImg" src="images/Call.png" alt="Accept Call" /></a>
				<a href="javascript:ui.chatManager.declineCall();"><img id="exitCall" class="tinyImg" src="images/Exit.png" alt="Decline Call" /></a>
			</div>
			<div id="outgoingCallOverlay" class="outgoingCallOverlay" align="center">
				<a href="javascript:ui.chatManager.declineCall();"><img id="cancelCall" class="tinyImg" src="images/Exit.png" alt="Cancel" /></a>
			</div>
			<div id='mediaContainer'>
				<div id="audioOverlay" class="audioOverlay" align="left">
					<audio id="aud1" autoplay="true" muted="true"></audio>
					<audio id="aud2" autoplay></audio>
					<a href="javascript:ui.chatManager.declineCall();"><img id="exitCall" class="tinyImg" src="images/Exit.png" alt="Exit Call" /></a>
				</div>
				<div id="videoOverlay" class="videoOverlay" align="left">
					<video class="otherVideo" id="vid2" autoplay ></video>
					<video class="ownVideo" id="vid1" autoplay="true" muted="true"></video>
					<a href="javascript:ui.chatManager.declineCall();"><img id="exitCall" class="tinyImg" src="images/Exit.png" alt="Exit Call" /></a>
				</div>
			</div>
			<div id='chatContainer' class='yScrollablePane'></div>
			<input name="uploadFile" type="file" size="50" maxlength="100000" accept="text/*"><BR>
			<textarea rows="3" cols="60" id='msgBox' onkeypress='handleKeyPress(event,this.form)'></textarea>
		</div>
<?PHP 
echo '
		<script type="text/javascript">window.mobilecheck = false; var serverlocation = "'.$serverAddress.'/api/"; var welcomeMessage = "'.$welcomeMessage.'";</script>
		<script src="'.$serverAddress.'/engine/emotes.js"></script>
		<script src="'.$serverAddress.'/engine/adapter.js"></script>
		<script src="'.$serverAddress.'/engine/helper.js"></script>
		<script src="'.$serverAddress.'/engine/chat.js"></script>
		<script src="'.$serverAddress.'/engine/videocall.js"></script>
		<script src="'.$serverAddress.'/engine/voicecall.js"></script>
		<script src="'.$serverAddress.'/engine/uimanager.js"></script>';
?>

	</body>
</html>
