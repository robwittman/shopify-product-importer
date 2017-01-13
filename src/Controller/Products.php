<?php

namespace App\Controller;

use App\Model\User;

use PhpAmqpLib\Message\AMQPMessage;

class Products
{
    public function __construct($view, $flash, $rabbit)
    {
        $this->view = $view;
        $this->flash = $flash;
        $this->rabbit = $rabbit;
    }

    public function show_form($request, $response, $arguments)
    {
        $user = User::find($request->getAttribute('user')->id);
        $shops = $user->shops;
        return $this->view->render($response, 'product.html', array(
            'shops' => $shops
        ));
    }

    public function create($request, $response, $arguments)
    {
        $msg = new AMQPMessage('Hello World');
        $this->rabbit->basic_publish($msg, '', 'hello');
    }
}
