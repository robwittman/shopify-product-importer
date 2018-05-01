<?php

require_once 'bootstrap.php';

use App\Model\Queue;
use App\Pipeline;

while (true) {
    $queue = Queue::with('template', 'sub_template', 'shop')
        ->where('status', Queue::PENDING)
        ->orderBy('created_at', 'asc')
        ->first();
    if ($queue) {
        sleep(5);
    } else {
        try {
            $productPipelineManager = new \App\Pipeline\ProductPipelineManager();
            $templatePipeline = $productPipelineManager->getTemplateForProduct($queue->template);
            $pipelineBuilder = new \League\Pipeline\PipelineBuilder();
            $pipelineBuilder
                ->add(new Pipeline\InitializeProduct())
                ->add(new Pipeline\ImageLoader())
                ->add(new Pipeline\MatrixLoader())
                ->add($templatePipeline)
                ->add(new Pipeline\Shopify\PersistProduct($container->get('ShopifyApi')))
                ->add(new Pipeline\ImageMapper())
                ->add(new Pipeline\Shopify\PersistMappedImages($container->get('ShopifyApi')))
                ->add(new Pipeline\LogToGoogle($container->get('GoogleDrive')));
            $pipeline = $pipelineBuilder->build();
            $res = $pipeline->process();
            $queue->finish($res);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            if ($message = json_decode($e->getMessage())) {
                $queue->fail($message->error->message);
            } else {
                $queue->fail($e->getMessage());
            }
        }
    }
}