<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Authorization
{
    public function __construct()
    {

    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        if (!isset($_SESSION['uid'])) {
            return $response->withRedirect('/auth/login');
        }

        return $next($request, $response);
    }
}
