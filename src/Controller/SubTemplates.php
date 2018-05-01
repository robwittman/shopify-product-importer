<?php

namespace App\Controller;

use App\Model\SubTemplate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Flash\Messages;
use Slim\Views\Twig;

class SubTemplates
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

    public function create(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $body = $request->getParsedBody();
        $template = $arguments['id'];
        $subTemplate = new SubTemplate();
        $subTemplate->name = $body['name'];
        $subTemplate->value = $body['value'];
        $subTemplate->enabled = 1;
        $subTemplate->template_id = $template;
        $subTemplate->save();
        $this->flash->addMessage('message', 'Sub Template added');
        return $response->withRedirect("/templates/{$template}");
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $template = $arguments['id'];
        $subTemplate = SubTemplate::find($arguments['subId']);
        $subTemplate->delete();
        $this->flash->addMessage('message', 'Sub Template deleted');
        return $response->withRedirect("/templates/{$template}");
    }

    public function enable(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $template = $arguments['id'];
        $subTemplate = SubTemplate::find($arguments['subId']);
        $subTemplate->enabled = true;
        $subTemplate->save();
        $this->flash->addMessage("message", 'Sub Template enabled');
        return $response->withRedirect("/templates/{$template}");
    }

    public function disable(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $template = $arguments['id'];
        $subTemplate = SubTemplate::find($arguments['subId']);
        $subTemplate->enabled = false;
        $subTemplate->save();
        $this->flash->addMessage("message", 'Sub Template enabled');
        return $response->withRedirect("/templates/{$template}");
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $body = $request->getParsedBody();
        $template = $body['template_id'];
        $subTemplate = SubTemplate::find($body['sub_template_id']);
        $subTemplate->name = $body['name'];
        $subTemplate->value = $body['value'];
        $subTemplate->save();
        $this->flash->addMessage("message", 'Sub Template updated');
        return $response->withRedirect("/templates/{$template}");
    }

    public function default(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $template = $arguments['id'];
        SubTemplate::where('template_id', '=', $template)->update(['default' => 0]);
        $subTemplate = SubTemplate::find($arguments['subId']);
        $subTemplate->default = 1;
        $subTemplate->save();
        $this->flash->addMessage("message", 'Sub Template enabled');
        return $response->withRedirect("/templates/{$template}");
    }
}
