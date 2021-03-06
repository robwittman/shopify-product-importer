<?php

namespace App\Controller;

use App\Model\User;
use App\Model\Errors;
use App\Model\Messages;
class Auth
{
    public function __construct($view, $flash)
    {
        $this->view = $view;
        $this->flash = $flash;
    }

    public function login($request, $response)
    {
        if ($request->isGet()) {
            return $this->view->render($response, 'auth/login.html');
        }

        $params = $request->getParsedBody();

        $user = User::where('email', $params['email'])->first();
        if (empty($user)) {
            return $this->view->render($response, 'auth/login.html', array(
                'error' => Errors::INVALID_EMAIL
            ));
        }

        if (!$user->authenticate($params['password'])) {
            return $this->view->render($response, 'auth/login.html', array(
                'error' => Errors::INVALID_PASSWORD
            ));
        }

        // Our login was correct, so let's start a session!
        $_SESSION['uid'] = $user->id;
        $_SESSION['email'] = $user->email;
        $_SESSION['expiration'] = strtotime('+2 hours');
        $_SESSION['role'] = $user->role;

        $this->flash->addMessage('message', Messages::LOGIN_SUCCESSFUL);
        return $response->withRedirect('/products');
    }

    public function logout($request, $response)
    {
        session_destroy();
        return $response->withRedirect('/auth/login');
    }
}
