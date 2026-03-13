<?php

require_once __DIR__ . '/../vendor/autoload.php';


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection(env('MB_HOST', 'localhost'), env('MB_PORT', 5672), env('MB_USERNAME', 'guest'), env('MB_PASSWORD', 'guest'));
$channel = $connection->channel();


$channel->queue_declare('hello', false, false, false, false);

$msg = new AMQPMessage('Hello World!');
$channel->basic_publish($msg, '', 'hello');

echo " [x] Sent 'Hello World!'\n";

$channel->close();
$connection->close();

