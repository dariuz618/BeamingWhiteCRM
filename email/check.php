<?PHP 
 
var_dump(checkemail('jing@beamingwhite.com'));


FUNCTION checkemail($email){ 
    LIST($mailbox,$domain) = SPLIT('@',$email,2); 
    $state = 'domain'; 
 
    // find preferred mailserver 
    IF(GETMXRR($domain,$mailhosts,$pref)){ 
        ASORT($pref); 
        FOREACH($pref AS $preferred){ 
            $mailserver =  $mailhosts[KEY($pref)]; 
            BREAK; 
        }     
        $state = "trying mailserver $mailserver"; 
        $state = mailconnect($mailserver,$email); 
     }ELSE{ 
        // no mail exchange found try as host 
        $state = "No MX, trying $domain"; 
        $state = mailconnect($domain,$email); 
     } 
     RETURN $state; 
} 
 
FUNCTION mailconnect($mailserver,$email){     
    $myhostname = $SERVER_NAME; 
    $connection = FSOCKOPEN($mailserver, 25); 
    IF($connection){ 
        $state = "connected to $mailserver"; 
        // Nothing to do with greeting 
        //$smtpgreeting = fread($connection, 512); 
 
        //if($smtpgreeting){ 
        FPUTS($connection, "HELO $myhostname\r\n"); 
        $hello = FGETS($connection, 512); 
        IF($hello){ 
            $state = "chatting to $mailserver: $hello"; 
            FPUTS($connection, "MAIL FROM: <webserver@$myhostname>\r\n");     
            $youok = FGETS($connection, 512); 
            IF($youok){ 
                $state = "chatting to $mailserver: $youok"; 
                FPUTS($connection, "RCPT TO: <$newaddress>\r\n"); 
                $recepient = FGETS($connection, 512); 
                $state = "chatting to $mailserver: $recepient"; 
                IF(EREG('250',$recepient)){ 
                    FPUTS($connection, "QUIT\r\n"); 
                    $deliverable = TRUE; 
                    $state = FALSE; 
                }ELSEIF(EREG('220',$recepient)){ 
                    FPUTS($connection, "QUIT\r\n"); 
                    $deliverable = TRUE; 
                    $state = FALSE; 
                }ELSE{ 
                    $deliverable = FALSE; 
                    $state = "RCPT? $recepient $newaddress";     
                }         
            } 
        }ELSE{ 
            $state = "$mailserver not accepting mail now, please try again."; 
        }         
        //}else{ 
            //$state = 'mailserver not greeting me'; 
            //break; 
        //}             
    }ELSE{ 
        $state = "$mailserver not listening"; 
    } 
    RETURN $state; 
}     
?>