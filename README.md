BlaChat-Server
==============

A secure and open source chat application, to give you insight what happens to your data.
It is can be interpreted as an opensource clone of hangouts.

An <a href="https://github.com/Bla-Chat/Android">app</a> compatible with the server looks like this:
<p><a href="https://github.com/Bla-Chat/Android/blob/master/images/overview.png?raw=true"><img src="https://github.com/Bla-Chat/Android/blob/master/images/overview.png?raw=true" height="400" target="_blank" /></a>
<a href="https://github.com/Bla-Chat/Android/blob/master/images/chat.png?raw=true"><img src="https://github.com/Bla-Chat/Android/blob/master/images/chat.png?raw=true" height="400" target="_blank" /></a></p>

The complete server including the web frontend is only at 4000 to 5000 lines of code, whereas the most recent api implementation doing all the work has only 500 lines of code to ensure easy maintainability and few space for bugs.

This chat is designed to be hosted on a simple php 5.2 webserver with mySql. Furthermore a design goal was to allow mobile devices to save energy by the avoidance of a continuous connection to the server and making developing clients for unstable internet connections easy.
