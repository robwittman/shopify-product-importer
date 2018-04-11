<?php

namespace App\Controller;

use App\Model\Setting;

class Settings
{
    public function __construct($view, $flash)
    {
        $this->view = $view;
        $this->flash = $flash;
    }

    public function index($request, $response, $arguments)
    {
        return $this->view->render($response, 'templates/index.html', array(
            'templates' => Setting::all()
        ));
    }

    public function show($request, $response, $arguments)
    {
        return $this->view->render($response, 'templates/show.html', array(
            'template' => Setting::find($arguments['id'])
        ));
    }

    public function update($request, $response, $arguments)
    {
        $template = Setting::find($arguments['id']);
        foreach ($request->getParsedBody() as $key => $value) {
            $template->{$key} = $value;
        }
        $template->save();
        return $response->withRedirect("/templates/{$template->id}");
    }
}
