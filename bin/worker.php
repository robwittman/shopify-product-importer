<?php

require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$url = getenv("RABBITMQ_BIGWIG_URL");
$pieces = parse_url($url);
error_log(json_encode($pieces));
$connection = new AMQPConnection(
    $pieces['host'],
    $pieces['port'],
    $pieces['user'],
    $pieces['host'],
    $pieces['path']
);
error_log("Connected");
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

error_log("Worker ready for messages");

$callback = function($msg) {
    error_log($msg);
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('task_queue','',false,false,false,false,$callback);

while(count($channel->callbacks)) {
    $channel->wait();
};

$channel->close();
$connection->close();
