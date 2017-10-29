<?php

use \Symfony\Component\DependencyInjection\Reference;
use \Symfony\Component\DependencyInjection\Parameter;

$container->setParameter('database.url', getenv('DATABASE_URL'));
$container->setParameter('jwt.secret', getenv("JWT_SECRET"));
$container->setParameter('creds.google.client_id', getenv("GOOGLE_OAUTH_CLIENT_ID"));
$container->setParameter('creds.google.client_secret', getenv("GOOGLE_OAUTH_CLIENT_SECRET"));
$container->setParameter('creds.google.redirect_uri', getenv("GOOGLE_OAUTH_REDIRECT_URI"));
$container->setParameter('database.config', parse_url($container->getParameter('database.url')));
$container->setParameter('s3.access_key', getenv("AWS_ACCESS_KEY"));
$container->setParameter('s3.secret_key', getenv("AWS_ACCESS_SECRET"));
$container->register('s3.credentials', \Aws\Credentials\Credentials::class)
    ->setArguments(array(
        '%s3.access_key%',
        '%s3.secret_key%'
    ));
$container->register('s3', \Aws\S3\S3Client::class)
    ->setArguments(array(
        array(
            'version' => 'latest',
            'region' => 'us-east-1',
            'credentials' => new Reference('s3.credentials')
        )
    ));
$container->register('controller.shops', \App\Controller\Shops::class);
$container->register('controller.products', \App\Controller\Products::class);
$container->register('controller.users', \App\Controller\Users::class);
$container->register('controller.catalog', \App\Controller\Catalogs::class);
$container->register('controller.auth', \App\Controller\Auth::class)->setArguments(array('%jwt.secret%'));
$container->register('controller.access', \App\Controller\Access::class);
$container->register('controller.google', \App\Controller\Google::class);
$container->register('controller.files', \App\Controller\Files::class)
    ->setArguments(array(
        new Reference('s3')
    ));

$container->register('middleware.authorization', \App\Middleware\Authorization::class);
$container->register('middleware.shop_access', \App\Middleware\ShopAccess::class);
$container->register('google.client', Google_Client::class)
    ->setArguments(array(
        array(
            'client_id' => '%creds.google.client_id%',
            'client_secret' => '%creds.google.client_secret%',
            'redirect_uri' => '%creds.google.redirect_uri%'
        )
    ))
    ->addMethodCall('setApplicationName', array('Product Importer'))
    ->addMethodCall('setScopes', array(implode(' ', array(
        Google_Service_Sheets::SPREADSHEETS
    ))))
    ->addMethodCall('setAccessType', array('offline'));
