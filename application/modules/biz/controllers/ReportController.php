<?php

class ReportController extends Zend_Controller_Action {

    public function init() {
        if (Zend_Auth::getInstance()->getIdentity() && (Zend_Auth::getInstance()->getIdentity()->role =='admin' || Zend_Auth::getInstance()->getIdentity()->role =='marketing') ) {            
        } else {        
           $this->_helper->redirector('index', 'fm');
        }
        $this->_users = new Application_Model_User;        
        $this->_auth = Zend_Auth::getInstance()->getIdentity();     
        $this->_reports = new Application_Model_Report;
    }
    
    public function dashboardAction() {
        $dealShipped = $this->_reports->getShippedRedemption();
        $this->view->dealShipped = json_encode($dealShipped);
              
        $contacts = $this->_reports->getContacts();
        $this->view->contacts = json_encode($contacts);
        $this->view->totalContact = $contacts[0]['total'];
        
        $storeTotal = $this->_reports->getStoreTotal();
        $this->view->storeTotal = json_encode($storeTotal);
    }
    
}
