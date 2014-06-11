<?php

/**
 * this example demonstrates a http request using 
 * the socks5socket.
 */

require('C:\xampp\htdocs\php\socket\client\Socks5Socket.class.php');

$s = new \Socks5Socket\Client();

$s->connect('25.108.238.139', 6667);

$request = "test";

$s->send($request);

$response = $s->readAll();

echo '<h1>The response was:</h1><pre>'.$response.'</pre>';