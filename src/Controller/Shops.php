<?php

namespace App\Controller;

use App\Model\Shop;
use App\Model\Errors;
use App\Model\Messages;
use App\Model\Template;
use App\Model\Setting;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

class Shops
{
    /**
     * @var Twig
     */
    protected $view;

    /**
     * @var \Slim\Flash\Messages
     */
    protected $flash;

    public function __construct(Twig $view, \Slim\Flash\Messages $flash)
    {
        $this->view = $view;
        $this->flash = $flash;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response)
    {
        $shops = Shop::all();
        return $this->view->render($response, 'shops/index.html', array(
            'shops' => $shops
        ));
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
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

    public function create(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
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
        $shop->description = $params['description'];

        try {
            $shop->save();
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withRedirect('/shops/create');
        }

        $this->flash->addMessage('message', "Shop successfully created");
        return $response->withRedirect('/shops');
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
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
        $shop->description = $params['description'];

        try {
            $shop->update();
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withRedirect("/shops/{$arguments['id']}");
        }

        $this->flash->addMessage('message', "Shop successfully updated");
        return $response->withRedirect("/shops/{$arguments['id']}");
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
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

    public function settings(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $shop = Shop::find($arguments['id']);
        $templates = Template::all();
        if ($handle = $request->getQueryParam('template')) {
            $template = Template::where('handle', $handle)->first();
            $setting = Setting::where(array(
                'template_id' => $template->id,
                'shop_id' => $shop->id
            ))->first();
            return $this->view->render($response, 'shops/settings.html', array(
                'shop' => $shop,
                'templates' => $templates,
                'template' => $template,
                'setting' => $setting
            ));
        } else {
            return $this->view->render($response, 'shops/settings.html', array(
                'shop' => $shop,
                'templates' => $templates
            ));
        }
    }

    public function update_settings(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $shop = Shop::find($arguments['id']);

        $body = $request->getParsedBody();
        $template = Template::find($arguments['templateId']);
        $setting = Setting::where(array(
            'template_id' => $template->id,
            'shop_id' => $shop->id
        ))->first();
        if (is_null($setting)) {
            $setting = new Setting();
            $setting->template_id = $template->id;
            $setting->shop_id = $shop->id;
        }
        foreach ($body as $key => $value) {
            $setting->{$key} = $value;
        }
        $setting->save();
        $this->flash->addMessage("message", "Shop Template Settings saved successfully");
        return $response->withRedirect("/shops/{$shop->id}/settings?template={$template->handle}");
    }

    public function setSheet(ServerRequestInterface $request, ResponseInterface $response)
    {
        $body = $request->getParsedBody();
        $shop = Shop::find($body['shop_id']);
        $shop->google_sheet_slug = $body['google_sheet_slug'];
        $shop->update();
        $this->flash->addMessage('message', 'Google Sheet updated successfully');
        return $response->withRedirect('/shops/'.$body['shop_id']);
    }
}
