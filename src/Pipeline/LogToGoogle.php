<?php

namespace App\Pipeline\Product;

use App\Pipeline;

class LogToGoogle
{
    public function __construct(Google_Client $client)
    {
        $this->client = $client;
    }

    public function __invoke($payload)
    {
        error_log("Google logging not enabled...");
        return $payload;
    }
}
