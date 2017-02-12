<?php

class Application_Model_Item {
    
    public function __construct()
    {
        $this->_db = Zend_Registry::get('db');
        $this->_auth = Zend_Auth::getInstance()->getIdentity();   
    }
        
    public function raw_material($sort, $order) {
        return $this->_db->fetchAll("SELECT * FROM raw_material ORDER BY $sort $order");
    }
    public function save_raw_material($data) {
        try {          
            $this->_db->insert('raw_material', $data);
            return (int)$this->_db->lastInsertId();                
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;
    }
    public function update_raw_material($data, $id) {
        try {              
            $update = $this->_db->update('raw_material', $data, $this->_db->quoteInto("id = ?", $id));            
            return $update;                       
        } catch (Exception $e) {                    
            return $e->getMessage();
        }
    }
    public function delete_raw_material($id) {
         try {              
            $delete = $this->_db->query("Delete from raw_material WHERE id = '$id'");            
            return $delete;                       
        } catch (Exception $e) {                    
            return $e->getMessage();
        }
    }
    
}