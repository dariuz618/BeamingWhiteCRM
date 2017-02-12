<?php
// include SMTP Email Validation Class
require_once('smtp_validateEmail.class.php');

/*$email = 'jing1@beamingwhite.com';
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

die();*/
   
   $row = 0; $bad = 0; $good = 0;
   
   $fp = fopen('bw.csv', 'w');
   //$SMTP_Validator = new SMTP_validateEmail();
   if (($handle = fopen("bw_groupon.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
         $num = count($data);
         $row++;
         $email =  trim($data[0]);
      //   echo '<pre>';
        // var_dump($email);
        
        $sender = 'contact@beamingwhite.mx';
        // instantiate the class
        
        // turn on debugging if you want to view the SMTP transaction
        //$SMTP_Validator->debug = true;
        // do the validation
        
        // view results
       // echo $email.' is '.($results[$email] ? 'valid' : 'invalid')."\n";
        //echo '<pre>';
        //echo $email;
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $SMTP_Validator = new SMTP_validateEmail();
            $results = $SMTP_Validator->validate(array($email), '');
            if ($results[$email]) {
               // echo $email.'<br>';
                fputcsv($fp, array($email));
                ++$good;
                ob_flush();
                flush(); 
            } else {
                ++$bad;
            }           
        }
         
     }
     fclose($fp);
     echo "total email: $row Total Bad email: $bad Total Good email: $good";
   }
?>

