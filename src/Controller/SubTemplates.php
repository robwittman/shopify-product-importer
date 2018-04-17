<?php

namespace App\Controller;

use App\Model\SubTemplate;

class SubTemplates
{
    public function __construct($view, $flash)
    {
        $this->view = $view;
        $this->flash = $flash;
    }

    public function create($request, $response, $arguments)
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

    public function delete($request, $response, $arguments)
    {
        $template = $arguments['id'];
        $subTemplate = SubTemplate::find($arguments['subId']);
        $subTemplate->delete();
        $this->flash->addMessage('message', 'Sub Template deleted');
        return $response->withRedirect("/templates/{$template}");
    }

    public function enable($request, $response, $arguments)
    {
        $template = $arguments['id'];
        $subTemplate = SubTemplate::find($arguments['subId']);
        $subTemplate->enabled = true;
        $subTemplate->save();
        $this->flash->addMessage("message", 'Sub Template enabled');
        return $response->withRedirect("/templates/{$template}");
    }

    public function disable($request, $response, $arguments)
    {
        $template = $arguments['id'];
        $subTemplate = SubTemplate::find($arguments['subId']);
        $subTemplate->enabled = false;
        $subTemplate->save();
        $this->flash->addMessage("message", 'Sub Template enabled');
        return $response->withRedirect("/templates/{$template}");
    }

    public function update($request, $response, $arguments)
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

    public function default($request, $response, $arguments)
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
