<?php

class Application_Model_Crm{
    # Object level variables

    protected $_db;

    /**
     * Class constructor - Setup the DB connection
     */
    public function __construct() {
        # get handle on our database object
        $this->_db = Zend_Registry::get('bwbusiness');     
    }
    
    public function createEvent($data) {
        try {              
            $insert =  $this->_db->insert('events', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
   } 
   
   public function getEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND (user_id = '$userId' OR public = 1)");
       return $events;
   }
   
   public function getEvent($eventId) {
       return $this->_db->fetchRow("SELECT * from events WHERE event_id = '$eventId'");       
   }
   
   public function getUpcomingEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND ADDDATE(start, INTERVAL 30 DAY) AND (user_id = '$userId' OR public = 1)
            AND events.start >= DATE(NOW())");
       return $events;
   }
   
    public function updateEvent(array $data, $eventId, $followUp = NULL) {
        try {                 
         
           //check public value                      
           if (!$followUp) {
               $row = $this->_db->fetchRow("SELECT event_id from events WHERE user_id = '{$data['user_id']}' AND event_id = $eventId");          
               if(!$row) {
                    return 'Permission Error';
               }
           }
           $update = $this->_db->update('events', $data, $this->_db->quoteInto("event_id = ?", $eventId));                      
          
           return $update;
           
           
        } catch (Exception $e) {           
            return $e->getMessage();
        }        
        return;        
    }
    
    public function fileCategories()
    {
        return array('business' => 'Business Documents',            
            'graphics'=> 'Graphics',
            'marketing' => 'Marketing Material',
            'product' => 'Product Information'            
            );
    }
    
    public function saveFiles($data) 
    {
        try {              
            $insert =  $this->_db->insert('files', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
    }
    public function findUsers($category, $value)
    {     
     

     if ($category == 'all') {        
        $term = addslashes($value);
            $query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type, status, account_user.parent_user, MATCH(firstname, lastname, email, businessname, contactphone, contactphone2, businessphone) AGAINST ('" .
                       '"' . $term . '"' . "' IN BOOLEAN MODE) AS score
            from user left join account_user on user.id = account_user.user
            WHERE type != 'Internal' AND MATCH(firstname, lastname, email, businessname, contactphone, contactphone2, businessphone) AGAINST('" .
                       '"' . $term . '"' . "' IN BOOLEAN MODE) ) a  
            left join user u on u.id = a.parent_user ORDER BY score DESC LIMIT 0,100";
            $result = $this->_db->fetchAll($query);
            if ($result) {
                return $result;
            }
            $parts = preg_split('/\s+/', $value);
            foreach ($parts as $part) {
                $term .= '+' . $part . ' ';
            }
            $query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type, status, account_user.parent_user, MATCH(firstname, lastname, email, businessname, contactphone, contactphone2, businessphone) 
            AGAINST ('$term' IN BOOLEAN MODE) AS score
            from user left join account_user on user.id = account_user.user
            WHERE type != 'Internal' AND MATCH(firstname, lastname, email, businessname, contactphone, contactphone2, businessphone) 
            AGAINST('$term' IN BOOLEAN MODE) ) a  
            left join user u on u.id = a.parent_user ORDER BY score DESC LIMIT 0,100";
            return $this->_db->fetchAll($query);
     } else { 
           if ($category == 'fullname') {
           $name = preg_split('/\s+/', $value, 2);                      
           $firstName = $this->_db->quote("%$name[0]%");
               if (isset($name[1])) {
                   $lastName = $this->_db->quote("%$name[1]%");
               }
           } else {           
               $value = $this->_db->quote("%$value%");
           }
            if ($category == 'email') {
                $filter = "email like $value OR email2 like $value";
            } else if ($category == 'businessname') {
                $filter = "businessname like $value";
            } else if ($category == 'firstname') {
                $filter = "firstname like $value";
            } else if ($category == 'lastname') {
                $filter = "lastname like $value";
            } else if ($category == 'contactphone') {
                $filter = "contactphone like $value OR contactphone2 like $value";
            } else if ($category == 'fullname') {               
                $filter = "firstname like $firstName";
                if (isset($lastName)) {
                    $filter .= " AND lastName like $lastName";
                }
            }
            $query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user
            from user left join account_user on user.id = account_user.user
            WHERE type != 'Internal' AND ($filter) ) a  
            left join user u on u.id = a.parent_user ORDER BY businessname ASC LIMIT 0,100";
        }
        

     $result = $this->_db->fetchAll($query);
     return $result;
    }            
    
    public function getUsersTotal($type, $parent_user=NULL, $potential=NULL, $source=NULL, $soldBy=NULL, $lastAttempt=NULL) {  
               
        $filter = '';
        if ($parent_user) {
            $filter = " AND account_user.parent_user = '$parent_user' ";
        } elseif($soldBy != 'Magic White') {
            $filter = " AND account_user.parent_user != '10318' ";
        }
        if ($potential) {
            $filter .= " AND user.potential = '$potential' ";
        }
        if ($source) {
            $filter .= " AND user.source = '$source' ";
        }
        if ($soldBy) {
            $filter .= " AND user.soldby = '$soldBy' ";
        }
      
        $contact = '';      
        if ($lastAttempt) {
            if($lastAttempt == 10) {              
                $contact = " AND contactDays >=1 AND contactDays < 10";
            }elseif($lastAttempt == 30) {
                $contact = " AND contactDays >=10 AND contactDays < 30";
            }elseif($lastAttempt == 60) {
                $contact = " AND contactDays >=30 AND contactDays < 60";
            }elseif($lastAttempt == 90) {
                $contact = " AND contactDays >=60 AND contactDays < 90";
            }elseif($lastAttempt == 91) {
                $contact = " AND contactDays >=91";
            }
            $query = "select count(user.id) as total from user
            left join account_user on user.id = account_user.user 
            inner join (SELECT DATEDIFF(NOW(), max(enter_time)) AS contactDays, 
            user_id
            FROM `user_notes`            
            group by user_id) b on b.user_id = user.id 
            WHERE user.status != 'Disabled' AND user.type = '$type' $filter $contact";
        } else {       
            $query = "SELECT count(id) as total from `user` left join account_user on user.id = account_user.user
            WHERE user.status != 'Disabled' and type = '$type' $filter";
            
        }       
        $result = $this->_db->fetchRow($query);
        return $result;
    }
    public function getSalesUsers () {       
        $sql = "SELECT id, concat(firstname, ' ', lastname) as name, email FROM user WHERE (role like 'sales%' 
                OR firstname = 'Luis' OR firstname = 'Heather' OR firstname = 'Loli' OR firstname = 'Bridgette')
                and status = 'Active' AND type = 'Internal' ORDER BY firstname";
        $results = $this->_db->fetchAll($sql);
        return $results;
    }
    public function getSalesRep ($userId) {
        $result = $this->_db->fetchRow("SELECT parent_user, concat(firstname, ' ', lastname) as name, user.email from account_user 
            LEFT JOIN user ON account_user.parent_user = user.id 
            WHERE account_user.user = '$userId' ");
        return $result;
    }       
    
    public function assign_account_user($parent, $child){
        
        //if exist, update, else add
        $accountUser = $this->_db->fetchRow("select * from account_user where user = '$child'");                
        $data = array('parent_user' => (int)$parent, 'user' => (int)$child);        
       
        if($accountUser && $accountUser['parent_user'] != $parent) {             
            //find the original rep
           $originalRep = self::getSalesRep($child);            
           $change =  $this->_db->query("UPDATE  account_user set parent_user = '$parent', user='$child' WHERE account_user_id = '{$accountUser['account_user_id']}'");          
           $newRep = self::getSalesRep($child);
           
           //send notification to the new assinee
            if ($newRep['parent_user'] != 1) {
                $mail = new Zend_Mail();
                $message = "Dear {$newRep['name']},<br><br>
                        A new contact has been assigned to you. Please click <a href='http://www.beamingwhite.mx/crm/customer/id/$child'>here</a> to see the detail.";
                $message .= "<br><br>Thank you, <br><br>
                         Beaming White CRM";
                $mail->setBodyHTML($message);
                $mail->setFrom('no_reply@beamingwhite.com', 'New Contact Assigned');
                $mail->addTo($newRep['email'], $newRep['name']);
                $mail->setSubject("Notification - contact assigned");
                $mail->send();
            }
           $auth = Zend_Auth::getInstance()->getIdentity();
           
           if($auth) {
               $log = array('user_id' => $child, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
               $log['notes'] = 'Change sales rep from '. $originalRep['name']. ' to '. $newRep['name'];           
               self::savenotes($log);
           }
           
        } elseif(!$accountUser) {
           $change =  $this->_db->insert('account_user', $data);           
        }        
        return $change;
    }
      
    
    
    public function editUser(array $data, $id) {
        try {
            if (isset($data['parentAccountID'])) {
                $parentId = $data['parentAccountID'];
                unset($data['parentAccountID']);
                $change = self::assign_account_user($parentId, $id);                
            }            
            $update = $this->_db->update('user', $data, $this->_db->quoteInto("id = ?", $id));  
            
            $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
            $path = $config->toArray();
                       
            $writer = new Zend_Log_Writer_Stream($path['userLogPath'] . date("Ymd") . '.txt');
            $logger = new Zend_Log($writer);   
            $author = '';
            if (Zend_Auth::getInstance()->getIdentity()) {
                $author = Zend_Auth::getInstance()->getIdentity()->firstname.' '.Zend_Auth::getInstance()->getIdentity()->lastname;
            }
            $data['user_id'] = $id;
            $logger->info ($author. '--'.serialize($data));
            
            return $update;
        } catch (Exception $e) {         
            return $e->getMessage();
        }        
        return;        
    }
    public function deleteUser($userId) {     
        
        $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
        $path = $config->toArray();
              
        $user = self::getUser($userId);
        $writer = new Zend_Log_Writer_Stream($path['userDeleteLogPath'] . date("Ymd") . '.txt');
        $logger = new Zend_Log($writer);     
        $logger->info (Zend_Auth::getInstance()->getIdentity()->firstname. '--'.serialize($user));
        //delete user profile from ANet
        $profile = $this->_db->fetchRow("SELECT profile_id FROM user_profile WHERE user_id = '$userId'");
        if ($profile && $profile['profile_id']) {
            $request = new Application_Service_AuthorizeNetCIM;            
            $request->deleteCustomerProfile($profile['profile_id']);            
        }        
        $this->_db->delete('user_profile', $this->_db->quoteInto("user_id = ?", $userId));
        $this->_db->delete('user_address', $this->_db->quoteInto("user_id = ?", $userId));
        $this->_db->delete('user_billing', $this->_db->quoteInto("user_id = ?", $userId));
        $this->_db->delete('user_notes', $this->_db->quoteInto("user_id = ?", $userId));
        $this->_db->delete('account_conversion', $this->_db->quoteInto("user_id = ?", $userId));
        $this->_db->delete('account_user', $this->_db->quoteInto("user = ?", $userId));
        $this->_db->delete('user', $this->_db->quoteInto("id = ?", $userId));
        return true;
    }
    public function deleteAddress ($id) {
        $data['active'] = 0;        
        return $this->_db->update('user_address', $data, $this->_db->quoteInto("address_id = ?", $id));        
    }
    public static function getLeadSource() {
          return array (            
            "Cold Call" => 'Cold Call',
            "Contact Form" => 'Contact Form',
            "Registration" => 'Registration', 
            "Phone Call" => 'Phone Call',
            "Email" => 'Email',
            "Zoho" => 'Zoho',            
            "Other" => 'Other'
            );
    }
     public static function getSource() {        
         return array('Internet'=>'Internet Search',
            'Outside Sales'=>'Outside Sales',
            'Referred'=>'Referred',            
            'Social Media'=>'Social Media',
            'Tradeshow' => 'Trade Show',
            'Other' => 'Other'
            );
    }
     public function getTodayEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end,customer_id, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND (user_id = '$userId' OR public = 1)
            AND DATE(start) = DATE(NOW()) ORDER BY start ASC, event_id");
       return $events;
   }
   //set 10 mins ahead of time, +/-2 mins variation
   public function getAlertEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND
            start BETWEEN (NOW() - INTERVAL 12 HOUR) AND (NOW() + INTERVAL 12 MINUTE)
            AND (user_id = '$userId' OR public = 1) AND popup_alert IS NULL
            ORDER BY start ASC, event_id");
       return $events;
   }
    
   public static function getAccountType() {
        return array (
            "Lead" => 'Lead',
            "Prospect" => 'Prospect',
            "Account" => 'Account'
           );
    }
    public static function getCustomerType() {
        return array (
            "Business" => 'Business',
            "Individual" => 'Individual',            
           );
    }
    public static function getPaymentOptions() {
        return array (
            "card" => 'Credit Card',
            "paypal" => 'Paypal',
            "wire" => 'Wire',
            "wu" => "Western Union",
            "mg" => "MG"            
           );
    }
    public static function getSoldByOptions() {
        return array (          
            'Beaming White' => 'Beaming White',
            'Cliona' => 'Cliona',
            'Cool Smart Product' => 'Cool Smart Product',
            'Magic White' => 'Magic White',
            'Sapient Dental' => 'Sapient Dental',
            'Teeth Whitening Technology' => 'Teeth Whitening Technology'            
           );
    }    
    
    public static function getAccountStatus() {
        return array('Pending'=>'Pending', 'Disabled'=>'Disabled', 'Active'=>'Active');
    }
    public static function getAccountPotential() {        
        return array('Hot'=>'HOT(ready to buy)', 'Warm'=>'Warm (maybe soon)', 'Cool'=>'Cool (future maybe)',
            'Cold'=>'Cold (unlikely)', 'Big'=>'BIG (Min $5,000)', 'Huge'=>'HUGE (Min. $10,000)');
    }
    
    public function getCountries() {
        $countries = $this->_db->fetchAll("SELECT name, iso_code_2 FROM country WHERE status = 1 ORDER BY name");
        return $countries;
    }
    
    public function getRegions($country) {
        $regions = $this->_db->fetchAll("SELECT zone.code, zone.name FROM `zone` left join country on zone.country_id = country.country_id where country.iso_code_2 = '$country' order by zone.name");
      
        if (!$regions) {
            $regions = $this->_db->fetchAll("SELECT zone.code, zone.name FROM `zone` left join country on zone.country_id = country.country_id where country.iso_code_2 = 'US' order by zone.name");
        }        
        return $regions;
    }
    
    public function getCountryName($iso2) {
        return $this->_db->fetchRow("SELECT name from country WHERE iso_code_2  = '$iso2'");
    }
    
    public function getZoneName($code, $country) {
        return $this->_db->fetchRow("SELECT zone.name FROM zone left join country on zone.country_id = country.country_id where 
            country.iso_code_2 = '$country' AND zone.code='$code'");
    }
        
    public static function gen_password() {                
        $characters = '#@!$%ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvqxyz01234567890';
        $string ='';
               
        for ($p = 0; $p < 8; $p++) {
            $string .= $characters[mt_rand(1, strlen($characters)-1 )];
        }
        return $string;        
    }
    
    
    public static function getBusinessType() {
        return  array('Dentist' => 'Dentist', 'Distributor'=>'Distributor', 'Individual'=>'Individual', 
            'Mobile'=>'Mobile', 'Planet Beach'=> 'Planet Beach', 'Retail'=>'Retail', 'Stationary'=>'Salon/Tanning Salon/Spa/Clinic/Kiosk', 'Other'=>'Other');
    }
    
    public function getEuTraining($userId) {
       return $this->_db->fetchRow("SELECT * from eu_training WHERE user_id = '$userId' AND active = 1");
    }
    public function setEuTraining($userId, $active) {
        $euTraining = $this->_db->fetchRow("SELECT * from eu_training WHERE user_id = '$userId'");
        if ($euTraining){ //is exissting,            
            $set = $this->_db->update('eu_training', array('active'=>$active), $this->_db->quoteInto("id = ?", $euTraining['id']));
        } else {
            $set =  $this->_db->insert('eu_training', array('user_id' => $userId, 'active'=>1));
        }
        return $set;
    }
    
    public function getPaymentProfiles($userId) {
        return $this->_db->fetchAll("SELECT * FROM user_profile WHERE user_id = '$userId' AND active = 1");
    }
    public function getPaymentProfileById($user_profile_id) {
        return $this->_db->fetchRow("SELECT * FROM user_profile WHERE user_profile_id = '$user_profile_id'");
    } 
    public function saveProfile ($data) {
         $data['action_time'] = date("Y-m-d H:i:s");
         try {     
            $check = $this->_db->fetchRow("SELECT user_profile_id FROM user_profile WHERE payment_profile_id = '{$data['payment_profile_id']}'");              
            if (!empty($check)) {
                $this->_db->update('user_profile', $data, $this->_db->quoteInto("user_profile_id = ?", $check['user_profile_id']));
                return $check['user_profile_id'];
            }
            $insert =  $this->_db->insert('user_profile', $data);
            return $insert;                       
        } catch (Exception $e) {           
            echo $e->getMessage();
            return false;
        }
        return false;
    }
    
    public function getCardType($ccNum) {
        if (preg_match("/^5[1-5][0-9]{14}$/", $ccNum))                
                return "MasterCard";
 
        if (preg_match("/^4[0-9]{12}([0-9]{3})?$/", $ccNum))
                return "Visa";
 
        if (preg_match("/^3[47][0-9]{13}$/", $ccNum))
                return "American Express";
         
        if (preg_match("/^6011[0-9]{12}$/", $ccNum))
                return "Discover";
        
         if (preg_match("/^3(0[0-5]|[68][0-9])[0-9]{11}$/", $ccNum))
                return "Diners Club";
 
        if (preg_match("/^(3[0-9]{4}|2131|1800)[0-9]{11}$/", $ccNum))
                return "JCB";
    }
    public function deletePaymentProfile ($id) {
        $data['active'] = 0;
        $data['action_time'] = date("Y-m-d H:i:s");
        return $this->_db->update('user_profile', $data, $this->_db->quoteInto("user_profile_id = ?", $id));        
    }
    public function saveExam($data, $userId) {
        $euTraining = $this->_db->fetchRow("SELECT * from eu_training WHERE user_id = '$userId'");
        if ($euTraining){ //is exissting,            
            $set = $this->_db->update('eu_training', $data, $this->_db->quoteInto("id = ?", $euTraining['id']));
        }        
    }
    public function saveAddress ($data) {          
        try {       
           
            //$check = $this->_db->fetchRow("SELECT address_id FROM user_address WHERE address_id = '{$data['address_id']}'");       
            //if (!empty($check)) {
            if (isset($data['address_id'])) {
                $this->_db->update('user_address', $data, $this->_db->quoteInto("address_id = ?", $data['address_id']));
                return $data['address_id'];
            }                
            //}
            $insert =  $this->_db->insert('user_address', $data);
            return $this->_db->lastInsertId();                       
        } catch (Exception $e) {   
           // echo $e->getMessage();
            return $e->getMessage();
        }
        return; 
    }
    
     public function saveBillingAddress (array $data, $id) {          
       try {
           $address = $this->_db->fetchRow("SELECT address_id from user_billing WHERE user_id = '$id'");
           if ($address) {
               $update = $this->_db->update('user_billing', $data, $this->_db->quoteInto("user_id = ?", $id));
           } else {
               $data['user_id'] = $id;
               $update =  $this->_db->insert('user_billing', $data);
           }      
           return $update;
        } catch (Exception $e) {
          //  echo $e->getMessage();
           
            return $e->getMessage();
        }        
        return;
    }
    
     public function saveContact(array $data) {
        $data['ip'] = $_SERVER['REMOTE_ADDR'];
        $data['salt'] = md5($this->_getSalt());
        $data['password'] = sha1($data['password'] . $data['salt']);
       
        try {
            //default BW as the parent account            
            $rep = isset($data['parent_user'])?$data['parent_user']:1;
            unset($data['parent_user']);
            $data['firstname'] = ucwords(strtolower($data['firstname']));
            $data['lastname'] = ucwords(strtolower($data['lastname']));
            $insert = $this->_db->insert('user', $data);     
            $userId = $this->_db->lastInsertId();
            
            self::assign_account_user($rep, $userId);
            //create FM account
           // self::_createFmUser($data);
            //log raw data
            $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
            $path = $config->toArray();
            $writer = new Zend_Log_Writer_Stream($path['userLogPath'] . date("Ymd") . '.txt');
            $logger = new Zend_Log($writer);
            $author = '';
            if (Zend_Auth::getInstance()->getIdentity()) {
                $author = Zend_Auth::getInstance()->getIdentity()->firstname.' '.Zend_Auth::getInstance()->getIdentity()->lastname;
            }
            $logger->info ($author. '--'.serialize($data));
            
            return $userId;                       
        } catch (Exception $e) {                       
            return $e->getMessage();
        }        
    }
        
    private static function _getSalt() {
        $salt = '';
        for ($i = 0; $i < 50; $i++) {
            $salt .= chr(rand(33, 126));
        }
        return $salt;
    }
    
    public function getUserFiles($userId, $cat) {        

      $files = $this->_db->fetchAll("select * from files where category = '$cat' AND (userType = 'All'  
          OR userType in (select case when businesstype = 'Dentist' then 'Dentist' else 'Non-Dentist' end 
          filetype from user where id = '$userId')) ORDER BY uploadTime DESC");   
      return $files;
    }
    
     public function getUsersPage ($type, $sort, $order, $start, $end, $parent_user = null, $potential = null, $source = null, $soldBy = null, $lastAttempt = null) {    
        $filter = '';
        if ($parent_user) {
            $filter = " AND account_user.parent_user = '$parent_user' ";
        } elseif($soldBy != 'Magic White') {
            $filter = " AND account_user.parent_user != '10318' ";
        }
        if ($potential) {
            $filter .= " AND user.potential = '$potential' ";
        }
        if ($source) {
            $filter .= " AND user.source = '$source' ";
        }
        if ($soldBy) {
            $filter .= " AND user.soldby = '$soldBy' ";
        }
      
        if ($sort == 'show_time') {
            $sort = 'created_time';
        }
        $contact = '';  $lastContact = '';                
        if ($lastAttempt) {
            if($lastAttempt == 10) {              
                $contact = " AND CASE WHEN contactDays IS NULL
                    THEN DATEDIFF(NOW(), user.created_time ) >= 1 AND DATEDIFF(NOW() , user.created_time ) < 10
                    ELSE contactDays >=1 AND contactDays < 10 END";                 
            }elseif($lastAttempt == 30) {
                $contact = " AND CASE WHEN contactDays IS NULL
                    THEN DATEDIFF(NOW(), user.created_time ) >=10 AND DATEDIFF(NOW() , user.created_time ) < 30
                    ELSE contactDays >=10 AND contactDays < 30 END";                
            }elseif($lastAttempt == 60) {
                $contact = " AND CASE WHEN contactDays IS NULL
                    THEN DATEDIFF(NOW(), user.created_time ) >=30 AND DATEDIFF(NOW() , user.created_time ) < 60
                    ELSE contactDays >= 30 AND contactDays < 60 END";                 
            }elseif($lastAttempt == 90) {
                $contact = " AND CASE WHEN contactDays IS NULL
                    THEN DATEDIFF(NOW(), user.created_time ) >=60 AND DATEDIFF(NOW() , user.created_time ) < 90
                    ELSE contactDays >=60 AND contactDays <90 END";                 
            }elseif($lastAttempt == 91) {
                $contact = " AND CASE WHEN contactDays IS NULL
                    THEN DATEDIFF(NOW(), user.created_time ) >= 91
                    ELSE contactDays >=91 END";                 
            }          
            
            $query = "select concat(u.firstname, ' ', LEFT(u.lastname , 1) ) as rep, user.id, user.businessname, user.contactphone, 
        user.firstname, user.lastname, user.email, user.created_time, user.state, user.country,
                DATE_FORMAT(user.created_time,'%m/%d/%y   %h:%i %p') as show_time, 
                account_user.parent_user, user.potential,contactDays,               
                
                case when note_time = user.created_time THEN NULL ELSE enter_time END enter_time
                from user left join account_user on user.id = account_user.user 
                and user.status != 'Disabled' AND user.type = '$type' $filter
        left join user u on u.id = account_user.parent_user 
        left join (SELECT DATE_FORMAT(max(enter_time),'%m/%d/%y   %h:%i %p') as enter_time,
                max(enter_time) as note_time,
                DATEDIFF(NOW(), max(enter_time)) AS contactDays, user_id 
                FROM `user_notes` group by user_id) b on b.user_id = user.id 
        WHERE user.status != 'Disabled' AND user.type = '$type' $filter $contact
            ORDER BY $sort $order LIMIT $start, $end";     
                       
        } 
                
         if (!$lastAttempt) {
           
            $ids = $this->_db->fetchAll("SELECT id from user 
                    left join account_user on account_user.user = user.id
                    WHERE user.status != 'Disabled' AND user.type = '$type' $filter $contact
                    ORDER BY $sort $order LIMIT $start, $end
                    ");               
           
            if(!$ids) return;

            $thisIds = ''; $index = 0;
            foreach ($ids as $id) {
                $thisIds[$index] = $id['id'];
                ++$index;
            }
            $finalIds = "'" . implode("','", $thisIds) . "'"; 

            $query = "select concat(u.firstname, ' ', LEFT(u.lastname , 1) ) as rep, user.id, user.businessname, user.contactphone, 
            user.firstname, user.lastname, user.email, user.created_time,  user.state, user.country,
                    DATE_FORMAT(user.created_time,'%m/%d/%y   %h:%i %p') as show_time, 
                    account_user.parent_user, user.potential,contactDays,               

                    case when note_time = user.created_time THEN NULL ELSE enter_time END enter_time
                    from user left join account_user on user.id = account_user.user                 
            left join user u on u.id = account_user.parent_user 
            left join (SELECT DATE_FORMAT(max(enter_time),'%m/%d/%y   %h:%i %p') as enter_time,
                    max(enter_time) as note_time,
                    DATEDIFF(NOW(), max(enter_time)) AS contactDays, user_id 
                    FROM `user_notes` 
                    WHERE user_id in ($finalIds) group by user_id) 
                    b on b.user_id = user.id 
            WHERE user.status != 'Disabled' AND user.type = '$type' $filter $contact
                ORDER BY $sort $order LIMIT $start, $end";
                      
         }
        
         
        $result = $this->_db->fetchAll($query);        
        return $result;
    }

    public function getUser($id = null) {
        $user = $this->_db->fetchRow("SELECT user.*, account_user.parent_user from user left join account_user on account_user.user = user.id             
                where user.id = '$id'");        
        return $user?$user:false;        
    }
   
    public function getUserByEmail($email = null) {
        $user = $this->_db->fetchRow("SELECT * from user where email = '$email'");        
        return $user?$user:false;
    }
    public function savenotes($data) {
        try {              
            $insert =  $this->_db->insert('user_notes', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
    }
     public function getPreviousNext($id, $type, $link) {
        $action = $link == 'prev'?'>':'<';   
        $order = $link == 'prev'?'ASC':'DESC';   
        
        //previous
        $query = "SELECT user.*, account_user.parent_user from user left join account_user on account_user.user = user.id 
            where created_time $action= (SELECT created_time FROM user WHERE id = '$id') AND id !='$id' AND type = 'Lead' 
                AND status != 'Disabled' order by created_time $order limit 1";
       
        $user = $this->_db->fetchRow($query);       
        return $user?$user:false;
    }
    public function getLast() {
        $query = "SELECT user.*, account_user.parent_user from user left join account_user on account_user.user = user.id 
            where status != 'Disabled' order by created_time DESC, id DESC limit 1";       
        $user = $this->_db->fetchRow($query);
        return $user?$user:false;
    }
    
    public function getNotes($userId, $type="note") {        
        return $this->_db->fetchAll("SELECT * FROM user_notes WHERE user_id = '$userId' and type='$type' order by enter_time DESC ");         
    }
     public function getBillingAddress($userId) {
        return $this->_db->fetchRow("SELECT * FROM user_billing WHERE user_id = '$userId' AND active = 1");
    }    
    
    public function getAddresses($userId) {       
        return $this->_db->fetchAll("SELECT * FROM user_address WHERE user_id = '$userId' AND active = 1");
    }
     public function getUserAddress($addressId) {
        return $this->_db->fetchRow("SELECT * FROM user_address WHERE address_id = '$addressId' ");     
    } 
    public function account_conversion($data) {
        try {              
            $insert = $this->_db->insert('account_conversion', $data);
            
            $mail = new Zend_Mail();
            $message = "This is an automatic email to inform you that an account has been converted. Please 
                    click <a href='http://beamingwhite.mx/crm/customer/id/{$data['user_id']}'>here</a> to see the account detail. <br><br>";
            $mail->setBodyHTML($message);
            $mail->setFrom('no_reply@beamingwhite.com', 'Beaming White Sales CRM');
            $mail->addTo('dg@beamingwhite.com', 'Darragh');            
            $mail->setSubject('BeamingWhite Account Conversion Notification');
            $mail->send();            
            return $insert;
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
    }
   public function getFollowup($customerId) {
       return $this->_db->fetchRow("SELECT * from events WHERE customer_id = '$customerId'");  
   } 
    //delete followup
    public function deleteFollowup($customerId) {
        return $this->_db->delete('events', $this->_db->quoteInto("customer_id = ?", $customerId));
    }
     
		// Get list of all tradeshows, but group by City, State, and Country.
    public function getTradeshowCity() {
			$query = 'SELECT DISTINCT city, state, country FROM tradeshows ORDER BY tradeshows . id ASC';
			$results = $this->_db->fetchAll($query); 
			return $results; 
    }
		
		public function getTradeshowName() {
			$query = 'SELECT DISTINCT name FROM tradeshows ORDER BY name ASC';
			$results = $this->_db->fetchAll($query);
			return $results;
		}
		
		public function getTradeshowYear() {
			$query = 'SELECT DISTINCT year FROM tradeshows ORDER BY year ASC';
			$results = $this->_db->fetchAll($query);
			return $results;
		}
    
}
