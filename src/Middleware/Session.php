<?php

namespace App\Middleware;

use App\Model\User;

class Session
{
    public function __construct()
    {

    }

    public function __invoke($request, $response, $next)
    {
        if (isset($_SESSION['uid'])) {
            $user = User::find($_SESSION['uid']);
            if (empty($user)) {
                error_log("destroying session");
                session_destroy();
                return $response->withRedirect('/auth/login');
            }
            $request = $request->withAttribute('user', $user);
        }
        // var_dump($request);
        return $next($request, $response);
    }
}
