<?php

if (!function_exists("callShopify")) {
    function callShopify($url, $method = 'GET', $params = array())
    {
        var_dump($params);
        $c = curl_init();
        if ($method == "GET") {
            $url = $url . "?" . http_build_query($params);
        } else if($method == "POST") {
            curl_setopt($c, CURLOPT_POST, 1);
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($params));
        }
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json"
        ));
        curl_setopt($c, CURLOPT_VERBOSE, 1);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($c);
        return json_decode($res);
    }
}

if (!function_exists("generateUrl")) {
    function generateUrl($session)
    {
        return sprintf("https://%s:%s@%s", $session['shopify_api_key'], $session['shopify_password'], $session['myshopify_domain']);
    }
}
