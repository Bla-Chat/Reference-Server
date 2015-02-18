BlaChat-Server
==============

A secure and open source chat application, to give you insight what happens to your data.
It is can be interpreted as an opensource clone of hangouts.

![Image of the chat](http://www.michaelfuerst.de/wordpress/wp-content/uploads/2015/02/BlaChatServer.png)

The complete server including the web frontend is only at 4000 to 5000 lines of code, whereas the most recent api implementation doing all the work has only 500 lines of code to ensure easy maintainability and few space for bugs.

This chat is designed to be hosted on a simple php 5.2 webserver with mySql. Furthermore a design goal was to allow mobile devices to save energy by the avoidance of a continuous connection to the server and making developing clients for unstable internet connections easy.
