<?php
error_reporting(E_ERROR | E_PARSE);
require '../php/flight/Flight.php';
require_once 'api.php';

Flight::route('/chat', function(){
	API::send(API::exec("chat", $_REQUEST));
});

Flight::route('/login', function(){
	API::send(API::exec("login", $_REQUEST));
});

Flight::route('/remote', function(){
	API::send(API::exec("remote", $_REQUEST));
});

Flight::map('error', function(Exception $ex){
    // Handle error
	echo $ex->getMessage()."<br />";
    echo $ex->getTraceAsString();
});

Flight::map('notFound', function(){
    API::send(array("error" => "Invalid Request!", "code" => 404));
});

Flight::start();

// Verbindung zum Datenbankserver beenden
$db->Close();
?>