<?php

require_once(__DIR__ . '/vendor/autoload.php');

use RingCentral\SDK\SDK;
$credentials = require(__DIR__ . '/_credentials.php');



$tokenUrl = $credentials['server'] . '/restapi/v1.0/account/'.$credentials['accountId'].'/extension/'.$credentials['extensionId'].'/call-log';


try {
    $ch = curl_init();

    if (FALSE === $ch)
        throw new Exception('failed to initialize');

    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Authorization: '.$credentials['token_type']. ' ' . $credentials['acces_token'],
      'Accept: application/json'	      
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);


    $response = curl_exec($ch);

    if (FALSE === $response)
        throw new Exception(curl_error($ch), curl_errno($ch));
        
   

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $headerSize);

	curl_close($ch);

    $body = json_encode(json_decode($body, true), JSON_PRETTY_PRINT);

    
    
    // ...process $content now
} catch(Exception $e) {

    $body = sprintf('Curl failed with error #%d: %s',$e->getCode(), $e->getMessage());
	
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>        
    </head>
    <body>
        <h1>RingCentral - Call Log</h1>
		<p>Call Log</p>
        <pre style="background-color:#efefef;padding:1em;overflow-x:scroll"><?php echo isset($body) ? $body : '';?></pre>

       
    </body>
</html>



