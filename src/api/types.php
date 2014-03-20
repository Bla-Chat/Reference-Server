<?PHP
	class OutputMessage {
		public $id = null; // The id of the client. IP bound. (However globaly unique.)
		public $events = null;
		public $onError = null;
		public $onLoginError = null;
		public $onGetChats = null;
		public $onGetContacts = null;
		public $onGetHistory = null;
		public $onMessage = null;
		public $onInjectEvent = null;
		public $onNewConversation = null;
		public $onRenameConversation = null;
		public $onAddFriend = null;
		public $onData = null;
		public $onSetProfileImage = null;
		public $onSetGroupImage = null;
	}
	class OutputMessageMini {
	}

	class Event {
		public $type = "onError";
		public $msg = "unspecified error";
		public $nick = "error";
		public $text = "";
	}

	class History {
		public $messages = array();
		public $conversation = "error";
	}

	class ChatObject {
		public $conversation = "error";
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
		public $nick = "error";
		public $time = "1900-01-01";
		public $text = "unspecified error";
	}

	function compressObject($obj) {
		$out = new OutputMessageMini;
		if ($obj->id != null) {
			$out->i = $obj->id;
		}
		if ($obj->onLoginError != null) {
			$out->onLoginError = $obj->onLoginError;
		}
		if ($obj->events != null) {
			$out->e = $obj->events;
		}
		if ($obj->onGetChats != null) {
			$out->c = $obj->onGetChats;
		}
		if ($obj->onGetContacts != null) {
			$out->k = $obj->onGetContacts;
		}
		if ($obj->onGetHistory != null) {
			$out->h = $obj->onGetHistory;
		}
		if ($obj->onMessage != null) {
			$out->m = $obj->onMessage;
		}
		if ($obj->onNewConversation != null) {
			$out->n = $obj->onNewConversation;
		}
		if ($obj->onRenameConversation != null) {
			$out->r = $obj->onRenameConversation;
		}
		if ($obj->onAddFriend != null) {
			$out->aF = $obj->onAddFriend;
		}
		if ($obj->onSetProfileImage != null) {
			$out->sP = $obj->onSetProfileImage;
		}
		if ($obj->onSetGroupImage != null) {
			$out->sG = $obj->onSetGroupImage;
		}
		if ($obj->onInjectEvent != null) {
			$out->iE = $obj->onInjectEvent;
		}
		if ($obj->onData != null) {
			$out->da = $obj->onData;
		}
		return $out;
	}
	
	function decompressObject($obj) {
		$obj->id = $obj->i;
		$obj->user = $obj->u;
		$obj->pw = $obj->p;
		$obj->getChats = $obj->c;
		$obj->getContacts = $obj->k;
		$obj->getHistory = $obj->h;
		$obj->message = $obj->m;
		$obj->removeEvent = $obj->d;
		$obj->newConversation = $obj->n;
		$obj->renameConversation = $obj->r;
		$obj->setName = $obj->sN;
		$obj->addFriend = $obj->aF;
		$obj->setStatus = $obj->sS;
		$obj->setProfileImage = $obj->sP;
		$obj->setGroupImage = $obj->sG;
		$obj->injectEvent = $obj->iE;
		$obj->data = $obj->da;
		return $obj;
	}

	function encodeObject($obj, $minify) {
		if ($minify) {
			$obj = compressObject($obj);
		}
		return json_encode($obj);
	}
	function decodeObject() {
		$minified = false;
		$preEncode = null;

		if (isset($_POST["msg"])) {
			$preEncode = urldecode($_POST["msg"]);
			$minified = false;
		} else if (isset($_GET["msg"])) {
			$preEncode = urldecode($_GET["msg"]);
			$minified = false;
		} else if (isset($_POST["m"])) {
			$preEncode = urldecode($_POST["m"]);
			$minified = true;
		} else if (isset($_GET["m"])) {
			$preEncode = urldecode($_GET["m"]);
			$minified = true;
		}
	
		$message = json_decode($version1);
		
		if ($minified) {
			$message = decompressObject($message);
		}
		$message->minified = $minified;

		return $message;
	}
?>
