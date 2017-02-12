<?php

class BusinessReportController extends Zend_Controller_Action {

    public function init() {
        
        $auth = Zend_Auth::getInstance()->getIdentity();
        if (!$auth || (!in_array('businessreport', unserialize($auth->permission)))) {
           $this->_helper->redirector('index', 'fm');
        }
        $this->_crms = new Application_Model_Crm;        
        $this->_auth = Zend_Auth::getInstance()->getIdentity();     
        $this->_reports = new Application_Model_Businessreport;
        
        /* Dariuz Rubin */
		// check if an active account does not have any current activity in a predetermined time period
		$this->_crms->notifyUnactivityAccount();
		/*==============*/
    }
    public function indexAction() {
        
    }
    
    public function accountConversionAction() {
        $this->view->reps = $this->_crms->getSalesUsers();
        
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;
            /* Dariuz Rubin */        
            $this->view->leads_assigned = $this->_reports->leads_assigned($_POST);  
            $this->view->conversions = $this->_reports->account_conversion($_POST);            
            $this->view->conversions_assigned = $this->_reports->account_conversion_assigned($_POST);            
            $this->view->accounts_conversions = $this->_reports->conversion_accounts($_POST);              
            $this->view->accounts_conversions_assigned = $this->_reports->assigned_conversion_accounts($_POST);              
            /*==============*/
        }
    }
    
     public function leadSourceAction() {
        $this->view->reps = $this->_crms->getSalesUsers();
        
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;
            /* Dariuz Rubin */        
            $this->view->leads_by_source = $this->_reports->leads_by_source($_POST);    
        	$this->view->prospects_by_source = $this->_reports->prospects_by_source($_POST);    
        	$this->view->accouns_by_source = $this->_reports->accouns_by_source($_POST);    
        	
            $this->view->accounts = $this->_reports->leadsource_accounts($_POST);              
            /*==============*/
        }
    }
    
    public function phoneConversionAction() {
        $this->view->reps = $this->_crms->getSalesUsers();
        
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;        	
        	 /* Dariuz Rubin */            
        	$this->view->phonecalls = $this->_reports->phone_calls($_POST);
            $this->view->conversions = $this->_reports->phone_conversion($_POST);            
            $this->view->accounts = $this->_reports->phone_accounts($_POST);              
            /*==============*/           
            
        }
    }
        
    public function contactSourceAction() {        
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;
            $this->view->contacts = $this->_reports->user_source($_POST['from'], $_POST['to']);  
            $this->view->totalContacts = $this->_reports->total_contacts($_POST['from'], $_POST['to']);
        }
    }
    public function responseTimeAction() {  
        $this->view->reps = $this->_crms->getSalesUsers();
        
        /* Dariuz Rubin */
        $this->view->y_hours = $this->_crms->getYHours($this->_auth->id);
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;     
          	
          	// For Summary
          	$this->view->contacts = $this->_reports->response_time($_POST);            
          	
          	// get assigned leads by Cory
          	$total_leads_assigned = $this->_reports->getleads_Manager($_POST);        
            //$this->view->avgContacts = $this->_reports->avg_response_time($_POST);    
            $y_hours = $_POST['y_hours'];
            $i=0;
            $this->view->response_summary = array();
            foreach ($this->view->contacts as $key => $contacts)
			{								
				$total_response = 0;
				$longest_response = 0;
				$avg_response = 0;
				$totalContact = count($contacts);
				$leads_60 = 0;
				$leads_120 = 0;
				$leads_240 = 0;
				$leads_480 = 0;
				$leads_over_480 = 0;
				$leads_in_y_hours = 0;
				$total_sales_leads=0;
				$total_sales_amounts=0;
				$first_contact_phone=0;
				$not_responded=0;
				foreach ($contacts as $ind => $contact)
				{
					if ($contact["responseTime"]>0)
						$total_response += $contact["responseTime"];
					if ($longest_response < $contact["responseTime"])
						$longest_response = $contact["responseTime"];
						
					if ($contact["responseTime"]>480)
						$leads_over_480++;
					else if ($contact["responseTime"]>240)
						$leads_480++;
					else if ($contact["responseTime"]>120)
						$leads_240++;
					else if ($contact["responseTime"]>60)
						$leads_120++;
					else if ($contact["responseTime"]>0)
						$leads_60++;
					
					if ($contact["notes"] == 'phone')
						$first_contact_phone++;
						
					if (($contact["responseTime"]<=60*$y_hours) and ($contact["responseTime"]>0))
						$leads_in_y_hours++;
						
					if ($contact["responseTime"] == 0)
						$not_responded++;	
						
					$this->view->response_summary[$i]['parent_user'] = $contact["parent_user"];
					
					$total_sales_leads += $this->_reports->get_sales_leads($contact["id"]);            
          			$total_sales_amounts += $this->_reports->get_sales_amounts($contact["id"]);
				}
				if ($totalContact>0)
					$avg_response = round($total_response/$totalContact, 0);
				
				
				$this->view->response_summary[$i]['avg_response'] = $avg_response;
				$this->view->response_summary[$i]['longest_response'] = $longest_response;
				$this->view->response_summary[$i]['total_leads'] = $totalContact;
				$this->view->response_summary[$i]['leads_60'] = round($leads_60/$totalContact, 4) * 100;
				$this->view->response_summary[$i]['leads_120'] = round($leads_120/$totalContact, 4) * 100;
				$this->view->response_summary[$i]['leads_240'] = round($leads_240/$totalContact, 4) * 100;
				$this->view->response_summary[$i]['leads_480'] = round($leads_480/$totalContact, 4) * 100;
				$this->view->response_summary[$i]['leads_over_480'] = round($leads_over_480/$totalContact, 4) * 100;
				$this->view->response_summary[$i]['leads_in_y_hours'] = round($leads_in_y_hours/$totalContact, 4) * 100;
				
				$this->view->response_summary[$i]['total_sales_amounts'] = $total_sales_amounts;
				$this->view->response_summary[$i]['total_sales_leads'] = $total_sales_leads;
				$this->view->response_summary[$i]['total_leads_assigned'] = 0;
				
				$this->view->response_summary[$i]['firstconact_by_phone'] = $first_contact_phone;
				$this->view->response_summary[$i]['not_responded'] = $not_responded;
				
				if (isset($contacts[0]) and (strcasecmp($contacts[0]['parent_user'],'Cory Nielsen')==0))
					$this->view->response_summary[$i]['total_leads_assigned'] = $total_leads_assigned;
				
				$i++;
			}          
            
            // For Sheet2
          	$this->view->contacts_sheet2 = $this->_reports->response_time_for_rep($_POST);            
          
            $this->_crms->setYHours($this->_auth->id,$y_hours);
        }
        /*==============*/           
    }
    public function allaccountsAction() {
         $this->view->reps = $this->_crms->getSalesUsers();    
         $this->view->types = $this->_crms->getAccountType();
         $this->view->businessTypes = $this->_crms->getBusinessType();
         $this->view->soldBys = $this->_crms->getSoldByOptions();
         $this->view->sources = $this->_crms->getSource();
         $this->view->contactVias = $this->_crms->getLeadSource();
         
         /*select country, count(country) as count from (SELECT country as country, name from user  left join country on country = country.iso_code_2 where name is null) a group by country*/
         if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;     
         //   echo '<pre>';
          //  var_dump($_POST);
            $this->view->accounts = $this->_reports->accounts($_POST);
            $this->view->groupBy = $_POST['by'];
        }
    }
    
    /* Dariuz Rubin */
    public function followUpsAction() {
        $this->view->reps = $this->_crms->getSalesUsers();
        
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;
                       
            $this->view->createdEvents = $this->_reports->getCreatedEvents($_POST,$this->_auth->id);
            $this->view->scheduledEvents = $this->_reports->getScheduledEvents($_POST,$this->_auth->id);
            $this->view->contactedEvents = $this->_reports->getContactedEvents($_POST,$this->_auth->id);
            
           
        }
    }
    
    
    
    /**
	* Rep's Queues	
	*/
    public function repQueuesAction() {
        $this->view->reps = $this->_crms->getSalesUsers();
        
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;
                       
            $this->view->leadQueues = $this->_reports->getLeadQueues($_POST);
            $this->view->prospectQueues = $this->_reports->getProspectdQueues($_POST);
            $this->view->accountQueues = $this->_reports->getAccountQueues($_POST);
            
        }
    }
    
    /**
	* Dollar Amount Report 	
	*/
     public function dollarAmountAction() {
        $this->view->reps = $this->_crms->getSalesUsers();
        
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;                       
            $this->view->amountCustomers = $this->_reports->getAmountCustomers($_POST);
           
        }
    }
    
     /**
	* Dollar Amount Report 	
	*/
     public function callLogAction() {
        $this->view->reps = $this->_crms->getSalesUsers();
        
        if ($this->getRequest()->isPost()) {
            $this->view->isPost = 1;                       
            $this->view->amountCustomers = $this->_reports->getAmountCustomers($_POST);
           
        }
    }
    /*==============*/
}
