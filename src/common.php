<?php

if (!function_exists("callShopify")) {
    function callShopify($url, $method = 'GET', $params = array())
    {
        $base = generateUrl();
        $c = curl_init();
        if ($method == "GET") {
            $url = $url . "?" . http_build_query($params);
        } else if($method == "POST") {
            curl_setopt($c, CURLOPT_POST, 1);
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($params));
        } else {
            curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($params));
        }
        curl_setopt($c, CURLOPT_URL, $base.$url);
        curl_setopt($c, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json"
        ));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($c);
        return json_decode($res);
    }
}

if (!function_exists("generateUrl")) {
    function generateUrl()
    {
        $key = getenv("SHOPIFY_API_KEY");
        $pass = getenv("SHOPIFY_PASSWORD");
        $domain = getenv("MYSHOPIFY_DOMAIN");
        return sprintf("https://%s:%s@%s", $key, $pass, $domain);
    }
}
