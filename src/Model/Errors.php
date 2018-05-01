<?php

namespace App\Model;

class Errors
{
    const UNAUTHENTICATED = 'In order to use the app, you have to provide Shopify access';
    const UNAUTHORIZED = "You do not have permission to access this area";
    const INVALID_EMAIL = 'Provided email was invalid';
    const INVALID_PASSWORD = 'Provided password was invalid';
}
