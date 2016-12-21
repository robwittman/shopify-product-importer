<?php

namespace App\Middleware;

class ShopifyInstallation
{
    public function __invoke()
    {
        error_log("Shopify invoked");
    }
}
