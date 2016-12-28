<?php

namespace App;

class CustomException implements \Illuminate\Contracts\Debug\ExceptionHandler
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
