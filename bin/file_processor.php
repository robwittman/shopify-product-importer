<?php

require_once 'bootstrap.php';

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Batch;

while (true) {
    $batch = Batch::where('status', 'pending')->orderBy('created_at', 'asc')->first();
    if (!$batch) {
        sleep(5);
        continue;
    }

    $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($batch->file_path));
    foreach ($objects as $name => $object) {
        error_log($name);
        var_dump($object);
        if ($name == '__MACOSX') {
            continue;
        }
        error_log("Running...");
        foreach ($object as $file => $zip) {
            var_dump($file);
            var_dump($zip);
            exit;
            error_log($file);
            $hash = hash('sha256', uniqid());
            $zip = new \ZipArchive();
            $zip->open($file);
            $zip->extractTo("/tmp/{$hash}");

            $contents = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("/tmp/{$hash}"));
            var_dump($contents);
            foreach ($contents as $fileName => $fileObject) {
                error_log($fileName);
                $name = str_replace('/tmp/','',$fileName);
                $s3->putObject([
                    'Bucket' => "shopify-product-importer",
                    'SourceFile' => $object,
                    'ACL' => "public-read",
                    'Key' => str_replace(' ', '_', $fileName),
                    'Content-Type' => 'application/zip_file'
                ]);
            }
            $shop = Shop::find($batch->shop_id);
            $data = array(
                'file' => $hash,
                'post' => $batch->post,
                'file_name' => $batch->file_name
            );
            $data['post']['shop'] = $batch->shop_id;

            $queue = new Queue();
            $queue->data = $data;
            $queue->status = Queue::PENDING;
            $queue->shop = $shop->id;
            $queue->file_name = $data['file'];
            $queue->template = $batch->template;
            $queue->log_to_google = false;
            $queue->save();
        }
    }
    sleep(5);
}
