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


// try {
//     $result = $client->receiveMessage(array(
//         'AttributeNames' => ['SentTimestamp'],
//         'MaxNumberOfMessages' => 1,
//         'MessageAttributeNames' => ['All'],
//         'QueueUrl' => $queueUrl, // REQUIRED
//         'WaitTimeSeconds' => 0,
//     ));
//     if (count($result->get('Messages')) > 0) {
//         var_dump($result->get('Messages')[0]);
//         $result = $client->deleteMessage([
//             'QueueUrl' => $queueUrl, // REQUIRED
//             'ReceiptHandle' => $result->get('Messages')[0]['ReceiptHandle'] // REQUIRED
//         ]);
//     } else {
//         echo "No messages in queue. \n";
//     }
// } catch (AwsException $e) {
//     // output error message if fails
//     error_log($e->getMessage());
// }
