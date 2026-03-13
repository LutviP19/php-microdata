<?php 

/**
 *  @author Lutvip19 <lutvip19@gmail.com>
 */

// file: cron/rabbitmq-send.php

if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . "/..");
}

/**
 * Require Core init File.
 */
require_once BASEPATH .'/app/Core/init.php';


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

