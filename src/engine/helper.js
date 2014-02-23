var subtract = 140;
var lineHeight = 90;
if (window.mobilecheck != false) {
	subtract = 100;
	var lineHeight = 10;
	window.getElementsByTagName("body")[0].style.height = window.innerHeight;
	window.getElementsByTagName("body")[0].style.overflow = 'hidden';
	window.scroll = function () { 
       window.scrollTo(0,0); 
	};
}

document.head = document.head || document.getElementsByTagName('head')[0];

function changeFavicon(src) {
 var link = document.createElement('link'),
     oldLink = document.getElementById('dynamic-favicon');
 link.id = 'dynamic-favicon';
 link.rel = 'shortcut icon';
 link.href = src;
 if (oldLink) {
  document.head.removeChild(oldLink);
 }
 document.head.appendChild(link);
}

function moreMessages() {
	ui.chatManager.count = ui.chatManager.count + 30;
	ui.chatManager.chatUpdate(ui.chatManager.chat.nick);
}

function lessMessages() {
	ui.chatManager.count = ui.chatManager.count - 30;
	if (ui.chatManager.count < 30) {
		ui.chatManager.count = 30;
	}
	ui.chatManager.chatUpdate(ui.chatManager.chat.nick);
}

function removeFromArray(array, position) {
	var out = [];
	for (var i = 0; i < array.length-1; i++) {
		if (i >= position) {
			out[i] = array[i+1];
		} else {
			out[i] = array[i];
		}
	}
	return out;
}

function getComponent(id) {
	return document.getElementById(id);
}

function handleKeyPress(e,form){
	var key = e.keyCode || e.which;
	if (key==13){
		ui.chatManager.sendMsg();
	}
}

function contactListToHTML(contacts) {
	var out = "";
	for (var i = 0; i < contacts.length; i++) {
		var marker = "";
		if (contacts[i].marked == true) {
			marker = "*";
		}
		var state = "dcontactbutton";
		if (contacts[i].status == "1") {
			state = "dcontactbuttonOnline";
		}
		out = out + "<a class='button' href='javascript:ui.contact(\""+contacts[i].nick + "\",\"" + contacts[i].name + "\");'><div class='"+state+"'><img class='profile3' src='https://www.ssl-id.de/bla.f-online.net/api/imgs/profile_"+contacts[i].nick+".png' onerror=\"this.src='https://www.ssl-id.de/bla.f-online.net/api/imgs/user.png';\" />" + contacts[i].name + marker + "</div></a>";
	}
	return out;
}

function chatListToHTML(chats) {
	var out = "";
	for (var i = 0; i < chats.length; i++) {
		var marker = "dbutton";
		if (chats[i].marked == true) {
			marker = "dbuttonMarked";
		} else if (ui.chatManager.chat != null && chats[i].nick == ui.chatManager.chat.nick) {
			marker = "dbuttonActive";
		}
		out = out + "<a class='button' id='+"+chats[i].nick+"' href='javascript:ui.chat(\""+chats[i].nick + "\",\"" + chats[i].name + "\");'><div class='"+marker+"'>" + chats[i].name + "</div></a>";
	}
	return out;
}

function encode_utf8(rohtext) {
   // dient der Normalisierung des Zeilenumbruchs
   rohtext = rohtext.replace(/\r\n/g,"\n");
   var utftext = "";
   for(var n=0; n<rohtext.length; n++)
   {
        // ermitteln des Unicodes des  aktuellen Zeichens
        var c=rohtext.charCodeAt(n);
        // alle Zeichen von 0-127 => 1byte
    if (c<128)
        utftext += String.fromCharCode(c);
        // alle Zeichen von 127 bis 2047 => 2byte
    else if((c>127) && (c<2048)) {
        utftext += String.fromCharCode((c>>6)|192);
        utftext += String.fromCharCode((c&63)|128);}
    // alle Zeichen von 2048 bis 66536 => 3byte
    else {
        utftext += String.fromCharCode((c>>12)|224);
        utftext += String.fromCharCode(((c>>6)&63)|128);
	    utftext += String.fromCharCode((c&63)|128);}
    }
	return utftext;
}

var totalMarks = 0;

function mark(list, nick) {
	for (var i = 0; i < list.length; i++) {
		if (list[i].nick == nick && (ui.chatManager.chat == null || ui.chatManager.chat.nick != nick || ui.isForeground == false)) {
			if (list[i].marked != true) {
				totalMarks++;
			}
			list[i].marked = true;
		}
	}
	if (totalMarks > 0 || ui.isForeground == false) {
		window.document.title = "Bla chat *";
		changeFavicon('notified.png');
	} else {
		window.document.title = "Bla chat";
		changeFavicon('normal.png');
	}
}

function unmark(list, nick) {
	window.document.title = "Bla chat";
	for (var i = 0; i < list.length; i++) {
		if (list[i].nick == nick) {
			if (list[i].marked == true) {
				totalMarks--;
			}
			list[i].marked = false;
		}
	}
	if (totalMarks > 0) {
		window.document.title = "Bla chat *";
		changeFavicon('notified.png');
	} else {
		window.document.title = "Bla chat";
		changeFavicon('normal.png');
	}
}

function send(object, callback) {
	var xmlhttp;
	var msg = escape(JSON.stringify(object));
    if (window.XMLHttpRequest) {
    	xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
    	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
    		if (xmlhttp.responseText != "") {
        		var inobject = JSON.parse(xmlhttp.responseText);
        		callback(inobject, xmlhttp.responseText);
        	} else {
        		callback(null, xmlhttp.responseText);
        	}
    	}
    }
    var params = "msg="+msg;
    xmlhttp.open("POST", serverlocation+"api.php",true);
    //Send the proper header information along with the request
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send(params);
}

function sendViaGet(object, callback) {
	var xmlhttp;
	var msg = escape(JSON.stringify(object));
    if (window.XMLHttpRequest) {
    	xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
    	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
    		if (xmlhttp.responseText != "") {
			console.log(xmlhttp.responseText);
        		var inobject = JSON.parse(xmlhttp.responseText);
        		callback(inobject, xmlhttp.responseText);
        	} else {
        		callback(null, xmlhttp.responseText);
        	}
    	}
    }
    var params = "msg="+msg;
    xmlhttp.open("POST", serverlocation+"api.php?"+params,true);
    xmlhttp.send();
}

function get(nick, list) {
	for (var i = 0; i < list.length; i++) {
		if (list[i].nick == nick) {
			return list[i];
		}
	}
	return null;
}

var hasFocus = true;

(function() {
    var hidden = "hidden";

    // Standards:
    if (hidden in document)
        document.addEventListener("visibilitychange", onchange);
    else if ((hidden = "mozHidden") in document)
        document.addEventListener("mozvisibilitychange", onchange);
    else if ((hidden = "webkitHidden") in document)
        document.addEventListener("webkitvisibilitychange", onchange);
    else if ((hidden = "msHidden") in document)
        document.addEventListener("msvisibilitychange", onchange);

    // IE 9 and lower:
    else if ('onfocusin' in document)
        document.onfocusin = document.onfocusout = onchange;

    // All others:
    else
        window.onfocus = window.onblur = onchange;

    function onchange (evt) {
        var body = document.body;
        evt = evt || window.event;

        if (evt.type == "focus" || evt.type == "focusin")
            hasFocus = true;
        else if (evt.type == "blur" || evt.type == "focusout")
            hasFocus = true
        else        
            hasFocus = this[hidden] ? false : true;
            
        if (totalMarks < 1 || hasFocus == false) {
			window.document.title = "Bla chat";
		changeFavicon('normal.png');
		}
    }
})();

function playSound() {
 document.getElementById("notifications").play();
}

function startRing() {
 document.getElementById("calls").play();
}

function stopRing() {
 document.getElementById("calls").pause();
 document.getElementById("calls").currentTime = 0;
}
