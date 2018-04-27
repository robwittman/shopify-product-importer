<?php

namespace App\Controller;

use App\Model\Queue;
use App\Model\Template;
use App\Model\Shop;

class Queues
{
    public function __construct($view, $flash)
    {
        $this->view = $view;
        $this->flash = $flash;
    }

    public function index($request, $response, $arguments)
    {
        $queue = Queue::orderBy('created_at', 'desc')->with('shop', 'template', 'sub_template')->take(300)->get();
        // echo json_encode($queue);
        // exit;
        return $this->view->render($response, 'queue/index.html', array(
            'queue' => $queue
        ));
    }

    public function show($request, $response, $arguments)
    {
        $queue = Queue::with('shop', 'template', 'sub_template')->find($arguments['id']);
        return $this->view->render($response, 'queue/show.html', array(
            'queue' => $queue
        ));
    }

    public function retry($request, $response, $arguments)
    {

    }

    public function update($request, $response, $arguments)
    {

    }

    public function delete($request, $response, $arguments)
    {

    }
}
