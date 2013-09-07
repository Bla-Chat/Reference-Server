function VideoCall(nick) {
	var that = this;
	var frame = null;
	var participants = [];
	
	this.nick = nick;
	
	this.start = function () {
		frame = ui.mediaContainer;
	};
	this.add = function (newParticipants) {
		for (var i = 0; i < newParticipants.length; i++) {
			participants[participants.length+i] = newParticipants[i];
		}
		that.updateUI();
	};
	this.remove = function (oldParticipants) {
		for (var i = 0; i < oldParticipants.length; i++) {
			for (var j = 0; j < participants.length; j++) {
				if (participants[j].nick == oldParticipants[i].nick) {
					participants = removeFromArray(participants, j);
					break;
				}
			}
		}
		that.updateUI;
	};
	this.stop = function () {
		frame = null;
	};
	this.updateUI = function () {
		// TODO
	};
}
