<?php

if (!function_exists("callShopify")) {
    function callShopify($url, $method = 'GET', $params = array())
    {
        $base = generateUrl();
        $c = curl_init();
        if ($method == "GET") {
            $url = $url . "?" . http_build_query($params);
        } elseif ($method == "POST") {
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

if (!function_exists("checkLogin")) {
    function checkLogin($request, $response, $next)
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['expiration'] > time()) {
            return $next($request, $response);
        }
        session_destroy();
        return $response->withRedirect('/login');
    }
}

if (!function_exists("writeLog")) {
    function writeLog($message)
    {
        return true;
        // error_log($message);
        // if (getenv("ENV") == "dev") {
        //     error_log($message);
        // }
    }
}

if (!function_exists('resizeImage')) {
    /**
     * Resize image - preserve ratio of width and height.
     * @param string $sourceImage path to source JPEG image
     * @param string $targetImage path to final JPEG image file
     * @param int $maxWidth maximum width of final image (value 0 - width is optional)
     * @param int $maxHeight maximum height of final image (value 0 - height is optional)
     * @param int $quality quality of final image (0-100)
     * @return bool
     */
    function resizeImage($sourceImage, $targetImage, $maxWidth = 375, $maxHeight = 700, $quality = 100)
    {
        // Obtain image from given source file.
        if (!$image = @imagecreatefromjpeg($sourceImage))
        {
            if (!$image = @imagecreatefrompng($sourceImage)) {
                return false;
            }
        }

        // Get dimensions of source image.
        list($origWidth, $origHeight) = getimagesize($sourceImage);

        if ($maxWidth == 0)
        {
            $maxWidth  = $origWidth;
        }

        if ($maxHeight == 0)
        {
            $maxHeight = $origHeight;
        }

        // Calculate ratio of desired maximum sizes and original sizes.
        $widthRatio = $maxWidth / $origWidth;
        $heightRatio = $maxHeight / $origHeight;

        // Ratio used for calculating new image dimensions.
        $ratio = min($widthRatio, $heightRatio);

        // Calculate new image dimensions.
        $newWidth  = (int)$origWidth  * $ratio;
        $newHeight = (int)$origHeight * $ratio;

        // Create final image with new dimensions.
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $white);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
        imagejpeg($newImage, $targetImage, $quality);

        // Free up the memory.
        imagedestroy($image);
        imagedestroy($newImage);

        return true;
    }
}

if (!function_exists('cropImage')) {
    function cropImage($source, $destination, $maxWidth = 375, $maxHeight = 700, $quality = 100)
    {
        $image = @imagecreatefromjpeg($source);
        if (!$image) {
            return false;
        }

        list($origWidth, $origHeight) = getimagesize($source);
        if ($maxWidth > $origWidth) {
            $maxWidth = $origWidth;
        }

        if ($maxHeight > $origHeight) {
            $maxHeight = $origHeight;
        }

        $newImage = imagecreatetruecolor($maxWidth, $maxHeight);
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefilledrectangle($newImage, 0, 0, $maxWidth, $maxHeight, $white);
        $widthToRemove = $origWidth - $maxWidth;
        $heightToRemove = $origHeight - $maxHeight;
        $startWidth = $widthToRemove / 2;
        $endWidth = $origWidth - ($widthToRemove / 2);
        $startHeight = $heightToRemove / 2;
        $endHeight = $origHeight - ($heightToRemove / 2);

        /**
         * Add cropped image data
         */
        imagecopyresampled($newImage, $image, 0, 0, $startWidth, $startHeight, $maxWidth, $maxHeight, $maxWidth, $maxHeight);
        /* End cropped image */
        imagejpeg($newImage, $destination, $quality);

        imagedestroy($image);
        imagedestroy($newImage);

        return true;
    }
}

if (!function_exists('resizeImageByPercentage')) {
    function resizeImageByPercentage($source, $destination, $percent)
    {
        // Obtain image from given source file.
        if (!$image = @imagecreatefromjpeg($source))
        {
            return false;
        }

        list($origWidth, $origHeight) = getimagesize($source);
        $newHeight = $origHeight * $percent;
        $newWidth = $origWidth * $percent;
        return resizeImage($source, $destination, $newWidth, $newHeight, 100);
    }
}
