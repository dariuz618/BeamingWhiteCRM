<?php

class Application_Model_Report {
    # Object level variables

    protected $_db;

    /**
     * Class constructor - Setup the DB connection
     */
    public function __construct() {
        # get handle on our database object
        $this->_db = Zend_Registry::get('db');
        $this->_crm = Zend_Registry::get('bwbusiness');
    }
    
    public function getShippedRedemption() {
        $result = $this->_db->fetchAll("SELECT site,year(date_shipped) as year, month(date_shipped) as month, count(id) as total
            FROM deal_customers where date_shipped > DATE_SUB(CURDATE(), INTERVAL 24 MONTH) 
            GROUP BY YEAR(date_shipped), MONTH(date_shipped), site");
        
        foreach ($result as $key => $data) {
            $return[$data['year'].$data['month']]['month'] = $data['year'].'-'.$data['month'];
            $return[$data['year'].$data['month']][$data['site']] = $data['total'];           
        }
        foreach ($return as $data) {
            $array[] = $data;
        }
               
        return $array;
        
    }
    public function getContacts() {
        return $this->_crm->fetchAll("SELECT type as label, count(id) as value, 
            (select count(id) from user where  type != 'Internal' and status != 'Disabled' ) as total 
            from user where type != 'Internal' and status != 'Disabled' group by type order by type");
    }
    public function getStoreTotal() {
        $result = $this->_db->fetchAll("SELECT site, year( date_shipped ) AS year, 
                  quarter( date_shipped ) AS quarter , sum( total ) AS total FROM store_orders 
                  WHERE date_shipped > DATE_SUB( CURDATE( ) , INTERVAL 36
                  MONTH ) GROUP BY YEAR( date_shipped ) , quarter( date_shipped ) , site");        
        
        foreach ($result as $key => $data) {
            $return[$data['year'].$data['quarter']]['quarter'] = $data['year'].' Q'.$data['quarter'];
            $return[$data['year'].$data['quarter']][$data['site']] = $data['total'];
            
        }
        foreach ($return as $key => $data) {            
            $temp = $data;
            unset($temp['quarter']);
            $data['total'] = array_sum($temp);            
            $array[] = $data;            
        }       
                    
        return $array;
    }
    
}