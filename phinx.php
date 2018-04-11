<?php

$dbString = getenv("DATABASE_URL");
$dbConfig = parse_url($dbString);

$creds = array(
    'environments' => array(
        'default_database' => 'development',
        'development' => array(
            'adapter' => $dbConfig['scheme'] === 'postgres' ? 'pgsql' : 'mysql',
            'host' => $dbConfig['host'],
            'name' => ltrim($dbConfig['path'], '/'),
            'user' => $dbConfig['user'],
            'pass' => $dbConfig['pass'],
            'port' => $dbConfig['port']
        )
    ),
    "paths" => array(
        "migrations" => "db/migrations",
        "seeds" => "db/seeds"
    ),
);
echo json_encode($creds);
return $creds;
