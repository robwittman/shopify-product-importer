<?php
require_once 'bootstrap.php';

$consumer = $container->get('QueueConsumer');

while (true) {
    $consumer->consume();
}
