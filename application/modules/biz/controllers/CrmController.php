<?php
//require_once(__DIR__ . '/ringcentral-php/demo/_bootstrap.php');
require('AuthnetAIM.class.php');
//use RingCentral\SDK\SDK;
class CrmController extends Zend_Controller_Action {
    public function init() {        
        if(!Zend_Auth::getInstance()->getIdentity() || !in_array('crm', unserialize(Zend_Auth::getInstance()->getIdentity()->permission))){
            $this->_helper->redirector('index', 'fm');
        } 
        $this->view->headLink()->appendStylesheet('/public/css/crm.css');
        $this->_auth = Zend_Auth::getInstance()->getIdentity();
       
        $this->_users = new Application_Model_User;       
        $this->_crms = new Application_Model_Crm;  
        $this->_products = new Application_Model_Product;
				
		// mychael added
		$this->_helper->ajaxContext->addActionContext('crm', 'html')->initContext();
		
		/* Dariuz Rubin */
		// check if an active account does not have any current activity in a predetermined time period
		$this->_crms->notifyUnactivityAccount();
		/*==============*/
		
    }
    
  
    public function createUserAction() {
        
        $country = $this->getRequest()->isPost()?$_POST['country']:'US';        
        
        		
        /* Dariuz Rubin */
        $this->view->current_rep = $this->_auth->id;
        $this->view->businesstypes = $this->_crms->getBusinessType();
        $this->view->soldbys = $this->_crms->getSoldByOptions();
        $this->view->applys = $this->_crms->getApplys();
        $this->view->leadsources = $this->_crms->getLeadSource();
        $this->view->reps = $this->_crms->getSalesUsers(); 
        $this->view->countries = $this->_crms->getCountries();
        $this->view->tradeshows = $this->_crms->getTradeshows(); 
        $this->view->states = $this->_crms->getRegions($country);
        $this->view->country = $country;
        /*==============*/
              
        $form = new Application_Form_Signup($country);
        $form->removeElement('password');
        $form->removeElement('confirm_password');
        $form->removeElement('parentAccountID');
        $form->removeElement('interest');
        $form->removeElement('captcha');
        
        $this->view->type = $this->_getParam('type');
        
        if ($this->getRequest()->isPost()) {
            /* Dariuz Rubin */
            if ($_POST['submit_type'] != 'Noitfy')
            {
            	if ($_POST['submit_type'] == 'Create')
            		$this->_crms->noitifyCreationExistingAccount($_POST);
            	
				if($_POST['source'] == 'Internet') {
                  $_POST['source_text'] = trim($_POST['source_internet']);
             	} elseif ($_POST['source'] == 'Referred') {
                  $_POST['source_text'] = trim($_POST['source_refer']);
              	} elseif ($_POST['source'] == 'Tradeshow') {
                  $_POST['source_text'] = trim($_POST['source_tradeshow']);
             	} elseif ($_POST['source'] == 'Other') {
                  $_POST['source_text'] = trim($_POST['source_other']);
              	}
              	if (!isset($_POST['source']))
              	{
              		$_POST['source'] = 'Other';
					$_POST['source_text'] = trim($_POST['source_other']);
				}
              	if($_POST['source'] != 'Tradeshow') {
              		unset($_POST['tradeshows']);
            		unset($_POST['tradeshow_year']);
              	}	
             	$_POST['type'] = ucwords($this->_getParam('type'));
             	$_POST['password'] = $this->_users->gen_password();
              
          	    $_POST['username'] = $_POST['businessname'];
            	$_POST['created_time'] = date("Y-m-d H:i:s");
              	$_POST['parent_user'] = $_POST['parent_user'];
              	
              	
              	
              	$contactphone_all="";
		        $contactphone2_all="";
		        
	        	if (isset($_POST['contactphone']) and strlen($_POST['contactphone'])>0)
	        	{
	        		$_POST['contactphone'] = preg_replace("/[^0-9]*/s", "",$_POST['contactphone']);
					$contactphone_all .= $_POST['contactphone'].', ';
				}
				if (isset($_POST['cellphone']) and strlen($_POST['cellphone'])>0)
	        	{
	        		$_POST['cellphone'] = preg_replace("/[^0-9]*/s", "",$_POST['cellphone']);
					$contactphone_all .= $_POST['cellphone'].', ';
				}
		        
		        if (strlen($contactphone_all)>2)    
		        	$contactphone_all = substr($contactphone_all,0,strlen($contactphone_all)-2);
		        	
		    	if (isset($_POST['contactphone2']) and strlen($_POST['contactphone2'])>0)
	        	{
	        		$_POST['contactphone2'] = preg_replace("/[^0-9]*/s", "",$_POST['contactphone2']);
					$contactphone2_all .= $_POST['contactphone2'].', ';
				}
				if (isset($_POST['secondary_cellphone']) and strlen($_POST['secondary_cellphone'])>0)
	        	{
	        		$_POST['secondary_cellphone'] = preg_replace("/[^0-9]*/s", "",$_POST['secondary_cellphone']);
					$contactphone2_all .= $_POST['secondary_cellphone'].', ';
				}
		        
		        if (strlen($contactphone2_all)>2)    
		        	$contactphone2_all = substr($contactphone2_all,0,strlen($contactphone2_all)-2);
		        	
		    			        	
		        $_POST['contactphone_all'] = $contactphone_all;
		        $_POST['contactphone2_all'] = $contactphone2_all;
		        
		        // phone type
		        $contactphone_type_all="";
		        $contactphone2_type_all="";
		        
	        	if (isset($_POST['contactphone']) and strlen($_POST['contactphone'])>0)
	        	{
					$contactphone_type_all .= 'Work'.', ';
				}
				if (isset($_POST['cellphone']) and strlen($_POST['cellphone'])>0)
	        	{
					$contactphone_type_all .= 'Cell'.', ';
				}
		        
		        if (strlen($contactphone_type_all)>2)    
		        	$contactphone_type_all = substr($contactphone_type_all,0,strlen($contactphone_type_all)-2);
		        	
		    	if (isset($_POST['contactphone2']) and strlen($_POST['contactphone2'])>0)
	        	{
					$contactphone2_type_all .= 'Work'.', ';
				}
				if (isset($_POST['secondary_cellphone']) and strlen($_POST['secondary_cellphone'])>0)
	        	{
					$contactphone2_type_all.= 'Cell'.', ';
				}
		        
		        if (strlen($contactphone2_type_all)>2)    
		        	$contactphone2_type_all = substr($contactphone2_type_all,0,strlen($contactphone2_type_all)-2);
		        	
		    			        	
		        $_POST['contactphone_type_all'] = $contactphone_type_all;
		        $_POST['contactphone2_type_all'] = $contactphone2_type_all;
		        
		        $email_all="";
		        $email2_all="";
		        if (isset($_POST['email']) and strlen($_POST['email'])>0)
	        	{
					$email_all .= $_POST['email'];
				}
				 if (isset($_POST['email2']) and strlen($_POST['email2'])>0)
	        	{
					$email2_all .= $_POST['email2'];
				}
				
		        	
		        $_POST['email_all'] = $email_all;
		        $_POST['email2_all'] = $email2_all;
        		
        		// Products in interested
        		$user_prodcut = '';
	            if(!empty($_POST['applys_check'])) {
	               
	                foreach ($_POST['applys_check'] as $product) {                   
	                    $user_prodcut .= $product.',';
	                   
	                }
	                if (strlen($user_prodcut)>0)
	                	$user_prodcut = substr($user_prodcut,0,strlen($user_prodcut)-1);                 
	            }
	            
	            $_POST['product'] = $user_prodcut;
	            
              	$insert = $this->_crms->saveContact($_POST);
	            
	            //$this->_crms->saveUserProducts($insert,$user_prodcut);        
            	
         	
        
                //user created successfully
                if ($_POST['type'] == 'Account') {
                    /* $conversion = array ('user_id'=> $insert, 'user_type' => 'Account', 
                         'lead_source' => $_POST['imported'],
                         'created_time' => $_POST['created_time'], 'parent_id' => $_POST['parent_user'] , 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname                             
                                        );*/
                     $conversion = array ('user_id'=> $insert, 'user_type' => 'Account', 
                         'source' => $_POST['source'],
                         'created_time' => $_POST['created_time'], 'parent_id' => $_POST['parent_user'] , 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname                             
                                        );
                     $this->_crms->account_conversion($conversion);            
                }  
			    // Track the transition to Prospect
			   if ($_POST['type'] == 'Prospect') {
                    /* $conversion = array ('user_id'=> $insert, 'user_type' => 'Prospect', 
                         'lead_source' => $_POST['imported'],
                         'created_time' => $_POST['created_time'], 'parent_id' => $_POST['parent_user'] , 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname                             
                                        );*/
                    $conversion = array ('user_id'=> $insert, 'user_type' => 'Prospect', 
                         'source' => $_POST['source'],
                         'created_time' => $_POST['created_time'], 'parent_id' => $_POST['parent_user'] , 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname                             
                                        );
                     $this->_crms->prospect_conversion($conversion);            
                }        
                $this->_redirect('/crm/customer/id/' . $insert);
			}else
			{					
				$this->_crms->noitifyExistingAccount($_POST);
			}
          	/*================*/
           
        }    
      
    }

    public function customerAction() {

        $this->view->reps = $this->_crms->getSalesUsers(); 
        
        /* Dariuz Rubin */
        $this->view->user = $this->_crms->getUser($this->_getParam('id'));
        $this->view->tradeshows = $this->_crms->getTradeshows(); 
        $this->view->time_left = $this->_crms->getTimeleft($this->_getParam('id')); 
        $this->view->branches = $this->_crms->getBranches($this->_getParam('id'));
        
     	/*==============*/
     	
        if ($this->_getParam('prev') && $this->_getParam('type')) {          
            $user = $this->_crms->getPreviousNext($this->_getParam('prev'), $this->_getParam('type'), 'prev');
        } elseif ($this->_getParam('next') && $this->_getParam('type')) {          
            $user = $this->_crms->getPreviousNext($this->_getParam('next'), $this->_getParam('type'), 'next');
        } elseif ($this->_getParam('id') ){          
            $user = $this->_crms->getUser($this->_getParam('id'));
        }
        //var_dump($user);
        if (!isset($user) || !$user) {
            $user = $this->_crms->getLast();
        }
       
        $this->view->type = $user['type'];
        //if admin, you can view everyone, if not, you can only see your own and customer
        if ( $this->_auth->role != 'admin' && $user['type'] == 'Internal' && $this->_getParam('id') !=  $this->_auth->id ) {
            $this->_helper->flashMessenger->addMessage("You don't have the permission to access this information.");
            $this->_helper->redirector('login', 'user');
        }        
       
        $this->view->customerName = $user['firstname'].' '.$user['lastname'];     
        $this->view->customerId = $user['id'];                
        $this->view->headTitle('Beaming White Business Account');
       
    }
    
    public function leadsAction() {        
    	
    	/* Dariuz Rubin */
    	// No Contact Marketing Rep
    	$this->_crms->updateNoContactMarketing();
    	/*=================*/
		
        $total = $this->_crms->getUsersTotal('Lead');
        $this->view->total = $total['total']; 
        $this->view->reps = $this->_crms->getSalesUsers();  
        $this->view->potentials = $this->_crms->getAccountPotential();
        $this->view->soldBys = $this->_crms->getSoldByOptions();
        $this->view->sources = $this->_crms->getSource();
        
        unset($this->view->sources[""]);;
        $this->view->events = $this->_crms->getTodayEvents($this->_auth->id);
        
        /* Dariuz Rubin */
        // Get events from website contact
    	$this->view->website_events = $this->_crms->getTodayWebsiteEvents($this->_auth->id);
    	/*=================*/
    	
        if ($this->_getParam('repid')) {
             $this->view->rep = $this->_getParam('repid'); 
        }
        if ($this->_getParam('pid')) {
             $this->view->pid = $this->_getParam('pid'); 
        }
        if ($this->_getParam('source')) {
             $this->view->source = $this->_getParam('source'); 
        }
        if ($this->_getParam('soldBy')) {
             $this->view->soldBy = $this->_getParam('soldBy'); 
        } 
        if ($this->_getParam('lastAttempt')) {
             $this->view->lastAttempt = $this->_getParam('lastAttempt'); 
        }
        
        /* Dariuz Rubin */
        if ($this->_getParam('from')) {
             $this->view->from = $this->_getParam('from'); 
        }
        if ($this->_getParam('to')) {
             $this->view->to = $this->_getParam('to'); 
        }
        /*=============*/
    }
    public function getLeadsAction() {               
   
       $rep = $this->_getParam('repid');
       $potential = $this->_getParam('pid');
       $source = $this->_getParam('source');
       $soldBy = $this->_getParam('soldBy');
       $lastAttempt = $this->_getParam('lastAttempt');
                  
       $page = !$this->_getParam('page')?1: $this->_getParam('page');        
       $rows = !$this->_getParam('rows')?1:$this->_getParam('rows');
     
       $offset = $rows * ($page - 1);
       $sort = !$this->_getParam('sort') ? 'created_time' : $this->_getParam('sort');
       $order = !$this->_getParam('order') ? 'DESC' : $this->_getParam('order');
           
       //echo $start; echo $end;        
       
       /* Dariuz Rubin */
    	$from = $this->_getParam('from');
        $to = $this->_getParam('to');
        $result['rows'] = $this->_crms->getUsersPage ('Lead', $sort, $order, $offset, $rows, $rep, $potential, $source, $soldBy, $lastAttempt,$from,$to);   
        $total = $this->_crms->getUsersTotal('Lead', $rep, $potential,$source, $soldBy, $lastAttempt,$from,$to);
        /*=============*/
        
        $result['total'] = $total['total'];
        
        echo json_encode($result);
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    public function prospectAction() {        
    	/* Dariuz Rubin */
    	// Contacted Marketing Rep
    	$this->_crms->updateContactMarketing();
    	/*=================*/
        $total = $this->_crms->getUsersTotal('Prospect');
        $this->view->total = $total['total']; 
        $this->view->reps = $this->_crms->getSalesUsers();  
        $this->view->potentials = $this->_crms->getAccountPotential();
        $this->view->soldBys = $this->_crms->getSoldByOptions();
        $this->view->sources = $this->_crms->getSource();
        
        unset($this->view->sources[""]);;
        $this->view->events = $this->_crms->getTodayEvents($this->_auth->id);        
        
        /* Dariuz Rubin */
        // Get events from website contact
    	$this->view->website_events = $this->_crms->getTodayWebsiteEvents($this->_auth->id);
    	/*=================*/
    	
        if ($this->_getParam('repid')) {
             $this->view->rep = $this->_getParam('repid'); 
        }
        if ($this->_getParam('pid')) {
             $this->view->pid = $this->_getParam('pid'); 
        }
        if ($this->_getParam('source')) {
             $this->view->source = $this->_getParam('source'); 
        }
        if ($this->_getParam('soldBy')) {
             $this->view->soldBy = $this->_getParam('soldBy'); 
        } 
        if ($this->_getParam('lastAttempt')) {
             $this->view->lastAttempt = $this->_getParam('lastAttempt'); 
        }      
        
        /* Dariuz Rubin */
        if ($this->_getParam('from')) {
             $this->view->from = $this->_getParam('from'); 
        }
        if ($this->_getParam('to')) {
             $this->view->to = $this->_getParam('to'); 
        }
        /*=============*/
        
    }
     public function getProspectAction() {     
        
       $rep = $this->_getParam('repid');
       $potential = $this->_getParam('pid');
       $source = $this->_getParam('source');
       $soldBy = $this->_getParam('soldBy');
       $lastAttempt = $this->_getParam('lastAttempt');
                   
       $page = !$this->_getParam('page')?1: $this->_getParam('page');        
       $rows = !$this->_getParam('rows')?1:$this->_getParam('rows');
     
       $offset = $rows * ($page - 1);
       $sort = !$this->_getParam('sort') ? 'created_time' : $this->_getParam('sort');
       $order = !$this->_getParam('order') ? 'DESC' : $this->_getParam('order');
        
         /* Dariuz Rubin */
    	$from = $this->_getParam('from');
        $to = $this->_getParam('to');
        $result['rows'] = $this->_crms->getUsersPage ('Prospect', $sort, $order, $offset, $rows, $rep, $potential, $source, $soldBy, $lastAttempt,$from,$to);   		$total = $this->_crms->getUsersTotal('Prospect', $rep, $potential,$source, $soldBy, $lastAttempt,$from,$to);
        /*=============*/
        
      
        $result['total'] = $total['total'];
        
        echo json_encode($result);
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    public function deleteCustomerAction(){        
        if (in_array('delete_crm', unserialize($this->_auth->permission))) {
            if ($this->_crms->deleteUser($_POST['id'])) {
                echo 'success';
            } else {
                echo 'Unable to delete';
            }
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();        
    }  


		
	public function accountsAction() {
        $total = $this->_crms->getUsersTotal('Account');
        $this->view->total = $total['total']; 
        $this->view->reps = $this->_crms->getSalesUsers();  
        $this->view->potentials = $this->_crms->getAccountPotential();
        $this->view->soldBys = $this->_crms->getSoldByOptions();
        $this->view->sources = $this->_crms->getSource();
        
        unset($this->view->sources[""]);;
        $this->view->events = $this->_crms->getTodayEvents($this->_auth->id);
        
        /* Dariuz Rubin */
        // Get events from website contact
    	$this->view->website_events = $this->_crms->getTodayWebsiteEvents($this->_auth->id);
    	/*=================*/
    	
        if ($this->_getParam('repid')) {
             $this->view->rep = $this->_getParam('repid'); 
        }
        if ($this->_getParam('pid')) {
             $this->view->pid = $this->_getParam('pid'); 
        }
        if ($this->_getParam('source')) {
             $this->view->source = $this->_getParam('source'); 
        }
        if ($this->_getParam('soldBy')) {
             $this->view->soldBy = $this->_getParam('soldBy'); 
        } 
        if ($this->_getParam('lastAttempt')) {
             $this->view->lastAttempt = $this->_getParam('lastAttempt'); 
        }  
        
        /* Dariuz Rubin */
        if ($this->_getParam('from')) {
             $this->view->from = $this->_getParam('from'); 
        }
        if ($this->_getParam('to')) {
             $this->view->to = $this->_getParam('to'); 
        }
        /*=============*/ 
    }
    public function getAccountsAction() {
         $rep = $this->_getParam('repid');
       $potential = $this->_getParam('pid');
       $source = $this->_getParam('source');
       $soldBy = $this->_getParam('soldBy');
       $lastAttempt = $this->_getParam('lastAttempt');
                   
       $page = !$this->_getParam('page')?1: $this->_getParam('page');        
       $rows = !$this->_getParam('rows')?1:$this->_getParam('rows');
     
       $offset = $rows * ($page - 1);
       $sort = !$this->_getParam('sort') ? 'created_time' : $this->_getParam('sort');
       $order = !$this->_getParam('order') ? 'DESC' : $this->_getParam('order');
           
       
         /* Dariuz Rubin */
    	$from = $this->_getParam('from');
        $to = $this->_getParam('to');
        $result['rows'] = $this->_crms->getUsersPage ('Account', $sort, $order, $offset, $rows, $rep, $potential, $source, $soldBy, $lastAttempt,$from,$to);   		$total = $this->_crms->getUsersTotal('Account', $rep, $potential,$source, $soldBy, $lastAttempt,$from,$to);
        /*=============*/
       
        $result['total'] = $total['total'];
        
        echo json_encode($result);
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $this->_helper->layout()->disableLayout();
    }
    
    public function addressBookAction() {
        $this->view->billingAddress = $this->_crms->getBillingAddress($this->_getParam('id'));
        $this->view->addresses = $this->_crms->getAddresses($this->_getParam('id'));
        $this->view->userId = $this->_getParam('id');      
        $this->_helper->layout()->disableLayout();
    }    
    public function deleteAddressAction() {
        $this->_crms->deleteAddress($this->_getParam('id'));
        $this->_helper->layout()->disableLayout();  
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
     public function paymentAction() {        
        $user = $this->_crms->getUser($this->_getParam('id'));
        if ($user['payment_option'] != 'card') {
            $this->view->alert = "This Customer Payment Option is {$user['payment_option']}";
        }
        $this->view->profiles = $this->_crms->getPaymentProfiles($this->_getParam('id'));       
        $this->view->userId = $this->_getParam('id');
        $this->_helper->layout->disableLayout();        
    }
    public function editPaymentProfileAction()
    {         
        if ($this->_getParam('from') == 'crm') {
              //$form->setAction("/biz/user/edit-payment-profile/id/{$this->_getParam('id')}/from/crm");
            $profile = $this->_crms->getPaymentProfileById($this->_getParam('id'));
        } else {
            $profile = $this->_crms->getPaymentProfile($this->_auth->id, $this->_getParam('id'));
        }
        if (empty($profile))
            $this->_helper->redirector('wallet');
                
        $request = new Application_Service_AuthorizeNetCIM;
        $paymentProfile = $request->getCustomerPaymentProfile($profile['profile_id'], $profile['payment_profile_id']);           
     
        //$number = $paymentProfile->xml->paymentProfile->payment->creditCard;
        $card['number'] = $paymentProfile->xml->paymentProfile->payment->creditCard->cardNumber;
        $card['month'] = $profile['month'];
        $card['year'] = $profile['year'];
        
        $bill = $paymentProfile->xml->paymentProfile->billTo;
        $card['firstName'] = $bill->firstName;
        $card['lastName'] = $bill->lastName;
        $card['address1'] = $bill->address;
        $card['city'] = $bill->city;
        $card['state'] = $bill->state;
        $card['country'] = $bill->country;
        $card['zip'] = $bill->zip;
        $card['phone'] = $bill->phoneNumber;
        
        $form = new Application_Form_Card($card['country']);
        if ($this->_getParam('from') == 'crm') {
              $form->setAction("/crm/edit-payment-profile/id/{$this->_getParam('id')}/from/crm");
              $form->removeElement('submit');
              $form->setAttrib('id', 'card') ;
                //$profile = $this->_users->getPaymentProfileById($this->_getParam('id'));
        }
        $form->populate($card);
        $form->getElement('number')->setAttrib('readonly', 'readonly')->setValidators(array());
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {            
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $customerProfile                    = new stdClass();
                    
                $billTo ->firstName = $data['firstName'];
                $billTo ->lastName = $data['lastName'];
                $billTo ->address = $data['address1'].''.$data['address2'];
                $billTo ->city = $data['city'];
                $billTo ->state = $data['state'];
                $billTo ->zip = $data['zip'];
                $billTo ->country = $data['country'];
                $billTo->phoneNumber = $data['phone'];
                $billTo->faxNumber = '123';                
                $customerProfile->billTo[] = $billTo; 
                
                $sale->creditCard->cardNumber = $data['number'];
                $sale->creditCard->expirationDate = $data['year'].'-'.$data['month'];
                $customerProfile->payment[] = $sale; 
           
               // $response = $request->updateCustomerPaymentProfile($this->_auth->profile_id, $this->_getParam('id'), $customerProfile, 'none'); 
                $response = $request->updateCustomerPaymentProfile($profile['profile_id'], $profile['payment_profile_id'], $customerProfile, 'none'); 
                
               // var_dump($response);
                if ($response->isOk()) {                    
                    $this->customerPaymentProfileId = $response->getPaymentProfileId();           
                    //$user_profile['payment_profile_id'] = $this->_getParam('id');
                    $user_profile['payment_profile_id'] = $profile['payment_profile_id'];
                    $user_profile['month'] = $data['month'];
                    $user_profile['year'] = $data['year'];
                    if ($this->_crms->saveProfile($user_profile)) {
                        //$this->_helper->redirector('wallet');
                    }
                    if ($this->getRequest()->isXmlHttpRequest()) {
                        //$this->_helper->layout()->disableLayout();
                        $this->_helper->viewRenderer->setNoRender(true);
                    }
                }
            } else {
                if ($this->getRequest()->isXmlHttpRequest()) {
                     echo "Please Enter all required fields";
                     $this->_helper->viewRenderer->setNoRender(true);
                }
            }
        }
         if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->layout()->disableLayout();
            //$this->_helper->viewRenderer->setNoRender(true);
        }
    }    
    
    public function deleteCardAction() {        
        //first check if the card belong to this user
        $profile = $this->_crms->getPaymentProfileById($this->_getParam('id'));
        //delete from authnet
        $request = new Application_Service_AuthorizeNetCIM;
        $paymentProfile = $request->deleteCustomerPaymentProfile((int)$profile['profile_id'],(int)$profile['payment_profile_id']);
        //flag it in the database
        $this->_crms->deletePaymentProfile((int)$profile['user_profile_id']);               
      
        $this->_helper->layout()->disableLayout();  
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    public function addCardAction() {
        
    if (!$this->_auth) {
        $this->_helper->redirector('login'); 
    }
    
    $user = $this->_crms->getUser($this->_getParam('id'));
   
    $form = new Application_Form_Card($user['country']);
    $form->removeElement('submit');
    $form->setAction("crm/add-card/{$this->_getParam('id')}");    
    $form->setAttrib('id', 'card') ;
    $form->populate($user);    
    $this->view->form = $form;
    
    
    $request = new Application_Service_AuthorizeNetCIM;
    
    //create user profile if not existing
    //to do: add CC user condition
    //var_dump($this->_auth->profile_id);
    
    if ($user['profile_id'] == 0 || is_null($user['profile_id'])) {        
        $customerProfile                  = (object)array();
        $customerProfile->merchantCustomerId= $user['id'];       
        $response = $request->createCustomerProfile($customerProfile); 
        if ($response->isOk()) {
            $user['profile_id'] = $response->getCustomerProfileId();           
        }
        //update      
        $data= array('profile_id' =>  $user['profile_id']);        
        $this->_crms->editUser($data, $user['id']); 
    }    
    

    if ($this->getRequest()->isPost()) { 
       
            if ($form->isValid($_POST))  {
                                
                //create customer payment profile
                $data = $form->getValues();
                
                $cardType = $this->_crms->getCardType($data['number']);
                // var_dump($cardType);
                //die();
                //Reject JCB and dinner
                if ($cardType == 'Diners Club' || $cardType == 'JCB') {
                    $error = "Sorry we don't accept Diners Club or JCB card.";
                    $form->getElement('number')->addError($error);                   
                    echo $error;
                    return;
                }
                
                //$customerProfile                  = (object)array();
                $customerProfile    = new stdClass();               
                $billTo = new stdClass();
                $sale = new stdClass();
                $sale->creditCard = new stdClass();
                
                $billTo->firstName = $data['firstName'];
                $billTo->lastName = $data['lastName'];
                $billTo->address = $data['address1'].''.$data['address2'];
                $billTo->city = $data['city'];
                $billTo->state = $data['state'];
                $billTo->zip = $data['zip'];
                $billTo->country = $data['country'];
                $billTo->phoneNumber = $data['phone'];
                $billTo->faxNumber = '123';                
                $customerProfile->billTo[] = $billTo; 
              
                $sale->creditCard->cardNumber = $data['number'];
                $sale->creditCard->expirationDate = $data['year'].'-'.$data['month'];                
                $customerProfile->payment[] = $sale;              
              
                $response = $request->createCustomerPaymentProfile($user['profile_id'], $customerProfile);  
                              
                if ($response->isOk()) {
                                                          
                    $this->customerPaymentProfileId = $response->getPaymentProfileId();                    
                   
                    //save it into the database
                    $profile = array('user_id' => $user['id'], 
                        'profile_id' => $user['profile_id'],
                        'type' => $cardType,
                        'payment_profile_id' => $this->customerPaymentProfileId,
                        'month' => $data['month'],
                        'year' => $data['year']
                        );      
                   
                   if ($this->_crms->saveProfile ($profile)) {                       
                        echo 'success';
                        $this->_helper->viewRenderer->setNoRender(true);
                   }
                }               
            } else {
                
                 if ($this->getRequest()->isXmlHttpRequest()) {                  
                     $this->_helper->layout->disableLayout();
                     echo "Please Enter all required fields";   
                     
                     $this->_helper->viewRenderer->setNoRender(true);                     
                }               
            }
         }
       //$this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();                
    }
       
    public function orderHistoryAction()
    {        
       $this->view->userId = $this->_getParam('id');
       $this->_helper->layout->disableLayout();            
    }
     public function orderHistoryDataAction()
    {               
        $perPage = 10;
        $count = $this->_users->getOrderTotal($this->_getParam('id'));
        $this->view->totalPages = ceil($count['total']/$perPage);        
        $this->view->page = $this->getParam('page');        
        
        $from = ($this->getParam('page') - 1) *$perPage;
        
        $this->view->orders = $this->_users->getOrders($this->_getParam('id'), $from, $perPage);
        $this->_helper->layout->disableLayout();
    }
    
    public function productAction()
    {
        $this->view->userId = $this->_getParam('id');    
        $this->view->products = $this->_products->getAllProduct();
        
        if ($this->_getParam('id')) {
            $userProducts = $this->_products->user_product($this->_getParam('id'));             
            foreach ($userProducts as $product) {
                $userProduct[]= $product['product_id'];
            }
            $this->view->userProduct = isset($userProduct)?$userProduct:array();
        }
       
        if($this->getRequest()->isPost()) {   
            if(!empty($_POST['selectedProduct'])) {
                //first selected
                foreach ($_POST['selectedProduct'] as $productId) {                   
                    $pid = $this->_products->user_product_price($_POST['userId'], $productId);
                   // echo 'Added ' . $pid.'<br>';
                }
                $product = "'" . implode("','", $_POST['selectedProduct']) . "'";
                // echo $product;                 
                //need to remove those that's not selected
                 $this->_products->user_unselect_product($_POST['userId'], $product);                       
            } else {
                //remove everything
                $this->_products->delete_all_user_products($_POST['userId']);
            }            
            
            $result['userproductnames'] = $this->_products->user_productname($_POST['userId']);     
            
         	echo json_encode($result);
        	
        	
            $this->_helper->viewRenderer->setNoRender(true);
        }
        $this->_helper->layout->disableLayout();
    }
    
    public function priceAction() {
        $this->view->prices = $this->_products->getUserProduct($this->_getParam('id'));   
        $this->_helper->layout->disableLayout();
    }
    public function updatePriceAction()
    {       
        $result = $this->_products->updateProductPrice($_POST['product_price_id'], $_POST['price']);
        echo $result; 
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }
    /*public function getProductAction()
    {
        $products = $this->_products->getUserProduct($this->_getParam('id'));        
        echo json_encode( $products);       
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }
    public function updatePriceAction()
    {       
        $this->_products->updateProductPrice($_POST, $_POST['product_price_id']);
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }
    public function deletePriceAction()
    {
        $data['active'] = 0;
        $this->_products->updateProductPrice($data, $_POST['id']);
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }
    
    
    public function savePriceAction()
    {
        unset($_POST['isNewRecord']);
        $data = $_POST;
        $data['user_id'] = (int)$this->_getParam('id');
        
        $this->_products->insertProductPrice($data);
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
    }
*/
  /*  public function accountsAction() {               
        
        if(!Zend_Auth::getInstance()->getIdentity() || Zend_Auth::getInstance()->getIdentity()->role =='customer') {
            $this->_helper->flashMessenger->addMessage("You don't have the permission to access this information.");
            $this->_helper->redirector('index', 'user');           
        }
        // get pending users
        $this->view->pendingUsers = $this->_users->getUsers(Zend_Auth::getInstance()->getIdentity()->id, 'Lead');
       
        // get representative users        
        $this->view->myUsers = $this->_users->getChildAccounts(Zend_Auth::getInstance()->getIdentity()->id, 'active');
        //get all active users
        $this->view->activeUsers = $this->_users->getUsers(Zend_Auth::getInstance()->getIdentity()->id, 'active');
              
    }*/
  
    public function notesAction() {
        if ($this->getRequest()->isPost()) {           
           // $auth = Zend_Auth::getInstance()->getIdentity();
            if (trim($_POST['notes']) != '' && trim($_POST['notes']) != 'Add a note') {
                $data = array ('user_id' =>(int)$_POST['userId'],
                    'type' => 'note',
                    'notes' => trim($_POST['notes']),
                    'author' => $this->_auth->firstname.' '.$this->_auth->lastname
                    );      
                $this->_crms->savenotes($data);               
                
                //if ajax call
                if ($this->getRequest()->isXmlHttpRequest()) {                    
                    echo '<br>'.date('m/d/y g:i a') . ' '. $data['author'].'<br>'.$data['notes'].'<br>';                    
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);                    
                }
            }
        }        
    }
    public function passwordAction() {
        if ($this->getRequest()->isPost()) {                        
             $tempPW = $this->_crms->gen_password();                                
             $update = $this->_crms->setPasswordByEmail(trim($_POST['email']), $tempPW);                
                if($update) {   
                    $user = $this->_crms->getUserByEmail(trim($_POST['email']));
                    $reset = array('user_id' => $user['id'],
                            'email'=> $user['email'],
                            'requested_time' => date("Y-m-d H:i:s"),
                            'ip' => $_SERVER['REMOTE_ADDR']
                             );                    
                    //save to flag them to change
                    $this->_crms->password_reset($reset);
                    $mail = new Zend_Mail();
                    $name = $user['firstname'].' '.$user['lastname'];
                    $message = "Dear $name,<br><br>
                    You have requested to have your password reset. <br><br>
                    Please use this temporary password to <a href='https://www.beamingwhite.com/biz/user/login'>login </a> to your account: $tempPW<br><br>
                    You will be prompted to change your password once you login using your temporary password. <br> 
                    <br><br>
                    Thank you, <br>
                    Beaming White <br>
                    1 (866) 944-8315";

                    //$message = "Email sent, Your temparary password is $tempPW. Please visit <a href = '/biz/user/password'> here </a>to reset your password";
                    $mail->setBodyHTML($message);
                    $mail->setFrom('customer.info@beamingwhite.com', 'beamingwhite.com');
                    $mail->addTo($user['email'], $name);
                    $mail->setSubject('Information regarding your account with Beaming White');
                    $mail->send();
                    
                    echo $tempPW;
                }
          
        }        
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true); 
    }
    public function leadAttemptAction() {
       if ($this->getRequest()->isPost()) {           
           // $auth = Zend_Auth::getInstance()->getIdentity();
            if (trim($_POST['leadAttempt']) != '') {
                $data = array ('user_id' =>(int)$_POST['userId'],
                    'type' => 'attempt',
                    'notes' => trim($_POST['leadAttempt']),
                    'author' => $this->_auth->firstname.' '.$this->_auth->lastname,
                    'author_id' => $this->_auth->id
                    );      
                $this->_crms->savenotes($data);               
                
                // check if this customer has followup reminder in 30 miniutes
                $this->_crms->doSetActiveValues($data); /* Dariuz Rubin */
                
                //if ajax call
                if ($this->getRequest()->isXmlHttpRequest()) {                    
                    echo '<br>'.date('m/d/y g:i a') . ' '. $data['author'].'<br>'.$data['notes'].'<br>';                    
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);                    
                }
            }
        } 
         $this->_helper->layout()->disableLayout();
         $this->_helper->viewRenderer->setNoRender(true); 
    }
        
    public function edituserAction()
    {       
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
               
        $id = $_POST['pk'];
        $data = array($_POST['name'] => $_POST['value']);
        $user = $this->_crms->getUser($id);
        if(isset($data['type'])) {
            if (($user['type'] == 'Lead' || $user['type'] == 'Prospect') && $data['type'] == 'Account') {
                //track conversion
                $conversion = array ('user_id'=> $id, 'user_type' => $user['type'], 'lead_source' => $user['imported'],
                        'created_time' => $user['created_time'], 'parent_id' => $user['parent_user'], 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname
                      );
                $this->_crms->account_conversion($conversion);
            }
            
            /* Dariuz Rubin */
			// Track the transition to Prospect
			if (($user['type'] == 'Lead') && $data['type'] == 'Prospect') {
                //track conversion
                $conversion = array ('user_id'=> $id, 'user_type' => $user['type'], 'lead_source' => $user['imported'],
                        'created_time' => $user['created_time'], 'parent_id' => $user['parent_user'], 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname
                      );
                $this->_crms->prospect_conversion($conversion);
            }
			/* ============= */
        }
                        
        if ($_POST['name'] == 'country' || $_POST['name'] == 'billingcountry') {
            foreach ($this->_crms->getRegions($_POST['value']) as $state) {
                 $this->view->states .= ' {value:"'.$state['code'].'", text: "'.$state['name'].'"},'; 
            }
            $return['currentStates'] = '['.$this->view->states.']';
        }
        
        //billing address is saved in a different table, Hell QB
        $billing = array('billingfirstname','billinglastname','billingcompany', 'billingaddress1', 'billingaddress2', 'billingcity', 'billingstate', 'billingcountry', 'billingzipcode');
        if (in_array($_POST['name'], $billing)) {
             $name = preg_split('/billing/', $_POST['name']);
             $data = array($name[1] => $_POST['value']);
             $update = $this->_crms->saveBillingAddress($data, $id);             
        } elseif ($_POST['name'] == 'euTraining') {
            $update = $this->_crms->setEuTraining($id, $_POST['value']);
        } else {            
            $update = $this->_crms->editUser($data, $id);
        }
        
        if ($update) {           
             if (strstr($update, 'SQLSTATE[23000]')) {
                 $return['success'] = false; 
                 $return['message'] = 'Email existing';
             }else {
                 $return['success'] = true;
                 if($_POST['name'] == 'potential') {
                     $return['potential'] = $_POST['value'];  
                     $return['prev'] = $user['potential'];  
                 }
             }        
        } else {
            $return['success'] = false;
            $return['message'] = 'Update failed';
        }        
        echo json_encode($return); 
      
        
    }  
     
   public function viewAction() {
         $this->view->user = $this->_crms->getUser($this->_getParam('id'));
         $this->view->user_billing = $this->_crms->getBillingAddress($this->_getParam('id'));
         $this->view->euTraining = $this->_crms->getEuTraining($this->_getParam('id'));
         $this->view->user['euTraining'] =  $this->view->euTraining?'1':'0';
        
         /* Dariuz Rubin */
         $this->view->branches = $this->_crms->getBranches($this->_getParam('id'));
         $this->view->time_left = $this->_crms->getTimeleft($this->_getParam('id')); 
         $this->view->tradeshows = $this->_crms->getTradeshows(); 
         $this->view->reps = $this->_crms->getSalesUsers(); 
         $this->view->potentials = $this->_crms->getAccountPotential();
         $this->view->leadsources = $this->_crms->getLeadSource();
         $this->view->sources = $this->_crms->getSource();
         $this->view->accounttypes = $this->_crms->getAccountType();
         $this->view->customertypes = $this->_crms->getCustomerType();
         $this->view->soldby = $this->_crms->getSoldByOptions();
         $this->view->products = $this->_products->getAllProduct();
         $this->view->user_products = $this->_products->getUserProducts();
         $this->view->user_product = explode(',',$this->view->user['product']); // products for Account Tab
         $this->view->countries = $this->_crms->getCountries();
         
         $this->view->secondaryContact = $this->_crms->getSecondaryContact($this->_getParam('id'));
         $this->view->current_notes = $this->_crms->getYearSalesNotes($this->_getParam('id'),date('Y'));
         
         $this->view->customer_comments = $this->_crms->getNotes($this->_getParam('id'), 'customer_comment');
          
         if ($this->_getParam('id')) {
            $userProducts = $this->_products->user_product($this->_getParam('id'));             
            foreach ($userProducts as $product) {
                $userProduct[]= $product['product_id'];
            }
            $this->view->userProduct = isset($userProduct)?$userProduct:array();
        }
         $this->view->userproductnames = $this->_products->user_productname($this->_getParam('id'));             
      
     	 /*==============*/
                 
         $this->view->attempts = $this->_crms->getNotes($this->_getParam('id'), 'attempt');
         $this->view->sales = $this->_crms->getNotes($this->_getParam('id'), 'entersale'); //Dariuz Rubin
         $this->view->notes = $this->_crms->getNotes($this->_getParam('id'), 'note');
         $this->view->followup = $this->_crms->getFollowup($this->_getParam('id'));
         
         $salesReps = $this->_crms->getSalesUsers();      
         $this->view->userRep = $this->_crms->getSalesRep($this->_getParam('id'));
         $this->view->auth = 0;
        
         if (($this->view->user['parent_user'] ==1) || in_array('change_rep', unserialize($this->_auth->permission))) {
             $this->view->auth = 1;
         }
         $this->view->billingAddress = $this->_crms->getBillingAddress($this->_getParam('id'));
        
         foreach ($this->_crms->getLeadSource() as $key => $value) {
             $this->view->leadSource .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
       /*  foreach ($this->_crms->getCountries() as $country) {
             $this->view->countries .= ' {value:"'.$country['iso_code_2'].'", text: "'.$country['name'].'"},'; 
         }*/
         //business address
         foreach ($this->_crms->getRegions($this->view->user['country']) as $state) {
             $this->view->states .= ' {value:"'.$state['code'].'", text: "'.$state['name'].'"},'; 
         }
         //billing address
         foreach ($this->_crms->getRegions($this->view->billingAddress['country']) as $state) {
             $this->view->billingStates .= ' {value:"'.$state['code'].'", text: "'.$state['name'].'"},'; 
         }
         
         foreach ($this->_crms->getAccountType() as $key => $value) {
             $this->view->accountType .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
         foreach ($this->_crms->getAccountStatus() as $key => $value) {
             $this->view->accountStatus .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
         foreach ($this->_crms->getAccountPotential() as $key => $value) {
             $this->view->accountPotential .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
         foreach ($this->_crms->getBusinessType() as $key => $value) {             
             $this->view->businessType .= ' {value:"'.$key.'", text: "'.$value.'"},';              
         }
         foreach ($this->_crms->getCustomerType() as $key => $value) {
             $this->view->customerType .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
         foreach ($this->_crms->getPaymentOptions() as $key => $value) {
             $this->view->paymentOptions .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
         foreach ($this->_crms->getSoldByOptions() as $key => $value) {
             $this->view->soldbyOptions .= ' {value:"'.$key.'", text: "'.$value.'"},'; 
         }
             
         $this->view->euTrainingOption = ' {value:"1", text: "Yes"}, {value:"0", text: "No"}'; 
         
         
         foreach ($salesReps as $rep) {
             $this->view->salesReps .= ' {value:"'.$rep['id'].'", text: "'.$rep['name'].'"},'; 
         }
         $this->_db = Zend_Registry::get('bwbusiness');     
         $countryCode = $this->_db->fetchRow("select country_id from country WHERE iso_code_2 =  '{$this->view->user['country']}'");
         if (!$countryCode && $this->view->user['country'] != '') {
             $this->view->countryName = $this->view->user['country'];
         }
         
         
         
         if ($this->_request->isXmlHttpRequest()){
            $this->_helper->layout->disableLayout();
         }
        
   }
   
   
   public function followupAction()
   {            
      $followupTime =  date('Y-m-d H:i', strtotime($_POST['followup']));    
      $customer = $this->_crms->getUser($_POST['userId']);
      
      //only the rep can schedule the followup.
      if($this->_auth->id != $customer['parent_user'] && $this->_auth->role != 'admin') {
          echo 'You can only schedule the follow-up if you are the account rep.';
          exit();
      }            
      
      // check if it has the same follow-up time for same rep
      $event = $this->_crms->getConflictingReminder($followupTime,$this->_auth->id);
      if (isset($event) and $event['is_exists'] > 0)
      {      	
		  /*echo "<font style='color:red'>You tried to set the same reminder to same rep!</font>";*/
		  echo "You tried to set the same reminder to same rep!";
          exit();
	  }
	  
	  // update follow_up time into user table
	  $this->_crms->updateFollowupUser($_POST['userId'],$followupTime);
	  
      //first check if there is a previous followup
      $event = $this->_crms->getFollowup($_POST['userId']);
      
      /* Dariuz Rubin */      
      
      // Log followup Event
      $data = array('user_id' => $this->_auth->id, 'customer_id'=>$_POST['userId'], 
        'followup_time' => $followupTime, 'created_time'=>date('Y-m-d H:i:s') );        
	  $this->_crms->createFollowupLog($data);
            
      // Follow up event
      if($event) {
        if(isset($_POST['followup']) && $_POST['followup'] == '') {
          //remove
            $this->_crms->deleteFollowup($_POST['userId']);
            echo 'Follow-up removed';
        } else {
            /*$data = array('user_id' => $customer['parent_user'], 'start' => $followupTime, 
                'end' => date('Y-m-d H:i', strtotime('+15 minutes', strtotime($_POST['followup']))),
                'title' =>"Follow-up for {$customer['firstname']} {$customer['lastname']}", 
                'email_alert' => NULL,'popup_alert'=>NULL,  'update_time'=>date('Y-m-d H:i:s')
                );  */
            $data = array('user_id' => $this->_auth->id, 'start' => $followupTime, 
                'end' => date('Y-m-d H:i', strtotime('+15 minutes', strtotime($_POST['followup']))),
                'title' =>"Follow-up for {$customer['firstname']} {$customer['lastname']}", 
                'email_alert' => NULL,'active' => 1,'follow_up_from_website' => 0,'read_follow_up_from_website' => 0,'popup_alert'=>NULL,  'update_time'=>date('Y-m-d H:i:s')
                );
            
            $this->_crms->updateEvent($data, $event['event_id'], 'followUp');
            echo 'Follow-up scheduled';
        }
            
      } else {
        $data = array('user_id' => $this->_auth->id, 'customer_id'=>$_POST['userId'], 
            'start' => $followupTime, 'end' => date('Y-m-d H:i', strtotime('+15 minutes', strtotime($_POST['followup']))),
            'all_day'=>0, 'title' =>"Follow-up for {$customer['firstname']} {$customer['lastname']}", 'update_time'=>date('Y-m-d H:i:s') );
        $this->_crms->createEvent($data);
        echo 'Follow-up scheduled';
      }
      /*==============*/
      
      $this->_helper->layout()->disableLayout();
      $this->_helper->viewRenderer->setNoRender(true);           
   }
   
  
    /**
	* Save the reason for follow up
	*/
   public function saveReasonfollowupAction()
   {            
      $reason_followup =  trim($_POST['reason_followup']);    
      $customer = $this->_crms->getUser($_POST['userId']);      
      
      $data = array('id'=>$_POST['userId'],'reason_followup' => $reason_followup );
      $this->_crms->editUser($data,$_POST['userId']);      
      
      $this->_helper->layout()->disableLayout();
      $this->_helper->viewRenderer->setNoRender(true);           
   }
   
     /**
	* Clear the reason for follow up
	*/
   public function clearReasonfollowupAction()
   {            
      $reason_followup =  '';    
      $customer = $this->_crms->getUser($_POST['userId']);      
      
      $data = array('id'=>$_POST['userId'],'reason_followup' => $reason_followup );
      $this->_crms->editUser($data,$_POST['userId']);      
      
      $this->_helper->layout()->disableLayout();
      $this->_helper->viewRenderer->setNoRender(true);           
   }
   
   public function tradeshowsAction()
   {
		 $this->view->tscity = $this->_crms->getTradeshowCity();
		 $this->view->tsname = $this->_crms->getTradeshowName();
		 $this->view->tsyear = $this->_crms->getTradeshowYear();
		 
		 
		 //echo Zend_Version::getLatest();
       //get next 30 days events
       //$this->view->events = $this->_crms->getUpcomingEvents($this->_auth->id);
   }
	 
	 public function testingAction()
	 {
		 echo Zend_Version::getLatest();
		 
		 $form = new Zend_Form;
		 $form->addElement('select', 'yes');
		 
		 echo $form->render($view);
	 }
     
   public function activityAction()
   {
       //get next 30 days events
       $this->view->events_activity = $this->_crms->getUpcomingEvents($this->_auth->id);
       $this->view->website_events_activity = $this->_crms->getUpcomingWebsiteEvents($this->_auth->id);
       
   }
   public function createEventAction()
   {    
    $data = array('user_id' => $this->_auth->id,
                  'start' => trim($_POST['start']),
                  'end' =>trim($_POST['end']),
                  'title' => trim($_POST['title']), 
                  'description'=>trim($_POST['description']),
                  'update_time'=> date("Y-m-d H:i:s"),
                  'all_day' => $_POST['all_day'],
                  'public' => $_POST['public']);
    if ($_POST['all_day'] == 1) {
        $endParts = preg_split("/[\s]+/", $_POST['end']);
        $data['end'] = $endParts[0].' 23:59:59';
    }
    $error = '';
    
    if(isset($data['start']) && strtotime(trim($data['start'])) <= strtotime(date("Y-m-d H:i:s"))){
           $error .= 'Pleae enter time in format 20XX-XX-XX XX:XX and the time must be in the future '. '<br>';
    }
    if(isset($data['end']) && strtotime(trim($data['end'])) <= strtotime(trim($data['start']))){
           $error .= 'Pleae enter time in format 20XX-XX-XX XX:XX and end time must be later than start time '. '<br>';
    }    
    if ($error !='') {      
        echo "<div class='ui-widget'>
            <div class='ui-state-error ui-corner-all' style='padding: 0 .1em;'>
                <p>
                    <strong>$error</strong>
                </p>
            </div>
        </div>";
    } else {
        if ($this->_crms->createEvent($data)) {
            echo "success";
        }
    }
    
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender(TRUE);
        
   }   
   
   public function updateEventAction()
   {      
       $this->view->event = $this->_crms->getEvent($this->_getParam('id'));  
       
       if ($this->getRequest()->isPost()) {          
           $data = array('start' => trim($_POST['start']), 'end' => trim($_POST['end']), 
            'user_id'=>$this->_auth->id,           
            'update_time'=>date("Y-m-d H:i:s"),
            'follow_up_from_website' => 0,'read_follow_up_from_website' => 0
           );
           
           
           if(isset($_POST['public'])) {
               $data['public'] = $_POST['public'];
           }
           if(isset($_POST['description'])) {
               $data['description'] = trim($_POST['description']);
           }
           if(isset($_POST['title'])) {
               $data['title'] = trim($_POST['title']);
           }
           
           if(isset($_POST['all_day'])) {
               $data['all_day'] = $_POST['all_day'];
               if ($_POST['all_day'] == 1) {
                   $endParts = preg_split("/[\s]+/", $_POST['end']);
                   $data['end'] = $endParts[0].' 23:59:59';
               }
           }
           
            $error = '';

            if(isset($data['start']) && strtotime(trim($data['start'])) < strtotime(date("Y-m-d H:i:s"))){
                   $error .= 'Pleae enter time in format 20XX-XX-XX XX:XX and the time must be in the future '. '<br>';
            }
            if(isset($data['end']) && strtotime(trim($data['end'])) < strtotime(trim($data['start']))){
                   $error .= 'Pleae enter time in format 20XX-XX-XX XX:XX and end time must be later than start time '. '<br>';
            }
                      
            if($data['title'] == '') {
                $error .= 'Please enter a title for this event.';
            }
            if (!$error) {
                
                $update = $this->_crms->updateEvent($data, $_POST['event_id']);
                if ($update == 'Permission Error'){              
                    $error .=  'You can not change the event unless you are the author. <br>';
                } elseif ($update == 1) {           
                    echo 'success';
                }
            }
            if ($error !='') {      
                echo "<div class='ui-widget'>
                <div class='ui-state-error ui-corner-all' style='padding: 0 .1em;'>
                    <p>
                        <strong>$error</strong>
                    </p>
                </div>
                 </div>";
             }
           
            $this->_helper->viewRenderer->setNoRender(TRUE);    
       }
       $this->_helper->layout->disableLayout();       
   }
   
   public function removeEventAction()
   {
     //  echo $_POST['event_id'];
       $data = array('user_id' => $this->_auth->id,
            'update_time' => date("Y-m-d H:i:s"), 'active' => 0,'follow_up_from_website' => 0,'read_follow_up_from_website' => 0
        );       
       $update = $this->_crms->updateEvent($data, $_POST['event_id']);
       if ($update == 'Permission Error') {
            $error = 'You can not remove the event unless you are the author. <br>';
             if ($error !='') {      
                echo "<div class='ui-widget'>
                <div class='ui-state-error ui-corner-all' style='padding: 0 .1em;'>
                    <p>
                        <strong>$error</strong>
                    </p>
                </div>
                 </div>";
             }
           
       } elseif ($update == 1) {
            echo 'success';
       }
       $this->_helper->viewRenderer->setNoRender(TRUE);
       $this->_helper->layout->disableLayout();
    }
     
   public function getActivityAction()
   {
       $year = date('Y');
       $month = date('m');
       $events = $this->_crms->getEvents($this->_auth->id);
       echo json_encode($events);
      // echo '[{"id":"1","start":"2014-02-28 12:00:00","end":"2014-02-28 13:00:00","allDay":false,"title":"barbecue"},{"id":"3","start":"2014-02-22 01:00:00","end":"2014-02-22 01:30:00","all_day":"0","title":"rest"},{"id":"4","start":"2014-02-22 07:30:00","end":"2014-02-22 08:00:00","all_day":"0","title":"get up"},{"id":"6","start":"2014-02-22 07:00:00","end":"2014-02-22 07:30:00","all_day":"0","title":"abc"},{"id":"7","start":"2014-02-22 08:00:00","end":"2014-02-22 08:30:00","all_day":"0","title":"xyz"},{"id":"8","start":"2014-03-06 00:00:00","end":"2014-03-06 23:59:59","all_day":"1","title":"another day"}]';
	/*echo json_encode(array(
	
		array(
			'id' => 111,
			'title' => "Event1",
			'start' => "$year-$month-10",
		//	'url' => "http://yahoo.com/"
		),
		
		array(
			'id' => 222,
			'title' => "Event2",
			'start' => "$year-$month-20",
			'end' => "$year-$month-22",
		//	'url' => "http://yahoo.com/"
		),
               array(
			'id' => 333,
			'title' => "Event3",
			'start' => "$year-$month-03 08:00:00",
                        'end'   =>   "$year-$month-03 10:00:00",
                        'allDay'=> false
		//	'url' => "http://yahoo.com/"
		)
	
	));*/
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);        
   }
   
   public function searchAction()
   {
      //  $this->_helper->layout->disableLayout();
   }
   
   /* Dariuz Rubin */
   public function searchResultAction()
   {   
       if ($this->_getParam('customer') == 'all' && strlen($this->_getParam('query').$this->_getParam('query2')) < 2) {
           $this->view->error = "Sorry, the mininum key word length for 'all' search is 2, please select a different category or enter a longer key word.";
           return;
       }
       $this->view->users = $this->_crms->findUsers($this->_getParam('customer'),$this->_getParam('filter'),$this->_getParam('query'), $this->_getParam('query2'));
       //var_dump($this->view->users);
       // $this->_helper->viewRenderer->setNoRender(TRUE);      
       //die();        
   }   
   /*=============*/
   public function ajaxGetRegionsAction() {
        $regions = $this->_crms->getRegions($this->_getParam('country'));
        foreach ($regions as $region) {      
             echo  "<option value='{$region['code']}'>{$region['name']}</option>";                     
        } 
        $this->_helper->layout()->disableLayout();  
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }  
    
     /**
  	* Dismiss event
  	*/
  
  	public function dismissEventAction() 
  	{
        if ($this->getRequest()->isPost()) 
        {           
             
         	$data = array('active' => 0);            
            $this->_crms->updateEvent($data, $_POST['event_id'], 'followUp');
        }        
    }
    
   public function pingAction() {
   	
       if (!Zend_Auth::getInstance()->getIdentity()) {
           echo "logout";
           exit();
       }
       
       /* Dariuz Rubin */       
       $snoozeTime = $_POST['snoozeTime']; 
       
       // popup action
       $events = $this->_crms->getAlertEvents($this->_auth->id,$snoozeTime);
       if ($events) {
           foreach ($events as $event) {
           		$customer_id = $event['customer_id'];
           		echo "<a target='_blank' href='/biz/crm/customer/id/{$customer_id}' style='color:black'>{$event['title']}</a>"; 
               $this->_crms->updateEvent(array('popup_alert' => date('Y-m-d H:i:s'),'follow_up_from_website' => 0,'read_follow_up_from_website' => 0), $event['id'], 'followup');
           }
       }
       
       // email acton
       $events = $this->_crms->getEmailAlertEvents($this->_auth->id);
       if ($events) {
           foreach ($events as $event) {
           		$this->_crms->emailalertEvent($event);
                $this->_crms->updateEvent(array('email_alert' => date('Y-m-d H:i:s'),'follow_up_from_website' => 0,'read_follow_up_from_website' => 0), $event['id'], 'followup');
           }
       }       
       
       // Get events from website contact
       $new_website_events = $this->_crms->getTodayNewWebsiteEvents($this->_auth->id);
       if ($new_website_events) {
          echo "<p>An existing lead/prospect/account contacts us through the sidebar contact form.   Please check lead/prospect/account page</p>";           
       }
       
       /* // Get Events *
        $this->view->events = $this->_crms->getTodayEvents($this->_auth->id);             
        // Get events from website contact
    	$this->view->website_events = $this->_crms->getTodayWebsiteEvents($this->_auth->id);
    	
    	//get next 30 days events
        $this->view->events_activity = $this->_crms->getUpcomingEvents($this->_auth->id);
        $this->view->website_events_activity = $this->_crms->getUpcomingWebsiteEvents($this->_auth->id);*/
    	/*=================*/
    	
       $this->_helper->layout->disableLayout();
       $this->_helper->viewRenderer->setNoRender(TRUE);
  }
  
    /* Dariuz Rubin */
    
    /**
	* Check box event for automated removal date by an additional 60 days
	*/    
    public function removaladditionAction() {
        if ($this->getRequest()->isPost()) {           
           
           	if (isset($_POST['removal_days']) and $_POST['removal_days'] == 'yes')
           	{
           		$user_id = (int)$_POST['userId'];           		 
		   		
		   		$result = array();
		   		$this->_crms->removaladdition($user_id);    
		   		
		   		$result['time_left'] = $this->_crms->getTimeleft($user_id);    
           		
            	//if ajax call
                
	            if ($this->getRequest()->isXmlHttpRequest()) {  
	            	echo json_encode($result);   
	            	
	                $this->_helper->layout()->disableLayout();
	                $this->_helper->viewRenderer->setNoRender(true);                    
	            }
                	
		   	}           
        }        
    }
    
     /**
	* Enter sale's date and amount
	*/ 
    public function enterSaleAction()
    {            
      	$enter_sale_time=  date('Y-m-d H:i', strtotime($_POST['enter_sale_time']));    
      	$created_time=  date('Y-m-d H:i', strtotime($_POST['created_time']));    
     	$customer = $this->_crms->getUser($_POST['userId']);
     	$sample_order = $_POST['sample_order'];
     	if ($sample_order == 'true')
     		$sample_order = 'sample';
     	
     	
     	
     	if ($enter_sale_time != '') {
     		$type= $_POST['type'];
            $data = array ('user_id' =>(int)$_POST['userId'],
                'type' => 'entersale',
                'attempt_type' => $sample_order,
                'enter_sale_amount' => trim($_POST['enter_sale_amount']),
                'enter_time' => $enter_sale_time,
                'author' => $this->_auth->firstname.' '.$this->_auth->lastname,
                'author_id' => $this->_auth->id
                );      
            
            //user created successfully
            $is_account = $this->_crms->isAccount($data['user_id']); //Dariuz Rubin
            if (($sample_order === "false") and ($is_account === false))
            {
            	$parent_user_name= $this->_crms->getUserName($_POST['parent_user']);
            	if (isset($parent_user_name) and $parent_user_name['username'] != 'Sales Manager')
            	{
					$conversion = array ('user_id'=> $data['user_id'], 'user_type' => $_POST['type'], 
	                 'source' => $_POST['source'],
	                 'created_time' => $created_time, 'parent_id' => $_POST['parent_user'] , 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname);
	             	$this->_crms->account_conversion($conversion);            	
	             	
	             	//update user Type
	             	
	             	$type = 'Account';
		    		$update = $this->_crms->updateUserType($data['user_id'], $type);
				}
	    		
	    		
			}
          
            $result = array();
            $res = $this->_crms->entersale($data);
            $result['total_sale'] = $res['total_sale'];        
            $result['type'] =$type;                           
            $result['enter_sale_time'] =$_POST['enter_sale_time'];
            $result['enter_sale_amount'] = $data['enter_sale_amount'];
	      
            //if ajax call
                
            if ($this->getRequest()->isXmlHttpRequest()) {  
            	echo json_encode($result);   
            	
                $this->_helper->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);                    
            }
        }
   	}
   	
   	/**
	* set order frequency field
	*/    
    public function setorderfrequencyAction() {
        if ($this->getRequest()->isPost()) {           
           
           	if (isset($_POST['order_frequency']))
           	{
           		$user_id = (int)$_POST['userId']; 
           		$order_frequency =(int)$_POST['order_frequency']; 	 
		   		$this->_crms->setOrderFrequency($user_id,$order_frequency);        	
		   	}           
        }        
    }
    
    /**
	* check if this lead/prospect/account had been already exists
	*/    
    public function searchAccountAction() {
    	$result = array();
    	$result['exists'] = 'No';
    	
    	$data = $this->_request->getPost();  
        // check if this lead/prospect/account had been already exists
        $exists = $this->_crms->searchAccount($data);
        if (isset($exists) and $exists)
        {
		  	$result['exists'] = 'Yes';
		}
        
    	echo $result['exists'];                              	                
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);            
        
    } 	
    
    
    /**
	* set lead source
	*/    
     public function setleadAction()
    {       
               
        $id = $_POST['userId'];
        $data = array('source' => $_POST['source'],'source_text' => $_POST['source_text'],'tradeshows' => $_POST['tradeshows'],'tradeshow_year' => $_POST['tradeshow_year']);
        
        $update = $this->_crms->editUser($data, $id);
        
    }  
    
    /**
	* Click to call
	*/    
    public function ringAction($to_phone) {
   		
       if (!Zend_Auth::getInstance()->getIdentity()) {
           echo "logout";
           exit();
       }

		// Create SDK instance
		
		$credentials = require(__DIR__ . '/ringcentral-php/demo/_credentials.php');

		$rcsdk = new SDK($credentials['appKey'], $credentials['appSecret'], $credentials['server'], 'Demo', '1.0.0');

		$platform = $rcsdk->platform();

		// Authorize

		$platform->login($credentials['username'], $credentials['extension'], $credentials['password']);

		// Make a call
		$to_phone = $_POST['tocall'];
		$credentials['fromPhoneNumber'] = '13602835811';
		$credentials['toPhoneNumber'] = $to_phone;
		$response = $platform->post('/account/~/extension/~/ringout', array(
		    'from' => array('phoneNumber' => $credentials['fromPhoneNumber']),
		    'to'   => array('phoneNumber' => $credentials['toPhoneNumber'])
		));

		$json = $response->json();

		$lastStatus = $json->status->callStatus;

		// Poll for call status updates

		while ($lastStatus == 'InProgress') {

		    $current = $platform->get($json->uri);
		    $currentJson = $current->json();
		    $lastStatus = $currentJson->status->callStatus;
		    echo 'Status: ' . json_encode($currentJson->status) . PHP_EOL;

		    sleep(2);

		}

		echo 'Done.' . PHP_EOL;

    	
       $this->_helper->layout->disableLayout();
       $this->_helper->viewRenderer->setNoRender(TRUE);
  }
  
  /**
  * Contact Made Action  
  */
  public function contactmadeAction()
  {
  	 if ($this->getRequest()->isPost()) 
  	 { 
  	 	$contact_made =  date('Y-m-d', strtotime($_POST['contact_made']));    
  	 	$created_time =  date('Y-m-d H:i', strtotime($_POST['created_time']));    
		
		$customer = $_POST['userId'];     
      
       
                            
	   
	    
	    $parent_user_name= $this->_crms->getUserName($_POST['parent_user']);
        if (isset($parent_user_name) and $parent_user_name['username'] != 'Sales Manager')
        {
        	 // convert lead to prospect
		    $data = array ('id'=> $_POST['userId'], 'type' => 'Prospect',          
	         'created_time' => $created_time, 'contact_made' => $contact_made);
		    $update = $this->_crms->editUser($data, $customer);
	    
		    // record prospect_conversion
	        $conversion = array ('user_id'=> $_POST['userId'], 'user_type' => 'Prospect', 
	         'source' => $_POST['source'],
	         'created_time' => $created_time, 'action_time' => $contact_made, 'parent_id' => $_POST['parent_user'] , 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname);
	         
		    $this->_crms->prospect_conversion($conversion);   
	    }else
	    {
			 // convert lead to prospect
		    $data = array ('id'=> $_POST['userId'], 'type' => 'Lead',          
	         'created_time' => $created_time, 'contact_made' => $contact_made);
		    $update = $this->_crms->editUser($data, $customer);
		}
	    $this->_helper->layout()->disableLayout();
	    $this->_helper->viewRenderer->setNoRender(true);           
  	 } 	
  }
  
   /**
  * Contact Attemp Note for notes
  */
  
  public function contactAttemptNoteAction() {
        if ($this->getRequest()->isPost()) {           
           /*	if (strlen($_POST['type'])==0)
           		$_POST['type'] = 'note';*/
            if (trim($_POST['notes']) != '')
            {
                $data = array ('user_id' =>(int)$_POST['user_id'],
                    'attempt_type' => $_POST['type'],
                    'type' => 'note',
                    'notes' => trim($_POST['notes']),
                    'author' => $this->_auth->firstname.' '.$this->_auth->lastname
                    );      
                $this->_crms->savenotes($data);               
                
                $this->_crms->setAlertEvents($data['user_id']);               
                
              
                //if ajax call
                if ($this->getRequest()->isXmlHttpRequest()) {     
                
                	$result = $this->_crms->getYearSalesNotes($_POST['user_id'],date('Y'));                 
         			
         			$result_notes = "";         			
					
					$this->view->user = $this->_crms->getUser($_POST['user_id']);
					
                  	if (isset($this->view->user['description']) and strlen($this->view->user['description'])>0)
                    {
                    	$result_notes .= 'Customer Description : '.$this->view->user['description'].'<br />'.'<br />'; 
					}
					if (isset($this->view->user['interest']) and strlen($this->view->user['interest'])>0)
                    {		
                    	$result_notes .= 'Customer Description : '.$this->view->user['interest'].'<br />'.'<br />'; 
					}										                    
		                                				
         			foreach($result as $note)
         			{
						$result_notes .= date('m/d/y &\nb\sp;&\nb\sp; g:i A',strtotime($note['enter_time'])) . '    '. $note['author']. '    '. $note['attempt_type'].'<br />'.$note['notes'].'<br />'.'<br />'; 
					}
					$result_notes = str_replace("<br />", "\n", $result_notes);
					
                    echo $result_notes;
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);                    
                }
            }
        }        
    }
    
     
   
    
    /**
  	* get Note for specific year
  	*/
  
  	public function getYearNotesAction() 
  	{
        if ($this->getRequest()->isPost()) 
        {           
             
            //if ajax call
            if ($this->getRequest()->isXmlHttpRequest()) 
            {     
            
            	$result = $this->_crms->getYearSalesNotes($_POST['user_id'],$_POST['year']);                 
     			
     			$result_notes = "";         			
										
     			foreach($result as $note)
     			{
					$result_notes .= date('m/d/y &\nb\sp;&\nb\sp; g:i A',strtotime($note['enter_time'])) . '    '. $note['author'].'<br />'.$note['notes'].'<br />'.'<br />'; 
				}
				$result_notes = str_replace("<br />", "\n", $result_notes);
				
                echo $result_notes;
                $this->_helper->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);                    
            }
            
        }        
    }
   
    
    /**
  	* Save User Product
  	*/
  
    public function saveUserProductAction()
    {
      
        if($this->getRequest()->isPost()) {   
        	$userId = $_POST['userId'];
        	$user_prodcut = '';
            if(!empty($_POST['selectedProduct'])) {
               
                foreach ($_POST['selectedProduct'] as $product) {                   
                    $user_prodcut .= $product.',';
                   
                }
                if (strlen($user_prodcut)>0)
                	$user_prodcut = substr($user_prodcut,0,strlen($user_prodcut)-1);                 
            }            
            
            $this->_crms->saveUserProducts($userId,$user_prodcut);                       
            $result['userproductnames'] = $_POST['selectedProduct'];     
            
         	echo json_encode($result);
        	
        	
            $this->_helper->viewRenderer->setNoRender(true);
        }
        $this->_helper->layout->disableLayout();
    }
    
     /**
  	* Save Account Info
  	*/
  
 	 public function saveAccountInfoAction() 
 	 {
 	 	     	       
        $id = $_POST['userId'];
        $user = $this->_crms->getUser($id);
        if(isset($_POST['type'])) {
            if (($user['type'] == 'Lead' || $user['type'] == 'Prospect') && $_POST['type'] == 'Account') {
                //track conversion
                $conversion = array ('user_id'=> $id, 'user_type' => $user['type'], 'lead_source' => $user['imported'],
                        'created_time' => $user['created_time'], 'parent_id' => $user['parent_user'], 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname
                      );
                $this->_crms->account_conversion($conversion);
            }
            
           
			// Track the transition to Prospect
			if (($user['type'] == 'Lead') && $_POST['type'] == 'Prospect') {
                //track conversion
                $conversion = array ('user_id'=> $id, 'user_type' => $user['type'], 'lead_source' => $user['imported'],
                        'created_time' => $user['created_time'], 'parent_id' => $user['parent_user'], 'modified_by' => $this->_auth->firstname.' '. $this->_auth->lastname
                      );
                $this->_crms->prospect_conversion($conversion);
            }
			
        }
        
        // get additional contactphones and contactphones2
        $_POST['businessphone'] = preg_replace("/[^0-9]*/s", "",$_POST['businessphone']);
        $_POST['secondary_businessphone'] = preg_replace("/[^0-9]*/s", "",$_POST['secondary_businessphone']);
        
        // contact phone
        $contactphone_all="";
        $contactphone2_all="";
        foreach ($_POST['contactphones'] as $key=> $contactphone) {
        	if (strlen($contactphone)>0)
        	{
        		$contactphone = preg_replace("/[^0-9]*/s", "",$contactphone);
				if ($key == 0)
	        		$_POST['contactphone'] = trim($contactphone);
	        	$contactphone_all .= $contactphone.', ';
			}
        	
        }
        if (strlen($contactphone_all)>2)    
        	$contactphone_all = substr($contactphone_all,0,strlen($contactphone_all)-2);
    	
    	foreach ($_POST['secondary_contactphones'] as $key=> $contactphone) {
    		if (strlen($contactphone)>0)
        	{
        		$contactphone = preg_replace("/[^0-9]*/s", "",$contactphone);
        		if ($key == 0)
        			$_POST['contactphone2'] = trim($contactphone);
        		$contactphone2_all .= $contactphone.', ';
        	}        	
        }
        if (strlen($contactphone2_all)>2)    
        	$contactphone2_all = substr($contactphone2_all,0,strlen($contactphone2_all)-2);
        	
        $_POST['contactphone_all'] = $contactphone_all;
        $_POST['contactphone2_all'] = $contactphone2_all;
        
        // contact phone type
        $contactphone_type_all="";
        $contactphone2_type_all="";
        foreach ($_POST['contactphones_type'] as $key=> $contactphone_type) {
        	if (strlen($contactphone_type)>0)
        	{				
	        	$contactphone_type_all .= trim($contactphone_type).', ';
			}
        	
        }
        if (strlen($contactphone_type_all)>2)    
        	$contactphone_type_all = substr($contactphone_type_all,0,strlen($contactphone_type_all)-2);
    	
    	foreach ($_POST['secondary_contactphones_type'] as $key=> $contactphone_type) {
    		if (strlen($contactphone_type)>0)
        	{        		
        		$contactphone2_type_all .= trim($contactphone_type).', ';
        	}        	
        }
        if (strlen($contactphone2_type_all)>2)    
        	$contactphone2_type_all = substr($contactphone2_type_all,0,strlen($contactphone2_type_all)-2);
        	
        $_POST['contactphone_type_all'] = $contactphone_type_all;
        $_POST['contactphone2_type_all'] = $contactphone2_type_all;
        
        // email
        $email_all="";
        $email2_all="";
        foreach ($_POST['contactemails'] as $key=> $email) {
        	if (strlen($email)>0)
        	{
        		if ($key == 0)
	        		$_POST['email'] = trim($email);
	        	$email_all .= $email.', ';
        	}
        	
        }
        if (strlen($email_all)>2)    
        	$email_all = substr($email_all,0,strlen($email_all)-2);
    	
    	foreach ($_POST['secondary_contactemails'] as $key=> $email) {
    		if (strlen($email)>0)
        	{
        		if ($key == 0)
	        		$_POST['email2'] = trim($email);
	        	$email2_all .= $email.', ';
        	}
        	
        }
        if (strlen($email2_all)>2)    
        	$email2_all = substr($email2_all,0,strlen($email2_all)-2);
        	
        $_POST['email_all'] = $email_all;
        $_POST['email2_all'] = $email2_all;
        $_POST['opportunity'] = substr($_POST['opportunity'],1,strlen($_POST['opportunity']));
        $_POST['follow_up'] = $_POST['followup'];
        
        unset($_POST['secondary_contactemails']);
        unset($_POST['contactemails']);
        unset($_POST['secondary_contactphones']);
        unset($_POST['contactphones']);
        unset($_POST['contact_attempt']);
        unset($_POST['customer_comment']);
        unset($_POST['enter_sale_amount']);
        unset($_POST['enter_sale_time']);
        unset($_POST['followup']);
        unset($_POST['new_note']);
        unset($_POST['notes']);
        unset($_POST['time_left']);
        unset($_POST['userId']);
        
        if (isset($_POST['follow_up']) and strlen($_POST['follow_up'])>0)  
        	$_POST['follow_up'] =  date('Y-m-d H:i', strtotime($_POST['follow_up']));    
        if (isset($_POST['created_time']) and strlen($_POST['created_time'])>0)  
        	$_POST['created_time'] =  date('Y-m-d H:i', strtotime($_POST['created_time']));  
        if (isset($_POST['contact_made']) and strlen($_POST['contact_made'])>0)  
        	$_POST['contact_made'] =  date('Y-m-d', strtotime($_POST['contact_made']));    
        if (isset($_POST['certificate_issued']) and strlen($_POST['certificate_issued'])>0)  
        	$_POST['certificate_issued'] =  date('Y-m-d', strtotime($_POST['certificate_issued']));    
        if (isset($_POST['training_complete']) and strlen($_POST['training_complete'])>0)  
        	$_POST['training_complete'] =  date('Y-m-d', strtotime($_POST['training_complete']));    
      	
        $_POST['source_text'] = trim($_POST['source_text']);
        $update = $this->_crms->updateUser($_POST, $id);
       
        
        if ($update) {           
             if (strstr($update, 'SQLSTATE[23000]')) {
                 $return['success'] = false; 
                 $return['message'] = 'Email existing';
             }else {
                 $return['success'] = true;                 
             }        
        } else {
            $return['success'] = false;
            $return['message'] = 'Update failed';
        }        
       
        
        if ($this->getRequest()->isXmlHttpRequest()) {  
        	 echo json_encode($return);                           	                
            $this->_helper->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);                    
        }
    }
    
    /**
	* create branch
	*/    
     public function createBranchAction() {
     	$id = $this->_getParam('id');
        $this->view->user = $this->_crms->getUser($this->_getParam('id'));
         $this->view->branches = $this->_crms->getBranches($this->_getParam('id')); 
        $this->view->branch = $this->_crms->getBranch($this->_getParam('id'));
        
        if ($this->getRequest()->isPost())
        {
        	 // get additional contactphones and contactphones2
	        $contactphone_all="";
	        $contactphone2_all="";
	        foreach ($_POST['contactphones'] as $key=> $contactphone) {
	        	if (strlen($contactphone)>0)
	        	{
	        		$contactphone = preg_replace("/[^0-9]*/s", "",$contactphone);
					if ($key == 0)
		        		$_POST['contactphone'] = trim($contactphone);
		        	$contactphone_all .= $contactphone.', ';
				}
	        	
	        }
	        if (strlen($contactphone_all)>2)    
	        	$contactphone_all = substr($contactphone_all,0,strlen($contactphone_all)-2);
	    	
	    	foreach ($_POST['secondary_contactphones'] as $key=> $contactphone) {
	    		if (strlen($contactphone)>0)
	        	{
	        		$contactphone = preg_replace("/[^0-9]*/s", "",$contactphone);
	        		if ($key == 0)
	        			$_POST['contactphone2'] = trim($contactphone);
	        		$contactphone2_all .= $contactphone.', ';
	        	}        	
	        }
	        if (strlen($contactphone2_all)>2)    
	        	$contactphone2_all = substr($contactphone2_all,0,strlen($contactphone2_all)-2);
	        	
	        $_POST['contactphone_all'] = $contactphone_all;
	        $_POST['contactphone2_all'] = $contactphone2_all;
	        
	        $email_all="";
	        $email2_all="";
	        foreach ($_POST['contactemails'] as $key=> $email) {
	        	if (strlen($email)>0)
	        	{
	        		if ($key == 0)
		        		$_POST['email'] = trim($email);
		        	$email_all .= $email.', ';
	        	}
	        	
	        }
	        if (strlen($email_all)>2)    
	        	$email_all = substr($email_all,0,strlen($email_all)-2);
	    	
	    	foreach ($_POST['secondary_contactemails'] as $key=> $email) {
	    		if (strlen($email)>0)
	        	{
	        		if ($key == 0)
		        		$_POST['email2'] = trim($email);
		        	$email2_all .= $email.', ';
	        	}
	        	
	        }
	        if (strlen($email2_all)>2)    
	        	$email2_all = substr($email2_all,0,strlen($email2_all)-2);
	        	
	        $_POST['email_all'] = $email_all;
	        $_POST['email2_all'] = $email2_all;        
        	$_POST['customer_id'] = $id;
        	unset($_POST['secondary_contactemails']);
	        unset($_POST['contactemails']);
	        unset($_POST['secondary_contactphones']);
	        unset($_POST['contactphones']);
	      
        
        	$res = $this->_crms->saveBranch($_POST);		
			
			if ($this->getRequest()->isXmlHttpRequest()) {  
            	echo 'Success';                             	                
                $this->_helper->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender(true);                    
            }
			
		}
    
    }
    
    
    /* Branch Info Tab */
    public function branchInfoAction() {
         
		$this->view->user = $this->_crms->getUser($this->_getParam('id'));
        $this->view->branches = $this->_crms->getBranches($this->_getParam('id')); 
        $this->view->branch = $this->_crms->getBranch($this->_getParam('id'));
        
        if ($this->getRequest()->isPost()) {    
		   
        	$id = $_POST['id'];
        	$branch_name = $_POST['branch_name'];
        	$this->view->user = $this->_crms->getUser($id);
        	 $this->view->branches = $this->_crms->getBranches($id);
        	$this->view->branch = $this->_crms->getBranchByName($branch_name,$id);
        
        }            
        
		if ($this->_request->isXmlHttpRequest()){
       		$this->_helper->layout->disableLayout();
       	}     
   }
   
   /* Document Info*/
 	public function documentsAction() {
         $this->view->user = $this->_crms->getUser($this->_getParam('id'));
         $this->view->documents = $this->_crms->getDocuments($this->_getParam('id'));           
         
         if ($this->_request->isXmlHttpRequest()){
            $this->_helper->layout->disableLayout();
         }
        
   } 
   
   /* Document Upload*/
 	public function documentsUploadAction() {
         $this->view->user = $this->_crms->getUser($this->_getParam('id'));
         $this->view->documents = $this->_crms->getDocuments($this->_getParam('id'));           
         
         if ($this->getRequest()->isPost()) {    
		   
        	$id = $this->_getParam('id');
        	if (isset($_FILES['upload_file']) and is_uploaded_file($_FILES['upload_file']['tmp_name']))
        	{
				$customer_id = $this->_getParam('id');
				$customer_name = $this->view->user['username'];
				$uploader = $this->view->user['username'];
				/*$title = str_repeat(' ','-',strtolower($_FILES['upload_file']['name']));*/
				$title = $_FILES['upload_file']['name'];
				$size = $_FILES['upload_file']['size']/1024;
				$real_title="";
				$ext ="";
				$lastDotPos = strrpos($title,'.');
				if ($lastDotPos !== false)
				{
					$real_title = substr($title,0,$lastDotPos);
					$ext = substr($title,$lastDotPos);
				}
				$description = "Uploaded from Document Library";
				
				$new_title =$real_title.'-'.time().'-'.$customer_name.$ext;
				$Destination = "data/upload";
				
				move_uploaded_file($_FILES['upload_file']['tmp_name'],"$Destination/$new_title");
				$url = "$Destination/$new_title";
				
				$res = $this->_crms->saveDocument($customer_id,$url,$title,$size,$description,$uploader,$ext);	
				
				$return['success'] = true;
				echo json_encode($result);
				
		        $this->_helper->viewRenderer->setNoRender(TRUE);
		        $this->_helper->layout()->disableLayout();
           	                
            	
			}        
        
        }            
      
        
   } 
   
   public function documentsDeleteAction() {
          
         if ($this->getRequest()->isPost()) 
         {    		   
        	$id = $this->_getParam('id');
        	if (isset($_POST['sel_documents']))
        	{
				if (is_array($_POST['sel_documents']))
				{
					$res = $this->_crms->deleteDocument($_POST['sel_documents']);	
						
					$return['success'] = true;
					echo json_encode($result);
					
			        $this->_helper->viewRenderer->setNoRender(TRUE);
			        $this->_helper->layout()->disableLayout();
				}
			}
        }
   } 
   
   
    /* Transaction */
 	public function transactionAction() {
		$this->view->user = $this->_crms->getUser($this->_getParam('id'));
        $this->view->transactions = $this->_crms->getTransaction($this->_getParam('id'));              
         
        if ($this->getRequest()->isPost()) 
        {    		   
        	try
        	{  
			   
				$user_id = $this->view->user['id'];
			    $email   = $this->view->user['email'];
			    //$product = $this->view->user['product'];
			    $product = trim($_POST['transaction_description']);			   
			    $description = trim($_POST['transaction_description']);			   
			    $sold_by = $this->view->user['soldby'];
			     
			     
			    $business_firstname = $this->view->user['businessname'];
			    $business_lastname  =$this->view->user['businessname'];
			    $business_address   = $this->view->user['address1'];
			    $business_city      = $this->view->user['city'];
			    $business_state     = $this->view->user['state'];
			    $business_zipcode   = $this->view->user['zip'];
			    $business_telephone = $this->view->user['contactphone'];
			    $business_country = $this->view->user['country'];
			    $business_fax = $this->view->user['fax'];			   
			    
			    $shipping_firstname = $this->view->user['firstname'];
			    $shipping_lastname  = $this->view->user['lastname'];
			    $shipping_address   = $this->view->user['shipping_address1'];
			    $shipping_city      =$this->view->user['shipping_city'];
			    $shipping_state     = $this->view->user['shipping_state'];
			    $shipping_zipcode   = $this->view->user['shipping_zip'];
			    $shipping_country = $this->view->user['shipping_country'];
			  
				
			    $creditcard = trim($_POST['transaction_card_number']);
			    $expiration = trim($_POST['transaction_card_expire']);
			    $total      = $_POST['transaction_amount'];			    
			    $cvv        = $_POST['transaction_cvv'];
			    $invoice    = substr(time(), 0, 6);
			    $tax        = 0.00;
			 	
			 	$now = date('Y-m-d h:i:s');
			 
			 	$author =  $this->_crms->getTransactionAuthor($sold_by);              
			    $author_api_login = $author['api_login'];
			    $author_transaction_key = $author['transaction_key'];
			    
			    $payment = new AuthnetAIM($author_api_login, $author_transaction_key);
			    //$payment = new AuthnetAIM($author_api_login, $author_transaction_key, true);
			    
			    $payment->setTransaction($creditcard, $expiration, $total, $cvv, $invoice, $tax);
			    
			    $payment->setParameter("x_duplicate_window", 180);
			    $payment->setParameter("x_cust_id", $user_id);
				$payment->setParameter("x_customer_ip", $_SERVER['REMOTE_ADDR']);
			    if (strlen($email))
			    	$payment->setParameter("x_email", $email);
			    $payment->setParameter("x_email_customer", FALSE);
			    if (strlen($business_firstname))
			    	$payment->setParameter("x_first_name", $business_firstname);
			    if (strlen($business_lastname))
			    	$payment->setParameter("x_last_name", $business_lastname);
			    if (strlen($business_address))
			    	$payment->setParameter("x_address", $business_address);
			    if (strlen($business_city))
			    	$payment->setParameter("x_city", $business_city);
			    if (strlen($business_state))
			   		$payment->setParameter("x_state", $business_state);
			    
			    if (strlen($business_zipcode))
			    	$payment->setParameter("x_zip", $business_zipcode);
			    	
			    if (strlen($business_telephone))
			    	$payment->setParameter("x_phone", $business_telephone);
			    if (strlen($business_country))
			    	$payment->setParameter("x_country", $business_country);
			    if (strlen($shipping_firstname))
			    	$payment->setParameter("x_ship_to_first_name", $shipping_firstname);
			    if (strlen($shipping_lastname))
			    	$payment->setParameter("x_ship_to_last_name", $shipping_lastname);
			    if (strlen($shipping_address))
			    	$payment->setParameter("x_ship_to_address", $shipping_address);
			    if (strlen($shipping_city))
			    	$payment->setParameter("x_ship_to_city", $shipping_city);
			    if (strlen($shipping_state))
			    	$payment->setParameter("x_ship_to_state", $shipping_state);
			    
			    if (strlen($shipping_zipcode))
			    	$payment->setParameter("x_ship_to_zip", $shipping_zipcode);
			    if (strlen($shipping_country))
			    	$payment->setParameter("x_ship_to_country", $shipping_country);
			    if (strlen($description))
			    	$payment->setParameter("x_description", $description);
			    
			   /* Card Type	Card Number
American Express Test Card	370000000000002
Discover Test Card	6011000000000012
Visa Test Card	4007000000027
Second Visa Test Card	4012888818888
JCB	3088000000000017
Diners Club/ Carte Blanche	38000000000006*/

			    $payment->process();
    			
    			$res = "";
    			$approval_code  = "";
    			$avs_result  = "";
    			$cvv_result  = "";
    			$transaction_id  = "";    			
    			$error_number  = "";
    			$error_message  = "";
			   
			    
			   
			    if ($payment->isApproved())
			    {
			        // Get info from Authnet to store in the database
			        $approval_code  = $payment->getAuthCode();
			        $avs_result     = $payment->getAVSResponse();
			        $cvv_result     = $payment->getCVVResponse();
			        $transaction_id = $payment->getTransactionID();
			        
			        $res ="Success";
			 
			        // Do stuff with this. Most likely store it in a database.
			        // Direct the user to a receipt or something similiar.
			    }
			    else if ($payment->isDeclined())
			    {
			        // Get reason for the decline from the bank. This always says,
			        // "This credit card has been declined". Not very useful.
			        $reason = $payment->getResponseText();
			 
			        // Politely tell the customer their card was declined
			        // and to try a different form of payment.
			        $res ="Declined". " : ". $reason;
			    }
			    else if ($payment->isError())
			    {
			        // Get the error number so we can reference the Authnet
			        // documentation and get an error description.
			        $error_number  = $payment->getResponseSubcode();
			        $error_message = $payment->getResponseText();
			 
			        // OR
			 
			        // Capture a detailed error message. No need to refer to the manual
			        // with this one as it tells you everything the manual does.
			        $full_error_message =  $payment->getResponseMessage();
			 
			        // We can tell what kind of error it is and handle it appropriately.
			        if ($payment->isConfigError())
			        {
			            // We misconfigured something on our end.
			        }
			        else if ($payment->isTempError())
			        {
			            // Some kind of temporary error on Authorize.Net's end. 
			            // It should work properly "soon".
			        }
			        else
			        {
			            // All other errors.
			        }
			 
			        // Report the error to someone who can investigate it
			        // and hopefully fix it
			 
			        // Notify the user of the error and request they contact
			        // us for further assistance
			         $res ="Error". " : ". $full_error_message;
			    }
			    
			    
			    
			    $result = $this->_crms->saveTransaction($user_id,$creditcard,$total,$cvv,$expiration,$description,$sold_by,$now,$product,$approval_code,$avs_result,$cvv_result,$transaction_id,$res,$error_number,$error_message);	
			    
			    echo $res;
			    
			}
			catch (AuthnetAIMException $e)
			{
				
			    echo 'There was an error processing the transaction. Here is the error message : '.$e->__toString();;
			    //echo $e->__toString();
			}
			if ($this->_request->isXmlHttpRequest()){
	            $this->_helper->layout()->disableLayout();
	            $this->_helper->viewRenderer->setNoRender(true);                    
	        }
			
        }
        
   } 
   
   
    /**
   * clear the follow up and all reminders after the follow up has been completed.
   **/

   public function clearFollowupAction()
   {            
      $followupTime =  NULL;
      $customer = $this->_crms->getUser($_POST['userId']);
      
      
	  // update follow_up time into user table
	  $this->_crms->updateFollowupUser($_POST['userId'],$followupTime);
	  
      //first check if there is a previous followup
      $event = $this->_crms->getFollowup($_POST['userId']);
      
      
      if($event) 
      {      
      
        $this->_crms->deleteFollowup($_POST['userId']);
        echo 'Follow-up cleared';     
            
      }
      
      
      $this->_helper->layout()->disableLayout();
      $this->_helper->viewRenderer->setNoRender(true);           
   }
   /* ============== */
}

