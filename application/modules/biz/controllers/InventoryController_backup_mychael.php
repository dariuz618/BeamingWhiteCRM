<?php

class InventoryController extends Zend_Controller_Action {

    private $_requriedFields = array('ordered_by' => 'Ordered By Field is Required',
                                     'buyer' => 'Buyer Field is Required',
                                     'item'  => 'Item Field is Required.',
                                     'quantity_ordered'   => 'Quantity Field is Required.'
                                    );
    private $_shipmentRequriedFields = array(                                    
                                     'destination' => 'Destination Field is Required.',
                                     'shipDate' => 'Ship Date Field is Required.',
                                     'shipTo' => 'Ship To Field is Required.'
                                     );
    private $_itemRequiredFields = array('item'  => 'Item Field is Required.',                                                                          
                                     'quantity' => 'Item Quantity is Required'
                                    );
    
    
    public function init() {        
        if(!Zend_Auth::getInstance()->getIdentity() || !in_array('china_inventory', unserialize(Zend_Auth::getInstance()->getIdentity()->permission))){
            $this->_helper->redirector('index', 'fm');
        } 
        $this->_auth = Zend_Auth::getInstance()->getIdentity();        
       // $this->m_product = new Application_Model_Product;
        $this->m_user = new Application_Model_User; 
        $this->m_inventory = new Application_Model_Inventory;         
    }

    public function viewAction() {
        $this->view->orders = $this->m_inventory->get_orders();
        //$this->view->usCbm = 0;
        foreach ($this->view->orders as $order) {
            $this->view->usCbm += $order['usCbm'];
            $this->view->spainCbm += $order['spainCbm'];
        }
    }
    public function findItemAction() {
         if ($this->_getParam('id')) {
             $this->view->itemNumber = $this->_getParam('id');
         } 
         $this->view->items = $this->m_inventory->get_order_items();        
    }
    public function findItemResultAction() {
        $itemNumber = $this->_getParam('id');      
        $this->view->orders = $this->m_inventory->get_orders_by_item($itemNumber); 
        $this->view->shipments = $this->m_inventory->get_item_shipments($itemNumber);
        $this->view->transactions = $this->m_inventory->get_item_transactions($itemNumber);      
        $this->_helper->layout()->disableLayout();
    }
    public function logAction() {
         $id = $this->_getParam('id');
         $type = $this->_getParam('type');
         $this->view->logs = $this->m_inventory->get_log($id, $type);   
         if ($type == 'order') {
             $this->view->item = $this->m_inventory->get_order($id);
         }
         $this->_helper->layout()->disableLayout();
    }
    
    public function shipmentHistoryAction() {
        $id = $this->_getParam('id');        
        $this->view->shipments = $this->m_inventory->get_order_shipments($id);          
        $this->_helper->layout()->disableLayout();
    }
    public function transactionHistoryAction() {
        $id = $this->_getParam('id');        
        $this->view->transactions = $this->m_inventory->get_transactions($id);          
        $this->_helper->layout()->disableLayout();
    }
       
    private function _validator($data = array())
    {
       foreach ($this->_requriedFields as $field => $message) {
           if (empty($data[$field])) {
               $this->error .= $message . '<br>';
           }
       }
    }
    
    public function updateOrderAction() {
        
       // $files = array();      
        $id = $this->_getParam('id');
        $this->view->items = $this->m_inventory->get_items();
        $this->view->order = $this->m_inventory->get_order($id);
        $originalOrder = $this->view->order;
        $this->view->buyers = $this->m_inventory->get_buyers();
        $this->view->orderBys = $this->m_inventory->get_orderBys();
        $this->view->notes = $this->m_inventory->get_inventory_notes($id, 'order');
        
        $files = $this->m_inventory->getOrderFiles($id);        
        $this->view->filePreview = '[]'; $fileConfig = array();
        $this->view->fileConfig = '';
        if (!empty($files)) {
           $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
           $filePath = $config->toArray();
          // $this->_downloadsDir =  DIRECTORY_SEPARATOR.$filePath['inventoryFilePath'];
           $this->_downloadsDir = '/data/upload/inventory';
            
           $this->view->filePreview = '['; $i = 0;
           foreach ($files as $file) {                               
                $this->view->filePreview .= "\"<a href=\'$this->_downloadsDir/$id/{$file['url']}\' target=_new><img src=\'$this->_downloadsDir/$id/{$file['url']}\' class=\'file-preview-image\'></a>\",";
                $fileConfig[$i]['caption'] = $file['url'];
                $fileConfig[$i]['url'] = "/inventory/remove-image/id/{$file['file_id']}";     
                //$fileConfig[$i]['key'] = $file['file_id'];
                ++$i;
            }            
            $this->view->filePreview .= ']';           
        }
         $this->view->fileConfig = json_encode($fileConfig);
         //$this->view->fileConfig = '[{caption:"1.jpg",url:"\/inventory\/delete-image",key:3},{caption:"7.jpg",url:"\/inventory\/delete-image",key:10},{caption:"11.jpg",url:"\/inventory\/delete-image",key:20},{caption:"12.tiff",url:"\/inventory\/delete-image",key:21}]';
        //echo $this->view->fileConfig;
         
        if ($this->getRequest()->isPost()) {

            $data = array('quantity_ordered' => trim($this->_getParam('quantity_ordered')),
                          'priority' => $this->_getParam('priority'),
                          'buyer' => $this->_getParam('buyer'),
                          'ordered_by' => $this->_getParam('ordered_by'),
                          'supplier' => trim($this->_getParam('supplier')), 
                          'supplier_english' => trim($this->_getParam('supplier_english')), 
                          'specification' => $this->_getParam('specification'),                                                 
                          'quantity_received'=> trim($this->_getParam('current_quantity')) + $this->view->order['quantity_received'],
                          'EDD' => $this->_getParam('EDD'),
                          'ADD' => $this->_getParam('ADD'),                         
                          'modified_by' => $this->_auth->firstname. ' '.$this->_auth->lastname,
                          'modified_time' => date('Y-m-d H:i:s'));         
            if (trim($this->_getParam('item')!= '')) {
                $nameParts = preg_split('/[|||]+/', trim($this->_getParam('item')));
                $data['itemNumber'] = $nameParts[0];
                $data['item'] = $nameParts[1];
            } 
            if ($data['EDD'] == '') {
                $data['EDD'] = NULL;
            }
            if ($data['ADD'] == '') {
                $data['ADD'] = NULL;
            }
                      
            $this->_validator($data);
            //sum of USA and spain quantity needs to be equal to the total quantity            
           
            $data['usaQuantity'] = $this->_getParam('usaQuantity')==''?0:$this->_getParam('usaQuantity');
            $data['spainQuantity'] = $this->_getParam('spainQuantity')==''?0:$this->_getParam('spainQuantity');
           
            // echo '<pre>';
            //var_dump($data);
            //die();
            
            if ($_POST['quantity_ordered'] != $_POST['usaQuantity'] + $_POST['spainQuantity']) {
                $this->error = 'Spain and USA quantity not match total quantity ordered.<br>';
            }
           // die();                      
            if (isset($this->error)) {
                $this->view->error = $this->error;
                return;
            } 
            
            if ($data['quantity_received'] != $originalOrder['quantity_received'] ){
                //$data['total_shipped'] = $this->m_inventory->shipment_total($id);                
                //update quantity oh
                $data['quantity_oh_china'] = $originalOrder['quantity_oh_china'] + trim($this->_getParam('current_quantity'));
                //record current quantity received in the transaction
                $transaction = array('order_id' => $id,
                    'quantity' => trim($this->_getParam('current_quantity')), 
                    'author' => $this->_auth->firstname. ' '.$this->_auth->lastname,
                    'notes' => 'Current Quantity Received');
                $this->m_inventory->save_inventory_transaction($transaction);
                
                //update qoh for each item allocaiton
               /* $allocation = array('itemNumber' => $originalOrder['itemNumber'], 
                                    'item'=> $originalOrder['item'],
                                    'order_id' => $originalOrder['id'],
                                    'quantity' => trim($this->_getParam('current_quantity')),
                                    'quantity_ordered'=> $originalOrder['quantity_ordered'],
                                    'usaQuantity' => $originalOrder['usaQuantity'],
                                    'spainQuantity' => $originalOrder['spainQuantity']);*/
                $allocation = array('itemNumber' => $data['itemNumber'], 
                                    'item'=> $data['item'],
                                    'order_id' => $originalOrder['id'],
                                    'quantity' => trim($this->_getParam('current_quantity')),
                                    'quantity_ordered'=> $data['quantity_ordered'],
                                    'usaQuantity' => $data['usaQuantity'],
                                    'spainQuantity' => $data['spainQuantity']);
                
                $allocation['firstReceived']= ($originalOrder['quantity_oh_china'] > 0 )?0:1;
                $updatedAllocation = $this->m_inventory->update_allocation($allocation);                
            }
           //save order notes
            if (trim($this->_getParam('notes')) != '') {
                $notes = array('notes' =>  $this->_getParam('notes'), 
                               'type' => 'order',
                               'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                               'id' => $id);
                $this->m_inventory->save_inventory_notes($notes);
            }
        
            if ($this->m_inventory->update_orders($data, $id)) {
                //log it
                $logFields = array('item' =>'item',
                                   'priority'=>'priority',
                                   'supplier' => 'supplier',
                                   'specification' => 'specification',
                                   'buyer' => 'buyer',
                                   'ordered_by'=> 'ordered_by',
                                   'quantity_ordered' => 'quantity ordered',
                                   'quantity_received' => 'current quantity received',
                                   'usaQuantity' => 'USA destination quantity',
                                   'spainQuantity' => 'Spain destination quantity',
                                   'EDD' => 'EDD',
                                   'ADD' => 'ADD');
                foreach ($logFields as $key=> $logField) {
                   // echo '<pre>';
                   // var_dump($originalOrder[$key]);
                   // var_dump($data[$key]);
                    if ($originalOrder[$key] != $data[$key]) {                        
                         $log = array('action' => 'Changed',
                             'id'=> $id,
                             'type' => 'order',
                             'item' => $logField,
                             'value' => $data[$key],                             
                             'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                             'original_values' => serialize($originalOrder));
                        // var_dump($log);
                         if ($key == 'item') $log['value'] = $data['itemNumber']. '--'. $data['item'];
                         if ($key == 'quantity_received') {
                             $log['action'] = 'Entered'; $log['value'] = trim($this->_getParam('current_quantity')); 
                             $this->m_inventory->log(array('action' => 'Updated', 'id'=>$id, 'type'=>'order', 
                                  'item'=>'USA QOH', 'value'=> $updatedAllocation['usaQOH'], 'author'=>$this->_auth->firstname. ' '. $this->_auth->lastname));
                             $this->m_inventory->log(array('action' => 'Updated', 'id'=>$id, 'type'=>'order', 
                                  'item'=>'Spain QOH', 'value'=> $updatedAllocation['spainQOH'], 'author'=>$this->_auth->firstname. ' '. $this->_auth->lastname));
                         }
                         $this->m_inventory->log($log);
                    }
                }
               // die();
                
                
                /*$mail = new Zend_Mail();
                $message = "This is an automatic email to notify you an order for {$data['item']} has been requested.";
		$mail->setBodyHTML($message);
		$mail->setFrom('inventory@beamingwhite.com', 'beamingwhite.com');		
		$mail->addTo('jing@beamingwhite.com', 'Support');
		$mail->setSubject('Item Order Created');
                */
                $this->view->notes = $this->m_inventory->get_inventory_notes($id, 'order');
                $this->view->order = $this->m_inventory->get_order($id);  
                $this->view->message = 'Update Successfully';
                 //$this->_redirector->gotoUrl('/my-controller/my-action/param1/test/param2/test2');
                //$this->_helper->flashMessenger->addMessage("Order info updated successfully.");
               // $this->_redirect('/inventory/update-order/id/' . $id);
           } else {
               $this->view->error = 'Unable to update';
           }
        }
    }
   /* public function ajaxUpdateShipmentAction() {
        //ajax call to update the shipment         
            
        if (isset($_POST['inventory_shipment_id'])) {
            $this->_helper->layout()->disableLayout();     
            $this->_helper->viewRenderer->setNoRender(TRUE);
        
            //var_dump($_POST);
            $shipmentId = $_POST['inventory_shipment_id'];
            $shipment = $this->m_inventory->get_shipment_by_container(trim($_POST['container']));
            //it's okay for a new container name
            if ( $shipment && $shipment['inventory_shipment_id'] != $shipmentId) {
                echo 'Another container with the same number already exists!';
                return;
            }         
                                 
            $error = '';
            foreach ($this->_shipmentRequriedFields as $field => $message) {
                if (empty($_POST[$field])) {
                    $error .=  $message . '<br>';
                }
            }            
            if ($error != '') {
                echo $error;
                return;
            }
            //everything checkout
            unset($_POST['inventory_shipment_id']);
            
            if ($this->m_inventory->update_shipment($_POST, $shipmentId)){
               echo 'success';   
            } else {
               echo 'Unable to update';
            }  
            
        }      
                 
    }*/
    public function ajaxRemoveShipmentItemAction() {
        $this->_helper->layout()->disableLayout();     
        $this->_helper->viewRenderer->setNoRender(TRUE);        
        if($_POST['action'] == 'delete') {            
            $originalShipmentItem = $this->m_inventory->get_shipmentItemById($_POST['shipment_items_id']); 
            $this->m_inventory->delete_shipment_item($_POST['shipment_items_id']);
            
            $log = array('action' => 'Deleted Shipment Item',
                'id' => $originalShipmentItem['inventory_shipment_id'],
                'type' => 'shipment',
                'item' => '',
                'value' => '',
                'author' => $this->_auth->firstname . ' ' . $this->_auth->lastname,
                'original_values' => serialize($originalShipmentItem));
            $logFields = array('item' => 'Item',
                'qtyPerBox' => 'Qty per carton',
                'numberOfBoxes' => 'Total carton',
                'quantity' => 'Quantity',
                'volPerBox' => 'Volume/Carton',
                'weightPerBox' => 'GW/Carton',
                'actualPrice' => 'Price',
                'invoicePrice' => 'Commercial Invoice Price'
            );
            foreach ($logFields as $key => $logField) {
                $log['value'] .= $logField . ': ' . $originalShipmentItem[$key] . '<br>';
            }
            $this->m_inventory->log($log);
            
            
            echo 'success';
        }        
    }
    public function ajaxSaveShipmentNotesAction() {
        $this->_helper->layout()->disableLayout();     
        $this->_helper->viewRenderer->setNoRender(TRUE);
        //var_dump($_POST);
        if(trim($_POST['notes'] != '')) {
            $notes = array('notes' =>  $_POST['notes'], 
                               'type' => 'shipment',
                               'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                               'id' => $_POST['id']);
            $this->m_inventory->save_inventory_notes($notes);
            echo 'success';
        } 
    }
   
    
    public function ajaxAddShipmentItemAction() {
        $this->_helper->layout()->disableLayout();     
        $this->_helper->viewRenderer->setNoRender(TRUE);
        if($_POST['action'] == 'add') {
            $itemParts = preg_split('/[***]+/', trim($_POST['item']));
            $data = array('item' => trim($itemParts[2]), 'qtyPerBox' => trim($_POST['qtyPerBox']), 
                'numberOfBoxes'=> trim($_POST['numberOfBoxes']), 'quantity'=> trim($_POST['quantity']),
                'volPerBox'=> trim($_POST['volPerBox']), 'weightPerBox'=> trim($_POST['weightPerBox']),
                'actualPrice'=> trim($_POST['actualPrice']), 'invoicePrice'=> trim($_POST['invoicePrice']),
                'inventory_shipment_id'=> $_POST['inventory_shipment_id']
                );
            $error = '';
            foreach ($this->_itemRequiredFields as $field => $message) {
                if (empty($data[$field])) {
                    $error .=  $message . '<br>';
                }
            }            
            if ($error != '') {
                echo $error;
                return;
            }
            if (!preg_match('/^[1-9]\d*$/', $data['quantity'])) {
                echo "Quantity must be greater than 0";
                return;
            }

            $qoh = $this->m_inventory->get_oh_by_item($data['item']);            
           
            if ($qoh < $data['quantity']) {
            
                echo "Item {$data['item']} only has $qoh on stock, can not ship out {$data['quantity']} <br>";
                return;
            } 
             $order = $this->m_inventory->get_stock_by_item($data['item']);                    
             $orderUpdate['quantity_oh_china'] = $order['quantity_oh_china'] - $data['quantity'];
             $orderUpdate['total_shipped'] = $order['total_shipped'] + $data['quantity'];                   
             //var_dump($orderUpdate); 
             //update order
             $data['order_id'] = $order['id'];                    
                   // var_dump($shipmentItems);
             $this->m_inventory->save_shipment_items($data);                    
             $this->m_inventory->update_orders($orderUpdate, $order['id']);   
             if ($orderUpdate['quantity_oh_china'] < 0 ) {
                   $this->m_inventory->balance_order($order['id']);   
                   $currentOrder = $this->m_inventory->get_order($order['id']);
                   if(($currentOrder['quantity_received'] - $currentOrder['quantity_ordered'] > 0) && $currentOrder['quantity_received'] == $currentOrder['total_shipped']){
                         $adjustment['quantity_received'] = $currentOrder['quantity_ordered'];
                         $adjustment['total_shipped']     = $currentOrder['quantity_ordered'];
                         $this->m_inventory->update_orders($adjustment, $order['id']);
                  }
             }
           
            //update allocation quantity
            //$originalShipment = $this->m_inventory->get_shipment($_POST['inventory_shipment_id']);
            $usaQuantity = $spainQuantity = 0;
            if ($_POST['shipTo'] == 2) {
                $usaQuantity = $data['quantity'];
            } else {
                $spainQuantity = $data['quantity'];
            }
            $this->m_inventory->update_shipment_allocation($order['itemNumber'], $usaQuantity, $spainQuantity);

            //record it in the transaction table
            $transaction = array('order_id' => $order['id'],
                'quantity' => 0 - $data['quantity'],
                'author' => $this->_auth->firstname . ' ' . $this->_auth->lastname,
                'notes' => "Add shipment item: transfer to shipement {$_POST['inventory_shipment_id']}");
            $this->m_inventory->save_inventory_transaction($transaction);

            $log = array('action' => 'Add New Item',
                'id' => $data['inventory_shipment_id'],
                'type' => 'shipment',
                'item' => '',
                'value' => '',
                'author' => $this->_auth->firstname . ' ' . $this->_auth->lastname,
                'original_values' => serialize($data));
            $logFields = array('item' => 'Item',
                'qtyPerBox' => 'Qty per carton',
                'numberOfBoxes' => 'Total carton',
                'quantity' => 'Quantity',
                'volPerBox' => 'Volume/Carton',
                'weightPerBox' => 'GW/Carton',
                'actualPrice' => 'Price',
                'invoicePrice' => 'Commercial Invoice Price'
            );
            foreach ($logFields as $key => $logField) {
                $log['value'] .= $logField . ': ' . $data[$key] . '<br>';
            }
            $this->m_inventory->log($log);
            echo 'success';
        }      
    }
    
    public function ajaxUpdateShipmentItemAction() {
        $this->_helper->layout()->disableLayout();     
        $this->_helper->viewRenderer->setNoRender(TRUE);
       
        $requestQty = trim($_POST['quantity']);
        if (!preg_match('/^[1-9]\d*$/', $requestQty)) {
            echo "Item Quantity must be greater than 0";
            return;
        }
        
        //if it's the same item, add the original one        
        $totalQtyOnHand = $this->m_inventory->get_oh_by_item($_POST['item']) + $_POST['originalQty'];
        
        if($totalQtyOnHand < $requestQty) {
            echo "Total On Hand: $totalQtyOnHand,  $requestQty requested";
            return;
        }       
        
        foreach ($_POST as $key => $value) {
            if($value == '') {
                $_POST[$key] = NULL;
            }
        }
        
        $data = array("qtyPerBox" => $_POST['qtyPerBox'], "numberOfBoxes" => $_POST['numberOfBoxes'],
                'quantity' => $requestQty, 'volPerBox' => $_POST['volPerBox'], 'weightPerBox'=> $_POST['weightPerBox'], 
            'actualPrice' =>$_POST['actualPrice'], 'invoicePrice' => $_POST['invoicePrice']);            
              
        $originalShipmentItem = $this->m_inventory->get_shipmentItemById($_POST['shipment_items_id']);  
        if ($this->m_inventory->update_shipment_item($data, $_POST['shipment_items_id'])) {
            //update successfully, now need to update the order
             $order = $this->m_inventory->get_order($_POST['order_id']);             
             $orderUpdate['quantity_oh_china'] = $order['quantity_oh_china'] + $_POST['originalQty'] - $requestQty;
             $orderUpdate['total_shipped'] = $order['total_shipped'] - $_POST['originalQty'] + $requestQty;
             $this->m_inventory->update_orders($orderUpdate, $order['id']);   
             if ($orderUpdate['quantity_oh_china'] < 0 ) {
                 $this->m_inventory->balance_order($order['id']);  
                 $currentOrder = $this->m_inventory->get_order($order['id']);
                 if(($currentOrder['quantity_received'] - $currentOrder['quantity_ordered'] > 0) && $currentOrder['quantity_received'] == $currentOrder['total_shipped']){
                         $adjustment['quantity_received'] = $currentOrder['quantity_ordered'];
                         $adjustment['total_shipped']     = $currentOrder['quantity_ordered'];
                         $this->m_inventory->update_orders($adjustment, $order['id']);
                 }
             } 
             
              //update allocation quantity           
            $usaQuantity = $spainQuantity = 0;
            if ($_POST['shipTo'] == 2) {
                $usaQuantity = $requestQty - $_POST['originalQty'];
            } else {
                $spainQuantity = $requestQty - $_POST['originalQty'];
            }
            $this->m_inventory->update_shipment_allocation($originalShipmentItem['itemNumber'], $usaQuantity, $spainQuantity);
             
            $transaction = array('order_id' => $_POST['order_id'],
                'quantity' => trim($this->_getParam('current_quantity')),
                'author' => $this->_auth->firstname . ' ' . $this->_auth->lastname,
                'notes' => "Changed shipment {$_POST['inventory_shipment_id']} item quantity from {$_POST['originalQty']} to $requestQty");

             $transaction['quantity'] = ($requestQty - $_POST['originalQty']) > 0 ? $_POST['originalQty'] - $requestQty : $_POST['originalQty'] - $requestQty;
             $this->m_inventory->save_inventory_transaction($transaction);
                       
             //log it              
              $logFields = array(  'qtyPerBox' =>'Qty per carton',
                                   'numberOfBoxes'=>'Total carton', 
                                   'quantity' => $requestQty, 
                                   'volPerBox' => 'Volume/Carton',
                                   'weightPerBox' => 'GW/Carton',
                                   'actualPrice' => 'Price',
                                   'invoicePrice' => 'Commercial Invoice Price'
                                   );           
                foreach ($logFields as $key=> $logField) {
                    if ($originalShipmentItem[$key] != $data[$key]) {                        
                         $log = array('action' => "Changed item {$originalShipmentItem['itemNumber']}",
                             'id'=> $originalShipmentItem['inventory_shipment_id'],
                             'type' => 'shipment',
                             'item' => $logField,
                             'value' => $data[$key],                             
                             'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                             'original_values' => serialize($originalShipmentItem));                         
                         $this->m_inventory->log($log);
                    }
                }
             echo 'success';                                
       } else {
           echo "Unable to update";
       }
        
    }
    public function companyAction() {
        
    }
    public function getCompanyAction() {     
        $companies = $this->m_inventory->get_companies();
        echo json_encode($companies);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    public function saveCompanyAction() {
        $data = array ('name'=> trim($_POST['name']), 'address' => trim($_POST['address']), 'phone' => trim($_POST['phone']),
            'fax'=>trim($_POST['fax']),'taxId'=>trim($_POST['taxId']), 'attn'=>trim($_POST['attn']),
            'attn1'=>trim($_POST['attn1']));
        $this->m_inventory->save_company($data);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    public function updateCompanyAction() {      
        $data = array ('name'=> trim($_POST['name']), 'address' => trim($_POST['address']), 'phone' => trim($_POST['phone']),
            'fax'=>trim($_POST['fax']),'taxId'=>trim($_POST['taxId']), 'attn'=>trim($_POST['attn']),
            'attn1'=>trim($_POST['attn1']));
        $this->m_inventory->update_company($data, $_POST['id']);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);        
    }
    public function deleteCompanyAction() {
        $this->m_inventory->delete_company($_POST['id']);
        echo json_encode(array('success'=>true));
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);        
    }
    
    public function commercialInvoiceAction() {
        $this->_helper->layout()->disableLayout();
        $shipmentId = $this->_getParam('id');        
        
        $this->view->shipment = $this->m_inventory->get_shipment($shipmentId);        
        $this->view->entity = $this->m_inventory->get_company_by_id($this->view->shipment['entity']);
        $this->view->billTo = $this->m_inventory->get_company_by_id($this->view->shipment['billTo']);
        $this->view->shipTo = $this->m_inventory->get_company_by_id($this->view->shipment['shipTo']);
        $this->view->shipmentItems = $this->m_inventory->get_shipment_invoice_items($shipmentId);
        
    }
    public function invoiceAction() {
        $this->_helper->layout()->disableLayout();
        $shipmentId = $this->_getParam('id'); 
        if ($this->_getParam('export')) {
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"invoice_" . $shipmentId . ".xls\"");
        }
        
        $this->view->shipment = $this->m_inventory->get_shipment($shipmentId);        
        $this->view->entity = $this->m_inventory->get_company_by_id($this->view->shipment['entity']);
        $this->view->billTo = $this->m_inventory->get_company_by_id($this->view->shipment['billTo']);
        $this->view->shipTo = $this->m_inventory->get_company_by_id($this->view->shipment['shipTo']);
        $this->view->shipmentItems = $this->m_inventory->get_shipment_items($shipmentId);
        
    }
    
    
    public function packingListAction() {
        $this->_helper->layout()->disableLayout();
        $shipmentId = $this->_getParam('id'); 
        if ($this->_getParam('export')) {
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"packlist_" . $shipmentId . ".xls\"");
        }        
        $this->view->shipment = $this->m_inventory->get_shipment($shipmentId);        
        $this->view->entity = $this->m_inventory->get_company_by_id($this->view->shipment['entity']);
        $this->view->billTo = $this->m_inventory->get_company_by_id($this->view->shipment['billTo']);
        $this->view->shipTo = $this->m_inventory->get_company_by_id($this->view->shipment['shipTo']);
        $this->view->shipmentItems = $this->m_inventory->get_packing_items($shipmentId);
        
    }
    public function shipmentsAction() {        
        $this->view->shipments = $this->m_inventory->get_shipments();
    }  
    
    public function updateShipmentAction() {
        
        $shipmentId = $this->_getParam('id');
        if (isset($_POST['inventory_shipment_id'])) {
            $this->_helper->layout()->disableLayout();     
            $this->_helper->viewRenderer->setNoRender(TRUE);        
            //var_dump($_POST);
            $shipmentId = $_POST['inventory_shipment_id'];
            $shipment = $this->m_inventory->get_shipment($shipmentId);
                                         
            $error = '';
            foreach ($this->_shipmentRequriedFields as $field => $message) {
                if (empty($_POST[$field])) {
                    $error .=  $message . '<br>';
                }
            }            
            if ($error != '') {
                echo $error;
                return;
            }
            //everything checkout
            unset($_POST['inventory_shipment_id']);
            
            if ($this->m_inventory->update_shipment($_POST, $shipmentId)){       
                //log it
                $logFields = array('container' =>'container #',
                                   'destination'=>'destination',
                                   'shipDate' => 'ship Date',
                                   'shipvia' => 'ship Via', 
                                   'billContact' => 'billContact',
                                   'shipContact' => 'shipContact',
                                   'terms' => 'terms',
                                   'freight'=> 'freight'
                                   );
                foreach ($logFields as $key=> $logField) {
                    if ($shipment[$key] != $_POST[$key]) {                        
                         $log = array('action' => 'Changed',
                             'id'=> $shipmentId,
                             'type' => 'shipment',
                             'item' => $logField,
                             'value' => $_POST[$key],                             
                             'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                             'original_values' => serialize($shipment));                         
                         $this->m_inventory->log($log);
                    }
                }
               echo 'success';   
            } else {
               echo 'Unable to update';
            }  
            
        } 
             
            $this->view->companies = $this->m_inventory->get_companies();
            $this->view->contacts = $this->m_inventory->get_contacts();
            $this->view->items = $this->m_inventory->get_stock_item('');  
            $this->view->inventoryItems = $this->view->items;
            $this->view->shipment = $this->m_inventory->get_shipment($shipmentId);              
            $this->view->shipmentItems = $this->m_inventory->get_shipment_items($shipmentId);
            $this->view->notes = $this->m_inventory->get_inventory_notes($shipmentId, 'shipment');
            
            /*foreach ($this->view->shipmentItems as $items) {   
                $push = 1;
                foreach ($this->view->items as $stock) {                        
                    if (trim($stock['item']) == trim($items['item'])){
                        $push = 0;
                        break;                
                    }                    
                }
                if ($push == 1){                   
                    array_push( $this->view->items, array('item' => $items['item'], 'itemNumber' => $items['itemNumber']));
                }
                
            }   */       
        
    }
    public function multiItemBoxAction() {
       $this->_helper->layout()->disableLayout(); 
       if($this->_getParam('id')) {
           $this->view->shipemnt_id = (int)$this->_getParam('id'); 
           $this->view->boxes = $this->m_inventory->get_items_boxes($this->view->shipemnt_id);
       }       
    
       if ($this->getRequest()->isPost()) {                 
            $this->_helper->viewRenderer->setNoRender(TRUE);        
           if ($_POST['action'] == 'add' || $_POST['action'] == 'update' ) {
             //  var_dump($_POST);
               $shipmentItems = explode(",", trim($_POST['shipment_items_id']));
               $data = array('shipment_id'=> $this->view->shipemnt_id, 'numberOfBoxes' => 1,
                   'volPerBox' =>$_POST['volPerBox'] , 'weightPerBox'=> $_POST['weightPerBox']);
               if ($_POST['action'] == 'add') {
                   $boxId = $this->m_inventory->save_order_boxes($data);
                   if ($boxId) {
                        foreach ($shipmentItems as $shipmentItem) {
                            $items = array('box_id' => $boxId);
                            $this->m_inventory->update_shipment_item($items, $shipmentItem);
                            //echo  $this->view->shipmentItems[$shipmentItem - 1]['shipment_items_id'].'<br>';
                        }
                   }
                   echo 'success';
               } elseif ($_POST['action'] == 'update') {
                   //update the box information
                   
                   $update = $this->m_inventory->update_order_boxes($data, $_POST['box_id']);
                  
                   //empty the box first
                   $this->m_inventory->empty_box_items($_POST['box_id']);                     
                   foreach ($shipmentItems as $shipmentItem) {                      
                        $items = array('box_id' => $_POST['box_id']);                    
                        $this->m_inventory->update_shipment_item($items, $shipmentItem);                   
                   }
                   echo 'success';
               }
           }
           if ($_POST['action'] == 'remove') {               
                if($this->m_inventory->remove_order_box($_POST['box_id'])){
                   $this->m_inventory->empty_box_items($_POST['box_id']);
                   echo 'success';
                } else {
                    echo 'Unable to delete';
                }             
           }
           
       }
    }
    public function createBoxAction() {
       if($this->_getParam('id')) {
           $this->view->order_id = $this->_getParam('id');
           $this->view->boxes = $this->m_inventory->get_order_boxes($this->view->order_id);
           $this->view->boxQuantity = $this->m_inventory->total_quantity_in_boxes($this->view->order_id);          
       }
       
       $this->_helper->layout()->disableLayout();   
       if ($this->getRequest()->isPost()) {
           $this->_helper->viewRenderer->setNoRender(TRUE);
                   
           if ($_POST['action'] == 'add' || $_POST['action'] == 'update') {          
            $data = array('shipment_id' =>trim($_POST['shipmentId']) ==''?NULL:trim($_POST['shipmentId']), "qtyPerBox" => trim($_POST['qtyPerBox']),
                "numberOfBoxes" => trim($_POST['numberOfBoxes']),
                'quantity' => trim($_POST['quantity']), 'volPerBox' => trim($_POST['volPerBox']), 'weightPerBox'=> trim($_POST['weightPerBox']), 
            );
            if(!is_numeric($data['volPerBox']) || !is_numeric($data['numberOfBoxes']) || 
                    !preg_match('/^[1-9]\d*$/', $data['qtyPerBox']) || !preg_match('/^[1-9]\d*$/', $data['quantity'])) {
                echo "Please enter valid values for the required field";
                return;
            }
           }           
           if ($_POST['action'] == 'add') {
                $data['order_id'] = $_POST['order_id'];
                if(is_int($this->m_inventory->save_order_boxes($data))){
                    echo 'success';
                } else {
                    echo 'Unable to save this info';
                }
           }
           if ($_POST['action'] == 'update') {               
                if(is_int($this->m_inventory->update_order_boxes($data,$_POST['box_id'] ))){
                    echo 'success';
                } else {
                    echo 'Unable to save this info';
                }
           }
           if ($_POST['action'] == 'remove') {               
                if(is_int($this->m_inventory->remove_order_box($_POST['box_id']))){
                    echo 'success';
                } else {
                    echo 'Unable to delete';
                }               
           }
       }   
        
    }
    
    public function createShipmentAction() {
        
        $this->view->companies = $this->m_inventory->get_companies();
        $this->view->contacts = $this->m_inventory->get_contacts();
        
        $this->view->items = $this->m_inventory->get_stock_item('');
        
        if ($this->getRequest()->isPost()) {
         
            $rows = array();           
            //post from current inventory
            if (isset($_POST['createShipment'])) {
              
                for($i = 0; $i < sizeof($_POST['shipItems']); ++$i) {                  
                    $exportItems = $this->m_inventory->get_stock_by_itemNumber($_POST['shipItems'][$i]);                    
                    $rows[$exportItems['item']]['item'][] = $exportItems['item'];
                    $rows[$exportItems['item']]['quantity'][] = $exportItems['quantity_oh'];
                    $boxes = $this->m_inventory->get_boxes_by_item($exportItems['itemNumber']);                   
                                                          
                    if ($boxes) {  
                        $remaining = 0; $index = 0;
                        foreach($boxes as $box) {
                            $_POST['item'][] = $exportItems['quantity_oh'].'***'.$exportItems['itemNumber'].'***'.$exportItems['item'];                                                        
                          
                            if ($index == 0) {
                                if($index == 0 && $box['quantity_oh_china'] >= $box['quantity']) { 
                                    $_POST['qtyPerBox'][] = $box['qtyPerBox'];
                                    $_POST['volPerBox'][] = $box['volPerBox'];
                                    $_POST['weightPerBox'][] = $box['weightPerBox'];  
                                    $_POST['numberOfBoxes'][] = $box['numberOfBoxes'];                                    
                                    $_POST['quantity'][] = $box['quantity'];
                                    $remaining = $box['quantity_oh_china'] - $box['quantity'];
                                } 
                                if ($index == 0 && $box['quantity_oh_china'] < $box['quantity']) {
                                    //if divisiable
                                    if($box['quantity_oh_china'] % $box['qtyPerBox'] == 0) {
                                        $_POST['qtyPerBox'][] = $box['qtyPerBox'];
                                        $_POST['volPerBox'][] = $box['volPerBox'];
                                        $_POST['weightPerBox'][] = $box['weightPerBox'];  
                                        $_POST['numberOfBoxes'][] = $box['quantity_oh_china'] / $box['qtyPerBox'];
                                        $_POST['quantity'][] = (int)$box['qtyPerBox'] * ((int)$box['quantity_oh_china'] /(int)$box['qtyPerBox']);  
                                    } else {
                                        $_POST['quantity'][] = $box['quantity_oh_china'];
                                    }
                                }
                            } else {
                                if ($remaining > 0) {
                                    if ($remaining < $box['qtyPerBox']) {
                                        $_POST['quantity'][] = $remaining;                                       
                                    }elseif ($remaining > $box['quantity']) {
                                        $_POST['qtyPerBox'][] = $box['qtyPerBox'];
                                        $_POST['volPerBox'][] = $box['volPerBox'];
                                        $_POST['weightPerBox'][] = $box['weightPerBox'];  
                                        $_POST['numberOfBoxes'][] = $box['numberOfBoxes'];
                                        $_POST['quantity'][] = $box['quantity'];     
                                     
                                    } elseif ($remaining % $box['qtyPerBox'] == 0) {
                                        $_POST['qtyPerBox'][] = $box['qtyPerBox'];
                                        $_POST['volPerBox'][] = $box['volPerBox'];
                                        $_POST['weightPerBox'][] = $box['weightPerBox'];  
                                        $_POST['numberOfBoxes'][] = $remaining /(int)$box['qtyPerBox'];                                       
                                        $_POST['quantity'][] = $box['qtyPerBox'] * ($remaining /(int)$box['qtyPerBox']);                                        
                                       
                                    } elseif ($remaining % $box['qtyPerBox'] != 0) {                                       
                                       if(floor($remaining /(int)$box['qtyPerBox']) > 0) {
                                            $_POST['qtyPerBox'][] = $box['qtyPerBox'];
                                            $_POST['volPerBox'][] = $box['volPerBox'];
                                            $_POST['weightPerBox'][] = $box['weightPerBox'];  
                                            $_POST['numberOfBoxes'][] = floor($remaining /(int)$box['qtyPerBox']);
                                            $_POST['quantity'][] = (int)$box['qtyPerBox'] * (int)floor($remaining /(int)$box['qtyPerBox']);
                                            $remaining = $remaining - $box['qtyPerBox'] * floor($remaining /(int)$box['qtyPerBox']);                                           
                                            if ($remaining < $box['qtyPerBox']) {                                                
                                                $_POST['item'][] = $exportItems['quantity_oh'].'***'.$exportItems['itemNumber'].'***'.$exportItems['item'];
                                                $_POST['qtyPerBox'][] = '';
                                                $_POST['volPerBox'][] = '';
                                                $_POST['weightPerBox'][] = '';
                                                $_POST['numberOfBoxes'][] = '';
                                                $_POST['quantity'][] = $remaining;                                               
                                           }                                           
                                       }                               
                                    }
                                }
                            }
                            ++$index;
                        }
                    } else {//end if there are boxes info
                          $_POST['item'][] = $exportItems['quantity_oh'].'***'.$exportItems['itemNumber'].'***'.$exportItems['item'];                                                
                          $_POST['qtyPerBox'][] = '';
                          $_POST['volPerBox'][] = '';
                          $_POST['weightPerBox'][] = '';
                          $_POST['numberOfBoxes'][] = '';
                          $_POST['quantity'][] = $exportItems['quantity_oh'];              
                    }
                }              
                              
            } else {
                                
                for ($i = 0; $i < sizeof($_POST['item']); ++$i) {
                    if (trim($_POST['item'] != '' && trim($_POST['quantity'][$i]))) {                  
                        $itemParts = preg_split('/[***]+/', trim($_POST['item'][$i]));                     
                        $rows[$itemParts[2]]['item'][] = $itemParts[2];                        
                        $rows[$itemParts[2]]['qtyPerBox'][] = trim($_POST['qtyPerBox'][$i]);
                        $rows[$itemParts[2]]['numberOfBoxes'][] = trim($_POST['numberOfBoxes'][$i]);                    
                        $rows[$itemParts[2]]['quantity'][] = trim($_POST['quantity'][$i]);
                        $rows[$itemParts[2]]['volPerBox'][] = trim($_POST['volPerBox'][$i]);
                        $rows[$itemParts[2]]['weightPerBox'][] = trim($_POST['weightPerBox'][$i]);
                        $rows[$itemParts[2]]['actualPrice'][] = trim($_POST['actualPrice'][$i]);
                        $rows[$itemParts[2]]['invoicePrice'][] = trim($_POST['invoicePrice'][$i]);

                    }
                }
            }         
            if (empty($rows)) {
                $this->view->error = "Please Enter items to be shipped.<br>";
            }
           
            foreach ($rows as $key=>$value) {              
                $qoh = $this->m_inventory->get_oh_by_item($key);
                $totalQty =  array_sum($value['quantity']);
                if ($qoh < $totalQty) {
                   $this->view->error .= "Item $key only has $qoh on stock, can not ship out $totalQty <br>";
                } 
            }      
                      
            $shipment = array('container' => trim($_POST['container']), 
                    'entity' => (int)trim($_POST['entity']), 
                    'billTo' => (int)trim($_POST['billTo']),
                    'shipTo' => (int)trim($_POST['shipTo']),
                    'billContact' => trim($_POST['billContact']), 
                    'shipContact' => trim($_POST['shipContact']), 
                    'destination' => trim($_POST['destination']),                   
                    'shipvia' => trim($_POST['shipvia']), 
                    'shipDate' => trim($_POST['shipDate']), 
                    'terms' =>  trim($_POST['terms']),                      
                    'notes' => trim($_POST['notes']),
                    'created_by' =>  $this->_auth->firstname. ' '. $this->_auth->lastname
                );
            
           foreach ($this->_shipmentRequriedFields as $field => $message) {
                if (empty($shipment[$field])) {
                    $this->view->error .= $message . '<br>';
                }
           }
            
           if (isset($this->view->error) ) {
               return;
           }
            
            
           $shipmentId = $this->m_inventory->save_shipment($shipment);
           if ($shipmentId) {    
            
            foreach ($rows as $key=>$row) {
                for($i = 0; $i < sizeof($rows[$key]['item']); ++$i) {                    
                    $shipmentItems = array ('item' => $rows[$key]['item'][$i], 
                        'qtyPerBox' => $rows[$key]['qtyPerBox'][$i], 
                        'numberOfBoxes' => $rows[$key]['numberOfBoxes'][$i], 
                        'quantity' => $rows[$key]['quantity'][$i], 
                        'volPerBox' => $rows[$key]['volPerBox'][$i], 
                        'weightPerBox' => $rows[$key]['weightPerBox'][$i], 
                        'actualPrice' => $rows[$key]['actualPrice'][$i], 
                        'invoicePrice' => $rows[$key]['invoicePrice'][$i],
                        'inventory_shipment_id' => $shipmentId
                        );
                   
                    //get order id
                    $order = $this->m_inventory->get_stock_by_item($shipmentItems['item']);                    
                    $orderUpdate['quantity_oh_china'] = $order['quantity_oh_china'] - $shipmentItems['quantity'];
                    $orderUpdate['total_shipped'] = $order['total_shipped'] + $shipmentItems['quantity'];
              
                    //update order
                    $shipmentItems['order_id'] = $order['id'];                    
                   // var_dump($shipmentItems);
                    $this->m_inventory->save_shipment_items($shipmentItems);                    
                    $this->m_inventory->update_orders($orderUpdate, $order['id']);   
                    if ($orderUpdate['quantity_oh_china'] < 0 ) {
                        //save the ids and balance the quantities after the loop
                        $orderIds[] = $order['id'];
                    } 
                    //update allocation quantity
                    $usaQuantity = $spainQuantity = 0;
                    if ($shipment['shipTo'] == 2) {
                        $usaQuantity = $shipmentItems['quantity'];                                                        
                    } else {
                        $spainQuantity = $shipmentItems['quantity'];
                    }
                    $allocation = $this->m_inventory->update_shipment_allocation($order['itemNumber'], $usaQuantity, $spainQuantity);
                   
                            
                    //record it in the transaction table
                    $transaction = array('order_id' => $order['id'],
                        'quantity' => 0 - $shipmentItems['quantity'], 
                        'author' => $this->_auth->firstname. ' '.$this->_auth->lastname,
                        'notes' => "Create shipment: transfer to shipement $shipmentId");
                        $this->m_inventory->save_inventory_transaction($transaction);
                    
                }
                
                              
            }
             //need to balance orders
             if (!empty($orderIds)) {
                foreach ($orderIds as $orderId) {                  
                    $this->m_inventory->balance_order($orderId); 
                    //correct total received and total shipped number
                     $currentOrder = $this->m_inventory->get_order($orderId);
                     if(($currentOrder['quantity_received'] - $currentOrder['quantity_ordered'] > 0) && $currentOrder['quantity_received'] == $currentOrder['total_shipped']){
                         $adjustment['quantity_received'] = $currentOrder['quantity_ordered'];
                         $adjustment['total_shipped']     = $currentOrder['quantity_ordered'];
                         $this->m_inventory->update_orders($adjustment, $orderId);
                     }
                } 
             }
             //log it            
             $log = array('action' => 'New',
                          'type' => 'shipment',
                          'id' => $shipmentId,
                          'item' => '',                    
                          'value' => "Destination: {$shipment['destination']}, Shipdate: {$shipment['shipDate']},Container #: {$shipment['container']}",
                          'original_values'=> serialize($rows),
                          'author' => $this->firstname. ' '. $this->_auth->lastname);
            $this->m_inventory->log($log);
             
             
            }//end if shipmentId
            $this->view->message = "Shipment Created Successfully!";
            $this->_helper->redirector('shipments', 'inventory');
          }
     }
    
    /*public function createShipmentAction() {
        $id = $this->_getParam('id');
        $this->view->order = $this->m_inventory->get_order($id);  
        $this->view->notes = $this->m_inventory->get_inventory_notes($id, 'shipment');
        $this->view->items = $this->m_inventory->get_stock_item('');
        
        if (($this->_getParam('shipment'))) {            
            $shipmentId = (int)$this->_getParam('shipment');            
            $this->view->shipment = $this->m_inventory->get_shipment($shipmentId); 
            $originalShipment = $this->view->shipment;
            $this->view->notes = $this->m_inventory->get_inventory_notes($shipmentId, 'shipment');
            $this->view->action = 'Update';
        } else {
            $this->view->action = 'Create';
        }
        
        if ($this->getRequest()->isPost()) {
            $data = array('destination' => trim($this->_getParam('destination')),
                'carrier' => trim($this->_getParam('carrier')),
                'order_id' => $id,
                'depart_date'=>  trim($this->_getParam('depart_date')),
                'quantity' => (int)$this->_getParam('quantity'),
                'unit_vol' => $this->_getParam('unit_vol'),
                'unit_dimension' => $this->_getParam('unit_dimension'),
                'unit_quantity' => $this->_getParam('unit_quantity'),
                'unit_weight' => $this->_getParam('unit_weight'),
                'created_by' => $this->_auth->id,
                'created_time' => date('Y-m-d H:i:s'));
            //convinently assign this to display new input values
            $this->view->shipment = $data;
            foreach ($this->_shipmentRequriedFields as $field => $message) {
                if (empty($data[$field])) {
                    $this->view->error .= $message . '<br>';
                }
            }

            if (isset($this->view->error)) {                
                return;
            } 
            if (trim($this->_getParam('notes')) != '') {
                $notes = array('notes' =>  $this->_getParam('notes'), 
                               'type' => 'shipment',
                               'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                               'id' => $id);
                $this->m_inventory->save_inventory_notes($notes);
            }
            //var_dump($this->m_inventory->save_orders($data));
            if($this->view->action == 'Create') {
                $shipmentId = $this->m_inventory->save_shipment($data);
                if (is_int($shipmentId)) {
                    //save notes
                    if (trim($this->_getParam('notes')) != '') {
                        $notes = array('notes' =>  $this->_getParam('notes'), 
                                       'type' => 'shipment',
                                       'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                                       'id' => $shipmentId);
                        $this->m_inventory->save_inventory_notes($notes);
                    }                    
                    //update shipment total if quantity is changed
                    if($data['quantity'] != $originalShipment['quantity']) {
                        $order['total_shipped'] = $this->m_inventory->shipment_total($id);
                        $order['quantity_oh_china'] = $this->view->order['quantity_received'] - $order['total_shipped'];
                        $this->m_inventory->update_orders($order, $id);
                    }
                    //log it
                     $log = array('action' => 'New',
                             'type' => 'shipment',                             
                             'id' => $shipmentId,
                             'item' => '',
                             'value' => $data['quantity'].' '.$this->view->order['item'].' to '.$data['destination']. ' departed '. $data['depart_date'],
                             'author' => $this->_auth->firstname. ' '. $this->_auth->lastname);
                     $this->m_inventory->log($log);
                
                
                    $mail = new Zend_Mail();
                    $message = "This is an automatic email to notify you a shipment for {$data['item']} is created.
                    Please click here to see detail <br>";
                    $mail->setBodyHTML($message);
                    $mail->setFrom('inventory@beamingwhite.com', 'beamingwhite.com');
                    $mail->addTo('jing@beamingwhite.com', 'Support');
                    $mail->setSubject('Shipment Created');

                    $this->_redirect('/inventory/update-order/id/' . $id);
                }
            }
            if($this->view->action == 'Update') {
                if ($this->m_inventory->update_shipment($data, $shipmentId)) {
                    //save notes if there is any
                    if (trim($this->_getParam('notes')) != '') {
                        $notes = array('notes' =>  $this->_getParam('notes'), 
                                       'type' => 'shipment',
                                       'author' => $this->_auth->firstname. ' '. $this->_auth->lastname,
                                       'id' => $shipmentId);
                        $this->m_inventory->save_inventory_notes($notes);
                    }
                    
                    //update shipment total if quantity is changed
                    if($data['quantity'] !=  $originalShipment['quantity']) {
                        $order['total_shipped'] = $this->m_inventory->shipment_total($id);
                        $order['quantity_oh_china'] = $this->view->order['quantity_received'] - $order['total_shipped'];
                        $this->m_inventory->update_orders($order, $id);
                    }
                    //log it
                    $logFields = array('destination' => 'destination',
                        'depart_date' => 'depart date',
                        'carrier' => 'carrier',
                        'quantity' => 'quantity',
                        );
                    foreach ($logFields as $key => $logField) {
                        if ($originalShipment[$key] != $data[$key]) {
                            $log = array('action' => 'Changed',
                                'id' => $shipmentId,
                                'type' => 'shipment',
                                'item' => $logField,
                                'value' => $data[$key],
                                'author' => $this->_auth->firstname . ' ' . $this->_auth->lastname,
                                'original_values' => serialize($originalShipment));
                            $this->m_inventory->log($log);
                        }
                    }
                    
                    $mail = new Zend_Mail();
                    $message = "This is an automatic email to notify you a shipment for {$data['item']} is updated.
                    Please click here to see detail <br>";
                    $mail->setBodyHTML($message);
                    $mail->setFrom('inventory@beamingwhite.com', 'beamingwhite.com');
                    $mail->addTo('jing@beamingwhite.com', 'Support');
                    $mail->setSubject('Shipment Updated');
                    $this->_redirect('/inventory/update-order/id/' . $id);                      
                }
            }
        }        
    }*/
    public function stockAction() {
        $this->view->inventory = $this->m_inventory->get_inventory();
    }
    public function exportAction() {
     
        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"inventory_" . date("Y-m-d") . ".xls\"");

        //Export the data
        //$xls = "<table border=\"1\">";
        $xls = "<table>";
        $xls .= "<tr>";
        $xls .= "<td><b>Item Number</b></td>";
        $xls .= "<td><b>Item</b></td>";
        $xls .= "<td><b>Stock</b></td>";
        $xls .= "<td><b>USA Allocation</b></td>";
        $xls .= "<td><b>Spain Allocation</b></td>";
        $xls .= "<td><b>Total Ordered</b></td>";
        $xls .= "<td><b>Total Received</b></td>";
        $xls .= "<td><b>Total Shipped</b></td>";
        $xls .= "</tr>";

        for ($i = 0; $i < sizeof($_POST['shipItems']); ++$i) {
            $exportItems = $this->m_inventory->get_stock_by_itemNumber($_POST['shipItems'][$i]);
            $xls .= '<tr>' 
                    . '<td>' . $exportItems['itemNumber'] . '</td>'
                    . '<td>' . $exportItems['item'] . '</td>'
                    . '<td>' . $exportItems['quantity_oh'] . '</td>'
                    . '<td>' . $exportItems['usaQOH'] . '</td>'
                    . '<td>' . $exportItems['spainQOH'] . '</td>'
                    . '<td>' . $exportItems['quantity_ordered'] . '</td>'
                    . '<td>' . $exportItems['quantity_received'] . '</td>'
                    . '<td>' . $exportItems['total_shipped'] . '</td>' .
                    '</tr>';
        }
        $xls .= "</table>";
        echo $xls;
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        
    }
    
    
    public function createOrderAction() {        
        $this->view->items = $this->m_inventory->get_items();
        $this->view->buyers = $this->m_inventory->get_buyers();
        $this->view->orderBys = $this->m_inventory->get_orderBys();
        
        if ($this->getRequest()->isPost()) {               
          
            $data = array('ordered_by' => $this->_getParam('ordered_by'),
                          'buyer' => $this->_getParam('buyer'),
                          'quantity_ordered' => trim(preg_replace('#[^0-9\.]#', '', $this->_getParam('quantity_ordered'))),
                          'priority' => $this->_getParam('priority'),                          
                          'specification' => $this->_getParam('specification'),
                          'notes' => trim($this->_getParam('notes')),
                          'created_by' => $this->_auth->firstname.' '.$this->_auth->lastname,
                          'created_time' => date('Y-m-d H:i:s'));  
            $data['usaQuantity'] = $this->_getParam('usaQuantity')==''?0:$this->_getParam('usaQuantity');
            $data['spainQuantity'] = $this->_getParam('spainQuantity')==''?0:$this->_getParam('spainQuantity');
                        
            if (trim($this->_getParam('item')!= '')) {
                $nameParts = preg_split('/[|||]+/', trim($this->_getParam('item')));
                $data['itemNumber'] = $nameParts[0];
                $data['item'] = $nameParts[1];
            }   
            $this->view->data = $data;
            $this->_validator($data);

            if ($data['quantity_ordered'] != $data['usaQuantity'] + $data['spainQuantity']) {
                $this->error = 'Spain and USA quantity not match total quantity ordered.<br>';
            }
                
            if ($this->error) {
                $this->view->error = $this->error;                
                return;
            } 
            //var_dump($this->m_inventory->save_orders($data));
            $id = $this->m_inventory->save_orders($data);
            if (is_int($id)) {
                $log = array('action' => 'New',
                             'type' => 'order',
                             'id' => $id,
                             'item' => '',                    
                             'value' => 'Ordered By: '. $data['ordered_by']. ',Buyer: '. $data['buyer']. ', Item number '. $data['itemNumber'].', 
                                 Item name: '. $data['item'] .  ', quantity '.$data['quantity_ordered'].  ', USA quantity '.$data['usaQuantity']
                                 .  ', Spain quantity '.$data['spainQuantity'],
                             'author' => $this->_auth->firstname. ' '. $this->_auth->lastname);
                $this->m_inventory->log($log);
                
                $mail = new Zend_Mail();
                $message = "This is an automatic email to notify you an order for {$data['quantity_ordered']} {$data['item']} has been requested.<br>";
		$message .= "Please Click <a href='http://beamingwhite.mx/biz/inventory/update-order/id/$id'>here</a> to view detail.";
                        
                $mail->setBodyHTML($message);
		$mail->setFrom('no-reply@beamingwhite.com', 'China Inventory System');
                $emailLists = array('adrian@beamingwhite.com', 'lucky@beamingwhite.com', 'loli@beamingwhite.com', 'luis@beamingwhite.com', 'pedidos@beamingwhite.com', 'tom@beamingwhite.com');
                foreach ($emailLists as $email){
                    $mail->addTo($email);
                }
                $mail->addBcc('jing@beamingwhite.com');
		$mail->setSubject('China Inventory Order Created');
                $mail->send();
                
                $this->_helper->redirector('view', 'inventory');
           }
        }
    }
          
    public function itemAutosuggestAction() {     
      
        $items = $this->m_inventory->get_stock_item_suggestion($this->_getParam('queryString'));
        $options = array();

        if ($items) {
            foreach ($items as $item) {
                $options[] = $item['totalOnHand'].'***'.$item['itemNumber'].'***'.$item['item'];
            }
        }
        echo json_encode($options);
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
     public function boxAutosuggestAction() {   
        
        if($this->_getParam('term') != ''){
            $itemParts = preg_split('/[***]+/', $this->_getParam('term'));       
            $boxes = $this->m_inventory->get_boxes_by_item($itemParts[1]);          
            if($boxes) {
                echo json_encode($boxes);
            }       
        }
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
    
    public function quantityAction() {
        $this->view->items = $this->m_inventory->get_order_items();
        if ($this->getRequest()->isPost()) {                
            if($this->m_inventory->adjust_order_quantity($_POST['orderId'], $_POST['quantity'], $_POST['reason'])) {
               $this->view->message = 'Quantity Adjusted';
               $this->view->color = 'success';
            }  else {
                $this->view->message = 'Unable to adjust quantity.';
                $this->view->color = 'danger';             
            } 
        }
    }
    public function ajaxItemOrdersAction() {        
        $this->view->orders = $this->m_inventory->get_orders_by_item($_POST['itemNumber']);
        echo "<select name='orderId'  class='form-control'>";                        
        foreach ($this->view->orders as $order) {
           echo "<option value='{$order['id']}'> {$order['id']}  (QOH {$order['quantity_oh_china']}) </option>";
        }      
        echo "</select>";
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    
   public function uploadImageAction() {      
       
       $this->_helper->layout->disableLayout();
       $this->_helper->viewRenderer->setNoRender(TRUE);
       if (empty($_FILES['images'])) {
            $output = array('error'=>'No files found for upload.');
            echo json_encode($output);            
            return; // terminate
       } else {           
           $ImageType = $_FILES['images']['type'];
           $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
           $filePath = $config->toArray();
           $this->_downloadsDir = $filePath['inventoryFilePath'];
                     
           $upload = new Zend_File_Transfer_Adapter_Http();
           $upload->setDestination($this->_downloadsDir);
           $files = $upload->getFileInfo();           
           try {
                // upload received file(s)
                $upload->receive();
            } catch (Zend_File_Transfer_Exception $e) {
                $e->getMessage();
                exit;
            }
            $fname = $upload->getFileName();
            $mime_type = $upload->getMimeType();
            $size = $upload->getFileSize();

            //file extension;
            $fext_tmp = explode('.', $fname);
            $file_ext = $fext_tmp[(count($fext_tmp) - 1)];
         
            $newFileName = $this->m_inventory->get_file_name($_POST['order_id']);

            if (!file_exists($this->_downloadsDir.DIRECTORY_SEPARATOR.$_POST['order_id'])) {
                if (!mkdir($this->_downloadsDir.DIRECTORY_SEPARATOR.$_POST['order_id'], 0777, true)) {
                    throw new Exception('Can not create directory.');
                    return;
                }
            }
            
            $new_file = $this->_downloadsDir .DIRECTORY_SEPARATOR. $_POST['order_id']. DIRECTORY_SEPARATOR. $newFileName . '.' . $file_ext;
         
            $filterFileRename = new Zend_Filter_File_Rename(
                    array(
                'target' => $new_file, 'overwrite' => true
            ));

            $filterFileRename->filter($fname);

            if (file_exists($new_file)){ 
                $data = array('order_id'=> $_POST['order_id'],'url'=> $newFileName . '.' . $file_ext, 
                    'title' => $files['images']['name'], 'uploader' =>  $this->_auth->firstname);
                if($this->m_inventory->save_inventory_file($data)) {
                    $output['success'] = TRUE;
                    echo json_encode($output);
                } else {
                    $output['error'] = 'Database save failed';                    
                }             
            } else {
                $output = array('error' => 'No files found for upload.');
                echo json_encode($output);
                return; // terminate
            }
        } 
   }
   public function removeImageAction() {
    
       $this->_helper->layout->disableLayout();
       $this->_helper->viewRenderer->setNoRender(TRUE);   
    
       if ($this->_getParam('id')) {            
            //first check if the user has the prividlege
           /* if ($this->_auth->file_level < 3) {
                echo 'You do not have permission to delete this file!';
                return;
            }*/
            
           $config = new Zend_Config_Ini(CONFIGFILE, APPLICATION_ENV, true);
           $filePath = $config->toArray();
           $this->_downloadsDir = $filePath['inventoryFilePath'];           
           $thisFile =  $this->m_inventory->getFileById($this->_getParam('id'));
           $filePath =  $this->_downloadsDir . DIRECTORY_SEPARATOR . $thisFile['order_id'] .  DIRECTORY_SEPARATOR  .$thisFile['url'];
           
           if($thisFile && file_exists($filePath)) {
                if (unlink($filePath)) {
                    //delete from database
                    if ($this->m_inventory->deleteFile($this->_getParam('id'))){
                        $result['success'] = TRUE;
                        echo json_encode($result);                    
                    }
                } 
            }
       }       
   }        
    public function importInventoryAction() {
        $this->_db = Zend_Registry::get('db');
        /*
         * truncate table inventory_action_log;
          truncate table inventory_notes;
          truncate table inventory_order;
          truncate table inventory_shipment;
          truncate table inventory_shipment_items;
          truncate table inventory_transaction;
         */
        $row = 1;
        if (($handle = fopen("data/inventory.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
              //  echo "<p> $num fields in line $row: <br /></p>\n";
                $row++;
                
                $createdTime =  preg_split('/[\.]+/', $data[0]);    
                
                $orders = array(   
                    'created_time'=>$createdTime[0].'-'.$createdTime[1].'-'.$createdTime[2],    
                    'created_by'=>$data[1],
                    'priority' => 'normal',
                    'itemNumber' => $data[2],                    
                    'item' => $data[3],
                    'quantity_ordered' => $data[4],
                    'quantity_received' => $data[5],
                    'quantity_oh_china' => $data[6],
                    'supplier' => $data[7],
                    'total_shipped' => $data[10]                   
                );
             
             
                if ($orders['itemNumber'] !='' && $orders['quantity_ordered'] > 0 ) {
                    echo '<pre>';
                    var_dump($orders);
                    echo "<br />\n";
                    $orderId = $this->m_inventory->save_orders($orders);  
                    
                    //Add quantity received to inventory_transaction
                    //record it in the transaction table
                    if ($orders['quantity_received'] > 0) {
                        $transaction = array('order_id' => $orderId,
                        'quantity' => $orders['quantity_received'], 
                        'author' => 'Excel',
                        'notes' => "Import from Excel QTY Arrived Warehouse");
                        $this->m_inventory->save_inventory_transaction($transaction);
                    }
                    //shipment 1
                    if ($data[18] > 0) {
                        $shipment['notes'] = $data[19];
                        
                        $shipmentExist = 0;
                         //check if the shipment exists, if so try to find the shipmentid
                        if ($shipment['notes'] !== '' && (strpos($shipment['notes'], ':') !== FALSE ||strpos($shipment['notes'], '-') !== FALSE)) {  
                                                
                            $notes =  $this->_db->quote($shipment['notes']);                           
                            $checkId =  $this->_db->fetchRow("SELECT inventory_shipment_id FROM inventory_shipment WHERE notes = $notes"); 
                            if ($checkId) {
                                $shipmentId = $checkId['inventory_shipment_id'];                                
                                $shipmentExist = 1;                                
                            }                            
                        }
                        
                        if ($shipmentExist == 0) {
                            if ($data[19] != '') {
                               $dest = preg_split('/TO/', strtoupper($data[19]), -1, PREG_SPLIT_OFFSET_CAPTURE);                          
                                if (!empty($dest[0][0]) && !empty($dest[1][0])) {
                                    if (!empty($dest[0][0])) {
                                       $shipDate =  preg_split('/[\.]+/', $dest[0][0]);                     
                                       if (!empty($shipDate[0]) && !empty($shipDate[1]) && !empty($shipDate[2])) {
                                           $shipment['shipDate'] = $shipDate[0].'-'.$shipDate[1].'-'.preg_replace('/[A-Z_:;]/', '',trim($shipDate[2]));                                      
                                       }                    
                                    }
                                    if (!empty($dest[1][0])) {
                                        $location = preg_split('/[\s]+/', trim($dest[1][0]));                     
                                        if (!empty($location[0])) {                            
                                                $shipment['destination'] = $location[0];
                                                $shipment['shipvia'] = trim($location[1].' '.$location[2].' '.$location[3]);
                                        }
                                    }                                
                                }                  
                            }                        
                            $shipment['created_by'] = 'Excel';
                            $shipmentId = $this->m_inventory->save_shipment($shipment);
                        }  
                        
                        $shipmentItems = array('inventory_shipment_id' => $shipmentId,
                                        'itemNumber' => $data[2], 
                                        'item' => $data[3], 
                                        'order_id'=> $orderId, 
                                        'quantity' => $data[18]);
                        $this->m_inventory->save_shipment_items($shipmentItems);   
                        $transaction = array('order_id' => $orderId,
                        'quantity' => 0 - $data[18], 
                        'author' => 'Excel',
                        'notes' => "Import from Excel: Transfer to shipment $shipmentId");
                        $this->m_inventory->save_inventory_transaction($transaction);
                        
                    }
                    //shipment 2
                    if ($data[20] > 0) {
                        $shipment['notes'] = $data[21];
                        $shipmentExist = 0;
                         //check if the shipment exists, if so try to find the shipmentid
                        if ($shipment['notes'] !== '' && (strpos($shipment['notes'], ':') !== FALSE ||strpos($shipment['notes'], '-') !== FALSE)) {  
                                                
                            $notes =  $this->_db->quote($shipment['notes']);                           
                            $checkId =  $this->_db->fetchRow("SELECT inventory_shipment_id FROM inventory_shipment WHERE notes = $notes"); 
                            if ($checkId) {
                                $shipmentId = $checkId['inventory_shipment_id'];                                
                                $shipmentExist = 1;                                
                            }                            
                        }
                        
                        if ($shipmentExist == 0) {
                        
                        
                        if ($data[21] != '') {
                           $dest = preg_split('/TO/', strtoupper($data[21]), -1, PREG_SPLIT_OFFSET_CAPTURE);                          
                            if (!empty($dest[0][0]) && !empty($dest[1][0])) {
                                if (!empty($dest[0][0])) {
                                   $shipDate =  preg_split('/[\.]+/', $dest[0][0]);                     
                                   if (!empty($shipDate[0]) && !empty($shipDate[1]) && !empty($shipDate[2])) {
                                       $shipment['shipDate'] = $shipDate[0].'-'.$shipDate[1].'-'.preg_replace('/[A-Z_:;]/', '',trim($shipDate[2]));                                      
                                   }                    
                                }
                                if (!empty($dest[1][0])) {
                                    $location = preg_split('/[\s]+/', trim($dest[1][0]));                     
                                    if (!empty($location[0])) {                            
                                            $shipment['destination'] = $location[0];
                                            $shipment['shipvia'] = trim($location[1].' '.$location[2].' '.$location[3]);
                                    }
                                }                                
                            }                  
                        }
                        $shipment['created_by'] = 'Excel';
                        $shipmentId = $this->m_inventory->save_shipment($shipment);
                        }
                        
                        $shipmentItems = array('inventory_shipment_id' => $shipmentId,
                                        'itemNumber' => $data[2], 
                                        'item' => $data[3], 
                                        'order_id'=> $orderId, 
                                        'quantity' => $data[20]);
                        $this->m_inventory->save_shipment_items($shipmentItems); 
                         $transaction = array('order_id' => $orderId,
                        'quantity' => 0 - $data[20], 
                        'author' => 'Excel',
                        'notes' => "Import from Excel: Transfer to shipment $shipmentId");
                        $this->m_inventory->save_inventory_transaction($transaction);
                    }
                    //shipment 3
                    if ($data[22] > 0) {
                        $shipment['notes'] = $data[23];
                         $shipmentExist = 0;
                         //check if the shipment exists, if so try to find the shipmentid
                        if ($shipment['notes'] !== '' && (strpos($shipment['notes'], ':') !== FALSE ||strpos($shipment['notes'], '-') !== FALSE)) {  
                                                
                            $notes =  $this->_db->quote($shipment['notes']);                           
                            $checkId =  $this->_db->fetchRow("SELECT inventory_shipment_id FROM inventory_shipment WHERE notes = $notes"); 
                            if ($checkId) {
                                $shipmentId = $checkId['inventory_shipment_id'];                                
                                $shipmentExist = 1;                                
                            }                            
                        }
                        
                        if ($shipmentExist == 0) {
                        
                         if ($data[23] != '') {
                           $dest = preg_split('/TO/', strtoupper($data[23]), -1, PREG_SPLIT_OFFSET_CAPTURE);                          
                            if (!empty($dest[0][0]) && !empty($dest[1][0])) {
                                if (!empty($dest[0][0])) {
                                   $shipDate =  preg_split('/[\.]+/', $dest[0][0]);                     
                                   if (!empty($shipDate[0]) && !empty($shipDate[1]) && !empty($shipDate[2])) {
                                       $shipment['shipDate'] = $shipDate[0].'-'.$shipDate[1].'-'.preg_replace('/[A-Z_:;]/', '',trim($shipDate[2]));                                      
                                   }                    
                                }
                                if (!empty($dest[1][0])) {
                                    $location = preg_split('/[\s]+/', trim($dest[1][0]));                     
                                    if (!empty($location[0])) {                            
                                            $shipment['destination'] = $location[0];
                                            $shipment['shipvia'] = trim($location[1].' '.$location[2].' '.$location[3]);
                                    }
                                }                                
                            }                  
                        }
                        
                        
                        $shipmentId = $this->m_inventory->save_shipment($shipment);
                        }
                        $shipmentItems = array('inventory_shipment_id' => $shipmentId,
                                        'itemNumber' => $data[2], 
                                        'item' => $data[3], 
                                        'order_id'=> $orderId, 
                                        'quantity' => $data[22]);
                        $this->m_inventory->save_shipment_items($shipmentItems);       
                         $transaction = array('order_id' => $orderId,
                        'quantity' => 0 - $data[22], 
                        'author' => 'Excel',
                        'notes' => "Import from Excel: Transfer to shipment $shipmentId");
                        $this->m_inventory->save_inventory_transaction($transaction);
                    }
                    //shipment 4
                    if ($data[24] > 0) {
                        $shipment['notes'] = $data[25];
                          $shipmentExist = 0;
                         //check if the shipment exists, if so try to find the shipmentid
                        if ($shipment['notes'] !== '' && (strpos($shipment['notes'], ':') !== FALSE ||strpos($shipment['notes'], '-') !== FALSE)) {  
                                                
                            $notes =  $this->_db->quote($shipment['notes']);                           
                            $checkId =  $this->_db->fetchRow("SELECT inventory_shipment_id FROM inventory_shipment WHERE notes = $notes"); 
                            if ($checkId) {
                                $shipmentId = $checkId['inventory_shipment_id'];                                
                                $shipmentExist = 1;                                
                            }                            
                        }
                        
                        if ($shipmentExist == 0) {
                        
                         if ($data[25] != '') {
                           $dest = preg_split('/TO/', strtoupper($data[25]), -1, PREG_SPLIT_OFFSET_CAPTURE);                          
                            if (!empty($dest[0][0]) && !empty($dest[1][0])) {
                                if (!empty($dest[0][0])) {
                                   $shipDate =  preg_split('/[\.]+/', $dest[0][0]);                     
                                   if (!empty($shipDate[0]) && !empty($shipDate[1]) && !empty($shipDate[2])) {
                                       $shipment['shipDate'] = $shipDate[0].'-'.$shipDate[1].'-'.preg_replace('/[A-Z_:;]/', '',trim($shipDate[2]));                                      
                                   }                    
                                }
                                if (!empty($dest[1][0])) {
                                    $location = preg_split('/[\s]+/', trim($dest[1][0]));                     
                                    if (!empty($location[0])) {                            
                                            $shipment['destination'] = $location[0];
                                            $shipment['shipvia'] = trim($location[1].' '.$location[2].' '.$location[3]);
                                    }
                                }                                
                            }                  
                        }
                        $shipmentId = $this->m_inventory->save_shipment($shipment);
                        }
                        $shipmentItems = array('inventory_shipment_id' => $shipmentId,
                                        'itemNumber' => $data[2], 
                                        'item' => $data[3], 
                                        'order_id'=> $orderId, 
                                        'quantity' => $data[24]);
                        $this->m_inventory->save_shipment_items($shipmentItems);
                         $transaction = array('order_id' => $orderId,
                        'quantity' => 0 - $data[24], 
                        'author' => 'Excel',
                        'notes' => "Import from Excel: Transfer to shipment $shipmentId");
                        $this->m_inventory->save_inventory_transaction($transaction);
                    }
                    //shipment 5
                    if ($data[26] > 0) {
                        $shipment['notes'] = $data[27];
                          $shipmentExist = 0;
                         //check if the shipment exists, if so try to find the shipmentid
                        if ($shipment['notes'] !== '' && (strpos($shipment['notes'], ':') !== FALSE ||strpos($shipment['notes'], '-') !== FALSE)) {  
                                                
                            $notes =  $this->_db->quote($shipment['notes']);                           
                            $checkId =  $this->_db->fetchRow("SELECT inventory_shipment_id FROM inventory_shipment WHERE notes = $notes"); 
                            if ($checkId) {
                                $shipmentId = $checkId['inventory_shipment_id'];                                
                                $shipmentExist = 1;                                
                            }                            
                        }
                        
                        if ($shipmentExist == 0) {
                            
                        
                         if ($data[27] != '') {
                           $dest = preg_split('/TO/', strtoupper($data[27]), -1, PREG_SPLIT_OFFSET_CAPTURE);                          
                            if (!empty($dest[0][0]) && !empty($dest[1][0])) {
                                if (!empty($dest[0][0])) {
                                   $shipDate =  preg_split('/[\.]+/', $dest[0][0]);                     
                                   if (!empty($shipDate[0]) && !empty($shipDate[1]) && !empty($shipDate[2])) {
                                       $shipment['shipDate'] = $shipDate[0].'-'.$shipDate[1].'-'.preg_replace('/[A-Z_:;]/', '',trim($shipDate[2]));                                      
                                   }                    
                                }
                                if (!empty($dest[1][0])) {
                                    $location = preg_split('/[\s]+/', trim($dest[1][0]));                     
                                    if (!empty($location[0])) {                            
                                            $shipment['destination'] = $location[0];
                                            $shipment['shipvia'] = trim($location[1].' '.$location[2].' '.$location[3]);
                                    }
                                }                                
                            }                  
                        }
                        
                        $shipmentId = $this->m_inventory->save_shipment($shipment);
                        }
                        $shipmentItems = array('inventory_shipment_id' => $shipmentId,
                                        'itemNumber' => $data[2], 
                                        'item' => $data[3], 
                                        'order_id'=> $orderId, 
                                        'quantity' => $data[26]);
                        $this->m_inventory->save_shipment_items($shipmentItems);                        
                         $transaction = array('order_id' => $orderId,
                        'quantity' => 0 - $data[26], 
                        'author' => 'Excel',
                        'notes' => "Import from Excel: Transfer to shipment $shipmentId");
                        $this->m_inventory->save_inventory_transaction($transaction);
                    }
                    //shipment 6
                    if ($data[28] > 0) {
                        $shipment['notes'] = $data[29];
                          $shipmentExist = 0;
                         //check if the shipment exists, if so try to find the shipmentid
                        if ($shipment['notes'] !== '' && (strpos($shipment['notes'], ':') !== FALSE ||strpos($shipment['notes'], '-') !== FALSE)) {  
                                                
                            $notes =  $this->_db->quote($shipment['notes']);                           
                            $checkId =  $this->_db->fetchRow("SELECT inventory_shipment_id FROM inventory_shipment WHERE notes = $notes"); 
                            if ($checkId) {
                                $shipmentId = $checkId['inventory_shipment_id'];                                
                                $shipmentExist = 1;                                
                            }                            
                        }
                        
                        if ($shipmentExist == 0) {
                        
                         if ($data[29] != '') {
                           $dest = preg_split('/TO/', strtoupper($data[29]), -1, PREG_SPLIT_OFFSET_CAPTURE);                          
                            if (!empty($dest[0][0]) && !empty($dest[1][0])) {
                                if (!empty($dest[0][0])) {
                                   $shipDate =  preg_split('/[\.]+/', $dest[0][0]);                     
                                   if (!empty($shipDate[0]) && !empty($shipDate[1]) && !empty($shipDate[2])) {
                                       $shipment['shipDate'] = $shipDate[0].'-'.$shipDate[1].'-'.preg_replace('/[A-Z_:;]/', '',trim($shipDate[2]));                                      
                                   }                    
                                }
                                if (!empty($dest[1][0])) {
                                    $location = preg_split('/[\s]+/', trim($dest[1][0]));                     
                                    if (!empty($location[0])) {                            
                                            $shipment['destination'] = $location[0];
                                            $shipment['shipvia'] = trim($location[1].' '.$location[2].' '.$location[3]);
                                    }
                                }                                
                            }                  
                        }
                        
                        $shipmentId = $this->m_inventory->save_shipment($shipment);
                        }
                        $shipmentItems = array('inventory_shipment_id' => $shipmentId,
                                        'itemNumber' => $data[2], 
                                        'item' => $data[3], 
                                        'order_id'=> $orderId, 
                                        'quantity' => $data[27]);
                        $this->m_inventory->save_shipment_items($shipmentItems);   
                         $transaction = array('order_id' => $orderId,
                        'quantity' => 0 - $data[28], 
                        'author' => 'Excel',
                        'notes' => "Import from Excel: Transfer to shipment $shipmentId");
                        $this->m_inventory->save_inventory_transaction($transaction);
                    }
                   
                }
            }
            fclose($handle);
        }
         $this->_helper->layout()->disableLayout();     
         $this->_helper->viewRenderer->setNoRender(TRUE);
    }
}
