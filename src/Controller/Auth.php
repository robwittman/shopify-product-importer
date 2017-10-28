<?php

namespace App\Controller;

use App\Model\User;
use App\Model\Errors;
use App\Model\Messages;

use Firebase\JWT\JWT;

class Auth
{
    public function __construct($key)
    {
        $this->key = $key;
    }

    public function login($request, $response)
    {
        if ($request->isGet()) {
            return $this->view->render($response, 'auth/login.html');
        }

        $params = $request->getParsedBody();
        error_log(json_encode($params));
        $user = User::where('email', $params['email'])->first();
        if (empty($user)) {
            return $response->withStatus(400)->withJson(array(
                'error' => Errors::INVALID_EMAIL
            ));
        }

        if (!$user->authenticate($params['password'])) {
            return $response->withStatus(400)->withJson(array(
                'error' => "Invalid username or password"
            ));
        }

        $payload = array(
            'exp' => strtotime('+1 hour'),
            'iss' => time(),
            'user' => $user
        );
        $jwt = JWT::encode($payload, $this->key);
        return $response->withJson(array(
            'access_token' => $jwt
        ));
    }
}
