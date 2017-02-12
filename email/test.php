<?php
// include SMTP Email Validation Class
require_once('smtp_validateEmail.class.php');

$email = 'support@divineteethwhitening.com';
// an optional sender
$sender = 'customerservice@beamingwhite.com';
// instantiate the class
$SMTP_Validator = new SMTP_validateEmail();
// turn on debugging if you want to view the SMTP transaction
//$SMTP_Validator->debug = true;
// do the validation
$results = $SMTP_Validator->validate(array($email), $sender);
// view results
echo $email.' is '.($results[$email] ? 'valid' : 'invalid')."\n";


?>