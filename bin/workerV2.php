<?php

require_once 'bootstrap.php';

use App\Pipeline\QueueProcessor;
use App\Model\Queue;

while (true) {
    $queue = Queue::where('status', Queue::PENDING)
        ->orderBy('created_at', 'asc')
        ->first();
    if (!$queue) {
        sleep(5);
    } else {
        try {
            $queue->start();

            // Run this god-dang pipeline
            $processor = new QueueProcessor($queue, $s3);
            $processor->process();

            $queue->finish();
        } catch (\Exception)
    }
}
