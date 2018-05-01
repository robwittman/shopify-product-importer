<?php

namespace App;

use Illuminate\Contracts\Debug\ExceptionHandler;

class CustomException implements ExceptionHandler
{
    public function report(\Exception $e)
    {
        //
    }

    public function render($request, \Exception $e)
    {
        throw $e;
    }

    public function renderForConsole($output, \Exception $e)
    {
        throw $e;
    }
}
