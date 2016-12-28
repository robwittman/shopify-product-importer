<?php

$dbString = getenv("DATABASE_URL");
$dbConfig = parse_url($dbString);

return array(
    'environments' => array(
        'default_database' => 'development',
        'development' => array(
            'adapter' => 'pgsql',
            'host' => $dbConfig['host'],
            'name' => ltrim($dbConfig['path'], '/'),
            'user' => $dbConfig['user'],
            'pass' => $dbConfig['pass'],
            'port' => $dbConfig['port']
        )
    )
);
