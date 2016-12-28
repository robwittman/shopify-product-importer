<?php

return array(
    'settings' => array(
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => false,
        'db' => array(
            'driver' => 'pgsql',
            'host' => 'postgres',
            'database' => 'shopify',
            'username' => 'postgres',
            'password' => 'password',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => ''
        )
    )
);
