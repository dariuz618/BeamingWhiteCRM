<?php
   
/**
 * Example 1
 * Validate a single Email via SMTP
 */


// include SMTP Email Validation Class
require_once('smtp_validateEmail.class.php');
/*
// the email to validate
$email = 'Alasria1@hotmail.com';
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
*/
// send email?
/*if ($results[$email]) {
  //mail($email, 'Confirm Email', 'Please reply to this email to confirm', 'From:'.$sender."\r\n"); // send email
} else {
  echo 'The email addresses you entered is not valid';
}*/


   $row = 1; $bad = 0;
   if (($handle = fopen("divine.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
         $num = count($data);
         $row++;
         $email =  trim($data[0]);
         
         $sender = 'customerservice@beamingwhite.com';
        // instantiate the class
        $SMTP_Validator = new SMTP_validateEmail();
        // turn on debugging if you want to view the SMTP transaction
        //$SMTP_Validator->debug = true;
        // do the validation
        $results = $SMTP_Validator->validate(array($email), $sender);
        // view results
        //echo $email.' is '.($results[$email] ? 'valid' : 'invalid')."\n";
       if (!$results[$email]) {
           echo $results[$email].'<br>';
           ++$bad;
       }  
         
         
     }
     echo "total email: $row Total Bad email: $bad";
   }
?>

