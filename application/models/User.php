<?php

class Application_Model_User {
    # Object level variables

    protected $_db;

    /**
     * Class constructor - Setup the DB connection
     */
    public function __construct() {
        # get handle on our database object
        $this->_db = Zend_Registry::get('db');
    }

    protected static function _getAuthAdapter() {     
    
        $dbAdapter = Application::getDBConnection();
        $authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);
             
        $authAdapter = new Zend_Auth_Adapter_DbTable(
                $dbAdapter, 'user', 'email', 'password',  "SHA1(CONCAT(?,salt)) AND status = 'Active'"
        );                    
        return $authAdapter;
    }
    
    public static function authenticate($post)
    {
        // Get our authentication adapter and check credentials
        $adapter = self::_getAuthAdapter();
        $adapter->setIdentity($post['email']); 
      //  echo md5($post['password']);
        $adapter->setCredential($post['password']);
       
        $auth = Zend_Auth::getInstance();
        
        $result = $auth->authenticate($adapter);
        
        if ($result->isValid()) {        	        	
            $user = $adapter->getResultRowObject();            
            $auth->getStorage()->write($user);   
            
            $authSession = new Zend_Session_Namespace('Zend_Auth');
            $authSession->setExpirationSeconds(86400);            
            return true;
        }
        return false;
    }  
    
    public function save(array $data) {
        $data['salt'] = md5($this->_getSalt());
        $data['password'] = sha1($data['password'] . $data['salt']);
       
        try {          
            $insert = $this->_db->insert('user', $data);     
            $userId = $this->_db->lastInsertId();            
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
    public function getUsers() {
        return $this->_db->fetchAll("SELECT * FROM user WHERE status = 'Active' order by role, firstname");
    } 
    public function getDepartments() {
        return array ('Accounting'=>'Accounting','Admin' => 'Admin', 'Graphics'=>'Graphics', 'Marketing'=>'Marketing', 'Production'=>'Production', 'Sales'=>'Sales', 
            'Other'=>'Other', 'External' => 'External');
    }   
    public function getUser($id = null) {
        $user = $this->_db->fetchRow("SELECT user.* from user where id = '$id'");        
        return $user?$user:false;
    }
    public function editUser(array $data, $id) {             
        try {                     
           $update = $this->_db->update('user', $data, $this->_db->quoteInto("id = ?", $id));          
           //if it's in the sales department, need to update the businsss user table
           if ($data['role'] == 'sales') {
               $crmDb = Zend_Registry::get('bwbusiness');                 
               $update = $crmDb->update('user', $data, $this->_db->quoteInto("id = ?", $id));   
           }
           return $update;
        } catch (Exception $e) {           
            return $e->getMessage();
        }        
        return;        
    }
    
    
    
    
    
    
    /*public function getUserByEmail($email = null) {
        $user = $this->_db->fetchRow("SELECT * from user where email = '$email'");        
        return $user?$user:false;
    }*/
    
    
    /*public function getUsers($parentId = null, $filter = null) {       
        $sql = "select * from account_user left join user on user.id = account_user.user
            WHERE parent_user = '$parentId' AND  status != 'Disabled'
            AND type = '$filter'
            ORDER BY user.firstname";      
        $results = $this->_db->fetchAll($sql);
        return $results;
    }*/
    
    /*public function getUsersPage ($type, $sort, $order, $start, $end, $parent_user = null) {    
        $filter = '';
        if ($parent_user) {
            $filter = " AND account_user.parent_user = '$parent_user' ";
        }
        
        $query = "select concat(u.firstname, ' ', u.lastname) as rep, a.* from (select user.id, businessname, contactphone, 
            firstname, lastname, email, created_time, 
            account_user.parent_user
            from user left join account_user on user.id = account_user.user
            WHERE status != 'Disabled' AND type = '$type' $filter ) a  
            left join user u on u.id = a.parent_user ORDER BY $sort $order LIMIT $start, $end";
        $result = $this->_db->fetchAll($query);
        return $result;
    }*/
    
    /*public function getUsersTotal($type, $parent_user = null) {  
        $filter = '';
        if ($parent_user) {
            $filter = " AND account_user.parent_user = '$parent_user' ";
        }
        $result = $this->_db->fetchRow("SELECT count(id) as total from `user`  left join account_user on user.id = account_user.user
            WHERE user.status != 'Disabled' and type = '$type' $filter");
        return $result;
    }*/
    
    /*public function getChildAccounts($id = null) {
        $sql = "select * from account_user left join user on user.id = account_user.user
            WHERE parent_user = '$id' AND status != 'pending' ORDER BY user.firstname";   
        //echo $sql;
        $results = $this->_db->fetchAll($sql);
        return $results;        
    }*/
    
    public function getSalesUsers () {
        $sql = "SELECT id, concat(firstname, ' ', lastname) as name FROM user WHERE role like 'sales%' and status = 'Active' ORDER BY firstname";
        $results = $this->_db->fetchAll($sql);
        return $results;
    }
    public function getSalesRep ($userId) {
        $result = $this->_db->fetchRow("SELECT parent_user, concat(firstname, ' ', lastname) as name from account_user 
            LEFT JOIN user ON account_user.parent_user = user.id 
            WHERE account_user.user = '$userId' ");
        return $result;
    }     
    
    public function assign_account_user($parent, $child){
        //one to one????
        //if exist, update, else add
        $accountUser = $this->_db->fetchRow("select * from account_user where user = '$child'");                
        $data = array('parent_user' => (int)$parent, 'user' => (int)$child);        
        
        if($accountUser && $accountUser['parent_user'] != $parent) {             
           $change =  $this->_db->query("UPDATE  account_user set parent_user = '$parent', user='$child' WHERE account_user_id = '{$accountUser['account_user_id']}'");          
        } elseif(!$accountUser) {
           $change =  $this->_db->insert('account_user', $data);
        }        
        return $change;
    }
      
    
    
   
       
    public function checkPassword($id, $password) {       
                
        $user = $this->_db->fetchRow("select id from user where password = sha1(CONCAT('$password', salt)) and id= $id");
        return $user?$user:FALSE;
    }
    
    //set temparary password
    public function setPasswordByEmail($email, $password) {
        //get new salt
        $data['salt'] = md5($this->_getSalt());
        $data['password'] = sha1($password . $data['salt']);
        
        $update = $this->_db->update('user', $data, $this->_db->quoteInto("email = ?", $email));
        return $update;        
    }
    
       
    
    public function resetPassword($id, $password) {
        //get new salt
        $data['salt'] = md5($this->_getSalt());
        $data['password'] = sha1($password . $data['salt']);
        $update = $this->_db->update('user', $data, $this->_db->quoteInto("id = ?", $id));
        return $update;
    }
    public function password_reset($data) {
        try {             
            $insert =  $this->_db->insert('password_reset', $data);
            return $this->_db->lastInsertId();                       
        } catch (Exception $e) {              
            return $e->getMessage();
        }
        return; 
    }
    public function check_reset($id) {        
        $reset = $this->_db->fetchRow("SELECT reset from password_reset where user_id = $id order by requested_time desc limit 1");                        
        if(!$reset || ($reset && $reset['reset'] == 1))
            return 1;
        return 0;         
    }
    public function update_reset($userId) {        
        $current = date("Y-m-d H:i:s");
       // echo "Update password_reset set reset = 1, reset_time = '$current'
         //   WHERE user_id = $userId order by requested_time DESC limit 1";
        //die();
        return $this->_db->query("Update password_reset set reset = 1, reset_time = '$current'
            WHERE user_id = $userId order by requested_time DESC limit 1"); 
    }
   
       
    private function _createFmUser(array $data) {
        $file_user['user'] = $file_user['email'] = $data['email'];
        $file_user['name'] = $data['firstname'].' '.$data['lastname'];
        $file_user['created_by'] = 'web';
        $file_user['active'] = 1;
        $insert = $this->_db->insert('tbl_users', $file_user);   
        if ($lastId = $this->_db->lastInsertId()) {        
            $this->_db->insert('tbl_members', array('added_by' =>'web' , 'client_id' => $lastId, 'group_id'=> 1 ));
        }
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
    public function account_conversion($data) {
        try {              
            $insert =  $this->_db->insert('account_conversion', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;        
    }
    
    
    public function deleteCard ($id) {
        $data['active'] = 0;        
        return $this->_db->update('user_card', $data, $this->_db->quoteInto("card_id = ?", $id));        
    }
    public function deletePaymentProfile ($id) {
        $data['active'] = 0;
        $data['action_time'] = date("Y-m-d H:i:s");
        return $this->_db->update('user_profile', $data, $this->_db->quoteInto("user_profile_id = ?", $id));        
    }
    
   /* public function saveCard ($data) {          
        try {     
            $check = $this->_db->fetchRow("SELECT card_id FROM user_card WHERE number = '{$data['number']}'");              
            if (!empty($check)) {
                $this->_db->update('user_card', $data, $this->_db->quoteInto("card_id = ?", $check['card_id']));
                return $check['card_id'];
            }
            $insert =  $this->_db->insert('user_card', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return; 
    }*/
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
     public function deleteAddress ($id) {
        $data['active'] = 0;        
        return $this->_db->update('user_address', $data, $this->_db->quoteInto("address_id = ?", $id));        
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
      
    
    public function getNotes($userId, $type="note") {        
        return $this->_db->fetchAll("SELECT * FROM user_notes WHERE user_id = '$userId' and type='$type' order by enter_time DESC ");         
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
    
    public function getCards($userId) {
        return $this->_db->fetchAll("SELECT * FROM user_card WHERE user_id = '$userId' AND active = 1");
    }
    public function getPaymentProfiles($userId) {
        return $this->_db->fetchAll("SELECT * FROM user_profile WHERE user_id = '$userId' AND active = 1");
    }
    public function getPaymentProfile($userId, $paymentProfileId) {
        return $this->_db->fetchRow("SELECT * FROM user_profile WHERE user_id = '$userId' AND payment_profile_id = '$paymentProfileId' AND active = 1");
    }
    public function getPaymentProfileById($user_profile_id) {
        return $this->_db->fetchRow("SELECT * FROM user_profile WHERE user_profile_id = '$user_profile_id'");
    } 
    
    public function getBillingAddress($userId) {
        return $this->_db->fetchRow("SELECT * FROM user_billing WHERE user_id = '$userId' AND active = 1");
    }    
    
    public function getAddresses($userId) {       
        return $this->_db->fetchAll("SELECT * FROM user_address WHERE user_id = '$userId' AND active = 1");
    }
   /* public function getAddressesByEmail($email) {
        return $this->_db->fetchAll("SELECT user_address.* FROM user_address 
        left join user on user.id = user_address.user_id   
        WHERE user_address.active = 1 and user.email = '$email'");
    }*/
    
    public function getAddress($userId, $addressId) {
        $check = $this->_db->fetchRow("SELECT * FROM user_address WHERE user_id = '$userId' AND address_id = '$addressId' ");
        return $check;
    }  
    public function getUserAddress($addressId) {
        return $this->_db->fetchRow("SELECT * FROM user_address WHERE address_id = '$addressId' ");     
    } 
    
    public function getCard($userId, $cardId) {
        $check = $this->_db->fetchRow("SELECT * FROM user_card WHERE user_id = '$userId' AND card_id = '$cardId' ");
        return $check;
    }
    public function getOrders ($userId) {
        return $this->_db->fetchAll("SELECT * FROM `order` WHERE user_id = '$userId' AND order_status in ('processing','onhold', 'shipped')");
    }
            
    
    public static function getLeadSource() {
        return array (
            "" => '',
            "Web Form" => 'Web Form',
            "Voice Mail" => 'Voice Mail',
            "Phone Call" => 'Phone Call',
            "Zoho" => 'Zoho', 
            "Quickbook" => 'Quickbook', 
            "Other" => 'Other');
    }/*
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
          
            "Beaming White" => 'Beaming White',
            "Cliona" => 'Cliona',
            "Cool Smart Product" => 'Cool Smart Product',
            "Sapient Dental" => "Sapient Dental",
            "Teeth Whitening Technology" => "Teeth Whitening Technology"            
           );
    }    
    
    public static function getAccountStatus() {
        return array('Pending'=>'Pending', 'Disabled'=>'Disabled', 'Active'=>'Active');
    }*/
            
    public static function gen_password() {                
        $characters = '#@!$%ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvqxyz01234567890';
        $string ='';
               
        for ($p = 0; $p < 8; $p++) {
            $string .= $characters[mt_rand(1, strlen($characters)-1 )];
        }
        return $string;        
    }
            
      
    public function getUserFiles($userId) {        

      $files = $this->_db->fetchAll("select * from files where userType = 'All'  
          OR userType in (select case when businesstype = 'dental' then 'Dentist' else 'non-dental' end 
          filetype from user where id = '$userId') ORDER BY uploadTime DESC");   
      return $files;
    }
       
    public function saveZohoNotes($data) {
       try {              
            $insert =  $this->_db->insert('zoho_notes', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;   
    }
    public function getZohoNotes() {
        $result = $this->_db->fetchAll("SELECT zoho_notes . * , user.id, user.resale_number
FROM `zoho_notes`
LEFT JOIN user ON user.resale_number = zoho_notes.zoho_id");
        return $result;
    }
    public function fixName($email, $data) {                
       // $update = $this->_db->update('user', $data, $this->_db->quoteInto("email = ?", $email));
        $query = "Update user set firstname = '{$data['firstname']}', lastname='{$data['lastname']}'
            WHERE email = '$email'";
        echo $query.';'.'<br>';
         //return $this->_db->query($query); 
             
    }
    /*for zoho import*/
     public function saveZohoBillingAddress ($data) {          
        try {        
           
            //$check = $this->_db->fetchRow("SELECT address_id FROM user_address WHERE address_id = '{$data['address_id']}'");       
            //if (!empty($check)) {
            if (isset($data['address_id'])) {
                $this->_db->update('user_billing', $data, $this->_db->quoteInto("address_id = ?", $data['address_id']));
                return $data['address_id'];
            }                
            //}
            $insert =  $this->_db->insert('user_billing', $data);
            return $this->_db->lastInsertId();                       
        } catch (Exception $e) {   
           // echo $e->getMessage();
            return $e->getMessage();
        }
        return; 
    }
}
