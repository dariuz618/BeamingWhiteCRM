<?    

require_once('smtp_validateEmail.class.php');

   
   $row = 0; $bad = 0; $good = 0;
   
   $fp = fopen('clean_email.csv', 'w');
   //$SMTP_Validator = new SMTP_validateEmail();
   if (($handle = fopen("dirty_email.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
         $num = count($data);
         $row++;
         $email =  trim($data[0]);
      
        
        $sender = 'contact@beamingwhite.mx';
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $SMTP_Validator = new SMTP_validateEmail();
          //  $results = $SMTP_Validator->validate(array($email), $sender);
            
             $result =  $SMTP_Validator->validate(array($email), '');
            
            
            if ($result) {
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