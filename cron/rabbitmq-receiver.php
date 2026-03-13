<?php

require_once __DIR__ . '/../vendor/autoload.php';


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


$connection = new AMQPStreamConnection(env('MB_HOST', 'localhost'), env('MB_PORT', 5672), env('MB_USERNAME', 'guest'), env('MB_PASSWORD', 'guest'));
$channel = $connection->channel();

$channel->queue_declare('hello', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";


$callback = function (AMQPMessage $msg) {
  echo ' [x] Received ', $msg->getBody(), "\n";
};

$channel->basic_consume('hello', '', false, true, false, false, $callback);

try {
    $channel->consume();
} catch (\Throwable $exception) {
    echo $exception->getMessage();
}

