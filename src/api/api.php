<?PHP
	include "settings.php";
	include "helpers.php";
	
	ini_set('display_errors', '1');

	class Message {
		public $type = "onError";
		public $msg = "unspecified error";
	}
	class MessageE {
		public $type = "onError";
		public $msg = "unspecified error";
		public $nick = "error";
		public $text = "";
	}
	class ChatObject {
		public $nick = "error";
		public $name = "error";
		public $time = "1900-01-01";
	}
	class ContactObject {
		public $nick = "error";
		public $name = "error";
		public $status = "0";
	}
	class ChatMessage {
		public $author = "error";
		public $authorNick = "error";
		public $time = "1900-01-01";
		public $message = "unspecified error";
	}
	
	function cleanUpDB() {
		// Remove old events.
		$query = 'DELETE FROM `events` WHERE `Timestamp` < (NOW() - INTERVAL 10 DAYS);';
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
		$query = 'DELETE FROM `clients` WHERE `Timestamp` < (NOW() - INTERVAL 240 MINUTE);'; // 4 hours offline kills a session...
		mysql_query($query);
	}
	
	$out = new Message;
	
	// Connect to DB
	$out->msg = "Cannot connect to SQL!";
	@mysql_connect($server, $username, $password) or die (json_encode($out));
	$out->msg = "Cannot select DB!";
    @mysql_select_db($database) or die (json_encode($out));
	$out->msg = "Invalid operation!";
	
	mysql_set_charset("UTF8");
	
	function authentificate($obj) {
		$query = 'SELECT `Salt` FROM `users` WHERE `Nick`="'.xjcpSecureString(LOWER($obj->user)).'";';
		$result = mysql_query($query);
    		$num = mysql_num_rows($result);
    		if ($num != 1) {
        		$returnValue = new Message;
        		$returnValue->type = "onRejected";
        		$returnValue->msg = $obj;
        		return $returnValue;
    		}
		$salt = mysql_result($result,0,"Salt");
		$pw = md5($obj->password.$salt);
		
		$query = 'SELECT `Nick` FROM `users` WHERE `Nick`="'.xjcpSecureString(LOWER($obj->user)).'" AND `Password`="'.xjcpSecureString($pw).'";';
    		$result = mysql_query($query);
    		$num = mysql_num_rows($result);
    	
    		if ($num != 1) {
        		$returnValue = new Message;
        		$returnValue->type = "onRejected";
        		$returnValue->msg = $obj;
        		return $returnValue;
    		}
		$query = 'UPDATE `clients` SET `Timestamp`=CURRENT_TIMESTAMP WHERE `Nick`="'.xjcpSecureString(LOWER($obj->user)).'" AND `ClientID`="'.xjcpSecureString($obj->id).'";';
		mysql_query($query);

		cleanUpDB();
    	return null;
	}
	
	function login ($obj) {
		$returnValue = authentificate($obj);
    	if ($returnValue == null) {
        	$returnValue = new Message;
  		
    		$query = 'UPDATE `users` SET `Status`=1 WHERE `Nick`="'.xjcpSecureString(LOWER($obj->user)).'";';
    		mysql_query($query);
    		
    		$query = 'SELECT `RealName` FROM `users` WHERE `Nick`="'.xjcpSecureString(LOWER($obj->user)).'";';
    		$result = mysql_query($query);
    		$num = mysql_num_rows($result);
    		if ($num > 0) {
    			$line = mysql_fetch_assoc($result);
    			$returnValue->msg = $line['RealName'];
    		}
    		
    		$query = 'SELECT `Nick` FROM `contacts` WHERE `Friend`="'.xjcpSecureString(LOWER($obj->user)).'";';
    		$result = mysql_query($query);
    		while ($line = mysql_fetch_assoc($result)) {
                $currentUser = $line['Nick'];
    			$query = 'INSERT INTO `events`(`Nick`, `Type`, `Message`, `Trigger`) VALUES ("'.xjcpSecureString($currentUser).'","onStatusChange","1", "'.xjcpSecureString(LOWER($obj->user)).'");';
    			mysql_query($query);
    		}
        	$returnValue->type = "onConnect";
        }
        return $returnValue;
	}
	
	function generateID ($obj) {
		$returnValue = authentificate($obj);
    	if ($returnValue == null) {
        	$returnValue = new Message;
			
			$id = "ERROR";
			$num = 10;
			while ($num > 0) { // Ensure that our id is unique
				$id = randomstring(32);
				$query = 'SELECT `IP` FROM `clients` WHERE `ClientID`="'.xjcpSecureString($id).'";';
				$result = mysql_query($query);
				$num = mysql_num_rows($result);
			}

			$ip = $_SERVER['REMOTE_ADDR'];
			$query = 'INSERT INTO `clients`(`Nick`, `ClientID`, `IP`) VALUES ("'.xjcpSecureString(LOWER($obj->user)).'","'.xjcpSecureString($id).'","'.xjcpSecureString($ip).'")';
			mysql_query($query);
        	
    		$returnValue->msg = $id;
        	$returnValue->type = "onIdRequest";
        }
        return $returnValue;
	}

	function getChats ($obj) {
		$returnValue = authentificate($obj);
		if ($returnValue == null) {
			$returnValue = new Message;
			
			$query = 'SELECT Nick FROM `conversations` WHERE `Member`="'.xjcpSecureString(LOWER($obj->user)).'";';
			$result = mysql_query($query); 
    		while ($line = mysql_fetch_assoc($result)) {
                $nick = $line['Nick'];
				$query = 'SELECT `Receiver` FROM `conversations`, `messages` WHERE `Receiver`="'.xjcpSecureString(LOWER($nick)).'";';
				$result2 = mysql_query($query);
				if (0 == mysql_num_rows($result2)) {
					$query = 'INSERT INTO `messages`(`Author`, `Receiver`, `Message`) VALUES ("watchdog", "'.xjcpSecureString(LOWER($nick)).'", "Created new conversation.");';
					mysql_query($query);
				}
			}
			
			$query = 'SELECT Nick, LocalName, MAX(`Time`) FROM `conversations`, `messages` WHERE `Receiver`=`Nick` AND `Member`="'.xjcpSecureString(LOWER($obj->user)).'" group by Nick order by MAX(`Time`) desc;';
			$result = mysql_query($query); 
    		$returnValue->type = "onGetChats";
    		$returnValue->msg = array();
    		$i = 0;
    		while ($line = mysql_fetch_assoc($result)) {
                $conversation = new ChatObject;
                $conversation->nick = $line['Nick'];
                $conversation->name = $line['LocalName'];
                $conversation->time = $line['MAX(`Time`)'];
    			$returnValue->msg[$i] = $conversation;
    			$i++;
    		}
		}
        return $returnValue;
	}

	function getContacts ($obj) {
		$returnValue = authentificate($obj);
		if ($returnValue == null) {
			$returnValue = new Message;
			$query = 'SELECT `Friend`, `RealName`, `Status` FROM `contacts`, `users` WHERE contacts.Nick="'.xjcpSecureString(LOWER($obj->user)).'" AND `Friend`=users.Nick order by `Status` desc, `RealName` asc;';
			$result = mysql_query($query);
    		$returnValue->type = "onGetContacts";
    		$returnValue->msg = array();
    		$i = 0;
    		while ($line = mysql_fetch_assoc($result)) {
                $friend = new ContactObject;
                $friend->nick = $line['Friend'];
                $friend->name = $line['RealName'];
                $friend->status = $line['Status'];
    			$returnValue->msg[$i] = $friend;
    			$i++;
    		}
		}
        return $returnValue;
	}

	function getChatHistory ($obj) {
		$returnValue = authentificate($obj);
		if ($returnValue == null) {
			$returnValue = new MessageE;
			$count = 60;
			if (isset($obj->count)) {
				$count = xjcpSecureString($obj->count);
			}
			$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString(LOWER($obj->conversation)).'" AND `Member`="'.xjcpSecureString(LOWER($obj->user)).'"';
			$result = mysql_query($query);
    		$num = mysql_num_rows($result);
    		if ($num < 1) {
				return $returnValue;
			}
			$query = 'SELECT `RealName`, `Time`, `Author`, `Message` FROM `users`, `messages` WHERE `Author`=`Nick` AND `Receiver`="'.xjcpSecureString(LOWER($obj->conversation)).'" order by Time desc limit '.xjcpSecureString($count).';';
			$result = mysql_query($query);
    		$num = mysql_num_rows($result);
    		$returnValue->type = "onGetHistory";
    		$returnValue->msg = array();
    		$returnValue->nick = $obj->conversation;
    		$i = -1;
    		while ($line = mysql_fetch_assoc($result)) {
    			$i++;
                $message = new ChatMessage;
                $message->author = $line['RealName'];
                $message->authorNick = LOWER($line['Author']);
                $message->time = $line['Time'];
                $message->message = $line['Message'];
    			$returnValue->msg[$i] = $message;
    		}
		}
        return $returnValue;
	}

	function send($obj) {
		$returnValue = authentificate($obj);
		if ($returnValue == null) {
			$returnValue = new Message;
			// Check if conversation exists
			$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString(LOWER($obj->conversation)).'" AND `Member`="'.xjcpSecureString(LOWER($obj->user)).'";';
			$result = mysql_query($query);
    		$num = mysql_num_rows($result);
    		if ($num != 1) {
    			$returnValue->type = "onError";
    			$returnValue->msg = "The requested conversation '".$obj->conversation."' does not exist!";
    			return $returnValue;
    		}
			
			$query = 'INSERT INTO `messages`(`Author`, `Receiver`, `Message`) VALUES ("'.xjcpSecureString(LOWER($obj->user)).'", "'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString($obj->message).'");';
			$result = mysql_query($query);
			
    		cleanUpDB();
			
			$query = 'SELECT `ClientID` FROM `conversations`, `clients` WHERE conversations.Nick="'.xjcpSecureString(LOWER($obj->conversation)).'" AND `Member`=clients.Nick AND NOT clients.Nick="'.xjcpSecureString(LOWER($obj->user)).'";';
    		$result = mysql_query($query);
    		while ($line = mysql_fetch_assoc($result)) {
    			$query = 'INSERT INTO `events`(`ClientID`, `Type`, `Message`, `Trigger`, `Text`) VALUES ("'.xjcpSecureString($line['ClientID']).'","onMessage","'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($obj->user)).'", "'.xjcpSecureString($obj->message).'");';
    			mysql_query($query);
    		}
    		$returnValue->type = "onMessage";
    		$returnValue->msg = "success";
		}
		return $returnValue;
	}

	function blaCall($obj) {
		$returnValue = authentificate($obj);
		if ($returnValue == null) {
			$returnValue = new Message;
			// Check if conversation exists
			$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString(LOWER($obj->conversation)).'" AND `Member`="'.xjcpSecureString(LOWER($obj->user)).'";';
			$result = mysql_query($query);
    		$num = mysql_num_rows($result);
    		if ($num != 1) {
    			$returnValue->type = "onError";
    			$returnValue->msg = "The requested conversation '".$obj->conversation."' does not exist!";
    			return $returnValue;
    		}

    		cleanUpDB();
		$query = 'SELECT `ClientID` FROM `conversations`, `clients` WHERE conversations.Nick="'.xjcpSecureString(LOWER($obj->conversation)).'" AND `Member`=clients.Nick AND NOT clients.Nick="'.xjcpSecureString(LOWER($obj->user)).'";';
    		$result = mysql_query($query);
    		while ($line = mysql_fetch_assoc($result)) {
    			$query = 'INSERT INTO `events`(`ClientID`, `Type`, `Message`, `Trigger`, `Text`) VALUES ("'.$line['ClientID'].'","onBlaCall","'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($obj->user)).'", "'.xjcpSecureString(json_encode($obj->message)).'");';
    			mysql_query($query);
    		}
    		$returnValue->type = "onMessage";
    		$returnValue->msg = "success";
		}
		return $returnValue;
	}
	
	function newConversation($obj) {
		$returnValue = authentificate($obj);
		if ($returnValue == null) {
			$returnValue = new Message;
			
			$name = explode(',', LOWER($obj->conversation));
			sort($name);
			$obj->conversation = implode(',', $name);
			
			
			// Check if conversation exists
			$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString(LOWER($obj->conversation)).'" AND `Member`="'.xjcpSecureString(LOWER($obj->user)).'";';
			$result = mysql_query($query);
    		$num = mysql_num_rows($result);
    		if ($num > 0) {
    			$returnValue->type = "onError";
    			$returnValue->msg = "The requested conversation '".$obj->conversation."' already exists!";
    			return $returnValue;
    		}
			
			if (count($name) == 2) {
				$query = 'INSERT INTO `conversations`(`Nick`, `Member`, `LocalName`) VALUES ("'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($name[0])).'", "'.xjcpSecureString($name[1]).'");';
				mysql_query($query);
			
				$query = 'INSERT INTO `conversations`(`Nick`, `Member`, `LocalName`) VALUES ("'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($name[1])).'", "'.xjcpSecureString($name[0]).'");';
				mysql_query($query);
			} else {
				$i = 0;
				while ($i < count($name)) {
					$query = 'INSERT INTO `conversations`(`Nick`, `Member`, `LocalName`) VALUES ("'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($name[$i])).'", "'.xjcpSecureString(LOWER($obj->conversation)).'");';
					mysql_query($query);
					$i++;
				}
			}
			
			$query = 'INSERT INTO `messages`(`Author`, `Receiver`, `Message`) VALUES ("watchdog", "'.xjcpSecureString(LOWER($obj->conversation)).'", "Created new conversation.");';
			$result = mysql_query($query);
			
			
			$query = 'SELECT `ClientID` FROM `clients`,`conversations` WHERE `Member`=clients.Nick AND conversations.Nick="'.xjcpSecureString(LOWER($obj->conversation)).'";';
    		$result = mysql_query($query);
    		while ($line = mysql_fetch_assoc($result)) {
    			$query = 'INSERT INTO `events`(`ClientID`, `Type`, `Message`, `Trigger`) VALUES ("'.$line['ClientID'].'","onConversation","'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($obj->user)).'");';
    			mysql_query($query);
    		}
    		$returnValue->type = "onConversation";
    		$returnValue->msg = "success";
		}
		return $returnValue;
	}
	
	function renameConversation($obj) {
		$returnValue = authentificate($obj);
		if ($returnValue == null) {
			$returnValue = new Message;
			// Check if conversation exists
			$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString(LOWER($obj->conversation)).'" AND `Member`="'.xjcpSecureString(LOWER($obj->user)).'";';
			$result = mysql_query($query);
			$num = mysql_num_rows($result);
			if ($num < 1) {
			    $returnValue->type = "onError";
			    $returnValue->msg = "The requested conversation '".$obj->conversation."' does not exists!";
			    return $returnValue;
			}
			
			$query = 'UPDATE `conversations` SET `LocalName`="'.xjcpSecureString($obj->name).'" WHERE `Nick`="'.xjcpSecureString(LOWER($obj->conversation)).'" AND `Member`="'.xjcpSecureString(LOWER($obj->user)).'"';
			mysql_query($query);
			
			$query = 'SELECT `ClientID` FROM `clients`,`conversations` WHERE `Member`=clients.Nick AND conversations.Nick="'.xjcpSecureString(LOWER($obj->conversation)).'";';
    		$result = mysql_query($query);
    		while ($line = mysql_fetch_assoc($result)) {
    			$query = 'INSERT INTO `events`(`ClientID`, `Type`, `Message`, `Trigger`) VALUES ("'.$line['ClientID'].'","onConversation","'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($obj->user)).'");';
    			mysql_query($query);
    		}
    		$returnValue->type = "onConversation";
    		$returnValue->msg = "success";
		}
		return $returnValue;
	}
	
	function addFriend($obj) {
		$returnValue = authentificate($obj);
		if ($returnValue == null) {
			$returnValue = new Message;
			// Check if conversation exists
			$query = 'SELECT `Nick` FROM `contacts` WHERE `Nick`="'.xjcpSecureString(LOWER($obj->user)).'" AND `Friend`="'.xjcpSecureString(LOWER($obj->name)).'";';
			$result = mysql_query($query);
			$num = mysql_num_rows($result);
    			if ($num > 0) {
    				$returnValue->type = "onError";
    				$returnValue->msg = "You are already friends";
    				return $returnValue;
    			}
			
			$query = 'INSERT INTO `contacts`(`Nick`, `Friend`) VALUES ("'.xjcpSecureString(LOWER($obj->user)).'", "'.xjcpSecureString(LOWER($obj->name)).'");';
			mysql_query($query);
			
			$query = 'INSERT INTO `contacts`(`Nick`, `Friend`) VALUES ("'.xjcpSecureString(LOWER($obj->name)).'", "'.xjcpSecureString(LOWER($obj->user)).'");';
			mysql_query($query);
			
    			$returnValue->type = "onConversation";
    			$returnValue->msg = "success";
		}
		return $returnValue;
	}

	function removeEvents($obj) {
		$returnValue = authentificate($obj);
		if ($returnValue == null) {
			$query = 'SELECT `ClientID` FROM `clients`,`conversations` WHERE `Member`=clients.Nick AND clients.Nick="'.xjcpSecureString(LOWER($obj->user)).'" AND conversations.Nick="'.xjcpSecureString(LOWER($obj->conversation)).'";';
    		$result = mysql_query($query);
    		while ($line = mysql_fetch_assoc($result)) {
			//$query = 'DELETE FROM `events` WHERE `ClientID`="'.$line['ClientID'].' AND `Message`="'.xjcpSecureString($obj->conversation).'";';
    			//mysql_query($query);
    			$query = 'INSERT INTO `events`(`ClientID`, `Type`, `Message`, `Trigger`) VALUES ("'.$line['ClientID'].'","onMessageHandled","'.xjcpSecureString(LOWER($obj->conversation)).'", "'.xjcpSecureString(LOWER($obj->user)).'");';
			mysql_query($query);
    		}
    		$returnValue->type = "onRemoveEvent";
    		$returnValue->msg = "success";
    		
			cleanUpDB();
		}
		return $returnValue;
	}
	
	function pollEvents($obj) {
		$returnValue = authentificate($obj);
		if ($returnValue == null) {
		
			// Check if client exists if not, insert 
			$query = 'SELECT `ClientID` FROM `clients` WHERE `ClientID`="'.xjcpSecureString($obj->id).'";';
			$result = mysql_query($query);
			// If client was kicked off for inactivity send back error.
			if (mysql_num_rows($result) < 1) {
        		$returnValue = new Message;
        		$returnValue->type = "onRejected";
        		$returnValue->msg = $obj;
        		return $returnValue;
			}
		
			$returnValue = new Message;
			$query = 'SELECT `Type`, `Message`, `Trigger`, `Text` FROM `events` WHERE `ClientID`="'.xjcpSecureString($obj->id).'";';
			$result = mysql_query($query);
			
			$i = 0;
			$returnValue->type = "onEvent";
			$returnValue->msg = array();
			while ($line = mysql_fetch_assoc($result)) {
				$event = new MessageE;
				$event->type = $line['Type'];
				$event->msg = $line['Message'];
				$event->nick = $line['Trigger'];
				$event->text = $line['Text'];
				$returnValue->msg[$i] = $event;
				$i++;
			}
			
			$query = 'DELETE FROM `events` WHERE `ClientID`="'.xjcpSecureString($obj->id).'";';
			mysql_query($query);
			
			$query = 'UPDATE `clients` SET `Timestamp`=CURRENT_TIMESTAMP WHERE `ClientID`="'.xjcpSecureString($obj->id).'";';
			mysql_query($query);
			
			cleanUpDB();
		}
		return $returnValue;
	}
	
	function setStatus($obj) {
		$returnValue = authentificate($obj);
		
		if ($returnValue == null) {
			$query = 'UPDATE `users` SET `Status`='.xjcpSecureString($obj->status).' WHERE `Nick`="'.xjcpSecureString(LOWER($obj->user)).'";';
    		mysql_query($query);
    		
			$query = 'SELECT `Nick` FROM `contacts` WHERE `Friend`="'.xjcpSecureString(LOWER($obj->user)).'";';
			$result = mysql_query($query);
			
			while ($line = mysql_fetch_assoc($result)) {
				$currentUser = $line['Nick'];
				$query = 'INSERT INTO `events`(`Nick`, `Type`, `Message`, `Trigger`) VALUES ("'.$currentUser.'","onStatusChange","'.xjcpSecureString($obj->status).'", "'.xjcpSecureString(LOWER($obj->user)).'");';
				mysql_query($query);
			}
			
			$returnValue = new Message;
			$returnValue->type = "onSetStatus";
			$returnValue->msg = "success";
		}
		
		return $returnValue;
	}
	
	function setName($obj) {
		$returnValue = authentificate($obj);
		if ($returnValue == null) {
			$returnValue = new Message;
			
			$query = 'UPDATE `users` SET `RealName`="'.xjcpSecureString($obj->name).'" WHERE `Nick`="'.xjcpSecureString(LOWER($obj->user)).'"';
			mysql_query($query);
			
			$returnValue->type = "onConversation";
			$returnValue->msg = "success";
		}
		return $returnValue;
	}
	
	function postData($obj) {
	  $returnValue = authentificate($obj);
		
	  if ($returnValue == null) {
		$conversation = xjcpSecureString(LOWER($obj->conversation));
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
					$returnValue = new Message;
					$returnValue->msg = "Unknown datatype";
			}
			$returnValue = send($obj);
		} else {
			$returnValue = new Message;
			$returnValue->msg = "Unknown datatype or upload failed!";
		}
	  }
		
	  return $returnValue;
	}
	
	function setProfileImage($obj) {
		$returnValue = authentificate($obj);
		
		if ($returnValue == null) {
		
			$target_path = "imgs/profile_".xjcpSecureString(LOWER($obj->user)).".png";
			
			if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
				$returnValue = send($obj);
			} else {
				$returnValue = new Message;
				$returnValue->msg = "Upload failed";
			}
		}
		
		return $returnValue;
	}
	
	function setGroupImage($obj) {
		$returnValue = authentificate($obj);
		
		if ($returnValue == null) {

			$query = 'SELECT `Nick` FROM `conversations` WHERE `Nick`="'.xjcpSecureString(LOWER($obj->conversation)).'" AND `Member`="'.xjcpSecureString(LOWER($obj->user)).'"';
			$result = mysql_query($query);
    		$num = mysql_num_rows($result);
    		if ($num < 1) {
        		$returnValue = new Message;
        		$returnValue->type = "onRejected";
        		$returnValue->msg = $obj;
        		return $returnValue;
			}
		
			$name = explode(",", xjcpSecureString(LOWER($obj->conversation)));
			
			if (count($name) > 2) {
				$target_path = "imgs/profile_".xjcpSecureString(LOWER($obj->conversation)).".png";
			
				if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
					$returnValue = send($obj);
				} else {
					$returnValue = new Message;
					$returnValue->msg = "Upload failed";
				}
			} else {
				$returnValue = new Message;
				$returnValue->msg = "Upload failed";
			}
		}
		
		return $returnValue;
	}
	
	$version1 = "ERROR";
	if (isset($_POST["msg"])) {
		$version1 = urldecode($_POST["msg"]);
	} else if (isset($_GET["msg"])) {
		$version1 = urldecode($_GET["msg"]);
	}
	$message = json_decode($version1);
	if ($message == null) {
		var_dump($version1);
		$out->msg = "No urldecode '".$version1."'";
	} else {
		if($message->type == "onConnect") {
			$out = login($message->msg);
		} else if ($message->type == "onGetChats") {
			$out = getChats($message->msg);
		} else if ($message->type == "onGetContacts") {
			$out = getContacts($message->msg);
		} else if ($message->type == "onGetHistory") {
			$out = getChatHistory($message->msg);
		} else if ($message->type == "onMessage") {
			$out = send($message->msg);
		} else if ($message->type == "onBlaCall") {
			$out = blaCall($message->msg);
		} else if ($message->type == "onEvent") {
			$out = pollEvents($message->msg);
		} else if ($message->type == 'onRemoveEvent') {
			$out = removeEvents($message->msg);
		} else if ($message->type == "onIdRequest") {
			$out = generateID($message->msg);
		} else if ($message->type == "onNewConversation") {
			$out = newConversation($message->msg);
		} else if ($message->type == "onRenameConversation") {
			$out = renameConversation($message->msg);
		} else if ($message->type == "onAddFriend") {
			$out = addFriend($message->msg);
		} else if ($message->type == "onSetStatus") {
			$out = setStatus($message->msg);
		} else if ($message->type == 'onData') {
			$out = postData($message->msg);
		} else if ($message->type == 'onSetProfileImage') {
			$out = setProfileImage($message->msg);
		} else if ($message->type == 'onSetGroupImage') {
			$out = setGroupImage($message->msg);
		} else if ($message->type == 'onSetName') {
			$out = setName($message->msg);
		} else {
			$out->msg = "test: ".$_GET["msg"];
		}
	}
	

	echo json_encode($out);

	mysql_close();
?>
