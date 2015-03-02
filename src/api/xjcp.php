<?PHP

	ini_set('display_errors', '1');
	
	require_once "helpers.php";
	require_once "settings.php";
	require_once "types.php";
	
	$message = decodeObject();
	$minify = $message->minified;

	$out = new OutputMessage;
			
	// Connect to DB
	$out->onError = "Cannot connect to SQL!";
	@mysql_connect($server, $username, $password) or die (encodeObject($out, $minify));
	$out->onError = "Cannot select DB!";
	@mysql_select_db($database) or die (encodeObject($out, $minify));
	$out->onError = null;
	
	mysql_set_charset("UTF8");
	
	function send($user, $obj) {
		// Check if conversation exists
		$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString($obj->conversation).'" AND `Member`="'.xjcpSecureNick($user).'";';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num != 1) {
			return "Conversation '".$obj->conversation."' does not exist!";
		}

		$query = 'SELECT `RealName` FROM `users` WHERE `Nick`="'.xjcpSecureString($user).'";';
		$result = mysql_query($query);
		$author = "bla";
		if (mysql_num_rows($result) != 0) {
			$line = mysql_fetch_assoc($result);
			$author = $line['RealName'];
		} else {
			return "Username error";
		}

		$query = 'INSERT INTO `messages`(`Author`, `Receiver`, `Message`) VALUES ("'.xjcpSecureNick($user).'", "'.xjcpSecureString($obj->conversation).'", "'.xjcpSecureString($obj->message).'");';
		$result = mysql_query($query);
		$query = 'SELECT `ClientID` FROM `conversations`, `clients` WHERE conversations.Nick="'.xjcpSecureString($obj->conversation).'" AND `Member`=clients.Nick;';
		$result = mysql_query($query);
		while ($line = mysql_fetch_assoc($result)) {
			$query = 'INSERT INTO `events`(`ClientID`, `Type`, `Message`, `Trigger`, `Text`, `Author`) VALUES ("'.xjcpSecureString($line['ClientID']).'","onMessage","'.xjcpSecureString($obj->conversation).'", "'.xjcpSecureNick($user).'", "'.xjcpSecureString($obj->message).'", "'.xjcpSecureString($author).'");';
			mysql_query($query);
		}
		return "Success";
	}

	function cleanUpDB() {
		// Remove old events.
		$query = 'DELETE FROM `events` WHERE `Timestamp` < (NOW() - INTERVAL 10 DAY);';
		mysql_query($query);
		
		// Set users offline
		$query = 'SELECT users.Nick FROM `users` WHERE NOT `Status`=0;';
		$result = mysql_query($query);
		while ($lines = mysql_fetch_array($result)) {
			$query = 'SELECT Nick, ClientID FROM `clients` WHERE `Nick`="'.xjcpSecureNick($lines[0]).'" AND `Timestamp` > (NOW() - INTERVAL 10 MINUTE);';
			$res = mysql_query($query);
			
			if (mysql_num_rows($res) == 0) {
				setStatus($lines[0], 0);
			}
		}

		// Remove unused client ids.
		$query = 'DELETE FROM `clients` WHERE `Timestamp` < (NOW() - INTERVAL 100 DAY);';
		mysql_query($query);
	}
		
	function authentificate($id) {
		if ($id == null || $id == "") return null;
		$query = 'SELECT `IP` FROM `clients` WHERE `ClientID`="'.xjcpSecureString($id).'";';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num == 1) {
			$line = mysql_fetch_assoc($result);
			// stupid idea for mobile devices...
			//$ip = $_SERVER['REMOTE_ADDR'];
			//if ($line["IP"] == $ip) {
			//	return $id;
			//}
			return $id;
		}
		return null;
	}
	
	function generateID ($user, $minify) {
		$id = "ERROR";
		$num = 10;
		while ($num > 0) { // Ensure that our id is unique
			$id = randomstring(32);
			if ($minify) {
				$id = randomstring(8);
			}
			$query = 'SELECT `IP` FROM `clients` WHERE `ClientID`="'.xjcpSecureString($id).'";';
			$result = mysql_query($query);
			$num = mysql_num_rows($result);
		}

		$ip = $_SERVER['REMOTE_ADDR'];
		$query = 'INSERT INTO `clients`(`Nick`, `ClientID`, `IP`) VALUES ("'.xjcpSecureNick($user).'","'.xjcpSecureString($id).'","'.xjcpSecureString($ip).'")';
		mysql_query($query);
		return $id;
	}
	
	function login ($user, $pw, $minify) {
		$query = 'SELECT `Salt` FROM `users` WHERE `Nick`="'.xjcpSecureNick($user).'";';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num != 1) {
			return null;
		}
		$salt = mysql_result($result,0,"Salt");
		$md5PW = md5($pw.$salt);
		$query = 'SELECT `Nick` FROM `users` WHERE `Nick`="'.xjcpSecureNick($user).'" AND `Password`="'.xjcpSecureString($md5PW).'";';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num != 1) {
			return null;
		}
		$id = generateID($user, $minify);
		$query = 'UPDATE `clients` SET `Timestamp`=CURRENT_TIMESTAMP WHERE `ClientID`="'.xjcpSecureString($id).'";';
		mysql_query($query);
	
		$query = 'UPDATE `users` SET `Status`=1 WHERE `Nick`="'.xjcpSecureNick($user).'";';
		mysql_query($query);
    	
		$query = 'SELECT `Nick` FROM `contacts` WHERE `Friend`="'.xjcpSecureNick($user).'";';
		$result = mysql_query($query);
		while ($line = mysql_fetch_assoc($result)) {
			$currentUser = $line['Nick'];
			$query = 'INSERT INTO `events`(`Nick`, `Type`, `Message`, `Trigger`) VALUES ("'.xjcpSecureString($currentUser).'","onStatusChange","1", "'.xjcpSecureNick($user).'");';
			mysql_query($query);
		}
		return $id;
	}
		
	function getChats ($user) {
		$returnValue = array();
		$query = 'SELECT Nick, LocalName, MAX(`Time`) FROM `conversations`, `messages` WHERE `Receiver`=`Nick` AND `Member`="'.xjcpSecureNick($user).'" group by Nick order by MAX(`Time`) desc;';
		$result = mysql_query($query);
		$i = 0;
		while ($line = mysql_fetch_assoc($result)) {
			$conversation = new ChatObject;
			$conversation->conversation = $line['Nick'];
			$conversation->name = $line['LocalName'];
			$conversation->time = $line['MAX(`Time`)'];
			$returnValue[$i] = $conversation;
			$i++;
		}
		return $returnValue;
	}

	function getContacts ($user) {
		$returnValue = array();
		$query = 'SELECT `Friend`, `RealName`, `Status` FROM `contacts`, `users` WHERE contacts.Nick="'.xjcpSecureNick($user).'" AND `Friend`=users.Nick order by `Status` desc, `RealName` asc;';
		$result = mysql_query($query);
		$i = 0;
		while ($line = mysql_fetch_assoc($result)) {
			$friend = new ContactObject;
			$friend->nick = $line['Friend'];
			$friend->name = $line['RealName'];
			$friend->status = $line['Status'];
			$returnValue[$i] = $friend;
			$i++;
		}
		return $returnValue;
	}

	function getChatHistory ($user, $obj) {
		$returnValue = new History;
		$returnValue->conversation = $obj->conversation;

		$count = 60;
		if (isset($obj->count)) {
			$count = mysql_real_escape_string($obj->count);
		}
		$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString($obj->conversation).'" AND `Member`="'.xjcpSecureNick($user).'"';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num < 1) {
			return $returnValue;
		}
		$query = 'SELECT `RealName`, `Time`, `Author`, `Message` FROM `users`, `messages` WHERE `Author`=`Nick` AND `Receiver`="'.xjcpSecureString($obj->conversation).'" order by Time desc limit '.xjcpSecureString($count).';';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		$i = -1;
		while ($line = mysql_fetch_assoc($result)) {
			$i++;
			$message = new ChatMessage;
			$message->author = $line['RealName'];
			$message->nick = $line['Author'];
			$message->time = $line['Time'];
			$message->text = $line['Message'];
			$returnValue->messages[$i] = $message;
		}
		return $returnValue;
	}

	function injectEvent($user, $obj) {
		// Check if conversation exists
		$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString($obj->conversation).'" AND `Member`="'.xjcpSecureNick($user).'";';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num != 1) {
			return "Conversation '".$obj->conversation."' does not exist!";
		}
		$query = 'SELECT `ClientID` FROM `conversations`, `clients` WHERE conversations.Nick="'.xjcpSecureString($obj->conversation).'" AND `Member`=clients.Nick AND NOT clients.Nick="'.xjcpSecureNick($user).'";';
		$result = mysql_query($query);
		while ($line = mysql_fetch_assoc($result)) {
			$query = 'INSERT INTO `events`(`ClientID`, `Type`, `Message`, `Trigger`, `Text`) VALUES ("'.$line['ClientID'].'","'.xjcpSecureString($obj->type).'","'.xjcpSecureString($obj->conversation).'", "'.xjcpSecureNick($user).'", "'.xjcpSecureString(json_encode($obj->message)).'");';
			mysql_query($query);
		}
		return "Success";
	}
	
	// TODO only allow conversation ids with valid participants and user one participant
	function newConversation($user, $obj) {
		if (substr( $string_n, 0, 1 ) === "#") {
			# i think there is nothing to do here
			$name = array();
			$name[0] = $obj->user;
		} else {
			$name = explode(",", xjcpSecureString($obj->conversation));
			sort($name);
			//$obj->conversation = implode(",", $name);
		}
		$x = 0;
		$hit = false;
		while ($x < count($name)) {
			if ($name[$x] == $user) {
				$hit = true;
			}
			$x++;
		}
		if ($hit == false) {
			return "You must be part of the conversation";
		}
		
		// Check if conversation exists
		$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString($obj->conversation).'" AND `Member`="'.xjcpSecureNick($user).'";';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num > 0) {
			return "Conversation '".$obj->conversation."' already exists!";
		}
		
		if (count($name) == 2) {
			$query = 'INSERT INTO `conversations`(`Nick`, `Member`, `LocalName`) VALUES ("'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($name[0])).'", "'.xjcpSecureString($obj->conversation).'");';
			mysql_query($query);
		
			$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString(LOWER($obj->conversation)).'" AND `Member`="'.xjcpSecureString("watchdog").'";';
			$result = mysql_query($query);
    		$num = mysql_num_rows($result);
    		if ($num < 1) {
    			$query = 'INSERT INTO `conversations`(`Nick`, `Member`, `LocalName`) VALUES ("'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString("watchdog").'", "'.xjcpSecureString($obj->conversation).'");';
				mysql_query($query);
    		}
		} else if (count($name) == 2) {
			$query = 'INSERT INTO `conversations`(`Nick`, `Member`, `LocalName`) VALUES ("'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($name[0])).'", "'.xjcpSecureString($name[1]).'");';
			mysql_query($query);
		
			$query = 'INSERT INTO `conversations`(`Nick`, `Member`, `LocalName`) VALUES ("'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($name[1])).'", "'.xjcpSecureString($name[0]).'");';
			mysql_query($query);

			$query = 'INSERT INTO `conversations`(`Nick`, `Member`, `LocalName`) VALUES ("'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString("watchdog").'", "'.xjcpSecureString($obj->conversation).'");';
			mysql_query($query);
		} else {
			$i = 0;
			while ($i < count($name)) {
				$query = 'INSERT INTO `conversations`(`Nick`, `Member`, `LocalName`) VALUES ("'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($name[$i])).'", "'.xjcpSecureString(LOWER($obj->conversation)).'");';
				mysql_query($query);
				$i++;
			}
			$query = 'INSERT INTO `conversations`(`Nick`, `Member`, `LocalName`) VALUES ("'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString("watchdog").'", "'.xjcpSecureString($obj->conversation).'");';
			mysql_query($query);
		}


		$query = 'INSERT INTO `messages`(`Author`, `Receiver`, `Message`) VALUES ("watchdog", "'.xjcpSecureString(LOWER($obj->conversation)).'", "Created new conversation.");';
		$result = mysql_query($query);

		$query = 'SELECT `ClientID` FROM `clients`,`conversations` WHERE `Member`=clients.Nick AND conversations.Nick="'.xjcpSecureString($obj->conversation).'";';
		$result = mysql_query($query);
		while ($line = mysql_fetch_assoc($result)) {
			$query = 'INSERT INTO `events`(`ClientID`, `Type`, `Message`, `Trigger`) VALUES ("'.$line['ClientID'].'","onConversation","'.xjcpSecureString($obj->conversation).'", "'.xjcpSecureNick($user).'");';
			mysql_query($query);
		}
		return "Success";
	}
	
	function renameConversation($user, $obj) {
		// Check if conversation exists
		$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString($obj->conversation).'" AND `Member`="'.xjcpSecureNick($user).'";';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num < 1) {
		    return "Conversation '".$obj->conversation."' does not exist!";
		}
		$query = 'UPDATE `conversations` SET `LocalName`="'.xjcpSecureString($obj->name).'" WHERE `Nick`="'.xjcpSecureString($obj->conversation).'" AND `Member`="'.xjcpSecureNick($user).'"';
		mysql_query($query);
		$query = 'SELECT `ClientID` FROM `clients`,`conversations` WHERE `Member`=clients.Nick AND conversations.Nick="'.xjcpSecureString($obj->conversation).'";';
		$result = mysql_query($query);
		while ($line = mysql_fetch_assoc($result)) {
			$query = 'INSERT INTO `events`(`ClientID`, `Type`, `Message`, `Trigger`) VALUES ("'.$line['ClientID'].'","onConversation","'.xjcpSecureString($obj->conversation).'", "'.xjcpSecureNick($user).'");';
			mysql_query($query);
		}
		return "Success";
	}
	
	function addFriend($user, $obj) {
		// Check if friendship exists
		$query = 'SELECT `Nick` FROM `users` WHERE `Nick`="'.xjcpSecureNick($obj).'";';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num == 0) {
			return "The nick does not exist";
		}
		
		// Check if friendship exists
		$query = 'SELECT `Nick` FROM `contacts` WHERE `Nick`="'.xjcpSecureNick($user).'" AND `Friend`="'.xjcpSecureNick($obj).'";';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num > 0) {
    			return "You are already friends";
		}
		$query = 'INSERT INTO `contacts`(`Nick`, `Friend`) VALUES ("'.xjcpSecureNick($user).'", "'.xjcpSecureNick($obj).'");';
		mysql_query($query);
		$query = 'INSERT INTO `contacts`(`Nick`, `Friend`) VALUES ("'.xjcpSecureNick($obj).'", "'.xjcpSecureNick($user).'");';
		mysql_query($query);
		return "Success";
	}

	function removeEvents($user, $obj) {
		$query = 'SELECT `ClientID` FROM `clients`,`conversations` WHERE `Member`=clients.Nick AND clients.Nick="'.xjcpSecureNick($user).'" AND conversations.Nick="'.xjcpSecureString($obj->conversation).'";';
		$result = mysql_query($query);
		while ($line = mysql_fetch_assoc($result)) {
			//$query = 'DELETE FROM `events` WHERE `ClientID`="'.$line['ClientID'].' AND `Message`="'.xjcpSecureString($obj->conversation).'";';
			//mysql_query($query);
			$query = 'INSERT INTO `events`(`ClientID`, `Type`, `Message`, `Trigger`) VALUES ("'.$line['ClientID'].'","onMessageHandled","'.xjcpSecureString($obj->conversation).'", "'.xjcpSecureNick($user).'");';
			mysql_query($query);
		}
	}
	
	function pollEvents($id) {
		$returnValue = array();
		$query = 'SELECT `Type`, `Timestamp`, `Author`, `Message`, `Trigger`, `Text` FROM `events` WHERE `ClientID`="'.xjcpSecureString($id).'";';
		$result = mysql_query($query);
		$i = 0;
		while ($line = mysql_fetch_assoc($result)) {
			$event = new EventXJCP;
			$event->type = $line['Type'];
			$event->msg = $line['Message'];
			$event->nick = $line['Trigger'];
			$event->text = $line['Text'];
			$event->time = $line['Timestamp'];
			$event->author = $line['Author'];
			$returnValue[$i] = $event;
			$i++;
		}
		$query = 'DELETE FROM `events` WHERE `ClientID`="'.xjcpSecureString($id).'";';
		mysql_query($query);
		$query = 'UPDATE `clients` SET `Timestamp`=CURRENT_TIMESTAMP WHERE `ClientID`="'.xjcpSecureString($id).'";';
		mysql_query($query);
		return $returnValue;
	}
	
	function setStatus($user, $obj) {
		$query = 'UPDATE `users` SET `Status`='.xjcpSecureString($obj).' WHERE `Nick`="'.xjcpSecureNick($user).'";';
		mysql_query($query);
		$query = 'SELECT `Nick` FROM `contacts` WHERE `Friend`="'.xjcpSecureNick($user).'";';
		$result = mysql_query($query);
		while ($line = mysql_fetch_assoc($result)) {
			$currentUser = $line['Nick'];
			$query = 'INSERT INTO `events`(`Nick`, `Type`, `Message`, `Trigger`) VALUES ("'.$currentUser.'","onStatusChange","'.xjcpSecureString($obj).'", "'.xjcpSecureNick($user).'");';
			mysql_query($query);
		}
	}
	
	function setName($user, $obj) {
		$query = 'UPDATE `users` SET `RealName`="'.xjcpSecureString($obj).'" WHERE `Nick`="'.xjcpSecureNick($user).'"';
		mysql_query($query);
	}
	
	function postData($user, $obj) {
		$conversation = xjcpSecureString($obj->conversation);
		$target_path = "data/".date("Y-m-d_H-s",time())."_";
		$target_path = $target_path . basename( $_FILES['uploadedfile']['name']);
		// Only allow specific line endings.
		if((endsWith($target_path, ".png") || endsWith($target_path, ".jpg")
		  || endsWith($target_path, ".avi") || endsWith($target_path, ".mp4")
		  || endsWith($target_path, ".txt") || endsWith($target_path, ".zip")
		  || endsWith($target_path, ".java") || endsWith($target_path, ".rb")
		  || endsWith($target_path, ".js") || endsWith($target_path, ".c")
		  || endsWith($target_path, ".cpp") || endsWith($target_path, ".h")
		  || endsWith($target_path, ".obj") || endsWith($target_path, ".blend")
		  || endsWith($target_path, ".fsh") || endsWith($target_path, ".vsh"))
		  && move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
			if (endsWith($target_path, ".png") || endsWith($target_path, ".jpg")) {
				$obj->message = "#image https://www.ssl-id.de/hangout.f-online.net/api/".$target_path;
			} else if (endsWith($target_path, ".avi") || endsWith($target_path, ".mp4")) {
				$obj->message = "#video https://www.ssl-id.de/hangout.f-online.net/api/".$target_path;
			} else if (endsWith($target_path, ".txt") || endsWith($target_path, ".zip")
			  || endsWith($target_path, ".java") || endsWith($target_path, ".rb")
			  || endsWith($target_path, ".js") || endsWith($target_path, ".c")
			  || endsWith($target_path, ".cpp") || endsWith($target_path, ".h")
			  || endsWith($target_path, ".obj") || endsWith($target_path, ".blend")
			  || endsWith($target_path, ".fsh") || endsWith($target_path, ".vsh")) {
				$obj->message = "#file https://www.ssl-id.de/hangout.f-online.net/api/".$target_path;
			} else {
				return "Unknown datatype";
			}
			return send($user, $obj);
		} else {
			return "Upload failed or filetype not allowed.";
		}
	}
	
	function setProfileImage($user) {
		$target_path = "imgs/profile_".xjcpSecureNick($user).".png";	
		if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
			//return send($user, $obj);
		} else {
			return "Upload failed";
		}
		return "Success";
	}
	
	function setGroupImage($user, $obj) {
		$returnValue = null;
		$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString($obj->conversation).'" AND `Member`="'.xjcpSecureNick($user).'"';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num < 1) {
			return "Upload failed";
		}
		$name = explode(",", xjcpSecureString($obj->conversation));	
		if (count($name) > 2) {
			$target_path = "imgs/profile_".xjcpSecureString($obj->conversation).".png";
		
			if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
				//$returnValue = send($obj);
			} else {
				return "Upload failed";
			}
		} else {
			return "Upload failed";
		}
		return "Success";
	}

	function getUser($id) {
		$query = 'SELECT `Nick`, `IP` FROM `clients` WHERE `ClientID`="'.xjcpSecureString($id).'";';
		$result = mysql_query($query);
		$num = mysql_num_rows($result);
		if ($num == 1) {
			$line = mysql_fetch_assoc($result);
			// Bad idea on mobile devices...
			//$ip = $_SERVER['REMOTE_ADDR'];
			//if ($line['IP'] == $ip) {
			//	return $line['Nick'];
			//}
			return $line['Nick'];
		}
		return null;
	}
	if ($message == null) {
		//var_dump($version1);
		$out->onError = "Invalid msg or m tag";
	} else if ((authentificate($message->id) == null)
		&& (($out->id = login($message->user, $message->pw, $minify)) == null)){
		$out->onLoginError = "Cannot perform login, retry!";
	} else {
		if (isset($out->id)) {
			$message->id = $out->id;
		}
		$user = getUser($message->id);

		// Core xjcp features.
		if (isset($message->getChats)) {
			$out->onGetChats = getChats($user);
		}
		if (isset($message->getContacts)) {
			$out->onGetContacts = getContacts($user);
		}
		if ($message->getHistory != null) {
			$out->onGetHistory = getChatHistory($user, $message->getHistory);
		}
		if ($message->message != null) {
			$out->onMessage = send($user, $message->message);
		}
		if ($message->removeEvent != null) {
			removeEvents($user, $message->removeEvent);
		}
		if ($message->newConversation != null) {
			$out->onNewConversation = newConversation($user, $message->newConversation);
		}
		if ($message->renameConversation != null) {
			$out->onRenameConversation = renameConversation($user, $message->renameConversation);
		}
		if ($message->setName != null) {
			setName($user, $message->setName);
		}
		if ($message->addFriend != null) {
			$out->onAddFriend = addFriend($user, $message->addFriend);
		}
		if ($message->setStatus != null) {
			setStatus($user, $message->setStatus);
		}
		if ($message->setProfileImage != null) {
			$out->onSetProfileImage = setProfileImage($user, $message->setProfileImage);
		}
		if ($message->setGroupImage != null) {
			$out->onSetGroupImage = setGroupImage($user, $message->setGroupImage);
		}

		// Optional features.
		if ($message->data != null) {
			$out->onData = postData($user, $message->data);
		}
		if ($message->injectEvent != null) {
			$out->onInjectEvent = injectEvent($user, $message->injectEvent);
		}
		
		// Events must be last so they are triggered in one run.
		// Events are always requested.
		$out->events = pollEvents($message->id);
	}
	
	cleanUpDB();
	
	mysql_close();
	
	echo encodeObject($out, $minify);
?>
