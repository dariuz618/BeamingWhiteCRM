<?php

require_once(__DIR__ . '/vendor/autoload.php');

use RingCentral\SDK\SDK;

session_start();

$credentials = require(__DIR__ . '/_credentials.php');






function processCode()
{
	$credentials = require(__DIR__ . '/_credentials.php');

    if(!isset($_GET['code'])) {
        return;
    }
    $authCode = $_GET['code'];

    $tokenUrl = $credentials['server'] . '/restapi/oauth/token';
    
    $values = array(
        'grant_type'   => 'authorization_code',
        'code'         => $authCode,
        'redirect_uri' => $credentials['redirect_uri']
    );

    $apiKey = base64_encode($credentials['appKey'] . ':' . $credentials['appSecret']);

   try {
	    $ch = curl_init();

	    if (FALSE === $ch)
	        throw new Exception('failed to initialize');

	    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	      'Authorization: Basic' . $apiKey,
	      'Accept: application/json',
	      'Content-Type: application/x-www-form-urlencoded'
	    ));
	    curl_setopt($ch, CURLOPT_POST, count($values));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($values));
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

	    //Store the response in PHP Session Object
	    $_SESSION['response'] = $body;

	    return $body;
	    // ...process $content now
	} catch(Exception $e) {

	    $body = sprintf('Curl failed with error #%d: %s',$e->getCode(), $e->getMessage());
		  return $body;
	}

}

$result= processCode();

session_write_close();

?>

