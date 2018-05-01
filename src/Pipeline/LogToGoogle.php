<?php
/**
 * Created by PhpStorm.
 * User: rwittman
 * Date: 4/30/18
 * Time: 8:01 PM
 */

namespace App\Pipeline;


class LogToGoogle
{
    /**
     * @var Payload
     */
    protected $payload;

    public function __invoke(Payload $payload) : Payload
    {
        // Log everything to google ...
        return $payload;
    }
}