BlaChat-Server
==============

A secure and open source chat application, to give you insight what happens to your data.
It is can be interpreted as an opensource clone of hangouts.

The complete server including the web frontend is only at 4000 to 5000 lines of code, whereas the most recent api implementation doing all the work has only 500 lines of code to ensure easy maintainability and few space for bugs.

This chat is designed to be hosted on a simple php 5.2 webserver with mySql. Furthermore a design goal was to allow mobile devices to save energy by the avoidance of a continuous connection to the server and making developing clients for unstable internet connections easy.


Related work and projects
=========================

In early 2014 the underlying protocol was put into an extra project to ensure it is not developed for easy and hacky implementation but for performance and security. https://www.ssl-id.de/bla.f-online.net/api/XJCP-Spec.pdf

To make developing clients easier there is now a java client side protocol implementation. https://github.com/penguinmenac3/XJCP-Interface

There also exists an open source client implementation for android.
https://github.com/penguinmenac3/BlaChat.

The whole BlaChat-Project is more than 10k lines of code.
