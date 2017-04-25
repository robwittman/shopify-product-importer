<?php

namespace App\Controller;

use App\Model\Shop;
use App\Model\Errors;
use App\Model\Messages;

class Shops
{
    public function __construct($view, $flash)
    {
        $this->view = $view;
        $this->flash = $flash;
    }

    public function index($request, $response)
    {
        $shops = Shop::all();
        return $this->view->render($response, 'shops/index.html', array(
            'shops' => $shops
        ));
    }

    public function show($request, $response, $arguments)
    {
        if ($request->getAttribute('user')->role != 'admin') {
            $this->flash->addMessage('error', Errors::UNAUTHORIZED);
            return $response->withRedirect('/shops');
        }

        $shop = Shop::find($arguments['id']);
        if (empty($shop)) {
            $this->flash->addMessage('error', "Shop not found");
            return $response->withRedirect('/shops');
        }

        return $this->view->render($response, 'shops/show.html', array(
            'shop' => $shop
        ));
    }

    public function create($request, $response, $arguments)
    {
        if ($request->getAttribute('user')->role != 'admin') {
            $this->flash->addMessage('error', Errors::UNAUTHORIZED);
            return $response->withRedirect('/shops');
        }

        if ($request->isGet()) {
            return $this->view->render($response, 'shops/new.html');
        }

        $params = $request->getParsedBody();
        $shop = new Shop();
        $shop->myshopify_domain = preg_replace("(^https?://)", "", $params['myshopify_domain']);
        $shop->api_key = $params['api_key'];
        $shop->password = $params['password'];
        $shop->shared_secret = $params['shared_secret'];

        try {
            $shop->save();
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withRedirect('/shops/create');
        }

        $this->flash->addMessage('message', "Shop successfully created");
        return $response->withRedirect('/shops');
    }

    public function update($request, $response, $arguments)
    {
        if ($request->getAttribute('user')->role != 'admin') {
            $this->flash->addMessage('error', Errors::UNAUTHORIZED);
            return $response->withRedirect('/shops');
        }

        $params = $request->getParsedBody();
        $shop = Shop::find($arguments['id']);
        if (empty($shop)) {
            $this->flash->addMessage('error', "Shop not found");
            return $this->view->render($response, 'shops/index.html');
        }

        $shop->myshopify_domain = $params['myshopify_domain'];
        $shop->api_key = $params['api_key'];
        $shop->password = $params['password'];
        $shop->shared_secret = $params['shared_secret'];

        try {
            $shop->update();
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withRedirect("/shops/{$arguments['id']}");
        }

        $this->flash->addMessage('message', "Shop successfully updated");
        return $response->withRedirect("/shops/{$arguments['id']}");
    }

    public function delete($request, $response, $arguments)
    {
        if ($request->getAttribute('user')->role != 'admin') {
            $this->flash->addMessage('error', Errors::UNAUTHORIZED);
            return $response->withRedirect('/shops');
        }

        $shop = Shop::find($arguments['id']);
        if (empty($shop)) {
            $this->flash->addMessage('error', "Shop {$arguments['id']} not found");
            return $response->withRedirect('/shops');
        }

        if ($request->isGet()) {
            return $this->view->render($response, 'shops/confirm.html', array(
                'shop' => $shop
            ));
        } else {
            $shop->delete();
            $this->flash->addMessage('message', 'Shop succesfully deleted');
            return $response->withRedirect('/shops');
        }
    }
}
