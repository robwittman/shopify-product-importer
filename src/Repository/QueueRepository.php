<?php

namespace App\Repository;

use App\Model\Queue;

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
