<?php

namespace App\Controller;

use App\Model\Queue;
use App\Model\Template;
use App\Model\Shop;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Flash\Messages;
use Slim\Views\Twig;

class Queues
{
    /**
     * @var Twig
     */
    protected $view;

    /**
     * @var Messages
     */
    protected $flash;

    public function __construct(Twig $view, Messages $flash)
    {
        $this->view = $view;
        $this->flash = $flash;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $queue = Queue::orderBy('created_at', 'desc')->with('shop', 'template', 'sub_template')->take(300)->get();
        // echo json_encode($queue);
        // exit;
        return $this->view->render($response, 'queue/index.html', array(
            'queue' => $queue
        ));
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $queue = Queue::with('shop', 'template', 'sub_template')->find($arguments['id']);
        return $this->view->render($response, 'queue/show.html', array(
            'queue' => $queue
        ));
    }

    public function retry(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {

    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {

    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {

    }
}
