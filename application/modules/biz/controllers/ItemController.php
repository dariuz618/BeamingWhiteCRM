<?php

class ItemController extends Zend_Controller_Action {

    public function init() {
        
        $this->_auth = Zend_Auth::getInstance()->getIdentity();
        if (!$this->_auth) {
           $this->_helper->redirector('login', 'user');
        }
        $itemsAllowed = array('Tom', 'Luis', 'Jing', 'Mychael', 'Loli');
        if(!in_array($this->_auth->firstname, $itemsAllowed)) {
           $this->_helper->redirector('view', 'inventory'); 
        }      
        $this->m_user = new Application_Model_User; 
        $this->m_item = new Application_Model_Item;         
    }

    public function viewAction() {
        
    }
    
    public function getRawMaterialAction() { 
        $sort = isset($_POST['sort'])?$_POST['sort']:'name';
        $order = isset($_POST['order'])?$_POST['order']:'asc';
        
        $materials = $this->m_item->raw_material($sort, $order);
        echo json_encode($materials);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    public function saveRawMaterialAction() {       
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $itemsAllowed = array('Tom', 'Luis', 'Jing', 'Mychael');
        if(!in_array($this->_auth->firstname, $itemsAllowed)) {
           return;
        }           
        $data = array ('itemNumber'=> trim($_POST['itemNumber']), 'name' => trim($_POST['name']), 
            'invoice_name' => trim($_POST['invoice_name']), 'fdaNumber' => trim($_POST['fdaNumber']));  
        $save = $this->m_item->save_raw_material($data);
               
        if(is_int($save)) {
              $mail = new Zend_Mail();
                $message = "This is an automatic email to notify you a new inventory item has been created.<br><br>";
                $message .= "Item Number: {$data['itemNumber']} <br>";
                $message .= "Item Name: {$data['name']} <br>";
                $message .= "Commercial Invoice Name: {$data['invoice_name']} <br>";
                $mail->setBodyHTML($message);
		$mail->setFrom('no-reply@beamingwhite.com', 'China Inventory System');
                $emailLists = array('loli@beamingwhite.com', 'luis@beamingwhite.com','mark@beamingwhite.com', 'pedidos@beamingwhite.com', 'tom@beamingwhite.com', 'jeff@beamingwhite.com');                
                foreach ($emailLists as $email){
                    $mail->addTo($email);
                }
                $mail->addBcc('jing@beamingwhite.com', 'mychael@beamingwhite.com');
		$mail->setSubject('China Inventory New Item Created');
                $mail->send();
             echo json_encode(array('success'=>true));
        } else {
             $error = array('isError' => true);
             if (strstr($save, 'SQLSTATE[23000]')) {
                 $error ['msg'] = 'An item number or name is already existing.';
             } else {
                 $error['msg'] = 'Unable to save';
             }
            echo json_encode($error);
        }      
    }
    public function updateRawMaterialAction() {   
        
        $data = array ('itemNumber'=> trim($_POST['itemNumber']), 'name' => trim($_POST['name']),'invoice_name' => trim($_POST['invoice_name']),
             'fdaNumber' => trim($_POST['fdaNumber']));   
        $this->m_item->update_raw_material($data, $_POST['id']);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);        
    }
    public function deleteRawMaterialAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);  
        
        $itemsAllowed = array('Tom', 'Luis', 'Jing', 'Mychael');
        if(!in_array($this->_auth->firstname, $itemsAllowed)) {
           return;
        }
        $this->m_item->delete_raw_material($_POST['id']);
        echo json_encode(array('success'=>true));             
    }
}

