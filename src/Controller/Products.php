<?php

namespace App\Controller;

use App\Model\User;
use App\Model\Queue;
use App\Model\Shop;

class Products
{
    public function queue($request, $response, $arguments)
    {
        $queue = Queue::orderBy('created_at', 'desc')->take(50)->get();
        foreach ($queue as $record) {
            // TODO: Move shop_id to table column
            $data = json_decode($record->data, true);
            $shop = Shop::find($record->shop);
            $record->shop = $shop;
        }
        return $response->withJson(array(
            'queue' => $queue
        ));
    }

    public function create($request, $response, $arguments)
    {
        $params = $request->getParsedBody();

        $data = array(
            'file' => $params['file_name'],
            'post' => $params
        );

        $stores = $params['stores'];
        foreach ($stores as $store) {
            $queue = new Queue();
            $queue->data = json_encode($data);
            $queue->status = Queue::PENDING;
            $queue->shop = $store;
            $queue->file_name = $data['file'];
            $queue->template = $params['template'];
            $queue->log_to_google = $params['log_to_google'] ?: 0;
            $queue->save();
        }

        return $response->withJson(array(
            'success' => true
        ));
    }

    public function restart_queue($request, $response, $args)
    {
        $post = $request->getParsedBody();
        $queue_id = $post['queue_id'];
        $queue = Queue::find($queue_id);
        $queue->status = Queue::PEDING;
        $queue->save();
        $this->flash->addMessage("message", "Queued product successfully restarted");
        return $response->withRedirect('queue');
    }
}
