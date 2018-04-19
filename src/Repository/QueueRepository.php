<?php

namespace App\Repository;

class QueueRepository
{
    public function getFifoQueue($shop = null) {
        $queue = Queue::with('template', 'sub_template', 'shop')
            ->where('status', Queue::PENDING)
            ->orderBy('created_at', 'desc')
            ->first();
        return $queue;
    }
}
