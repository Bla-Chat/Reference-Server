function Chat(nick, name) {
	var that = this;
	this.nick = nick;
	this.name = name;
	var messages = null;
	this.update = function () {
		ui.chatManager.chatUpdate(that.nick);
	};
	this.getMessages = function() {
		return messages;
	};
	this.setMessagesQuiet = function(list) {
		messages = list;
	};
	this.setMessages = function(list) {
		messages = list;
		var scroll = false;
		if (getComponent("chatContainer").scrollTop < getComponent("chatContainer").scrollHeight - 30) {
			scroll = true;
		}
		if (that == ui.chatManager.chat) {
			var out = "";
			out = out + "<BR><a class='button' href='javascript:moreMessages();'><div class='dbutton'>more messages</div></a> <a class='button' href='javascript:lessMessages();'><div class='dbutton'>less messages</div></a><BR><BR>";
			for (var i = messages.length-1; i >= 0; i = i -1) {
				if (messages[i].authorNick == ui.user) {
					out = out + "<div class='messageWrapper' align='right'><img class='profile2' src='https://www.ssl-id.de/bla.f-online.net/api/imgs/profile_"+messages[i].authorNick+".png' onerror=\"this.src='https://www.ssl-id.de/bla.f-online.net/api/imgs/user.png';\" /><div class='messageYou' align='left'>";
				} else {
					out = out + "<div class='messageWrapper' align='left'><img class='profile' src='https://www.ssl-id.de/bla.f-online.net/api/imgs/profile_"+messages[i].authorNick+".png' onerror=\"this.src='https://www.ssl-id.de/bla.f-online.net/api/imgs/user.png';\" /><div class='messageOther' align='left'>";
				}
				var message = messages[i].message;
				var parts = message.split(" ");
				var outMsg = "";
				if (message.indexOf("#image") == 0) {
					outMsg = outMsg + "<a target='_blank' href='"+parts[1]+"'><div class='thumb' style='background-image:url(\""+parts[1]+"\");'></div></a>";
				} else if (message.indexOf("#video") == 0) {
					var videoTag = "<video src='" + parts[1] + "' />";
					if (window.mobilecheck != false) {
						videoTag = "video: " + parts[1];
					}
					outMsg = outMsg + "<a href='"+parts[1]+"'>" + videoTag + "</a>";
				} else {
					for (var j = 0; j < parts.length; j++) {
						if (parts[j].search(/ftp:\/\/.+/) >= 0 || parts[j].search(/ftps:\/\/.+/) >= 0 || parts[j].search(/http:\/\/.+/) >= 0 || parts[j].search(/https:\/\/.+/) >= 0) {
							parts[j] = "<a target='_blank' href='" + parts[j] + "'>" + parts[j] + "</a>";
						} else if (parts[j].search(/.+@.+\..+/) >= 0) {
							parts[j] = "<a target='_blank' href='mailto:" + parts[j] + "'>" + parts[j] + "</a>";
						} else if (parts[j].search(/www\..+/) >= 0) {
							parts[j] = "<a target='_blank' href='http://" + parts[j] + "'>" + parts[j] + "</a>";
						}
						outMsg = outMsg + parts[j] + " ";
					}
				}
				out = out + "<div class='msgHeader'><span class='msgHeaderName'>";
				out = out + messages[i].author + " </span> <span class='msgHeaderTime'> (" + messages[i].time + ")</span>";
				out = out + "</div><BR>";
				out = out + "<div class='msgBody'>";
				out = out + outMsg;
				out = out + "</div>";
				out = out + "</div></div>";
			}
			ui.container.innerHTML = out;
		}
		getComponent("listPane").style.height = (window.innerHeight-subtract)+"px";
		getComponent("container").style.height = (window.innerHeight-subtract)+"px";
		ui.resize();
		if (scroll == true) {
			window.setTimeout("scrollBottom()", 200);
			getComponent("chatContainer").scrollTop = getComponent("chatContainer").scrollHeight;
		}
	};
}

function scrollBottom() {
	getComponent("chatContainer").scrollTop = getComponent("chatContainer").scrollHeight;
};

function ChatManager() {
	this.videoManager = null;
	this.audioManager = null;
	var that = this;
	var clientID = null;
	var chats = [];
	this.chat = null;
	this.count = 60;
	var pw = null;
	var initialized = false;
	var dynamicUpdateIntervall = 1000;
	
	function loadState() {
		if(typeof(Storage) !== "undefined") {
			if (typeof(localStorage.bla_state) !== "undefined" && localStorage.bla_state != null && localStorage.bla_state != "null") {
				var bundle = JSON.parse(localStorage.bla_state);
				chats = [];
				for (var i = 0; i < bundle.length; i = i + 1) {
					chats[i] = new Chat(bundle[i].nick, bundle[i].name);
					chats[i].setMessagesQuiet(bundle[i].messages);
				}
				console.log("Loaded state");
			}
		}
	}
	
	function storeState() {
		if(typeof(Storage) !== "undefined") {
			var bundle = [];
			for (var i = 0; i < chats.length; i = i + 1) {
				bundle[i] = {nick:chats[i].nick, name:chats[i].name, messages:chats[i].getMessages()};
			}
			localStorage.bla_state = JSON.stringify(bundle);
			console.log("Stored state");
		}
	}

	function initialize() {
		send({'type':'onGetContacts','msg':{'user':ui.user, 'password':pw}},onIncoming);
		send({'type':'onGetChats','msg':{'user':ui.user, 'password':pw}},onIncoming);
		window.setTimeout("ui.chatManager.eventTrigger()", dynamicUpdateIntervall);
		that.videoManager = new VideoCall(pw);
		that.audioManager = new AudioCall(pw);
	}

	function onIncoming(object, msg) {
		if (object == null) {
			console.log("Unknown Error!");
			location.reload();
		} else if (object.type == "onRejected") {
			localStorage.user = null;
			alert("Login failed! Username and password incorrect!");
			ui.isConnected = false;
		} else if (object.type == "onConnect") {
			ui.isConnected = true;
			ui.username = object.msg;
			ui.container.innerHTML = "<div class='welcome'>Hi " + object.msg + ",</div><div class='welcomeBody'>"+welcomeMessage+"<BR>Greetings<BR><BR>Your Host</div>";
			if (clientID != null && clientID != "null") {
				initialize();
			} else {
				send({'type':'onIdRequest','msg':{'user':ui.user, 'password':pw}},onIncoming);
			}
		} else if (object.type == "onIdRequest") {
			clientID = object.msg;
			localStorage.clientID = clientID;
			initialize();
		} else if (object.type == "onGetContacts") {
			ui.setContactList(object.msg);
		} else if (object.type == "onGetChats") {
			ui.setChatList(object.msg);
			initialized = true;
		} else if (object.type == "onGetHistory") {
			var foundChat = get(object.nick, chats);
			if (foundChat != null) {
				foundChat.setMessages(object.msg);
				storeState();
			} else {
				//alert(object.nick + " not found in "+ JSON.stringify(chats))
			}
		} else if (object.type == "onMessage") {
			that.chat.update();
		} else if (object.type == "onMessageHandled") {
			that.chat.update();
			ui.unmarkAll();
		} else if (object.type == "onConversation") {
		
		} else if (object.type == "onEvent") {			
			if (ui.isConnected) {
				if (dynamicUpdateIntervall < 120000) {
					dynamicUpdateIntervall += 1000;
				}
				window.setTimeout("ui.chatManager.eventTrigger()", dynamicUpdateIntervall);
			}
			for (var i = 0; i < object.msg.length; i++) {
				var e = object.msg[i];
				ui.unmarkAll();
				if (e.type == "onMessage") {
					if ((ui.user == e.nick) == false) {
						playSound();
						if (!ui.isForeground) {
						    notify(e.msg, e.nick, e.text);
						}
						if (ui.chatManager.chat && e.nick == ui.chatManager.chat.nick && ui.isForeground) {
							that.consumeEvent(e);
						} else {
							ui.notifyContact(e.msg);
						}
					}
					dynamicUpdateIntervall = 1000;
					// Also retrieve if chat not active.
					that.chatUpdate(e.msg);
				} else if (e.type == "onStatusChange") {
					send({'type':'onGetContacts','msg':{'user':ui.user, 'password':pw}},onIncoming);
				} else if (e.type == "onConversation") {
					send({'type':'onGetChats','msg':{'user':ui.user, 'password':pw}},onIncoming);
				} else if (e.type == "onMessageHandled") {
					ui.unmarkChat(e.msg);
					that.chatUpdate(e.msg);
				} else if (e.type == "onBlaCall") {
					that.audioManager.onmessage(e, e.msg);
					that.videoManager.onmessage(e, e.msg);
				} else {
					console.log(e.type + "->" + JSON.stringify(e.msg));
				}
			}
		} else if (object.type == "onRemoveEvent") {
			// Nothing to do here?!
			console.log("remove: "+JSON.stringify(object)+":"+msg);
		} else if (object.type == "onError") {
			console.log("ERROR! " + object.msg);
			location.reload();
		} else {
			console.log("Unknown object received: "+JSON.stringify(object));
		}
	};
		
	this.acceptCall = function() {
		that.audioManager.acceptCall();
		that.videoManager.acceptCall();
	};
	this.declineCall = function() {
		that.audioManager.declineCall();
		that.videoManager.declineCall();
	};

	this.chatUpdate = function(nick) {
		send({'type':'onGetHistory','msg':{'user':ui.user, 'password':pw, 'conversation':nick, 'count':that.count}},onIncoming);
	};
	
	this.consumeEvent = function(event) {
		send({'type':'onRemoveEvent','msg':{'user':ui.user, 'password':pw, 'conversation':event.nick}},onIncoming);
	};
	
	this.eventTrigger = function () {
		if(initialized == false) {
			window.setTimeout("ui.chatManager.eventTrigger()", 500);
		} else {
			send({'type':'onEvent','msg':{'user':ui.user, 'password':pw, 'id':clientID}}, onIncoming);
		}
	};
	
	this.open = function (partner, name) {
		dynamicUpdateIntervall = 1000;
		that.chat = get(partner, chats);
		if (that.chat == null) {
			that.chat = new Chat(partner, name);
			chats[chats.length] = that.chat;
			ui.container.innerHTML = "loading";
		} else {
			that.chat.setMessages(that.chat.getMessages());
		}
		that.chat.update();
		that.consumeEvent({"nick":ui.chatManager.chat.nick});
		getComponent("chatContainer").scrollTop = getComponent("chatContainer").scrollHeight+200;
		send({'type':'onRemoveEvent','msg':{'user':ui.user, 'password':pw, 'id':clientID,'message':ui.chatManager.chat.nick,'type':'onMessage'}},onIncoming);
	};
	
	this.sendMsg = function () {
		dynamicUpdateIntervall = 1000;
		var msgBox = getComponent("msgBox");
		var message = msgBox.value;
		message = encode_utf8(message);
		var newMessages = that.chat.getMessages();
		if (newMessages != null) {
			for (var i = newMessages.length - 2; i >= 0; i = i - 1) {
				newMessages[i+1] = newMessages[i];
			}
			newMessages[0] = {"author": ui.username, "time":"0000-00-00 00:00:00","message":message, "authorNick":ui.user};
		}
		that.chat.setMessages(newMessages);
		getComponent("chatContainer").scrollTop = getComponent("chatContainer").scrollHeight+200;
		if (message != "") {
			send({'type':'onMessage','msg':{'user':ui.user, 'password':pw,'conversation':ui.chatManager.chat.nick,'message':message}},onIncoming);
		}
		ui.unmarkChat(ui.chatManager.chat.nick);
		that.consumeEvent({"nick":ui.chatManager.chat.nick});
		window.setTimeout("msgBox.value = ''", 100);
	};
	
	this.startConversation = function (chat) {
		send({'type':'onNewConversation','msg':{'user':ui.user, 'password':pw,'conversation':chat.nick}},onIncoming);
	};
	
	this.login = function () {
		var checkBox = "";
		if(typeof(Storage) !== "undefined") {
			if (typeof(localStorage.user) !== "undefined" && localStorage.user != null && localStorage.user != "null") {
				ui.user = localStorage.user;
				pw = localStorage.pw;
				ui.chatManager.onLoginConfirm();
				return;
			}
			checkBox = "<input type='checkbox' id='autologin' >auto login next time<br>";
		} else {
			alert("Your browser does not support local storage.");
			window.location = "about:blank";
		}
		ui.container.innerHTML = "Login<BR><form action='javascript:ui.chatManager.onLoginConfirm()'>Nickname: <input type='text' size='32' name='nick' id='nick' /><BR/>Password: <input type='password' size='32' name='password'  id='pw' /><BR/>"+checkBox+"<input type='submit' value='Login' /></form>";
	};
	
	this.onLoginConfirm = function () {
		loadState();
		if (pw == null) {
			ui.user = getComponent("nick").value;
			pw = getComponent("pw").value;
		}
		var autologBox = getComponent("autologin");
		if (autologBox != null && autologBox.checked == true) {
			localStorage.user = ui.user;
			localStorage.pw = pw;
		}
		if (pw == "" || ui.user == "") {
			ui.user = "unauthorized";
			alert("You must provide user information!");
		} else {
			send({'type':'onConnect','msg':{'user':ui.user, 'password':pw}}, onIncoming);
		}
	};
}
