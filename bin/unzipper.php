<?php

require_once 'bootstrap.php';

use App\Model\BatchUpload;
use App\Unzipper;

$queueClient = $container->get('SqsQueue');

while (true) {
    try {
        $result = $queueClient->receiveMessage(array(
            'MaxNumberOfMessages' => 1,
            'QueueUrl' => getenv('UPLOAD_QUEUE_URL'),
            'WaitTimeSeconds' => 0
        ));
        if (count($result->get('Messages')) > 0) {
            $message = $result->get('Messages')[0];
            $data = json_decode($message['Body'], true);
            $unzipper = new Unzipper($container->get('MountManager'));
            $batch = BatchUpload::find($data['id']);
            try {
                $unzipper->process($batch);
                $queueClient->deleteMessage(array(
                    'QueueUrl' => getenv('UPLOAD_QUEUE_URL'),
                    'ReceiptHandle' => $message['ReceiptHandle']
                ));
                // Update batch
            } catch (\Exception $e) {
                error_log($e->getMessage());
            }
        } else {
            sleep(10);
        }
    } catch (\Exception $e) {
        error_log($e->getMessage());
    }
}
