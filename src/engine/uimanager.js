var ui = new UI();
ui.init();

function UI () {
	if (window.location.protocol != 'https:') {
		window.location.protocol = 'https:';
	}
	
	var that = this;
	
	var isConnected = false;
	var chatList = [];
	var contactList = [];
	this.chatManager = new ChatManager();
	this.user = "unauthorized";
	this.username = "none";
	this.chatVisible = true;
	
	// Make the contianers accessable
	this.container = getComponent("chatContainer");
	this.mediaContainer = getComponent("mediaContainer");
	var contacts = getComponent("listPane");
	var chats = getComponent("tabBar");
	
	this.setContactList = function(list) {
		contactList = list;
		that.updateContacts();
	}
	
	this.setChatList = function(list) {
		chatList = list;
		that.updateChats();
	}
	
	this.updateContacts = function () {
		if (that.isConnected == true) {
			contacts.innerHTML = contactListToHTML(contactList);
		} else {
			contacts.innerHTML = "login required";
		}
	};
	
	this.updateChats = function () {
		if (that.isConnected == true) {
			chats.innerHTML = chatListToHTML(chatList);
		} else {
			chats.innerHTML = "login required";
		}
	};
	
	this.notifyContact = function (nick) {
		mark(contactList, nick);
		mark(chatList, nick);
		that.updateChats();
		that.updateContacts();
	};
	
	this.unmarkAll = function (nick) {
		if (contactList != null) {
			for (var i = 0; i < contactList.length; i++) {
				unmark(contactList, contactList[i]);
				unmark(contactList, contactList[i]);
			}
			that.updateChats();
			that.updateContacts();
		}
	};
	
	
	this.unmarkChat = function (chat) {
		unmark(contactList, chat);
	};
	
	this.addContact = function (contact) {
		contactList[contactList.length] = contact;
		that.updateContacts();
	};
	
	this.addChat = function(chat) {
		chatList[chatList.length] = chat;
		that.chatManager.startConversation(chat);
		that.updateChats();
	};
	
	this.chat = function (partner, name) {
		unmark(contactList, partner);
		unmark(chatList, partner);
		that.chatManager.open(partner, name);
		that.updateChats();
		that.updateContacts();
		getComponent("chatContainer").scrollTop = getComponent("chatContainer").scrollHeight;
	};
	
	this.contact = function (partner, name) {
		if (partner > that.user) {
			partner = that.user + "," + partner;
		} else {
			partner = partner + "," + that.user;
		}
		if(get(partner, chatList) == null) {
			that.addChat({'nick':partner, 'name':name, 'marked':false});
		}
		that.chat(partner, name);
	};
	
	// Initialization code.
	that.updateChats();
	that.updateContacts();
	
	this.resize = function(event) {
			var scroll = false;
			if (that.chatVisible == true && getComponent("chatContainer").scrollTop == getComponent("chatContainer").scrollHeight)
				scroll = true;
			getComponent("listPane").style.height = (window.innerHeight-subtract)+"px";
			getComponent("container").style.height = (window.innerHeight-subtract)+"px";
			if (that.chatVisible == true) {
				that.container.style.height = (window.innerHeight-subtract-lineHeight)+"px";
				that.mediaContainer.style.display = "none";
			} else {
				that.mediaContainer.style.height = (window.innerHeight-subtract-lineHeight)+"px";
				that.container.style.display = "none";
			}
			if (scroll == true)
				getComponent("chatContainer").scrollTop = getComponent("chatContainer").scrollHeight;
	};
	
	window.mobilecheck = function() {
		var check = false;
		(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4)))check = true})(navigator.userAgent||navigator.vendor||window.opera);
		return check; 
	}
	
	if (window.mobilecheck() == false) {
		//window.onresize = that.resize;
	}

	that.resize();
	
	// show login screen
	this.init = function() {
		that.chatManager.login();	
	};
}
