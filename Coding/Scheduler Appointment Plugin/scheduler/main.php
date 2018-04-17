<?php
require_once('Client.php');
require_once('Server.php');

$server = new Server();


$message = "Welcome to FCIH";
$client1 = new Client($server, "Ali");

$client1->subscribe();

$server->notify($message);
echo "\r\n\r\n";



$message = "Design patterns";
$client2 = new Client($server, "Aya");

$client2->subscribe();

$server->notify($message);



echo "\r\n\r\n";

$message = "Software Engineering 2";

$client1->unsubscribe();

$server->notify($message);