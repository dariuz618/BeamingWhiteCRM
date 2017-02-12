<?php

class ImportController extends Zend_Controller_Action {
                
    public function dbAction() {
        require_once('/home/bwbiz/etc/dbconfig.php');
        
        
         //divine white
        $dvConnect = mysql_connect(DV_HOSTNAME, DV_USERNAME, DV_PASSWORD) or die(mysql_error());
        mysql_select_db('divinty_rdm') or die(mysql_error());
        $dvCustomer = mysql_query("SELECT 'dvw' as site, date_shipped, description as product FROM customer
               left join deal_code on deal_code.deal_id = customer.deal_id 
               left join product on product.product_id = deal_code.product_id
               where date_shipped >= '2013-01-01' and shipped = 1 and customer.email not like '%beamingwhite%'
               AND (country = 'USA' or country = 'CANADA') order by date_shipped
               ");
        $dvLocalConnect = mysql_connect(BWMX_HOSTNAME, BWMX_USERNAME, BWMX_PASSWORD) or die(mysql_error());
        mysql_select_db('bwerp') or die(mysql_error());        
        mysql_query("TRUNCATE table deal_customers");
        while ($dvRow = mysql_fetch_assoc($dvCustomer)) {            
            $dvQuery = "INSERT INTO deal_customers (site, date_shipped, product) VALUES ('{$dvRow['site']}', '{$dvRow['date_shipped']}', '{$dvRow['product']}')";         
           // echo $query;
            mysql_query($dvQuery);
        }
        mysql_close($dvConnect);        
        mysql_close($dvLocalConnect);
        echo "Imported DVW at ".  date('m/d/Y h:i:s a', time()).'\n\r';
        
          
         //glamwhite
        $glwConnect = mysql_connect(GLW_HOSTNAME, GLW_USERNAME, GLW_PASSWORD) or die(mysql_error());
        mysql_select_db('glamjam_redeem') or die(mysql_error());
        $glwCustomer = mysql_query("SELECT 'glw' as site, date_shipped, description as product FROM customer
               left join deal_code on deal_code.deal_id = customer.deal_id 
               left join product on product.product_id = deal_code.product_id
               where date_shipped >= '2013-01-01' and shipped = 1 and customer.code not like '%test%' and email not like '%beamingwhite%' 
               AND (country = 'USA' or country = 'CANADA') order by date_shipped
               ");
        $glwLocalConnect = mysql_connect(BWMX_HOSTNAME, BWMX_USERNAME, BWMX_PASSWORD) or die(mysql_error());
        mysql_select_db('bwerp') or die(mysql_error());        
        while ($glwRow = mysql_fetch_assoc($glwCustomer)) {            
            $glwQuery = "INSERT INTO deal_customers (site, date_shipped, product) VALUES ('{$glwRow['site']}', '{$glwRow['date_shipped']}', '{$glwRow['product']}')";         
           // echo $query;
            mysql_query($glwQuery);
        }
        mysql_close($glwConnect);        
        mysql_close($glwLocalConnect);
        echo "Imported GLW at ".  date('m/d/Y h:i:s a', time()).'\n\r';
        
            //smile clinic
        $tscConnect = mysql_connect(TSC_HOSTNAME, TSC_USERNAME, TSC_PASSWORD) or die(mysql_error());
        mysql_select_db('grinner_rdm') or die(mysql_error());
        $tscCustomer = mysql_query("SELECT 'tsc' as site, date_shipped, description as product FROM customer
               left join deal_code on deal_code.deal_id = customer.deal_id 
               left join product on product.product_id = deal_code.product_id
               where date_shipped >= '2013-01-01' and shipped = 1 AND (country = 'USA' or country = 'CANADA') and email not like '%beamingwhite%' order by date_shipped
               ");
        $tscLocalConnect = mysql_connect(BWMX_HOSTNAME, BWMX_USERNAME, BWMX_PASSWORD) or die(mysql_error());
        mysql_select_db('bwerp') or die(mysql_error());        
        while ($tscRow = mysql_fetch_assoc($tscCustomer)) {            
            $tscQuery = "INSERT INTO deal_customers (site, date_shipped, product) VALUES ('{$tscRow['site']}', '{$tscRow['date_shipped']}', '{$tscRow['product']}')";         
           // echo $query;
            mysql_query($tscQuery);
        }
        mysql_close($tscConnect);        
        mysql_close($tscLocalConnect);
        echo "Imported TSC at ".  date('m/d/Y h:i:s a', time()).'\n\r';
        
        
        $connect = mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD) or die(mysql_error());
        mysql_select_db('whitesmi_rdm') or die(mysql_error());        
        $customer = mysql_query("SELECT 'wsc' as site, date_shipped, description as product FROM customer
               left join deal_code on deal_code.deal_id = customer.deal_id 
               left join product on product.product_id = deal_code.product_id
               where date_shipped >= '2013-01-01' and shipped = 1 AND (country = 'USA' or country = 'CANADA')
               and email not like '%beamingwhite%' order by date_shipped
               ");
        $localConnect = mysql_connect(BWMX_HOSTNAME, BWMX_USERNAME, BWMX_PASSWORD) or die(mysql_error());
        mysql_select_db('bwerp') or die(mysql_error());        
              
        while ($row = mysql_fetch_assoc($customer)) {            
            $query = "INSERT INTO deal_customers (site, date_shipped, product) VALUES ('{$row['site']}', '{$row['date_shipped']}', '{$row['product']}')";         
            //echo $query;
             mysql_query($query);
        }
        mysql_close($connect);
        mysql_close($localConnect);
        echo "Imported WSC at ".  date('m/d/Y h:i:s a', time()).'\n\r';              
                
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);        
    }
    
    public function storeAction() {
        require_once('/home/bwbiz/etc/dbconfig.php');
        
        $connect = mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD) or die(mysql_error());
        mysql_select_db('whitesmi_mart') or die(mysql_error());        
        
        $orders = mysql_query("SELECT 'wsc' as site, order.order_id, date_modified as date_shipped, value as total 
            FROM `order` left join order_total on order_total.order_id = order.order_id and order_total.title = 'Total' 
            WHERE order_status_id = 3");
        $localConnect = mysql_connect(BWMX_HOSTNAME, BWMX_USERNAME, BWMX_PASSWORD) or die(mysql_error());
        mysql_select_db('bwerp') or die(mysql_error());        
        mysql_query("TRUNCATE table store_orders");  
        
        while ($row = mysql_fetch_assoc($orders)) {            
            $query = "INSERT INTO store_orders (site, order_id, date_shipped, total) VALUES ('{$row['site']}','{$row['order_id']}', '{$row['date_shipped']}', '{$row['total']}')";         
            //echo $query;
            $result = mysql_query($query);
        }
        
        mysql_close($connect);
        mysql_close($localConnect);
        
        //smile clinic
        $connect = mysql_connect(TSC_HOSTNAME, TSC_USERNAME, TSC_PASSWORD) or die(mysql_error());
        mysql_select_db('grinner_mart') or die(mysql_error());
        
        $orders = mysql_query("SELECT 'tsc' as site, order.order_id, date_modified as date_shipped, value as total 
            FROM `order` left join order_total on order_total.order_id = order.order_id and order_total.title = 'Total' 
            WHERE order_status_id = 3");
        
        $localConnect = mysql_connect(BWMX_HOSTNAME, BWMX_USERNAME, BWMX_PASSWORD) or die(mysql_error());
        mysql_select_db('bwerp') or die(mysql_error());        
      
        while ($row = mysql_fetch_assoc($orders)) {            
            $query = "INSERT INTO store_orders (site, order_id, date_shipped, total) VALUES ('{$row['site']}','{$row['order_id']}', '{$row['date_shipped']}', '{$row['total']}')";         
            //echo $query;
            $result = mysql_query($query);
        }
        
        mysql_close($connect);        
        mysql_close($localConnect);
                
        
        //divine white
        $connect = mysql_connect(DV_HOSTNAME, DV_USERNAME, DV_PASSWORD) or die(mysql_error());
        mysql_select_db('divinty_storeus') or die(mysql_error());
        $orders = mysql_query("SELECT 'dvw' as site, order.order_id, date_modified as date_shipped, value as total 
            FROM `order` left join order_total on order_total.order_id = order.order_id and order_total.title = 'Total' 
            WHERE order_status_id = 3");
        $localConnect = mysql_connect(BWMX_HOSTNAME, BWMX_USERNAME, BWMX_PASSWORD) or die(mysql_error());
        mysql_select_db('bwerp') or die(mysql_error());        
      
        while ($row = mysql_fetch_assoc($orders)) {            
            $query = "INSERT INTO store_orders (site, order_id, date_shipped, total) VALUES ('{$row['site']}','{$row['order_id']}', '{$row['date_shipped']}', '{$row['total']}')";         
            //echo $query;
            $result = mysql_query($query);
        }
        mysql_close($connect);        
        mysql_close($localConnect);
        
         //glamwhite
        $connect = mysql_connect(GLW_HOSTNAME, GLW_STORE, GLW_STOREPASS) or die(mysql_error());
        mysql_select_db('glamjam_store_us') or die(mysql_error());
        
        $orders = mysql_query("SELECT 'glw' as site, order.order_id, date_modified as date_shipped, value as total 
            FROM `order` left join order_total on order_total.order_id = order.order_id and order_total.title = 'Total' 
            WHERE order_status_id = 3");
        $localConnect = mysql_connect(BWMX_HOSTNAME, BWMX_USERNAME, BWMX_PASSWORD) or die(mysql_error());
        mysql_select_db('bwerp') or die(mysql_error());        
    
        while ($row = mysql_fetch_assoc($orders)) {            
            $query = "INSERT INTO store_orders (site, order_id, date_shipped, total) VALUES ('{$row['site']}','{$row['order_id']}', '{$row['date_shipped']}', '{$row['total']}')";         
            //echo $query;
            $result = mysql_query($query);
        }        
        mysql_close($connect);        
        mysql_close($localConnect);
        
        //sonixfx
        $connect = mysql_connect(SFX_HOSTNAME, SFX_USERNAME, SFX_PASSWORD) or die(mysql_error());
        mysql_select_db('mart') or die(mysql_error());
        
        $orders = mysql_query("SELECT 'sfx' as site, order.order_id, date_modified as date_shipped, value as total 
            FROM `order` left join order_total on order_total.order_id = order.order_id and order_total.title = 'Total' 
            WHERE order_status_id = 3");
        $localConnect = mysql_connect(BWMX_HOSTNAME, BWMX_USERNAME, BWMX_PASSWORD) or die(mysql_error());
        mysql_select_db('bwerp') or die(mysql_error());        
    
        while ($row = mysql_fetch_assoc($orders)) {            
            $query = "INSERT INTO store_orders (site, order_id, date_shipped, total) VALUES ('{$row['site']}','{$row['order_id']}', '{$row['date_shipped']}', '{$row['total']}')";         
            //echo $query;
            $result = mysql_query($query);
        }        
        mysql_close($connect);        
        mysql_close($localConnect);
        
        //pw
        $connect = mysql_connect(PW_HOSTNAME, PW_USERNAME, PW_PASSWORD) or die(mysql_error());
        mysql_select_db('ab77231_store_us') or die(mysql_error());
        
        $orders = mysql_query("SELECT 'prw' as site, order.order_id, date_modified as date_shipped, value as total 
            FROM `order` left join order_total on order_total.order_id = order.order_id and order_total.title = 'Total' 
            WHERE order_status_id = 3");
        $localConnect = mysql_connect(BWMX_HOSTNAME, BWMX_USERNAME, BWMX_PASSWORD) or die(mysql_error());
        mysql_select_db('bwerp') or die(mysql_error());        
    
        while ($row = mysql_fetch_assoc($orders)) {            
            $query = "INSERT INTO store_orders (site, order_id, date_shipped, total) VALUES ('{$row['site']}','{$row['order_id']}', '{$row['date_shipped']}', '{$row['total']}')";         
            //echo $query;
            $result = mysql_query($query);
        }        
        mysql_close($connect);        
        mysql_close($localConnect);
        
        //bw
        $connect = mysql_connect(BW_HOSTNAME, BW_USERNAME, BW_PASSWORD) or die(mysql_error());
        mysql_select_db('beamwhite2013') or die(mysql_error());
        $orders = mysql_query("SELECT 'bw' as site, p.ID as order_id,p.post_modified as date_shipped, pmo.meta_value as total FROM bw_posts p 
LEFT JOIN bw_postmeta pmo ON pmo.post_id = p.ID AND pmo.meta_key = '_order_total' 
where p.post_status != 'trash' and p.post_type = 'shop_order'  and 
p.post_status = 'wc-completed' ORDER BY p.ID");
        
        $localConnect = mysql_connect(BWMX_HOSTNAME, BWMX_USERNAME, BWMX_PASSWORD) or die(mysql_error());
        mysql_select_db('bwerp') or die(mysql_error());        
    
        while ($row = mysql_fetch_assoc($orders)) {            
            $query = "INSERT INTO store_orders (site, order_id, date_shipped, total) VALUES ('{$row['site']}','{$row['order_id']}', '{$row['date_shipped']}', '{$row['total']}')";         
            //echo $query;
            $result = mysql_query($query);
        }        
        mysql_close($connect);        
        mysql_close($localConnect);
                
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);        
    }
    
}

