<?php

namespace App\Controller;

use App\Model\Template;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Flash\Messages;
use Slim\Views\Twig;

class Templates
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
        $templates = Template::with('sub_templates')->get();
        return $this->view->render($response, 'templates/index.html', array(
            'templates' => $templates
        ));
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        return $this->view->render($response, 'templates/show.html', array(
            'template' => Template::with('sub_templates')->find($arguments['id'])
        ));
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $template = Template::find($arguments['id']);
        foreach ($request->getParsedBody() as $key => $value) {
            $template->{$key} = $value;
        }
        $template->save();
        $this->flash->addMessage("message", "Template saved successfully.");
        return $response->withRedirect("/templates/{$template->id}");
    }
}
