<?php

namespace App\Consumer;

use App\Repository\QueueRepository;
use App\Queue\QueueProcessor;
use Psr\Log\LoggerInterface;

class UploadQueueConsumer implements ConsumerInterface
{
    protected $queueRepo;

    protected $queueProcessor;

    private $logger;

    private $sleepTime = 10;

    public function __construct(QueueRepository $queueRepo, QueueProcessor $queueProcessor, LoggerInterface $logger)
    {
        $this->queueRepo = $queueRepo;
        $this->queueProcessor = $queueProcessor;
        $this->logger = $logger;
    }

    public function consume()
    {
        while (true) {
            if ($queue = $this->queueRepo->getFifoQueue()) {
                $this->logger->debug("Processing queue {$queue->id}");
                $this->queueProcessor->process($queue);
            } else {
                $this->logger->debug("No queues to process... waiting");
                sleep($this->sleepTime);
            }
        }
    }
}
