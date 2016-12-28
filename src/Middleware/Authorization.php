<?php

namespace App\Middleware;

class Authorization
{
    public function __construct()
    {

    }

    public function __invoke($request, $response, $next)
    {
        if (!isset($_SESSION['uid'])) {
            return $response->withRedirect('/auth/login');
        }

        return $next($request, $response);
    }
}
