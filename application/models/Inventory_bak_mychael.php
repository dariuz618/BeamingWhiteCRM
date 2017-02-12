<?php

class Application_Model_Inventory {
    
    public function __construct()
    {
        $this->_db = Zend_Registry::get('db');
        $this->_auth = Zend_Auth::getInstance()->getIdentity();   
    }

    public function get_orders(){
                
      return $this->_db->fetchAll("SELECT inventory_order.*, user.firstname, group_concat(inventory_notes.notes) AS updatedNotes,
        usCbm, spainCbm
        FROM inventory_order 
        LEFT JOIN user on user.id = inventory_order.created_by  
        LEFT JOIN inventory_notes ON inventory_order.id = inventory_notes.id AND inventory_notes.type = 'order'
        LEFT JOIN (SELECT CASE WHEN usaQuantity > 0 AND spainQuantity > 0 THEN sum(volPerBox * numberOfBoxes) * (usaQuantity / quantity_ordered)
                ELSE SUM(volPerBox * numberOfBoxes) END usCbm,   
                iob.order_id AS order_id from inventory_order io left join inventory_order_boxes iob on iob.order_id = io.id  
                where io.quantity_oh_china > 0 and iob.order_id is not null and usaQuantity > 0 and iob.shipment_id is null group by order_id) a on a.order_id = inventory_order.id
        left join (select CASE WHEN usaQuantity > 0 AND spainQuantity > 0 THEN sum(volPerBox * numberOfBoxes) * (spainQuantity /quantity_ordered) 
                ELSE sum(volPerBox * numberOfBoxes) END spainCbm, 
                iob.order_id AS order_id from inventory_order io left join inventory_order_boxes iob on iob.order_id = io.id  
            where io.quantity_oh_china > 0 and iob.order_id is not null and spainQuantity > 0 and iob.shipment_id is null group by order_id) b on b.order_id = inventory_order.id
        GROUP BY inventory_order.id ORDER BY created_time DESC, item ASC");          
    }
    
    /*public function get_order($id) {
        return $this->_db->fetchRow("SELECT inventory_order.*, user.firstname, u2.firstname as modifiedBy           
            from inventory_order 
            left join user on user.id=inventory_order.created_by 
            left join user as u2 on u2.id = inventory_order.modified_by            
            WHERE inventory_order.id = '$id'");
    }*/
    public function get_order($id) {
        return $this->_db->fetchRow("SELECT * from inventory_order WHERE id = '$id'");
    }
    
    public function get_order_shipments($id) {
        return $this->_db->fetchAll("SELECT a.quantity, b.inventory_shipment_id, b.container, b.destination, b.shipDate from inventory_shipment_items a LEFT JOIN inventory_shipment b 
            ON a.inventory_shipment_id = b.inventory_shipment_id WHERE a.order_id = '$id'");
    }
    public function get_transactions($orderId) {
        return $this->_db->fetchAll("SELECT * from inventory_transaction WHERE order_id = '$orderId'");
    }
        
    public function get_inventory_notes($id, $type) {
        return $this->_db->fetchAll("SELECT * from inventory_notes WHERE type='$type' and id  = '$id'");        
    }
    
    public function get_inventory() {
        return $this->_db->fetchAll("SELECT inventory_order.itemNumber, inventory_order.item, sum(inventory_order.quantity_ordered) as quantity_ordered , sum(inventory_order.quantity_received)
            as quantity_received, sum(inventory_order.quantity_oh_china) as quantity_oh, usaQOH, spainQOH, sum(inventory_order.total_shipped ) as total_shipped
            FROM `inventory_order` LEFT JOIN inventory_allocation ON inventory_allocation.itemNumber = inventory_order.itemNumber
            GROUP BY itemNumber ORDER BY item");
    }
    //used when received quantity
    public function update_allocation($data= array()) {
        //first check if this item existing
        $check = $this->_db->fetchRow("SELECT * from inventory_allocation WHERE itemNumber = '{$data['itemNumber']}'");
        if($check) {
            //do update
            $allocation = array('order_id' => $data['order_id'],'QOH'=> ($data['quantity'] + $check['QOH'])); 
            $usaQuantity = ceil($data['quantity'] * ($data['usaQuantity']/$data['quantity_ordered']));
            $spainQuantity = $data['quantity'] -  $usaQuantity; 
            
            $allocation['usaQOH'] = $check['usaQOH'] + $usaQuantity ;
            $allocation['spainQOH'] = $check['spainQOH'] + $spainQuantity;
            //if this is the first receive of a new order, special case 
            if ($data['firstReceived'] == 1) {
                if( $check['usaQOH'] < 0 && $usaQuantity > 0 ) {                                       
                    $allocation['usaQOH'] = $usaQuantity;                   
                } 
                if( $check['spainQOH'] < 0 && $spainQuantity > 0) {                     
                      $allocation['spainQOH'] = $spainQuantity;                      
                }
            }
           
            $update = $this->_db->update('inventory_allocation', $allocation, $this->_db->quoteInto("id = ?", $check['id']));
            return $allocation; 
        } else { //insert
            $allocation = array('itemNumber'=>$data['itemNumber'], 'item'=>$data['item'], 'order_id' => $data['order_id'],
                'QOH'=>$data['quantity']);
            $allocation['usaQOH'] = ceil($data['quantity'] * ($data['usaQuantity']/$data['quantity_ordered']));
            $allocation['spainQOH'] = $data['quantity'] -  $allocation['usaQOH'];
            $this->_db->insert('inventory_allocation', $allocation);
            return $allocation;           
        }
    }
    //used when add to shipment
    public function update_shipment_allocation($itemNumber, $usaQuantity, $spainQuantity) {
        $allocation = $this->_db->fetchRow("SELECT * from inventory_allocation WHERE itemNumber = '$itemNumber'");
       
        if ($allocation) {            
            $data['QOH'] = $allocation['QOH'] - $usaQuantity - $spainQuantity;            
            //check if this allocation have anything in stock, if             
            $data['usaQOH'] = $allocation['usaQOH'] - $usaQuantity;
            $data['spainQOH'] = $allocation['spainQOH'] - $spainQuantity;                  
            
            //$data['usaQOH'] = $allocation['usaQOH'] - $usaQuantity - $spainQuantity;
            //$data['spainQOH'] = $allocation['spainQOH'] - $spainQuantity - $usaQuantity;
            
            if ($this->_db->update('inventory_allocation', $data, $this->_db->quoteInto("itemNumber = ?", $itemNumber))){
                return $data;
            }
            return FALSE;            
        }
    }
    
    public function get_items() {
        return $this->_db->fetchAll("SELECT * FROM raw_material order by name");
    }
    
    public function get_stock_by_item($item) {        
        return $this->_db->fetchRow("SELECT * from inventory_order WHERE item = {$this->_db->quote($item)} AND quantity_oh_china > 0 order by created_time limit 1");        
    }
    
    public function get_stock_by_itemNumber($itemNumber) {       
               
        return $this->_db->fetchRow("SELECT inventory_order.itemNumber, inventory_order.item, sum(inventory_order.quantity_ordered) as quantity_ordered ,
            sum(inventory_order.quantity_received) as quantity_received, sum(inventory_order.quantity_oh_china ) as quantity_oh , sum( total_shipped ) as total_shipped,
            usaQOH, spainQOH FROM `inventory_order` 
            LEFT JOIN inventory_allocation ON inventory_allocation.itemNumber = inventory_order.itemNumber
            WHERE inventory_order.itemNumber = '$itemNumber'");            
    }
    public function get_boxes($orderId,$qty ) {
        
    }
    public function get_order_items() {
        return $this->_db->fetchAll("SELECT * FROM inventory_order GROUP BY itemNumber ORDER BY item");
    }
    
    public function get_orders_by_item($itemNumber) {
        return $this->_db->fetchAll("SELECT * from inventory_order WHERE itemNumber = '$itemNumber' order by created_time");        
    }
    
    public function balance_order($orderId) {
        $currentOrder = $this->get_order($orderId);
        $outOrder = $this->get_stock_by_item($currentOrder['item']);
        if($currentOrder['quantity_oh_china'] + $outOrder['quantity_oh_china'] >=0) {
            //update current order
            $currentOrderUpdate['quantity_oh_china'] = 0;
            $currentOrderUpdate['quantity_received'] = $currentOrder['quantity_received'] + abs($currentOrder['quantity_oh_china']);
            $this->update_orders($currentOrderUpdate, $orderId);
            $transactionIn = array('order_id' => $orderId, 'quantity' => abs($currentOrder['quantity_oh_china']), 'author'=>'Inventory Shipping System', 'notes'=> "Create shipment adjustment - transfer from order {$outOrder['id']}");
            $this->save_inventory_transaction($transactionIn);
            //update transfer out order
            $outOrderUpdate['quantity_oh_china'] = $outOrder['quantity_oh_china'] + $currentOrder['quantity_oh_china'];
            $outOrderUpdate['total_shipped'] = $outOrder['total_shipped'] + abs($currentOrder['quantity_oh_china']);
            $this->update_orders($outOrderUpdate, $outOrder['id']);
            $transactionOut = array('order_id' => $outOrder['id'], 'quantity'=> $currentOrder['quantity_oh_china'],'author'=>'Inventory Shipping System', 'notes'=> "Create shipment adjustment - transfer to order {$currentOrder['id']}");
            $this->save_inventory_transaction($transactionOut);
        } else {
           $currentOrderUpdate['quantity_oh_china'] =  $currentOrder['quantity_oh_china'] + $outOrder['quantity_oh_china'];
           $currentOrderUpdate['quantity_received'] = $currentOrderUpdate['quantity_received'] + $outOrder['quantity_oh_china'];
           $this->update_orders($currentOrderUpdate, $orderId);
           $transactionIn = array('order_id' => $orderId, 'quantity' =>  $outOrder['quantity_oh_china'], 'author'=>'Inventory Shipping System','notes'=> "Create shipment adjustment - transfer from {$outOrder['id']}");
           $this->save_inventory_transaction($transactionIn);
           
           //update transfer out order
           $outOrderUpdate['quantity_oh_china'] = 0;
           $outOrderUpdate['total_shipped'] = $outOrder['total_shipped'] + $outOrder['quantity_oh_china'];
           $this->update_orders($outOrderUpdate, $outOrder['id']);
           $transactionOut = array('order_id' => $outOrder['id'],  'quantity'=> 0-$outOrder['quantity_oh_china'], 'author'=>'Inventory Shipping System','notes'=> "Create shipment adjustment - transfer to {$currentOrder['id']}");
           $this->save_inventory_transaction($transactionOut);           
           
           self::balance_order($orderId);
        }
        return;
    }
    
    public function adjust_order_quantity($orderId, $quantity, $reason)
    {
          $currentOrder = $this->get_order($orderId);          
          $currentOrderUpdate['quantity_received'] = $currentOrder['quantity_received'] + $quantity;
          $currentOrderUpdate['quantity_oh_china'] = $currentOrder['quantity_oh_china'] + $quantity;
          $this->update_orders($currentOrderUpdate, $orderId);
          $data = array('order_id' => $orderId,
                    'quantity' => $quantity,
                    'author' => $this->_auth->firstname. ' '.$this->_auth->lastname,
                    'notes' => 'Manual adjustment: '.$reason);
          $this->save_inventory_transaction($data);
          return true;
    }
    
    public function save_inventory_transaction($data) {
        try {          
            $this->_db->insert('inventory_transaction', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            echo $e->getMessage();
        }
        return;
    }
         
    public function get_oh_by_item($item) {              
        $result = $this->_db->fetchRow("SELECT sum(quantity_oh_china) as totalOnHand from inventory_order WHERE item = {$this->_db->quote($item)} AND quantity_oh_china > 0");
        return $result?$result['totalOnHand']:0;
    }
    
        
    public function update_orders($data, $id) {        
        try {              
            $update = $this->_db->update('inventory_order', $data, $this->_db->quoteInto("id = ?", $id));
            //var_dump($update);
            return $update;                       
        } catch (Exception $e) {         
           // var_dump ($e->getMessage());
            //die();
            return $e->getMessage();
        }
    }
    public function save_inventory_notes($data){           
        try {          
            $this->_db->insert('inventory_notes', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    }
    
    public function log($data) {
        try {          
            $this->_db->insert('inventory_action_log', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    }
    public function get_log($id, $type) {
       return $this->_db->fetchAll("SELECT * from  inventory_action_log WHERE id  = '$id' AND type = '$type' ORDER BY action_time");   
    }
    
    public function save_orders($data){           
        try {          
            $this->_db->insert('inventory_order', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    } 
    public function save_shipment($data){           
        try {          
            $this->_db->insert('inventory_shipment', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    } 
    public function save_shipment_items($data){       
        try {          
            //till we come out with a better solution, get the itemnumber from inventory_order table (just in case the name can change and it'll won't found again)               
            $itemNumber = $this->_db->fetchRow("SELECT itemNumber from inventory_order WHERE item = {$this->_db->quote($data['item'])}");
            if ($itemNumber) {
                $data['itemNumber'] = $itemNumber['itemNumber'];
            }            
            $keys = array('qtyPerBox', 'numberOfBoxes','quantity');
            for ($i = 0; $i < sizeof($keys); ++$i) {
                if((int)$data[$keys[$i]] == 0) {
                    $data[$keys[$i]] = NULL;
                }
            }
         
            $this->_db->insert('inventory_shipment_items', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    } 
    
    public function save_order_boxes($data) {
        try {          
            $this->_db->insert('inventory_order_boxes', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    }
    public function update_order_boxes($data, $boxId) {
        try {          
            $update = $this->_db->update('inventory_order_boxes', $data, $this->_db->quoteInto("box_id = ?", $boxId));           
            return $update;
        } catch (Exception $e) {           
            return $e->getMessage();
        }        
    }
    public function remove_order_box($boxId) {
        try {
            $delete = $this->_db->delete('inventory_order_boxes', $this->_db->quoteInto("box_id = ?", $boxId));
            //update invenetory order quantity        
            return $delete;             
        } catch (Exception $e) {           
            return $e->getMessage();
        }     
    }
    
    public function update_shipment($data, $shipmentId) {        
        try {              
            $update = $this->_db->update('inventory_shipment', $data, $this->_db->quoteInto("inventory_shipment_id = ?", $shipmentId));
            //var_dump($update);
            return $update;                       
        } catch (Exception $e) {         
            
            return $e->getMessage();
        }
    }
    
    public function update_shipment_item($data, $shipment_items_id) {        
        try {              
            $update = $this->_db->update('inventory_shipment_items', $data, $this->_db->quoteInto("shipment_items_id = ?", $shipment_items_id));                       
            return $update;                       
        } catch (Exception $e) {        
            
            return $e->getMessage();
        }
    }
    public function delete_shipment_item($shipment_items_id) {    
        //first update the inventory order
        $order = $this->_db->fetchRow("SELECT inventory_order.*, inventory_shipment_items.quantity, inventory_shipment_items.inventory_shipment_id, inventory_shipment.shipTo
            FROM inventory_shipment_items left join inventory_order on inventory_shipment_items.order_id = inventory_order.id
            left join inventory_shipment on inventory_shipment.inventory_shipment_id = inventory_shipment_items.inventory_shipment_id
            WHERE shipment_items_id ='$shipment_items_id'");
        if ($order) {
             $orderUpdate['quantity_oh_china'] = $order['quantity_oh_china'] + $order['quantity'];
             $orderUpdate['total_shipped'] = $order['total_shipped'] - $order['quantity'];
             self::update_orders($orderUpdate, $order['id']);  
             
              //update allocation quantity           
            $usaQuantity = $spainQuantity = 0;
            if ($order['shipTo'] == 2) {
                $usaQuantity = 0 - $order['quantity'];
            } else {
                $spainQuantity = 0 - $order['quantity'];
            }
            self::update_shipment_allocation($order['itemNumber'], $usaQuantity, $spainQuantity);             
        }
        //log it in the transaction table
         $transaction = array('order_id' => $order['id'],
                    'quantity' => $order['quantity'],
                    'author' => $this->_auth->firstname . ' ' . $this->_auth->lastname,
                    'notes' => "Remove shipment item: transfer out from shipement {$order['inventory_shipment_id']}");
         $this->save_inventory_transaction($transaction);
        
        //first check if this an orphan item
       /* $items = $this->_db->query("SELECT count(shipment_items_id) AS totalShipmentItems
                    FROM inventory_shipment_items WHERE inventory_shipment_id
                    IN (
                        SELECT inventory_shipment_id
                        FROM inventory_shipment_items
                        WHERE shipment_items_id = '$shipment_items_id'
                    )");*/
        
        $delete = $this->_db->delete('inventory_shipment_items', $this->_db->quoteInto("shipment_items_id = ?", $shipment_items_id));
        //update invenetory order quantity
        
        return $delete;  
    }
    
    /*public function get_order_shipments($id) {
       return $this->_db->fetchAll("SELECT * from inventory_shipment WHERE order_id  = '$id'");  
    }*/
    public function get_company_by_id($id) {
       return $this->_db->fetchRow("SELECT * from company WHERE id  = '$id'");  
    }
    public function get_shipments() {
        return $this->_db->fetchAll("SELECT * from inventory_shipment order by shipDate, inventory_shipment_id DESC");   
    }
    public function get_shipment($shipmentId) {
       return $this->_db->fetchRow("SELECT * from inventory_shipment WHERE inventory_shipment_id  = '$shipmentId'");   
    }
   
    public function get_shipment_items($shipmentId) {
        return $this->_db->fetchAll("select a.*,inventory_shipment_items.*  from 
            (select itemNumber, sum(quantity_oh_china) as totalOnHand 
            from inventory_order where itemNumber in (SELECT inventory_shipment_items.itemNumber from inventory_shipment_items             
            WHERE inventory_shipment_id  = '$shipmentId' group by itemNumber) group by itemNumber) a 
            left join inventory_shipment_items on inventory_shipment_items.itemNumber = a.itemNumber 
            WHERE inventory_shipment_items.inventory_shipment_id  = '$shipmentId' ");    
    }
    
    public function get_packing_items($shipmentId) {
        return $this->_db->fetchAll("SELECT isi.* , 
            CASE WHEN isi.box_id is NOT NULL THEN iob.volPerBox
                ELSE isi.volPerBox END volPerBox,
            CASE WHEN isi.box_id is NOT NULL THEN iob.weightPerBox
                ELSE isi.weightPerBox END weightPerBox
            from inventory_shipment_items isi left join inventory_order_boxes iob on isi.box_id = iob.box_id
            WHERE inventory_shipment_id  = '$shipmentId' order by isi.box_id, isi.item");    
    }
    
    public function get_box($boxId) {
        return $this->_db->fetchRow("SELECT group_concat( isi.shipment_items_id ) AS shipmentItem, sum(isi.quantity) as quantity, count(isi.shipment_items_id) as totalItems, 
            iob.volPerBox, iob.weightPerBox FROM `inventory_shipment_items` isi left join inventory_order_boxes iob on isi.box_id = iob.box_id 
            WHERE isi.box_id = '$boxId'");
    }
    
    public function get_shipment_invoice_items($shipmentId) {
       return $this->_db->fetchAll("SELECT * from inventory_shipment_items isi left join raw_material r 
           on isi.itemNumber = r.itemNumber WHERE isi.inventory_shipment_id  = '$shipmentId'                        
                        order by r.invoice_name");    
    }    
    public function get_item_shipments($itemNumber) {
       return $this->_db->fetchAll("SELECT a.quantity, a.order_id, b.inventory_shipment_id, b.destination, b.shipDate, b.shipvia
           from inventory_shipment_items a LEFT JOIN inventory_shipment b 
           ON a.inventory_shipment_id = b.inventory_shipment_id WHERE a.itemNumber = '$itemNumber' order by shipDate");
    }
    public function get_item_transactions($itemNumber) {
        return $this->_db->fetchAll("SELECT order_id, quantity, author,it.notes, transaction_time from inventory_order io left join inventory_transaction it on io.id = it.order_id 
            WHERE io.itemNumber = '$itemNumber' order by transaction_time");
    }
    public function get_shipmentItemById ($shipmentItemsId) {
       return $this->_db->fetchRow("SELECT * from inventory_shipment_items WHERE shipment_items_id = '$shipmentItemsId'");
    }
    public function get_boxes_by_item($itemNumber) {
       return $this->_db->fetchAll("SELECT a.*, b.quantity_oh_china from (SELECT inventory_order_boxes.*,inventory_order_boxes.qtyPerBox as value, inventory_order.itemNumber
            FROM inventory_order_boxes LEFT JOIN inventory_order ON inventory_order.id = inventory_order_boxes.order_id
            WHERE inventory_order.itemNumber = '$itemNumber' AND inventory_order.quantity_oh_china > 0
            AND inventory_order_boxes.shipment_id IS NULL
            ORDER BY inventory_order_boxes.enter_time DESC) a 
            LEFT JOIN (SELECT itemNumber, sum(quantity_oh_china) as quantity_oh_china FROM
            inventory_order WHERE inventory_order.itemNumber = '$itemNumber') b on a.itemNumber = b.itemNumber");
    }
    public function get_order_boxes($order_id) {
       return $this->_db->fetchAll("SELECT inventory_order_boxes.*, inventory_order.quantity_received from 
           inventory_order_boxes left join inventory_order on inventory_order.id = inventory_order_boxes.order_id
           WHERE order_id  = '$order_id' order by box_id");    
    }
    public function get_items_boxes($shipment_id) {
        return $this->_db->fetchAll("SELECT group_concat(isi.shipment_items_id) as shipment_items_id, iob.volPerBox, iob.weightPerBox, iob.box_id FROM `inventory_shipment_items` isi left join inventory_order_boxes iob on isi.box_id = iob.box_id 
            WHERE isi.inventory_shipment_id = '$shipment_id' and isi.box_id is not null group by isi.box_id");
    }    
    public function empty_box_items($boxId) {
       return $this->_db->query("UPDATE inventory_shipment_items SET box_id = NULL WHERE box_id = '$boxId'");
    }
    
    public function total_quantity_in_boxes($orderId) {
        $result = $this->_db->fetchRow("SELECT sum(quantity) as totalQuantity from inventory_order_boxes WHERE order_id = '$orderId' ");
        return $result?(int)$result['totalQuantity']:0;
    }
    
    public function shipment_total($id) {
       $result = $this->_db->fetchRow("SELECT sum(quantity) as totalShip from inventory_shipment_items WHERE order_id = '$id' ");
       return $result?(int)$result['totalShip']:0;
    }
    
    public function get_stock_item($queryString) {
       $result = $this->_db->fetchAll("SELECT itemNumber, item, sum(quantity_oh_china) as totalOnHand FROM inventory_order WHERE quantity_oh_china > 0 and item like '$queryString%' 
           group by item ORDER BY item");
       return $result;
    }
    public function get_stock_item_suggestion($queryString) {
       if ($queryString == 'all') $queryString = '';
       $result = $this->_db->fetchAll("SELECT itemNumber, item, sum(quantity_oh_china) as totalOnHand FROM inventory_order 
           WHERE quantity_oh_china > 0 and (item like '%$queryString%' or itemNumber like '%$queryString%')
           group by item ORDER BY item");
       return $result;
    }
    
    public function get_companies() {
        return $this->_db->fetchAll("SELECT * FROM company");
    }
    public function get_contacts() {
        return $this->_db->fetchAll("(select attn from company where attn !='') union (select attn1 from company where attn1 !='') order by attn");
    }
    public function get_buyers() {
         return array('Adrian T', 'Lucky S', 'Luis L', 'Tom T');
    }
    public function get_orderBys() {
         return array('Jordi F', 'Loli R', 'Luis L', 'Tom T');
    }
    
    public function save_company($data) {
        try {          
            $this->_db->insert('company', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    }
    public function update_company($data, $id) {
        try {              
            $update = $this->_db->update('company', $data, $this->_db->quoteInto("id = ?", $id));            
            return $update;                       
        } catch (Exception $e) {                    
            return $e->getMessage();
        }
    }
    public function delete_company($id) {
         try {              
            $delete = $this->_db->query("Delete from company WHERE id = '$id'");            
            return $delete;                       
        } catch (Exception $e) {                    
            return $e->getMessage();
        }
    }
    public function get_file_name($orderId) {
        $urls = $this->_db->query("SELECT url from inventory_order_files WHERE order_id = '$orderId'");       
        $filename = array();
        foreach ($urls as $url) {
            $temp = pathinfo($url['url']);
            $filename[] = $temp['filename'];
        }
        if (empty($filename)) return 1;    
        return max($filename) + 1;       
    }
   public function getFileById($id) {
        return $this->_db->fetchRow("SELECT * from inventory_order_files WHERE file_id = '$id' ");
   }
   public function deleteFile($id) {
        $delete = $this->_db->delete('inventory_order_files', $this->_db->quoteInto("file_id = ?", $id));        
        return $delete;  
   }
   public function getOrderFiles($id) {
       return $this->_db->fetchAll("SELECT * from inventory_order_files WHERE order_id = '$id'");
   }
   public function save_inventory_file($data) {
        try {          
            $this->_db->insert('inventory_order_files', $data);
            return (int)$this->_db->lastInsertId();                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;  
    }
}
