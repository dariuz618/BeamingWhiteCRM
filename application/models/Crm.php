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
       /*$events = $this->_db->fetchAll("SELECT event_id as id, start, end, active,
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND (user_id = '$userId' OR public = 1)");*/
	   /* Dariuz Rubin */
	   $events = $this->_db->fetchAll("SELECT event_id as id, start, end, active,
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE  active >= 1 AND (user_id = '$userId' OR public = 1)");
	   /*==============*/
       return $events;
   }
   
   public function getEvent($eventId) {
       return $this->_db->fetchRow("SELECT * from events WHERE event_id = '$eventId'");       
   }
   
    /* Dariuz Rubin */
   public function getUpcomingEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND follow_up_from_website !=1 AND ADDDATE(start, INTERVAL 30 DAY) AND (user_id = '$userId' OR public = 1)
            AND events.start >= DATE(NOW()) ORDER BY events.start");
       return $events;
   }
   
   public function getUpcomingWebsiteEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1  AND follow_up_from_website =1 AND ADDDATE(start, INTERVAL 30 DAY) AND (user_id = '$userId' OR public = 1)
            AND events.start >= DATE(NOW()) ORDER BY events.start desc");
       return $events;
   }
    /*==============*/
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
    /* Dariuz Rubin */
    public function findUsers($customer,$filter,$value,$value2=NULL)
    {     
    	$customer = addslashes($customer);
    	$filter = addslashes($filter);
    	$value = addslashes($value);
    	if ($value2)
    		$value2 = addslashes($value2);
    	
    	$cond_customer = "";
    	$cond_filter = "";
    	switch($customer)
    	{
			case 'Lead':
				$cond_customer = " (user.type = 'Lead') and ";
				break;
			case 'Prospect':
				$cond_customer = " (user.type = 'Prospect') and ";
				break;
			case 'Account':
				$cond_customer = " (user.type = 'Account') and ";
				break;
			default:
				$cond_customer = "";
				break;
		}
		$query = "";
		switch($filter)
    	{
			case 'First & last name':
				$cond_filter1 = " user.firstname='{$value}' and user.lastname='{$value2}' ";
				$cond_filter2 = " user.lastname='{$value2}' ";
			            	
            	$query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user
            from user left join account_user on user.id = account_user.user
            WHERE $cond_customer $cond_filter1 union select second_resul.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user
            from user left join account_user on user.id = account_user.user
            WHERE $cond_customer $cond_filter2 ORDER BY firstname ASC) second_resul) a  
            left join user u on u.id = a.parent_user";				
				break;
			case 'First name':
				$cond_filter1 = " MATCH(user.firstname) AGAINST('{$value}' IN NATURAL LANGUAGE MODE) ";
				$sub_values = explode(' ',$value);
				$cond_filter2="";
				foreach($sub_values as $sub_value)
				{
					$sub_value = substr($sub_value,0,4);
					$cond_filter2 .= " user.firstname like '%{$sub_value}%' or ";
				}
				if (strlen($cond_filter2)>0)
					$cond_filter2 = substr($cond_filter2,0,strlen($cond_filter2)-3);
				
			            	
            	$query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from ( select first_resul.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user,MATCH(user.firstname) AGAINST('{$value}' IN NATURAL LANGUAGE MODE) as score
            from user left join account_user on user.id = account_user.user
            WHERE $cond_customer $cond_filter1   ORDER BY -score,firstname ASC) first_resul union select second_resul.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user,MATCH(user.firstname) AGAINST('{$value}' IN NATURAL LANGUAGE MODE) as score
            from user left join account_user on user.id = account_user.user
            WHERE $cond_customer $cond_filter2 ORDER BY firstname ASC) second_resul) a  
            left join user u on u.id = a.parent_user";								
				break;
			case 'Business name':
				$cond_filter1 = " MATCH(user.businessname) AGAINST('{$value}' IN NATURAL LANGUAGE MODE) ";
				$sub_values = explode(' ',$value);
				$cond_filter2="";
				foreach($sub_values as $sub_value)
				{
					$sub_value = substr($sub_value,0,4);
					$cond_filter2 .= " user.businessname like '%{$sub_value}%' or ";
				}
				if (strlen($cond_filter2)>0)
					$cond_filter2 = substr($cond_filter2,0,strlen($cond_filter2)-3);
				
			            	
            	$query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from ( select first_resul.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user,MATCH(user.businessname) AGAINST('{$value}' IN NATURAL LANGUAGE MODE) as score
            from user left join account_user on user.id = account_user.user
            WHERE $cond_customer $cond_filter1   ORDER BY -score,businessname ASC) first_resul union select second_resul.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user,MATCH(user.businessname) AGAINST('{$value}' IN NATURAL LANGUAGE MODE) as score
            from user left join account_user on user.id = account_user.user
            WHERE $cond_customer $cond_filter2 ORDER BY businessname ASC) second_resul) a  
            left join user u on u.id = a.parent_user";				
				break;
			case 'Phone number':
				$value = preg_replace("/[^0-9]*/s", "",$value);
				$value1 = substr($value,strlen($value)-4);
				$cond_filter1 = " ((user.contactphone_all like '%{$value}%' ) or (user.contactphone2_all like '%{$value}%' ))";
				$cond_filter2 = " ((user.contactphone_all like '%{$value1}%' ) or (user.contactphone2_all like '%{$value1}%' ))";
				if (strlen($value)>0)
				{
					$query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from ( select first_resul.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user
            from user left join account_user on user.id = account_user.user
            WHERE $cond_customer $cond_filter1   ORDER BY firstname ASC) first_resul union select second_resul.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user
            from user left join account_user on user.id = account_user.user
            WHERE $cond_customer $cond_filter2 ORDER BY firstname ASC) second_resul) a  
            left join user u on u.id = a.parent_user";			
				}
				break;
			/*	$cond_filter1 = " (number_maker(user.contactphone) ='{$value}' or number_maker(user.contactphone2) ='{$value}') ";
				
				if (strlen($value)>0)
				{
					$query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
			            firstname, lastname, email, created_time, type,status,
			            account_user.parent_user
			            from user left join account_user on user.id = account_user.user
			            WHERE $cond_customer $cond_filter1  ORDER BY firstname ASC) a  
			            left join user u on u.id = a.parent_user";			
				}else
				{
					$query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
			            firstname, lastname, email, created_time, type,status,
			            account_user.parent_user
			            from user left join account_user on user.id = account_user.user
			            WHERE $cond_customer ORDER BY firstname ASC) a  
			            left join user u on u.id = a.parent_user";	
				}				
				break;*/
			case 'Email':
				$cond_filter1 = " (user.email='{$value}' or user.email2='{$value}') ";
				$pos = stripos($value,'@');
				$value2 = substr($value,0,$pos);
				
				$cond_filter2 = " (SUBSTRING_INDEX(user.email,'@',1)='{$value2}' or SUBSTRING_INDEX(user.email2,'@',1)='{$value2}') ";
			            	
            	$query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user
            from user left join account_user on user.id = account_user.user
            WHERE $cond_customer $cond_filter1 union select second_resul.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, type,status,
            account_user.parent_user
            from user left join account_user on user.id = account_user.user
            WHERE $cond_customer $cond_filter2 ORDER BY firstname ASC) second_resul) a  
            left join user u on u.id = a.parent_user";				
				break;
			case 'Zip code':
				$value = preg_replace("/\s| /",'',$value);
				$cond_filter = " replace(user.zip,' ','') ='{$value}' ";
				
				if (strlen($value)>0)
				{
					$query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
			            firstname, lastname, email, created_time, type,status,
			            account_user.parent_user
			            from user left join account_user on user.id = account_user.user
			            WHERE $cond_customer $cond_filter  ORDER BY firstname ASC) a  
			            left join user u on u.id = a.parent_user";			
				}else
				{
					
				}				
				break;
			case 'City':
				$value = trim($value);
				$cond_filter = " user.city ='{$value}' ";
				
				if (strlen($value)>0)
				{
					$query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
			            firstname, lastname, email, created_time, type,status,
			            account_user.parent_user
			            from user left join account_user on user.id = account_user.user
			            WHERE $cond_customer $cond_filter  ORDER BY firstname ASC) a  
			            left join user u on u.id = a.parent_user";			
				}else
				{
					
				}				
				break;
				
			default:
				
				break;
		}
		if (strlen($query)>0)
			return $this->_db->fetchAll($query);    	
    
    	return;
    }                
   
    public function getUsersTotal($type, $parent_user=NULL, $potential=NULL, $source=NULL, $soldBy=NULL, $lastAttempt=NULL, $from=NULL, $to=NULL) {  
               
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
      	 if ($from) {
            $filter .= " AND user.created_time >= '$from 00:00:00' ";
        }
        if ($to) {
            $filter .= " AND user.created_time <= '$to 23:59:59' ";
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
    /*====================*/
    
    
    
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
           	
           		/* Dariuz Rubin */
           	    // check if it has non 'change sale ...' notes before
           	   	$query = "select count(user_id) as cnt from user_notes where notes not like 'Change sales rep from%' and author != 'Request Info' and user_id = {$child}";            	
               	$res = $this->_db->fetchRow($query);
				if (isset($res) and $res['cnt']==0)
				{
					// delete these notes
					$query_del = "delete from user_notes where user_id = {$child}";            	
              	 	$res = $this->_db->query($query_del);
				}				
				/*================*/
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
        $this->_db->delete('prospect_conversion', $this->_db->quoteInto("user_id = ?", $userId)); // Rubin        
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
     /* Dariuz Rubin */
    public function getTodayEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end,customer_id, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND (user_id = '$userId' OR public = 1)
            AND DATE(start) = DATE(NOW()) AND follow_up_from_website != 1 ORDER BY start ASC, event_id");
       return $events;
   } 
   
   // Get Today Events which were happened from website    
   
    public function getTodayWebsiteEvents($userId) {
       $events = $this->_db->fetchAll("SELECT event_id as id, start, end,customer_id, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND (user_id = '$userId' OR public = 1)
            AND DATE(start) = DATE(NOW()) AND follow_up_from_website = 1 ORDER BY start ASC, event_id");
       return $events;
   }     
   
   // Get new Today Events which were happened from website and set these events as read 
   
    public function getTodayNewWebsiteEvents($userId) {
    	            
       	$events = $this->_db->fetchAll("SELECT event_id as id, start, end,customer_id, 
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND (user_id = '$userId' OR public = 1)
            AND DATE(start) = DATE(NOW()) AND follow_up_from_website = 1 AND read_follow_up_from_website = 0 ORDER BY start ASC, event_id");
       
        $upd_sql = "update events set read_follow_up_from_website = 1 WHERE active = 1 AND (user_id = '$userId' OR public = 1)
            AND DATE(start) = DATE(NOW()) AND follow_up_from_website = 1 AND read_follow_up_from_website = 0";
		$change =  $this->_db->query($upd_sql);   
                     
       	return $events;
   }     
   
   
   //set 10 mins ahead of time, +/-2 mins variation
  
   public function getAlertEvents($userId,$snoozeTime) {   
       $snoozeTime1 = $snoozeTime+2; // offset
       $sql = "SELECT event_id as id, start, end, customer_id,
            case when all_day = 0 then NULL
            else true
            end allDay, title
            FROM events WHERE active = 1 AND
           (TIMESTAMPDIFF(MINUTE,NOW(),start) >= $snoozeTime AND TIMESTAMPDIFF(MINUTE,NOW(),start) <= $snoozeTime1)
            AND (user_id = '$userId' OR public = 1) AND follow_up_from_website != 1 AND popup_alert IS NULL
            ORDER BY start ASC, event_id";
       $events = $this->_db->fetchAll($sql);
       return $events;
   }
   
   public function setAlertEvents($customerId) {   
       
       $sql = "update events set active = 0 WHERE active = 1 AND
            TIMESTAMPDIFF(MINUTE,NOW(),start) >= 0 
            AND (customer_id = '$customerId' OR public = 1) AND follow_up_from_website != 1
            ORDER BY start ASC, event_id";
       $change =   $this->_db->query($sql);   
       return $change;
   }
   /*============== */
   
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
            'Sapient Dental' => 'Sapient Dental',
            'Teeth Whitening Technology' => 'Teeth Whitening Technology',
            /*'Magic White' => 'Magic White',*/
            'Cool Smart Product' => 'Cool Smart Product'
           );
    }    
    
    public static function getAccountStatus() {
        return array('Pending'=>'Pending', 'Disabled'=>'Disabled', 'Active'=>'Active');
    }
    public static function getAccountPotential() {        
        return array('Hot'=>'HOT(ready to buy)', 'Warm'=>'Warm (maybe soon)', 'Cool'=>'Cool (future maybe)',
            'Cold'=>'Cold (unlikely)');
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
        return  array(''=>'',
            'Clinic'=>'Clinic',
            'Dentist' => 'Dentist', 
            'Distributor'=>'Distributor', 'Individual'=>'Individual', 
            'Mobile'=>'Mobile', 'Planet Beach'=> 'Planet Beach', 'Retail'=>'Retail', 
            'Salon/Spa'=>'Salon/Spa',
            'Stationary'=>'Stationary',            
            'Tanning'=>'Taning',            
            'Kiosk'=>'Kiosk',
            'Other'=>'Other');
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
            $data['firstname'] = ucwords(strtolower($data['firstname']));
            $data['lastname'] = ucwords(strtolower($data['lastname']));
            unset($data['parent_user']);
            
            /* Dariuz Rubin */            
            unset($data['applys_check']);
            unset($data['submit']);
            unset($data['submit_type']);
            unset($data['shipping_same_as_billing']);
            
            $insert = $this->_db->insert('user', $data);  
            
            /*==============*/
            
            //$insert = $this->_db->insert('user', $data);     
            $userId = $this->_db->lastInsertId();
            
            // set business phone with office phone
            $upd_sql = sprintf("update user set businessphone='%s',secondary_businessphone='%s' where id='%d'",$data['contactphone'],$data['contactphone2'],$userId);
	        $update =  $this->_db->query($upd_sql);   
	       
        
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
    
    /* Dariuz Rubin */
     public function getUsersPage ($type, $sort, $order, $start, $end, $parent_user = null, $potential = null, $source = null, $soldBy = null, $lastAttempt = null, $from = null, $to = null) {    
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
        if ($from) {
            $filter .= " AND user.created_time >= '$from 00:00:00' ";
        }
        if ($to) {
            $filter .= " AND user.created_time <= '$to 23:59:59' ";
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
            // Dariuz Rubin : Next Contact
            $query = "select concat(u.firstname, ' ', LEFT(u.lastname , 1) ) as rep, user.id, user.businessname, user.contactphone, 
        user.firstname, user.lastname, user.email, user.created_time, user.state, user.country,
                DATE_FORMAT(user.created_time,'%m/%d/%y   %h:%i %p') as show_time, 
                account_user.parent_user, user.potential,contactDays,DATE_FORMAT(user.follow_up,'%m/%d/%y   %h:%i %p') as follow_up,              
                
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
         	 /* Dariuz Rubin */
         	 if ($sort == 'enter_time') {
	            $ids_query = "SELECT id from user 
                    left join account_user on account_user.user = user.id
                    WHERE user.status != 'Disabled' AND user.type = '$type' $filter $contact
                    LIMIT $start, $end";
	        }else
	        {
				$ids_query = "SELECT id from user 
                    left join account_user on account_user.user = user.id
                    WHERE user.status != 'Disabled' AND user.type = '$type' $filter $contact
                    ORDER BY $sort $order LIMIT $start, $end";
			}
         	/*======================*/
            
                    
            $ids = $this->_db->fetchAll($ids_query);               
           
            if(!$ids) 
            	return;

            $thisIds = ''; $index = 0;
            foreach ($ids as $id) {
                $thisIds[$index] = $id['id'];
                ++$index;
            }
            $finalIds = "'" . implode("','", $thisIds) . "'"; 
			
			// Dariuz Rubin : Next Contact
            $query = "select concat(u.firstname, ' ', LEFT(u.lastname , 1) ) as rep, user.id, user.businessname, user.contactphone, 
            user.firstname, user.lastname, user.email, user.created_time,  user.state, user.country,
                    DATE_FORMAT(user.created_time,'%m/%d/%y   %h:%i %p') as show_time, 
                    account_user.parent_user, user.potential,contactDays,DATE_FORMAT(user.follow_up,'%m/%d/%y   %h:%i %p') as follow_up,               

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
    
    /**	
    Get the time left till the time when lead/prospect will be transferred into the Makreting Reps queues
	*/
     public function getTimeleft($id = null) {
     	$time_left = 0;
     	// get Marketing Reps
     	$query = "select id from user where (username like 'Contacted Marketing') or (username like 'No Contact Marketing')";     	
     	$ids = $this->_db->fetchAll($query);               
     	if (count($ids)>0)
     	{
			$filter = "";
			for ($i=0;$i<count($ids);$i++)
				$filter .= '(account_user.parent_User != '.$ids[$i]['id'].') and ';
			
			$query_sel = "select (removal_days-DATEDIFF(NOW(),created_time)) as time_left from user left join account_user on account_user.user = user.id  where $filter id = '$id' and (type = 'Lead' or type='Prospect')";
			
			$user = $this->_db->fetchRow($query_sel);     
			if ($user)
				$time_left = $user['time_left'];
        	
		}
		if ($time_left > 0)
			return $time_left;
		else
			return 0;
    }
    
 	/*======================*/
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
	
	/* Dariuz Rubin */
    /**
	* No Contact Marketing Rep 
	* @return
	*/    
    public function updateNoContactMarketing () {              
        // select user_id for no contact marketing rep
        $sql_sel_parent = "SELECT id from user where username='No Contact Marketing'";
        $result_parent = $this->_db->fetchRow($sql_sel_parent);
        if (isset($result_parent))
        {
        	$parent = $result_parent['id'];
        	        	
        	// select users who were created 30 days ago
        	$sql_sel_users = "select id from user left join account_user on user.id = account_user.user where account_user.parent_user != $parent and datediff(now(),created_time)>30 and type ='Lead'";
        	$results_users = $this->_db->fetchAll($sql_sel_users);
        	foreach ($results_users as $user) 
        	{
        		$user_id = $user['id'];        		
        		// update its parent with no contact marketing
                $upd_sql = "update account_user set parent_user = $parent where user=$user_id";
                $change =  $this->_db->query($upd_sql);   
            }			
		}
     	
        return $change;
    }
    
    /**
	* Contact Marketing Rep 
	* @return
	*/    
    public function updateContactMarketing () {              
        // select user_id for contacted marketing rep
        $sql_sel_parent = "SELECT id from user where username='Contacted Marketing'";
        $result_parent = $this->_db->fetchRow($sql_sel_parent);
        if (isset($result_parent))
        {
        	$parent = $result_parent['id'];
        	        	
        	// select users who were created 60 days ago
        	$sql_sel_users = "select user.id from user left join prospect_conversion on user.id = prospect_conversion.user_id where prospect_conversion.parent_id != $parent and datediff(now(),action_time)>user.removal_days and type ='Prospect'";
        	$results_users = $this->_db->fetchAll($sql_sel_users);
        	foreach ($results_users as $user) 
        	{
        		$user_id = $user['id'];        		
        		// update its parent with  contact marketing
                $upd_sql = "update account_user set parent_user = $parent where user=$user_id";
                $change =  $this->_db->query($upd_sql);  
                
                // update prospect_conversion  with contact marketing
                $upd_sql = "update prospect_conversion set parent_id = $parent where user_id=$user_id";
                $change =  $this->_db->query($upd_sql);   
            }			
		}
     	
        return $change;
    }
    
    /**
	* Track the transition to Prospect
	* @return
	*/    
    public function prospect_conversion($data) {
        try {              
            $insert = $this->_db->insert('prospect_conversion', $data);            
            return $insert;
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
    }
    
    /**
	* set an order frequency field
	* @return
	*/    
    public function setOrderFrequency($user_id,$order_frequency) {    	
    	
        $upd_sql = "update user set order_frequency = $order_frequency, order_frequency_times=0, order_frequency_date= now() where id=$user_id";
        $change =  $this->_db->query($upd_sql);       
        return $change;        
    }
    
     /**
	* set an additional 60 days to automated removal date for prospect
	* @return
	*/    
    public function removaladdition($user_id) {
    	
    	// update removal_days with 120
        $upd_sql = "update user set removal_days = removal_days+60 where id=$user_id";
        $change =  $this->_db->query($upd_sql); 
        
              
        return $change;        
    }
    
    
    
    /**
	* get an event which sohould be sent 15 minutes before the event
	* @return
	*/    
     public function getEmailAlertEvents($user_id) {
       $events = $this->_db->fetchAll("SELECT event_id as id, user_id, customer_id, start, end, title
            FROM events WHERE active = 1 AND ((TIMESTAMPDIFF(MINUTE,NOW(),start) >= 15 AND TIMESTAMPDIFF(MINUTE,NOW(),start) <= 17) OR (TIMESTAMPDIFF(MINUTE,NOW(),start) >= 2 AND TIMESTAMPDIFF(MINUTE,NOW(),start) <= 4))        
            AND (user_id = '$user_id' OR public = 1) AND email_alert IS NULL
            ORDER BY start ASC, event_id");
       return $events;
   }
    
    /**
	* send email alert to rep
	*/    
    public function emailalertEvent($event) 
    {
     	// get customer info
     	if (isset($event) and isset($event['customer_id']))
     	{
     		$customer_id = $event['customer_id'];
           	$message = "<a target='_blank' href='/biz/crm/customer/id/{$customer_id}' style='color:black'>{$event['title']}</a>"; 
           		
        	
			$customerData = $this->_db->fetchRow("select firstname,lastname from user where id = '$customer_id'");                
	     	if (isset($customerData))
	     	{
				$customer_firstname = $customerData['firstname'];
				$customer_lastname = $customerData['lastname'];
				
				// get rep info
				$user_id = $event['user_id'];				
				$toRep = $this->_db->fetchRow("select email, firstname, lastname from user left join account_user on user.id = account_user.parent_user where account_user.user = '$customer_id'");                
	        	if(isset($toRep)) 
	        	{
	        		$account_email = $toRep['email'];
	        		$account_name = $toRep['firstname'].' '.$toRep['lastname'];
	        		$time = $event['start'];
	        	
	        		$email_subj = "Reminder to contact";
	        		$email_body = "Reminder to contact :";
	        		
	        		if (isset($customer_firstname) and strlen($customer_firstname)>0)
	        			$email_subj .= " ".$customer_firstname;
	        		if (isset($customer_lastname) and strlen($customer_lastname)>0)
	        			$email_subj .= " ".$customer_lastname;
	        			
	        		if (isset($account_name) and strlen($account_name)>0)
	        		{
						$email_subj .= " from ".$account_name;
						$email_body .= " ".$account_name.".";
					}
	        			
	        		if (isset($time) and strlen($time)>0)
	        		{
	        			$time = substr($time,0, strlen($time)-3);
						$email_subj .= " at ".$time;
					}
	        		
	        		$email_body .= "<br>"."<br>".$message;
	        		$email_subj .= " today.";
	        		
	        		// send email
	        		$mail = new Zend_Mail();
	        		$mail->setBodyHTML($email_body);
	                $mail->setFrom('no_reply@beamingwhite.com', 'Reminder to contact');
	                $mail->addTo($account_email,$account_name);
	                $mail->setSubject($email_subj);
	                $res = $mail->send();	
	                
                
	                $auth = Zend_Auth::getInstance()->getIdentity();
	           
		            if($auth) {
		               $log = array('user_id' => $event['customer_id'], 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
		               $log['notes'] = 'Reminder to contact '. $account_name;           
		               self::savenotes($log);
		            }
	        	}
			}
		}
     	
    } 	
    
	/**
	* Check if this reminder has same followup time for same rep
	*/
    public function getConflictingReminder($followupTime,$user_id) {
       $query = "select count(*) as is_exists from events where user_id = {$user_id} and start='{$followupTime}'";
	 	
       return $this->_db->fetchRow($query);  
   	}
   	
   	/**
	* Check its followup time
	* current day = day(follow-up) => set active=2
	* follow_up - 30 minutes < current time < follow-up => set active=4
	* current day < day(follow-up) => set active=8
	* current day > day(follow-up) => set active=16
	*/ 
    
    public function doSetActiveValues($data) {
    	
    	// select event and active status
    	$query="select event_id,active,start,DATEDIFF(start,NOW()) as day_diff,TIMESTAMPDIFF(MINUTE,NOW(),start) as min_diff from events where  user_id = {$data['author_id']} and customer_id= {$data['user_id']}";
    	$result = $this->_db->fetchRow($query);                
	    if(isset($result)) 
	    {
    		$event_id = $result['event_id'];
    		$active = $result['active'];
    		$follow_up = $result['start'];
    		$day_diff = $result['day_diff'];
    		$min_diff = $result['min_diff'];
    		$active_value = 1;
    		if ($day_diff == 0)
    			$active_value = 2;
    		if ($min_diff>=0 and $min_diff<=30)
    			$active_value = 4;
    		else if ($day_diff<0)
    			$active_value = 16;
    		else if ($day_diff>0)
    			$active_value = 8;
    			
    		$query = "update events set active = {$active_value} where event_id = {$event_id}";
       		$change =  $this->_db->query($query);
    	}
	   	return $change;
   	}
   	
   	 /**
	* Enter sale's date and amount
	*/ 
   	public function entersale($data) {
        try {      
        	$query = sprintf("insert into user_notes (user_id,author,type,enter_time,enter_sale_amount,attempt_type) values ('%d','%s','%s','%s','%f','%s')",$data['user_id'],$data['author'],$data['type'],$data['enter_time'],$data['enter_sale_amount'],$data['attempt_type']);
        	
        	$insert =  $this->_db->query($query);   
        	
        	// reset order frequency date
        	$reset_query = "update user set order_frequency_date = NOW(),order_frequency_times=0 where id = {$data[user_id]}";
        	$this->_db->query($reset_query);   
        	
        	// get total sale      
        	$query = "select sum(enter_sale_amount) as total_sale from user_notes where user_id={$data[user_id]} and author='{$data[author]}' and type='{$data[type]}'";
        	$data = $this->_db->fetchRow($query);               	
            return $data;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
    }
   	
   	/**
	* check if an active account does not have any current activity in a predetermined time period
	*/    
    public function notifyUnactivityAccount() 
    {
     	// get unactivity accounts
     	$query  = "SELECT id,firstname,lastname,soldby,email,order_frequency,order_frequency_times from user where type='Account' and status='Active' and (order_frequency_times < 2) and (order_frequency_date is not null) and DATEDIFF(NOW(),order_frequency_date)>= order_frequency";
     	$unactivity_accounts = $this->_db->fetchAll($query);
     	foreach ($unactivity_accounts as $unactivity_account) 
    	{
    		$user_id = $unactivity_account['id'];		
    		$customer_name = $unactivity_account['firstname']. ' '.$unactivity_account['lastname'];		
    		$customer_email = $unactivity_account['email'];		
    		$customer_order_frequency = $unactivity_account['order_frequency'];		   
    		$customer_order_frequency_times = $unactivity_account['order_frequency_times']+1;		 
    		
    		
    		/* reset order frequency twice and order_frequency date */
    		if ($customer_order_frequency_times != 2)      		
            	$new_order_frequency = $customer_order_frequency-30;
            else
            	$new_order_frequency = $customer_order_frequency+30; //restore original order_frequency_date
            	
            $reset_query = "update user set order_frequency_times=1, order_frequency_times={$customer_order_frequency_times},order_frequency_date = DATE_ADD(CURDATE(),INTERVAL - {$new_order_frequency} DAY) where id = {$unactivity_account['id']}";
        	$this->_db->query($reset_query); 
        	
    		/* send to rep, sales manager, CSR */    		    		 		
    		// send to rep
			$toRep = $this->_db->fetchRow("select email, firstname, lastname from user left join account_user on user.id = account_user.parent_user where account_user.user = '$user_id'");      			
    		if(isset($toRep)) 
        	{
        		$to_email = $toRep['email'];
        		$to_name = $toRep['firstname'].' '.$toRep['lastname'];
        		$from_email = "no_reply@beamingwhite.com";
        	
        		$email_subj = "Customer Not Ordering...";
        		$email_body = "This is a notification that the customer account of ".$customer_name."(".$customer_email.") has not ordered in the ".$customer_order_frequency." days";        		
        		
        		// send email
        		$mail = new Zend_Mail();
        		$mail->setBodyHTML($email_body);
                $mail->setFrom($from_email, 'Customer Not Ordering');
                $mail->addTo($to_email,$to_name);
                $mail->setSubject($email_subj);
                $res = $mail->send();	
                
            
                $auth = Zend_Auth::getInstance()->getIdentity();
           
	            if($auth) {
	               $log = array('user_id' => $user_id, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
	               $log['notes'] = 'Reminder to contact '. $to_name;           
	               self::savenotes($log);
	            }
        	}        	
        	// send to sales manager
			{
				
				$to_email = 'cory@beamingwhite.com';
        		$to_name = 'manager';
        		$from_email = "no_reply@beamingwhite.com";
        	
        		$email_subj = "Customer Not Ordering...";
        		$email_body = "This is a notification that the customer account of ".$customer_name."(".$customer_email.") has not ordered in the ".$customer_order_frequency." days";    
        		
        		
        		// send email
        		$mail = new Zend_Mail();
        		$mail->setBodyHTML($email_body);
                $mail->setFrom($from_email, 'Customer Not Ordering');
                $mail->addTo($to_email,$to_name);
                $mail->setSubject($email_subj);
                $res = $mail->send();	
                
            
                $auth = Zend_Auth::getInstance()->getIdentity();
           
	            if($auth) {
	               $log = array('user_id' => $user_id, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
	               $log['notes'] = 'Reminder to contact '. $to_name;           
	               self::savenotes($log);
	            }
        	}        	
        	// send to CSR
			{				
				$to_email = 'customer.info@beamingwhite.com';
        		$to_name = 'CSR';
        		$from_email = "no_reply@beamingwhite.com";
        	
        		$email_subj = "Customer Not Ordering...";
        		$email_body = "This is a notification that the customer account of ".$customer_name."(".$customer_email.") has not ordered in the ".$customer_order_frequency." days";    
        		
        		
        		// send email
        		$mail = new Zend_Mail();
        		$mail->setBodyHTML($email_body);
                $mail->setFrom($from_email, 'Customer Not Ordering');
                $mail->addTo($to_email,$to_name);
                $mail->setSubject($email_subj);
                $res = $mail->send();	
                
            
                $auth = Zend_Auth::getInstance()->getIdentity();
           
	            if($auth) {
	               $log = array('user_id' => $user_id, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
	               $log['notes'] = 'Reminder to contact '. $to_name;           
	               self::savenotes($log);
	            }
	        }
			
			/* send to customer */
			$to_email = $customer_email;
        	$to_name = $customer_name;
			$email_subj = "Just checking in!";
			switch($unactivity_account['soldby'])
			{
				case 'Beaming White':
					$from_email = "bwsales@beamingwhite.com";
	        		$email_body = "Dear ".$unactivity_account['firstname'].",<br><br>It has been a while since your last order of teeth whitening products. We just wanted to make sure everything is going well and to remind you to check your supplies. Just call us at 360-635-5600 to place an order or email us at bwsales@beamingwhite.com. We are here for you.<br><br>Thanks - The Beaming White Team";
					break;
				case 'Cliona':
					$from_email = "info@clionamedical.com";
	        		$email_body = "Dear ".$unactivity_account['firstname'].",<br><br>It has been a while since your last order. We just wanted to make sure everything is going well and to remind you to check your supplies. Just call us at 360-635-5600 to place an order or email us at info@clionamedical.com.  We are here for you.<br><br>Thanks - The Cliona Beauty Team";
					break;
				case 'Sapient Dental':
					$from_email = "info@sapientdental.com";
	        		$email_body = "Dear ".$unactivity_account['firstname'].",<br><br>It has been a while since your last order of teeth whitening products. We just wanted to make sure everything is going well and to remind you to check your supplies. Just call us at 360-635-5600 to place an order or email us at info@sapientdental.com. We are here for you.<br><br>Thanks - The Sapient Dental Team";
					break;
				case 'Teeth Whitening Technology':
					$from_email = "kim.smith@teethwhiteningtechnologies.com";
	        		$email_body = "Dear ".$unactivity_account['firstname'].",<br><br>It has been a while since your last order of teeth whitening products. We just wanted to make sure everything is going well and to remind you to check your supplies. To place an order just send a quick email to kim.smith@teethwhiteningtechnologies.com and we will get it taken care of right away. We are here for you.<br><br>Thanks - The Teeth Whitening Technologies Team";
					break;
				default :
					break;
			}
			
			// send email
    		$mail = new Zend_Mail();
    		$mail->setBodyHTML($email_body);
            $mail->setFrom($from_email, 'Just checking in!');
            $mail->addTo($to_email,$to_name);
            $mail->setSubject($email_subj);
            $res = $mail->send();	
            
        
            $auth = Zend_Auth::getInstance()->getIdentity();
       
            if($auth) {
               $log = array('user_id' => $user_id, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
               $log['notes'] = 'Reminder to contact '. $to_name;           
               self::savenotes($log);
            }	
            
        }     	
    } 	
    
    
    /**
	* update follow_up time for user
	* @return
	*/    
    public function updateFollowupUser($user_id,$followup) {    	
    	
        $upd_sql = "update user set follow_up = '{$followup}' where id=$user_id";
        $change =  $this->_db->query($upd_sql);       
        return $change;        
    }
    
    /**
	* get Y Hours
	*/    
    public function getYHours($auth_id) {    	
        $query = "select y_hours from user_info where auth_id = {$auth_id}";
        $res = $this->_db->fetchRow($query);   
        $y_hours = 0;
        if (isset($res) and $res['y_hours']>0) 
        	$y_hours = $res['y_hours'];
        return $y_hours;        
    }
    
    /**
	* set Y Hours
	*/    
    public function setYHours($auth_id,$y_hours) {    	
     	$del_sql = "delete from user_info where auth_id = {$auth_id}";
        $res =  $this->_db->query($del_sql);   
        
        $query = "insert into user_info (auth_id,y_hours) values ({$auth_id},{$y_hours})";
        $res =  $this->_db->query($query);   
        return $res;        
    }
    
    /**
	* check if this lead/prospect/account had been already exists
	*/    
	public function searchAccount(array $data) {
		$cond="";
		if (isset($data['address1']) and strlen($data['address1'])>0)
			$cond .= " or (address1 = '{$data[address1]}')";
		if (isset($data['address2']) and strlen($data['address2'])>0)
			$cond .= " or (address2 = '{$data[address2]}')";
		if (isset($data['businessphone']) and strlen($data['businessphone'])>0)
			$cond .= " or (businessphone = '{$data[businessphone]}')";
		if (isset($data['contactphone']) and strlen($data['contactphone'])>0)
			$cond .= " or (contactphone = '{$data[contactphone]}')";
		if (isset($data['contactphone2']) and strlen($data['contactphone2'])>0)
			$cond .= " or (contactphone2 = '{$data[contactphone2]}')";
		if (isset($data['email']) and strlen($data['email'])>0)
			$cond .= " or (email = '{$data[email]}')";
		if (isset($data['email2']) and strlen($data['email2'])>0)
			$cond .= " or (email2 = '{$data[email2]}')";
		
		if (isset($data['firstname']) and (strlen($data['firstname'])>0) or (strlen($data['lastname'])>0))
		{
			$name = addslashes($data['firstname'].' '.$data['lastname']);
			$cond .= " or (concat(firstname,' ',lastname) = '{$name}')";
		}
		
		if (strlen($cond)>0)
		{
			$cond = substr($cond,4,strlen($cond));
			$cond = "where ".$cond;
		}
		
			
		$query = "select * from user $cond";
        $res = $this->_db->fetchRow($query);   
        return $res;                
    }    
    /*==============*/    
    
    /**
	* notify that this lead/prospect/account had been already exists
	*/    
    public function noitifyExistingAccount($data) 
    {
    	$cond="";
		if (isset($data['address1']) and strlen($data['address1'])>0)
			$cond .= " or (address1 = '{$data[address1]}')";
		if (isset($data['address2']) and strlen($data['address2'])>0)
			$cond .= " or (address2 = '{$data[address2]}')";
		if (isset($data['businessphone']) and strlen($data['businessphone'])>0)
			$cond .= " or (businessphone = '{$data[businessphone]}')";
		if (isset($data['contactphone']) and strlen($data['contactphone'])>0)
			$cond .= " or (contactphone = '{$data[contactphone]}')";
		if (isset($data['contactphone2']) and strlen($data['contactphone2'])>0)
			$cond .= " or (contactphone2 = '{$data[contactphone2]}')";
		if (isset($data['email']) and strlen($data['email'])>0)
			$cond .= " or (email = '{$data[email]}')";
		if (isset($data['email2']) and strlen($data['email2'])>0)
			$cond .= " or (email2 = '{$data[email2]}')";
		
		if (isset($data['firstname']) and (strlen($data['firstname'])>0) or (strlen($data['lastname'])>0))
		{
			$name = $data['firstname'].' '.$data['lastname'];
			$cond .= " or (concat(firstname,' ',lastname) = '{$name}')";
		}
		
		if (strlen($cond)>0)
		{
			$cond = substr($cond,4,strlen($cond));
			$cond = "where ".$cond;
		}		
			
		$query = "select id,firstname,lastname,email,contactphone from user left join account_user on account_user.user = user.id $cond";
        $res = $this->_db->fetchRow($query);           
        if (isset($res))
        {
        	$user_id = $res['id'];
        	$auth = Zend_Auth::getInstance()->getIdentity();
        	$sales_rep = $auth->firstname.' '.$auth->lastname;
        	$rep_email = $auth->email;
        	$customer_name = $res['firstname']. ' '.$res['lastname'];		
        	$contactphone = $res['contactphone'];
        	$customer_email = $res['email'];
        	$url = "http://".$_SERVER['HTTP_HOST']."/crm/customer/id/".$user_id;
        	
			// send email to rep	
			$toRep = $this->_db->fetchRow("select email, firstname, lastname from user left join account_user on user.id = account_user.parent_user where account_user.user = '$user_id'");      			
    		if(isset($toRep)) 
        	{
        		$to_email = $toRep['email'];
        		$to_name = $toRep['firstname'].' '.$toRep['lastname'];
        		$from_email = "no_reply@beamingwhite.com";
        	
        		$email_subj = $sales_rep.' just talked to your customer '.$customer_name;
        		$email_body = "Name : ".$customer_name."<br>";
        		$email_body .= "Email : ".$customer_email."<br>";
        		$email_body .= "Phone Number : ".$contactphone."<br>";        		
        		$email_body .= "URL : "."<a href='$url'>$url</a>";
        		
        		// send email
        		$mail = new Zend_Mail();
        		$mail->setBodyHTML($email_body);
                $mail->setFrom($from_email,$email_subj);
                $mail->addTo($to_email,$to_name);
                $mail->setSubject($email_subj);
                $res = $mail->send();	
           
	            if($auth) {
	               $log = array('user_id' => $user_id, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
	                $log['notes'] = $email_subj." TO : ".$to_name ;             
	               self::savenotes($log);
	            }
        	}       
        
        	
        	// send email to Sales Manager				
        	{
        		$to_email = 'cory@beamingwhite.com';
        		$to_name = 'manager';
        		$from_email = "no_reply@beamingwhite.com";
        	
        		$email_subj = $sales_rep.' just talked to your customer '.$customer_name;
        		$email_body = "Name : ".$customer_name."<br>";
        		$email_body .= "Email : ".$customer_email."<br>";
        		$email_body .= "Phone Number : ".$contactphone."<br>";
        		$email_body .= "URL : "."<a href='$url'>$url</a>";
        		
        		// send email
        		$mail = new Zend_Mail();
        		$mail->setBodyHTML($email_body);
                $mail->setFrom($from_email, $email_subj);
                $mail->addTo($to_email,$to_name);
                $mail->setSubject($email_subj);
                $res = $mail->send();
           
	            if($auth) {
	               $log = array('user_id' => $user_id, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
	               $log['notes'] = $email_subj." TO : ".$to_name ;            
	               self::savenotes($log);
	            }
        	}     
        	
        	// send email to Luis	
        	{
        		$to_email = 'luis@beamingwhite.com';
        		$to_name = 'Luis';
        		$from_email = "no_reply@beamingwhite.com";
        	
        		$email_subj = "New account created by ".$sales_rep." is duplicate of existing account ".$customer_name;   
        		$email_body = "Name : ".$customer_name."<br>";
        		$email_body .= "Email : ".$customer_email."<br>";
        		$email_body .= "Phone Number : ".$contactphone."<br>";
        		$email_body .= "URL : "."<a href='$url'>$url</a>";
        		
        		
        		// send email
        		$mail = new Zend_Mail();
        		$mail->setBodyHTML($email_body);
                $mail->setFrom($from_email, $email_subj);
                $mail->addTo($to_email,$to_name);
                $mail->setSubject($email_subj);
                $res = $mail->send();
                
	            if($auth) {
	               $log = array('user_id' => $user_id, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
	               $log['notes'] = $email_subj." TO : ".$to_name ;           
	               self::savenotes($log);
	            }
        	}         
		}     	
    } 	
     /**
	* notify that this lead/prospect/account had been already exists, but we are going to create
	*/    
    public function noitifyCreationExistingAccount($data) 
    {
    	$cond="";
		if (isset($data['address1']) and strlen($data['address1'])>0)
			$cond .= " or (address1 = '{$data[address1]}')";
		if (isset($data['address2']) and strlen($data['address2'])>0)
			$cond .= " or (address2 = '{$data[address2]}')";
		if (isset($data['businessphone']) and strlen($data['businessphone'])>0)
			$cond .= " or (businessphone = '{$data[businessphone]}')";
		if (isset($data['contactphone']) and strlen($data['contactphone'])>0)
			$cond .= " or (contactphone = '{$data[contactphone]}')";
		if (isset($data['contactphone2']) and strlen($data['contactphone2'])>0)
			$cond .= " or (contactphone2 = '{$data[contactphone2]}')";
		if (isset($data['email']) and strlen($data['email'])>0)
			$cond .= " or (email = '{$data[email]}')";
		if (isset($data['email2']) and strlen($data['email2'])>0)
			$cond .= " or (email2 = '{$data[email2]}')";
		
		if (isset($data['firstname']) and (strlen($data['firstname'])>0) or (strlen($data['lastname'])>0))
		{
			$name = $data['firstname'].' '.$data['lastname'];
			$cond .= " or (concat(firstname,' ',lastname) = '{$name}')";
		}
		
		if (strlen($cond)>0)
		{
			$cond = substr($cond,4,strlen($cond));
			$cond = "where ".$cond;
		}		
			
		$query = "select id,firstname,lastname,email,contactphone from user left join account_user on account_user.user = user.id $cond";
        $res = $this->_db->fetchRow($query);           
        if (isset($res))
        {
        	$user_id = $res['id'];
        	$auth = Zend_Auth::getInstance()->getIdentity();
        	$sales_rep = $auth->firstname.' '.$auth->lastname;
        	$rep_email = $auth->email;
        	$customer_name = $res['firstname']. ' '.$res['lastname'];		
        	$contactphone = $res['contactphone'];
        	$customer_email = $res['email'];
        	$url = "http://".$_SERVER['HTTP_HOST']."/crm/customer/id/".$user_id;
        	
			// send email to rep	
			$toRep = $this->_db->fetchRow("select email, firstname, lastname from user left join account_user on user.id = account_user.parent_user where account_user.user = '$user_id'");      			
    		if(isset($toRep)) 
        	{
        		$to_email = $toRep['email'];
        		$to_name = $toRep['firstname'].' '.$toRep['lastname'];
        		$from_email = "no_reply@beamingwhite.com";
        		
        		$email_subj = "New account created by ".$sales_rep." is duplicate of existing account ".$customer_name;        		
        		$email_body = "Name : ".$customer_name."<br>";
        		$email_body .= "Email : ".$customer_email."<br>";
        		$email_body .= "Phone Number : ".$contactphone."<br>";
        		$email_body .= "URL : "."<a href='$url'>$url</a>";
        		
        		// send email
        		$mail = new Zend_Mail();
        		$mail->setBodyHTML($email_body);
                $mail->setFrom($from_email,$email_subj);
                $mail->addTo($to_email,$to_name);
                $mail->setSubject($email_subj);
                $res = $mail->send();	
                
               
           
	            if($auth) {
	               $log = array('user_id' => $user_id, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
	                $log['notes'] = $email_subj." TO : ".$to_name ;     
	               self::savenotes($log);
	            }
        	}       
        	
        	// send email to Sales Manager				
        	{
        		$to_email = 'cory@beamingwhite.com';
        		$to_name = 'manager';
        		$from_email = "no_reply@beamingwhite.com";
        	
        		$email_subj = "New account created by ".$sales_rep." is duplicate of existing account ".$customer_name;   
        		$email_body = "Name : ".$customer_name."<br>";
        		$email_body .= "Email : ".$customer_email."<br>";
        		$email_body .= "Phone Number : ".$contactphone."<br>";
        		$email_body .= "URL : "."<a href='$url'>$url</a>";
        		
        		
        		// send email
        		$mail = new Zend_Mail();
        		$mail->setBodyHTML($email_body);
                $mail->setFrom($from_email, $email_subj);
                $mail->addTo($to_email,$to_name);
                $mail->setSubject($email_subj);
                $res = $mail->send();
                
            
              
           
	            if($auth) {
	               $log = array('user_id' => $user_id, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
	                $log['notes'] = $email_subj." TO : ".$to_name ;     
	               self::savenotes($log);
	            }
        	}       
        	
        	
        	// send email to Luis	
        	{
        		$to_email = 'luis@beamingwhite.com';
        		$to_name = 'Luis';
        		$from_email = "no_reply@beamingwhite.com";
        	
        		$email_subj = "New account created by ".$sales_rep." is duplicate of existing account ".$customer_name;   
        		$email_body = "Name : ".$customer_name."<br>";
        		$email_body .= "Email : ".$customer_email."<br>";
        		$email_body .= "Phone Number : ".$contactphone."<br>";
        		$email_body .= "URL : "."<a href='$url'>$url</a>";
        		
        		
        		// send email
        		$mail = new Zend_Mail();
        		$mail->setBodyHTML($email_body);
                $mail->setFrom($from_email, $email_subj);
                $mail->addTo($to_email,$to_name);
                $mail->setSubject($email_subj);
                $res = $mail->send();
                
	            if($auth) {
	               $log = array('user_id' => $user_id, 'author'=>$auth->firstname.' '.$auth->lastname, 'type'=>'note');       
	               $log['notes'] = $email_subj." TO : ".$to_name ;           
	               self::savenotes($log);
	            }
        	}       
		}     	
    } 	
	
	 /**
	* Get branch infos
	*/
 	public function getBranches ($id) {       
        $sql = "SELECT * from branch_info where customer_id={$id}";
        $results = $this->_db->fetchAll($sql);
        return $results;
    }
    
    /**
	* Get branch info
	*/
 	public function getBranch($id) {       
        $sql = "SELECT * from branch_info where customer_id={$id} limit 1";
        $results = $this->_db->fetchRow($sql);
        if ($results)
        	return $results;        
        return $results;
        
    }
    
    /**
	* Get branch info by branch name
	*/
 	public function getBranchByName($branch_name,$id) {     
 	
 		//update current branch for customer
 		$sql = "update branch_info set customer_id=0 where customer_id={$id}";
        $results = $this->_db->query($sql); 
        
 		$sql = "update branch_info set customer_id={$id} where branch_name='{$branch_name}'";
        $results = $this->_db->query($sql); 
        
        /// get branch
        $sql = "SELECT * from branch_info where branch_name='{$branch_name}' limit 1";
        $results = $this->_db->fetchRow($sql);        
        return $results;        
        
    }
    
     /**
	* save branch info
	*/
 	public function saveBranch ($data) {   
 		if (isset($data))
 		{
			$branch_name = addslashes($data['branch_name']);
			
			//check if same branch already exists, if so, then delete it
			$del_sql = "delete from branch_info where customer_id ={$data['customer_id']} and branch_name = '{$branch_name}'";
       		$res =  $this->_db->query($del_sql); 
       		
       		// create new branch       		
       		$sql_ins = sprintf("insert into branch_info (customer_id,branch_name,businessname,businesstype,firstname,lastname,contactphone,contactphone_all,email,email_all,secondary_businessname,secondary_businesstype,secondary_firstname,secondary_lastname,secondary_contactphone,contactphone2_all,email2_all) values ('%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",$data['customer_id'],addslashes($data['branch_name']),addslashes($data['businessname']),addslashes($data['businesstype']),addslashes($data['firstname']),addslashes($data['lastname']),addslashes($data['contactphone']),addslashes($data['contactphone_all']),addslashes($data['email']),addslashes($data['email_all']),addslashes($data['secondary_businessname']),addslashes($data['secondary_businesstype']),addslashes($data['secondary_firstname']),addslashes($data['secondary_lastname']),addslashes($data['secondary_contactphone']),addslashes($data['contactphone2_all']),addslashes($data['email2_all'])); 
       		$insert =  $this->_db->query($sql_ins); 
       		return $insert;
       		
		}
 		return;    
    }   
    
     /**
	* Get trade show lists
	*/
 	public function getTradeshows () {       
        $sql = "SELECT * from tradeshow_list";
        $results = $this->_db->fetchAll($sql);
        return $results;
    }
    
     /**
	* return the arry about applies for company information
	*/
	public static function getApplys() {
		
		return array (          
            'Teeth Whitening Packages' => 'Teeth Whitening Packages',
            'Futura Face' => 'Futura Face',
            'Futura 2400' => 'Futura 2400',
            'Private Labeling' => 'Private Labeling',
            'Home Whitening Products' => 'Home Whitening Products',
            'In Office Whitening' => 'In Office Whitening',
            'Syringes' => 'Syringes',
            'Kit Components' => 'Kit Components',
            'Tan White' => 'Tan White',
            'Evoke Masks' => 'Evoke Masks',
            'Collagen Bed' => 'Collagen Bed',
            'Skin-FX' => 'Skin-FX',
            'Sapient Dental Products' => 'Sapient Dental Products',
            'Sonic-FX Tooth Brush' => 'Sonic-FX Tooth Brush',
           );
           
       /* return array (          
            'TW Package' => 'TW Package',
            'Futura Face' => 'Futura Face',
            'Futura 2400' => 'Futura 2400',
            'Private Label' => 'Private Label',
            'Home Whitening Products' => 'Home Whitening Products',
            'In Office Whitening' => 'In Office Whitening',
            'Syringes' => 'Syringes',
            'Kit Components' => 'Kit Components',
            'Tan White' => 'Tan White',
            'Evoke' => 'Evoke',    
            'Collagen Bed' => 'Collagen Bed',    
            'Skin-FX Pen' => 'Skin-FX Pen',    
            'Sapient Products' => 'Sapient Products',    
            'Sonic-FX' => 'Sonic-FX',    
           );*/
    }         
    
    
    /**
	* check if this customer is account
	*/
 	public function isAccount ($id) {       
        $query = "SELECT id from user where type='Account' and id =".$id;
        $result = $this->_db->fetchRow($query);
        if ($result)
        	return true;
        return false;
        
    }
    
     public function updateUserType($id,$type) {
        $query = sprintf("update user set type='%s' where id='%d'",$type,$id);
       	$res =  $this->_db->query($query);        		
       	return $res;
    }
    
     /**
	* Get Notes	for specific year
	*/
    public function getYearSalesNotes($id,$year){        
   	   	$query = "select * from user_notes where user_id = {$id} and (YEAR(enter_time)={$year}) and (type = 'note' or type='attempt') ORDER BY enter_time desc";   	
        $results = $this->_db->fetchAll($query);
		return $results;
    }
    
    
     /**
	* Check if this user has secondary contact
	*/
    public function getSecondaryContact($id){        
   	   	$query = "select id from user where id = {$id} and (length(secondary_businessname)>0 or length(secondary_businesstype)>0 or length(secondary_businessphone)>0 or length(secondary_firstname)>0 or length(secondary_lastname)>0 or length(contactphone2_all)>0 or length(email2_all)>0)";   	
       	$result = $this->_db->fetchRow($query);
    	if ($result)
        	return true;
        return false;
    }
    
    /**
	* Get document
	*/
 	public function getDocuments($id) {     
 	
 		//update current branch for customer
 		$sql = "select * from documents_info where customer_id={$id}";
        $results = $this->_db->fetchAll($sql);
		return $results;
        
    }
	
	/**
	* save document
	*/
 	public function saveDocument ($customer_id,$url,$title,$size,$description,$uploader,$ext) {   
 		if ($customer_id)
 		{
			       		
       		$sql_ins = sprintf("insert into documents_info (customer_id,url,title,size,description,uploader,uploaded_time,ext) values ('%d','%s','%s','%d','%s','%s',sysdate(),'%s')",$customer_id,$url,$title,$size,$description,$uploader,$ext);	
       		
       		$insert =  $this->_db->query($sql_ins); 
       		return $insert;
       		
		}
 		return;    
    }  
    
    
    /**
	* delete documents
	*/
 	public function deleteDocument ($data) {   
 		foreach($data as $del_id)
 		{
			$sql_del = "delete from documents_info where id={$del_id}";
			$res =  $this->_db->query($sql_del); 
		}
 		return;    
    }       
    
    /**
	* Update user	
	*/
     public function updateUser(array $data, $id) {
        try {
            if (isset($data['parentAccountID'])) {
                $parentId = $data['parentAccountID'];
                unset($data['parentAccountID']);
                $change = self::assign_account_user($parentId, $id);                
            }            
            
          
             $upd_sql = sprintf("update user set address1='%s',address2='%s',businessname='%s',businesstype='%s',businessphone='%s',certificate_issued='%s',city='%s',contact_made='%s',contactphone2_all='%s',contactphone_all='%s',contactphone2_type_all='%s',contactphone_type_all='%s',country='%s',customertype='%s',email2_all='%s',email_all='%s',firstname='%s',follow_up='%s',imported='%s',lastname='%s',opportunity='%s',order_frequency='%s',potential='%s',reason_followup='%s',secondary_businessname='%s',secondary_businesstype='%s',secondary_businessphone='%s',secondary_firstname='%s',secondary_lastname='%s',soldby='%s',source='%s',source_text='%s',state='%s',training_complete='%s',type='%s',zip='%s',tradeshows='%d',tradeshow_year='%d',website='%s',secondary_website='%s',fax='%s',secondary_fax='%s' where id='%d'",addslashes($data['address1']),addslashes($data['address2']),addslashes($data['businessname']),addslashes($data['businesstype']),addslashes($data['businessphone']),addslashes($data['certificate_issued']),addslashes($data['city']),addslashes($data['contact_made']),addslashes($data['contactphone2_all']),addslashes($data['contactphone_all']),addslashes($data['contactphone2_type_all']),addslashes($data['contactphone_type_all']),addslashes($data['country']),addslashes($data['customertype']),addslashes($data['email2_all']),addslashes($data['email_all']),addslashes($data['firstname']),addslashes($data['follow_up']),addslashes($data['imported']),addslashes($data['lastname']),addslashes($data['opportunity']),addslashes($data['order_frequency']),addslashes($data['potential']),addslashes($data['reason_followup']),addslashes($data['secondary_businessname']),addslashes($data['secondary_businesstype']),addslashes($data['secondary_businessphone']),addslashes($data['secondary_firstname']),addslashes($data['secondary_lastname']),addslashes($data['soldby']),addslashes($data['source']),addslashes($data['source_text']),addslashes($data['state']),addslashes($data['training_complete']),addslashes($data['type']),addslashes($data['zip']),$data['tradeshows'],$data['tradeshow_year'],addslashes($data['website']),addslashes($data['secondary_website']),addslashes($data['fax']),addslashes($data['secondary_fax']),$id);
             
            $update =  $this->_db->query($upd_sql);   
            
            if (isset($data['certificate_issued']))
            {
				$upd_sql = sprintf("update user set certificate_issued='%s' where id='%d'",$data['certificate_issued'],$id);
           		$update =  $this->_db->query($upd_sql);   
			}
			if (isset($data['training_complete']))
            {
            	$upd_sql = sprintf("update user set training_complete='%s' where id='%d'",$data['training_complete'],$id);
            	
            	$update =  $this->_db->query($upd_sql);   
			}
        	
            
            // update billing address
            $query = "select count(*) as is_exists from user_billing where user_id = {$id}";
            $result = $this->_db->fetchRow($query);
	    	if ($result)
	    	{
				 $upd_sql = sprintf("update user_billing set address1='%s',address2='%s',firstname='%s',lastname='%s',company='%s',city='%s',state='%s',zipcode='%s',country='%s' where user_id='%d'",$data['billing_address1'],$data['billing_address2'],$data['firstname'],$data['lastname'],$data['company'],$data['billing_city'],$data['billing_state'],$data['billing_zip'],$data['billing_country'],$id);
            	$update =  $this->_db->query($upd_sql);   
			}else
			{
				$ins_sql = sprintf("insert into user_billing (user_id,address1,address2,firstname,lastname,company,city,state,zipcode,country) values ('%d','%s','%s','%s','%s','%s','%s','%s','%s','%s')",$id,$data['billing_address1'],$data['billing_address2'],$data['firstname'],$data['lastname'],$data['company'],$data['billing_city'],$data['billing_state'],$data['billing_zip'],$data['billing_country']);	
				$update =  $this->_db->query($sql_ins); 
			}
	        	
             
           
            
            
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
    
    /**
	* save Transaction
	*/
 	public function saveTransaction($user_id,$creditcard,$total,$cvv,$expiration,$description,$sold_by,$now,$product,$approval_code,$avs_result,$cvv_result,$transaction_id,$res,$error_number,$error_message)
 	{
 		if ($user_id)
 		{			       		
       		$sql_ins = sprintf("insert into transaction_info (user_id,card_number,amount,cvv,expires,description,sold_by,transaction_date,products,approval_code,avs_result,cvv_result,transaction_id,result,error_number,error_message) values ('%d','%s','%f','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",$user_id,$creditcard,$total,$cvv,$expiration,$description,$sold_by,$now,$product,$approval_code,$avs_result,$cvv_result,$transaction_id,$res,$error_number,$error_message);	
       		
       		$insert =  $this->_db->query($sql_ins); 
       		return $insert;
       		
		}
 		return;    
    }       
    
     /**
	* get Transaction
	*/
 	public function getTransaction($user_id)
 	{
		$query = sprintf("select * from transaction_info where user_id = '%d'",$user_id);       		
   		$results = $this->_db->fetchAll($query);
		return $results;       				
    }  
    
    /**
	* get Transaction Authorize Key
	*/
 	public function getTransactionAuthor($sold_by)
 	{
 		$result=array();

 		$result['api_login'] = '6K8tC8jk7cSV';
 		$result['transaction_key'] = '6BPC48NhjxJ34z7L';
 		
 		if (stripos($sold_by,'Beaming White') !== false)
 		{
			$result['api_login'] = '82FfHpX922h';
 			$result['transaction_key'] = '437dStG7C4AtX8Ea';	
		}else if (stripos($sold_by,'Teeth Whitening Technology') !== false)
 		{
			$result['api_login'] = '6wB2jCXRg8n';
 			$result['transaction_key'] = '337T45dE2p5bYPxm';	
		}else if (stripos($sold_by,'Cool Smart Product') !== false)
 		{
/*			$result['api_login'] = '';
 			$result['transaction_key'] = '';	*/
		}else if (stripos($sold_by,'Sapient Dental') !== false)
 		{
/*			$result['api_login'] = '';
 			$result['transaction_key'] = '';	*/
		}else if (stripos($sold_by,'Cliona') !== false)
 		{
/*			$result['api_login'] = '';
 			$result['transaction_key'] = '';	*/
		}

		return $result;       				
    }  
    
     /**
	* Save User Products 
	*/
    public function saveUserProducts($userId,$user_prodcut) {
       	$upd_sql = sprintf("update user set product='%s' where id=%d",$user_prodcut,$userId);
        $update =  $this->_db->query($upd_sql);   
        return $update;
    }
    
    /* Get User Name */
    public function getUserName($user_id)
 	{
		$query = "select concat(firstname, ' ', lastname) as username from user where id = {$user_id}";				
   		$result = $this->_db->fetchRow($query);
		return $result;       				
    }  
 	
 	 /* Log followup event */
 	public function createFollowupLog($data) {
        try {              
            $insert =  $this->_db->insert('follow_up_info', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
    } 
    
    /*========================*/
}
