<?php 
declare(strict_types=1);

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/rabbitmq-receiver.php


/**
 * Require Worker Bootstrap File.
 */
require_once 'bootstrap.php';



use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


$queue_name = 'hello'; // default
// $queue_name = 'crawler_queue'; // custom ffi
// $queue_name = 'payment_queue'; // bootListeners

$connection = new AMQPStreamConnection(env('MB_HOST', 'localhost'), env('MB_PORT', 5672), env('MB_USERNAME', 'guest'), env('MB_PASSWORD', 'guest'));
$channel = $connection->channel();

$channel->queue_declare('hello', false, false, false, false);

echo " [*] Waiting for messages [{$queue_name }]. To exit press CTRL+C\n";


$callback = function (AMQPMessage $msg) {
  echo ' [x] Received ', $msg->getBody(), "\n";
};

$channel->basic_consume('hello', '', false, true, false, false, $callback);

try {
    $channel->consume();
} catch (\Throwable $exception) {
    echo $exception->getMessage();
}

