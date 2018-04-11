<?php

namespace App\Controller;

use App\Model\Template;

class Templates
{
    public function __construct($view, $flash)
    {
        $this->view = $view;
        $this->flash = $flash;
    }

    public function index($request, $response, $arguments)
    {
        return $this->view->render($response, 'templates/index.html', array(
            'templates' => Template::all()
        ));
    }

    public function show($request, $response, $arguments)
    {
        return $this->view->render($response, 'templates/show.html', array(
            'template' => Template::find($arguments['id'])
        ));
    }

    public function update($request, $response, $arguments)
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
