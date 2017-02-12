<?php

class UserController extends Zend_Controller_Action {

    public function init() {
        $this->_users = new Application_Model_User;
        $this->_auth = Zend_Auth::getInstance()->getIdentity();      
    }
   
    public function indexAction() {
        if(!Zend_Auth::getInstance()->getIdentity()) {
            $this->_helper->flashMessenger->addMessage("You don't have the permission to access this information.");
            $this->_helper->redirector('login', 'user');           
        }
        $this->view->headTitle('Beaming White Business Account');             
    }
 
    public function loginAction() {
       
        if(Zend_Auth::getInstance()->hasIdentity()) {
             Zend_Auth::getInstance()->clearIdentity();
        }
        $this->view->headTitle('Beaming White Business Management Log In');
        $this->view->headLink()->appendStylesheet('/public/css/base.css');
        if ($this->getRequest()->isPost()) {            
                $data = array ('email'=> $_POST['email'], 'password' => $_POST['password']);
                $auth = $this->_users->authenticate($data);                
                if ($auth) {
                	$sess = new Zend_Session_Namespace('Snooze');                	
                	$sess->snooze_time = 2;
                	
                    $authentacation = Zend_Auth::getInstance()->getIdentity();  
                    $this->_users->editUser(array('last_login' =>date("Y-m-d H:i:s"), 'ip' => $_SERVER['REMOTE_ADDR']), (int)$authentacation->id);
                    if($authentacation->role == 'sales') {
                           $this->_helper->redirector->gotoUrl("/crm/leads?repid={$authentacation->id}&pid=");
                       } else {
                           $this->_helper->redirector('index', 'fm');
                       }                        
                } else {
                    $this->view->message = 'Invalid crediential, please try again.';
                }            
        }
    }
    
    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();
        session_destroy();
        $this->_helper->redirector('login', 'user'); // back to login page
    }
    
    public function resetPasswordAction() {
        if ($this->getRequest()->isPost()) {           
            if ($this->_users->setPasswordByEmail(trim($_POST['email']), trim($_POST['password']))) {
                $this->view->message = 'Password Changed Successfully.';
                $this->view->color = 'success';
            } else {
                $this->view->message = 'Unable to reset password.';
                $this->view->color = 'danger';
            }
        }
    }
    
    public function signupAction() {       
        if (!$this->_auth  || !in_array('manage_accounts', unserialize($this->_auth->permission))) {
            $this->_helper->flashMessenger->addMessage("You don't have the permission to access this information.");
            $this->_helper->redirector('login', 'user');
        }
        
        $this->view->departments = $this->_users->getDepartments();      
        if ($this->getRequest()->isPost()) {                 
               // $file_level = isset($_POST['file_level'])?$_POST['file_level']:0;
                $data = array ('email' => trim($_POST['email']),
                    'firstname' => trim($_POST['firstname']), 
                    'lastname' => trim($_POST['lastname']),
                    'password' => trim($_POST['password']),
                    'status' => 'Active',
                   // 'file_level' => $file_level,
                    'role' => strtolower(trim($_POST['role']))
                    );
                
                $insert = $this->_users->save($data);               
                if (strstr($insert, 'SQLSTATE[23000]')) {
                    $this->view->message = 'User name already taken. Please choose another one.';
                    $this->view->color = 'danger';
                    return;
                } elseif(is_numeric($insert)) {
                    $this->view->message = 'Account Created Successfully';
                    $this->view->color = 'success';
                }
         } 
    }
    public function usersAction() {        
        $this->view->users= $this->_users->getUsers();
    }
    public function editAction() {
        if (!in_array('manage_accounts', unserialize($this->_auth->permission))) {
           die(); 
        }
        $this->view->user = $this->_users->getUser($_POST['id']);
        $this->view->departments = $this->_users->getDepartments();
        if ($this->getRequest()->isPost() && isset($_POST['action']) && $_POST['action'] == 'edit') {
            $this->_helper->viewRenderer->setNoRender(TRUE);
            if (trim($_POST['firstname']) == '' || trim($_POST['lastname']) == '' || trim($_POST['email']) == '' || trim($_POST['role']) == ''){
                echo "Please enter all required fields.";
                $this->_helper->layout()->disableLayout();
                return;
            }
        //   $file_level = isset($_POST['file_level'])?$_POST['file_level']:0;
           $data = array ('email' => trim($_POST['email']),
                    'firstname' => trim($_POST['firstname']), 
                    'lastname' => trim($_POST['lastname']),                                 
                 //   'file_level' => $file_level,
                    'role' => strtolower(trim($_POST['role']))
                    );
                
                $save = $this->_users->editUser($data, $_POST['id']);             
                if (strstr($save, 'SQLSTATE[23000]')) {
                   echo 'User name already taken. Please choose another one.';                                       
                } elseif(is_numeric($save)) {
                   echo 'success';                   
                }
        }
        //disable a user
        if ($this->getRequest()->isPost() && isset($_POST['action']) && $_POST['action'] == 'delete') {
            $this->_helper->viewRenderer->setNoRender(TRUE);
            if($this->_users->editUser(array('status' => 'Disabled'), (int)$_POST['id'])) {
                echo 'success';
            } else {
                echo 'Unable to delete User';
            }
        }
        $this->_helper->layout()->disableLayout();
   }
   
   public function editPermissionAction() {       
       $this->_users->editUser(array('permission'=>serialize($_POST['permission'])), (int)$_POST['id']);   
       $this->_helper->layout->disableLayout();
       $this->_helper->viewRenderer->setNoRender(TRUE);
   }
}

