String.prototype.replaceAll = function (find, replace) {
    var str = this;
    return str.replace(new RegExp(find.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'g'), replace);
};

function AudioCall(password) {
	var that = this;
	var parentChat = null;
        var pw = password;
	var remotestream;
	var localstream;
	var statusIsCaller;
	var accepted = false;

	var pc;

var isChrome = !!navigator.webkitGetUserMedia;

var STUN = {
    url: isChrome 
       ? 'stun:stun.l.google.com:19302' 
       : 'stun:23.21.150.121'
};

var TURN = {
    url: 'turn:homeo@turn.bistri.com:80',
    credential: 'homeo'
};

var iceServers = {
   iceServers: [STUN, TURN]
};

// DTLS/SRTP is preferred on chrome
// to interop with Firefox
// which supports them by default

var DtlsSrtpKeyAgreement = {
   DtlsSrtpKeyAgreement: true
};

var optional = {
   optional: [DtlsSrtpKeyAgreement]
};

	this.send = function (data) {
	    if ( parentChat == null && ui.chatManager.chat ) {
		parentChat = ui.chatManager.chat.nick;
	    }
	    if( parentChat ) {
		data.audio = true;
	    	console.log("Address: "+JSON.stringify(parentChat));
		var stringified = JSON.stringify(data);
		stringified = stringified.replaceAll('\\r\\n','#r#n');
		data = JSON.parse(stringified);
	        send({'type':'onBlaCall','msg':{'user':ui.user, 'password':pw,'conversation':parentChat,'message':data}},function() {});
	    }
	};

	// run start(true) to initiate a call
	this.start = function(isCaller) {
	  if( parentChat == null && ui.chatManager.chat == null) { return; }
          statusIsCaller = isCaller;

	  getComponent("OpenCallOverlay").style.display = "none";
	  getComponent("ExitCallOverlay").style.display = "inline-block";
	  ui.videoVisible = true;
	  ui.resize();
	  if (isCaller == true) {
		accepted = true;
	  	getComponent("outgoingCallOverlay").style.display = "block";
		startRing();
	  }

	    pc = new RTCPeerConnection(iceServers, optional);
	
	    // send any ice candidates to the other peer
	    pc.onicecandidate = function (evt) {
		if (pc && evt.candidate) {
			if (remotestream) {
				window.setTimeout(function() {if (pc) that.send({ "candidate": evt.candidate });}, 2000);
	        		
			} else {
				window.setTimeout(function() {if (pc) pc.onicecandidate(evt);}, 200);
			}
		}
	    };
	
	    // once remote stream arrives, show it in the remote video element
	    pc.onaddstream = function (evt) {
	  	getComponent("outgoingCallOverlay").style.display = "none";
	  	getComponent("audioOverlay").style.display = "block";
		stopRing();
		remotestream = evt.stream;
	        aud2.src = URL.createObjectURL(evt.stream);
	    };
	
	    // get the local stream, show it in the local video element and send it
	    getUserMedia({ "audio": true, "video": false }, function (stream) {
	        aud1.src = URL.createObjectURL(stream);
		if (!pc) {stream.close;console.log("Critical Error"); return;}
	        pc.addStream(stream);
		localstream = stream;
		
	        if (isCaller)
	            pc.createOffer(function(desc) {
	            pc.setLocalDescription(desc);
	            that.send({ "sdp": desc });
	        }, function() {console.log("Error"); that.stop();});	            
	    }, function() {console.log("Error"); that.stop();});
	};
	
	this.onmessage = function (evt, chat) {	
	    evt.text = evt.text.replaceAll('#r#n','\\r\\n');
	    var signal = JSON.parse(evt.text);

	    if (!signal.audio) return;

	    if (!signal.sdp && !signal.candidate) {
	    	that.stop();
		return;
	    }

	    parentChat = chat;
	    if (!pc)
	        that.start(false);

	    if (signal.sdp) {
	  	if (!statusIsCaller) {
	  		getComponent("incomingCallOverlay").style.display = "block";
			startRing();
		}
		function func() {
			if (pc && localstream && accepted == true) {
			    pc.setRemoteDescription(new RTCSessionDescription(signal.sdp));
			    if (!statusIsCaller) {
				pc.createAnswer(function(desc) {
		        	    pc.setLocalDescription(desc);
		        	    that.send({ "sdp": desc });
		        	}, function() {console.log("Error"); that.stop();});
			    }
			} else {
				if (pc) {
					window.setTimeout(func, 200);
				}
			}
		}
	        window.setTimeout(func,200);
	    } else if (signal.candidate) {
	        pc.addIceCandidate(new RTCIceCandidate(signal.candidate));
            } else {
		console.log("This is an error.");
	    }
	};

	this.acceptCall = function() {
		if (pc) {
			stopRing();
			accepted = true;
	  		getComponent("incomingCallOverlay").style.display = "none";
		}
	};
	this.declineCall = function() {
		if (pc) {
			stopRing();
			accepted = false;
			that.stop();
		}
	};

	this.stop = function() {
	  getComponent("audioOverlay").style.display = "none";
	  getComponent("OpenCallOverlay").style.display = "inline-block";
	  getComponent("ExitCallOverlay").style.display = "none";
	  getComponent("incomingCallOverlay").style.display = "none";
	  getComponent("outgoingCallOverlay").style.display = "none";
	  if (pc) {
	    that.send({ "close": "closing" });
	    pc.close();
	    pc = null;
	    if (localstream) {
	    	localstream.stop();
	    	localstream = null;
	    }
            remotestream = null;
	    parent = null;
	  } else {
		console.log("Cannot close audiostream.");
	  }
	  accepted = false;
	  ui.videoVisible = false;
	  ui.resize();
	}
}
