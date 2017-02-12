<?php

class Application_Model_Fm {
    # Object level variables

    protected $_db;

    /**
     * Class constructor - Setup the DB connection
     */
    public function __construct() {
        # get handle on our database object
        $this->_db = Zend_Registry::get('db');
    }

    public function saveSharedFiles($data) {
        try {
            
            $insert =  $this->_db->insert('shared_files', $data);            
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;   
    }
    public function saveSalesFile($data) {
        try {            
            $insert =  $this->_db->insert('sales_files', $data);            
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;   
    }
    
    public function saveCategory($data) {
        try {              
            $insert =  $this->_db->insert('category', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;   
    }
    
    public function updateCategory($data, $id) {
        $update = $this->_db->update('category', $data, $this->_db->quoteInto("category_id = ?", $id));        
        return $update;  
    }
    
    public function removeCategory($data, $id) {
        self::updateCategory($data, $id); 
        //remove subcategories.
        $category = array ('status' => 0, 'date_modified' => date("Y-m-d H:i:s") ); 
        $this->_db->update('category', $category, $this->_db->quoteInto("parent_id = ?", $id));        
        return true;        
    }
    
     
    public function updateSharedFiles($data, $id) {        
        $update = $this->_db->update('shared_files', $data, $this->_db->quoteInto("id = ?", $id));
        return $update;
    }
        
    public function getFileByName($fileName) {       
       $fileName = $this->_db->quote($fileName);      
       return $this->_db->fetchRow("SELECT * from shared_files WHERE title = $fileName ORDER BY timestamp DESC LIMIT 1 ");
    }
    
    public function getFileByUrl($url) {
        return $this->_db->fetchRow("SELECT * from shared_files WHERE url = '$url'");
    }
    
    public function getFileById($id) {
        return $this->_db->fetchRow("SELECT * from shared_files WHERE id = '$id' ");
    }
    
    public function getSalesFileById($id) {
        return $this->_db->fetchRow("SELECT * from sales_files WHERE id = '$id' ");
    }   
    
    public function getFileByToken($token) {
        return $this->_db->fetchRow("SELECT * from shared_files WHERE public_token = '$token'");
    }
     public function getSalesFileByToken($token) {
        return $this->_db->fetchRow("SELECT * from sales_files WHERE public_token = '$token'");
    }
    
    public function deleteFile($id) {
        $update = $this->_db->delete('shared_files', $this->_db->quoteInto("id = ?", $id));        
        return $update;  
    }
     public function deleteSalesFile($id) {
        $update = $this->_db->delete('sales_files', $this->_db->quoteInto("id = ?", $id));        
        return $update;  
    }
    
    public function getCategory() {        
        $parents = $this->_db->fetchAll("SELECT category_id, name FROM `category` where status = 1 AND parent_id = 0 ORDER BY name");
        foreach ($parents as $key => $parent) {
            $children = $this->_db->fetchAll("SELECT category_id, name FROM `category` where status = 1 AND parent_id = '{$parent['category_id']}'");
            if ($children) {
                $parents[$key]['children'] = $children;
            }
        }
        return $parents;        
    }
    
    public function getCategoryById($id) {
        return $this->_db->fetchRow("SELECT * from category WHERE category_id = '$id' ");
    }
    
    public function getFiles($id) {
        $results = $this->_db->fetchAll("SELECT shared_files.*, category.name FROM shared_files LEFT JOIN category on shared_files.category_id = category.category_id
            WHERE public_allow = 0 AND (shared_files.category_id = '$id' OR category.category_id in (SELECT category_id from category WHERE parent_id = '$id' AND category.status = 1)) 
            ORDER BY shared_files.timestamp DESC");
        return $results;            
    }
    public function getSalesFilesCategory() {
       /* $parents = $this->_db->fetchAll("SELECT category_id, name FROM `sales_category` where status = 1 AND parent_id = 0 ORDER BY name");
        foreach ($parents as $key => $parent) {
            $children = $this->_db->fetchAll("SELECT category_id, name FROM `sales_category` where status = 1 AND parent_id = '{$parent['category_id']}'");
            if ($children) {
                $parents[$key]['children'] = $children;
            }
        }
        return $parents;*/
        $categories = $this->_db->fetchAll("SELECT distinct category as name from sales_files order by category");
        return $categories;
    }
    public function getSalesFilesTypes() {
        return array('jpg' => 'jpg',            
            'gif' => 'gif', 'png' => 'png', 'pdf' => 'pdf', 'mp3' => 'mp3',
            'mp4' => 'mp4');
    }
    
    public function getSalesFiles($category) {
       /* $results = $this->_db->fetchAll("SELECT sales_files.*, sales_category.name FROM sales_files LEFT JOIN sales_category on sales_files.category_id = sales_category.category_id
            WHERE public_allow = 0 AND (sales_files.category_id = '$id' OR sales_category.category_id in (SELECT category_id from sales_category WHERE parent_id = '$id' AND sales_category.status = 1)) 
            ORDER BY sales_files.timestamp DESC");*/
        $filter = '';
        if ($category) $filter = "where category = '$category' ";
        return $this->_db->fetchAll("SELECT *, category as name from sales_files $filter order by timestamp, category");
    }
    public function getSalesFileRequests() {
        return $this->_db->fetchAll("SELECT * from sales_file_request");
    }
    
    public function saveSalesFileRequest($data) {
        try {              
            $insert =  $this->_db->insert('sales_file_request', $data);
            return $insert;                       
        } catch (Exception $e) {           
            return $e->getMessage();
        }
        return;   
    }
    public function updateSalesFileStatus($data, $id) {
        $update = $this->_db->update('sales_file_request', $data, $this->_db->quoteInto("request_id = ?", $id));        
        return $update;  
    }   
    
    public function getToken($id)
    {
       $file = $this->getFileById($id);
       if($file) {
           //var_dump($file['public_token']);
           if($file['public_token'] != '') {
               return $file['public_token'];
           } else {
               $token = $this->generateToken(32);
               $this->updateSharedFiles(array('public_token' => $token), $id);
               return $token;
           }
       }
    }
    
    public function generateToken($length = 10)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $rnd_result = '';
        for ($i = 0; $i < $length; $i++) {
            $rnd_result .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $rnd_result;
    }
    
}