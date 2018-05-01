<?php

namespace App\Controller;

use App\Model\Setting;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Flash\Messages;
use Slim\Views\Twig;

class Settings
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
        return $this->view->render($response, 'templates/index.html', array(
            'templates' => Setting::all()
        ));
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        return $this->view->render($response, 'templates/show.html', array(
            'template' => Setting::find($arguments['id'])
        ));
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $template = Setting::find($arguments['id']);
        foreach ($request->getParsedBody() as $key => $value) {
            $template->{$key} = $value;
        }
        $template->save();
        return $response->withRedirect("/templates/{$template->id}");
    }
}
