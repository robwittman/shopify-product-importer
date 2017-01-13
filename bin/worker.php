<?php
// TODO: Get rabbitmq connection

$connection = null;
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

error_log("Worker ready for messages");

$callback = function($msg) {
    error_log($msg);
};

$channel->basic_qos(null, 1 null);
$channel->basic_consume('task_queue','',false,false,false,false,$callback)

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
