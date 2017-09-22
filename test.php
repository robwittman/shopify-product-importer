 <?php

require_once 'vendor/autoload.php';

$client = new Google_Client(array(
    'client_id' => getenv("GOOGLE_OAUTH_CLIENT_ID"),
    'client_secret' => getenv("GOOGLE_OAUTH_CLIENT_SECRET"),
    'redirect_uri' => getenv("GOOGLE_OAUTH_REDIRECT_URI"),
));
$spreadsheetId = '184pNym_COkgQAvEUT_GKSn2wQnCX0s7WsOgERgNtl1g';
$client->setAccessToken('ya29.GlvOBDoD9AcuXXJlvw6lCWkfKY7cUCnwcEkWuq4nN8xLsGIPZm2R1r5TnoDuT6gmHo6mb954xjbxCB_Qx4rIrAvbG5zeZtmqrHKuusnpLmazHZ18gWHLfd2FnvA4');
$service = new Google_Service_Sheets($client);

$range = 'Sheet1!A:J';
$valueRange = new Google_Service_Sheets_ValueRange();
$valueRange->setValues(array(
    'values' => ["a", "b"]
));
// $response = $service->spreadsheets_values->get($spreadsheetId, $range);
$response = $service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, array('valueInputOption' => "RAW"));
var_dump($response);
