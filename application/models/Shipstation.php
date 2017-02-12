<?php

class Application_Model_Shipstation {
    
    public function __construct()
    {       
        $this->_wscdb = Zend_Registry::get('whitesmi_rdm');
        $this->_tscdb = Zend_Registry::get('grinner_rdm');
        $this->_glwdb = Zend_Registry::get('glamjam_redeem');
        $this->_dvwdb = Zend_Registry::get('divinty_rdm');
    }    
    public function getOrder($site, $orderId) {
        $order_data = array();
        
        if($site == 'WSC') {
            $order = $this->_wscdb->fetchRow("SELECT concat(customer.first_name,' ', customer.last_name) AS shipname,          
              CASE WHEN customer.country = 'USA' THEN 'US'
                ELSE customer.country END AS COUNTRY,                                          
              customer.id, customer.code,customer.verification, customer.deal_id, customer.first_name, 
                  customer.last_name, customer.email, customer.address1, customer.address2, customer.city, customer.state,
                  customer.zip, customer.country, customer.phone, customer.datetime, customer.order_status, customer.shipped, 
                  customer.comments, customer.opt_in, deal_code.*, product.* , 
                  'WSC' as source,
                  CASE when purchased_suggestion.product_id in (1,6)  then 'Upgrade 1'
                     when purchased_suggestion.product_id in (2,7)  then 'Upgrade 2' 
                     when purchased_suggestion.product_id in (3,8)  then 'Upgrade 3' 
                     when purchased_suggestion.product_id in (4,9)  then 'Extra Kit' 
                     when purchased_suggestion.product_id in (5,10) then 'Extra Pen'
                     when purchased_suggestion.product_id = '11' then 'Cheek Retractor'
                     when deal_code.shipping > 0 then 'Shipping'
                     ELSE '' END AS Upgrade, 
                  CASE when purchased_suggestion.product_id in (4,9) then '8.2.3' 
                  when purchased_suggestion.product_id in (5,10) then '60.1'
                  when purchased_suggestion.product_id = '11' then '7030'
                     ELSE '' END AS UpgradeSKU,                     
                  purchased_suggestion.quantity AS upgradeQuantity,  
                  CASE WHEN purchased_suggestion.price > 0 then purchased_suggestion.price
                      WHEN deal_code.shipping>0 then deal_code.shipping
                  else '' END AS Price, 
                  CASE WHEN purchased_suggestion.price > 0 THEN purchased_suggestion.product_name
                    ELSE '' END AS Upgrade_Product_Name FROM customer          
              LEFT JOIN deal_code ON deal_code.deal_id = customer.deal_id
              LEFT JOIN product ON product.product_id = deal_code.product_id
              LEFT JOIN purchased_suggestion ON purchased_suggestion.code = customer.code          
              WHERE customer.code = '$orderId'");
        } elseif ($site == 'TSC') {
            $order = $this->_tscdb->fetchRow("SELECT concat(customer.first_name,' ', customer.last_name) AS shipname,      
          CASE WHEN customer.country = 'USA' THEN 'US'
            ELSE customer.country END AS COUNTRY,                   
           customer.id, customer.code,customer.verification, customer.deal_id, customer.first_name, 
              customer.last_name, customer.email, customer.address1, customer.address2, customer.city, customer.state,
              customer.zip, customer.country, customer.phone, customer.datetime, customer.order_status, customer.shipped, 
              customer.comments, customer.opt_in, deal_code.*, product.* , 
              'TSC' as source,
              CASE when purchased_suggestion.product_id in (1,6)  then 'Upgrade 1'
                 when purchased_suggestion.product_id in (2,7)  then 'Upgrade 2' 
                 when purchased_suggestion.product_id in (3,8)  then 'Upgrade 3' 
                 when purchased_suggestion.product_id in (4,9)  then 'Extra Kit' 
                 when purchased_suggestion.product_id in (5,10) then 'Extra Pen'
                 when purchased_suggestion.product_id in (16) and purchased_suggestion.price > 0 then 'Remineralizing Gel'
                 when purchased_suggestion.product_id = '17' and purchased_suggestion.price > 0 then purchased_suggestion.product_name
                 when deal_code.shipping > 0 then 'Shipping'
                 ELSE '' END AS Upgrade, 
              CASE when purchased_suggestion.product_id in (4,9) then '8.2.2' 
                  when purchased_suggestion.product_id in (5,10) then '60.1'
                  when purchased_suggestion.product_id = '16' then '70.3'
                  when purchased_suggestion.product_id = '17' then '12700'
                     ELSE '' END AS UpgradeSKU,                     
              '1' AS upgradeQuantity,                    
              CASE WHEN purchased_suggestion.price > 0 then purchased_suggestion.price
                  WHEN deal_code.shipping>0 then deal_code.shipping
              else '' END AS Price, 
              CASE WHEN purchased_suggestion.price > 0 THEN purchased_suggestion.product_name
                ELSE '' END AS Upgrade_Product_Name FROM customer          
	  LEFT JOIN deal_code ON deal_code.deal_id = customer.deal_id
	  LEFT JOIN product ON product.product_id = deal_code.product_id
          LEFT JOIN purchased_suggestion ON purchased_suggestion.code = customer.code          
	  WHERE customer.code = '$orderId'");
        } elseif ($site == 'GLW') {
            $order = $this->_glwdb->fetchRow("SELECT concat(customer.first_name,' ', customer.last_name) AS shipname,      
          CASE WHEN customer.country = 'USA' THEN 'US'
            ELSE customer.country END AS COUNTRY,                   
           customer.id, customer.code,customer.verification, customer.deal_id, customer.first_name, 
              customer.last_name, customer.email, customer.address1, customer.address2, customer.city, customer.state,
              customer.zip, customer.country, customer.phone, customer.datetime, customer.order_status, customer.shipped, 
              deal_code.*, 
              CASE when purchased_suggestion.product_id = 12  then 'Home Whitening Kit Elite' 
              ELSE product.description END AS description, 
              CASE when purchased_suggestion.product_id = 12  then '8.3' 
              ELSE product.bb_number END AS bb_number, 
              'GLW' as source,
              CASE when purchased_suggestion.product_id = 15 then 'Daily White Fusion' 
                 ELSE '' END AS Upgrade,  
              CASE when purchased_suggestion.product_id = 15 then '50.1'
                     ELSE '' END AS UpgradeSKU,                     
              '1' AS upgradeQuantity,                    
              CASE WHEN purchased_suggestion.price > 0 then purchased_suggestion.price
                  WHEN deal_code.shipping>0 then deal_code.shipping
              else '' END AS Price, 
              CASE WHEN purchased_suggestion.price > 0 THEN purchased_suggestion.product_name
                ELSE '' END AS Upgrade_Product_Name FROM customer          
	  LEFT JOIN deal_code ON deal_code.deal_id = customer.deal_id
	  LEFT JOIN product ON product.product_id = deal_code.product_id
          LEFT JOIN purchased_suggestion ON purchased_suggestion.code = customer.code          
	  WHERE customer.code = '$orderId'");
        } elseif ($site == 'DVW') {
            $order = $this->_dvwdb->fetchRow("SELECT concat(customer.first_name,' ', customer.last_name) AS shipname,      
          CASE WHEN customer.country = 'USA' THEN 'US'
            ELSE customer.country END AS COUNTRY,                   
           customer.id, customer.code,customer.verification, customer.deal_id, customer.first_name, 
              customer.last_name, customer.email, customer.address1, customer.address2, customer.city, customer.state,
              customer.zip, customer.country, customer.phone, customer.datetime, customer.order_status, customer.shipped, 
              customer.comments, customer.opt_in, deal_code.*, product.* , 
              'DVW' as source,
              CASE   
                 when purchased_suggestion.product_id = '13'  then 'Extra Kit'                  
                 when deal_code.shipping > 0 then 'Shipping'
                 ELSE '' END AS Upgrade, 
              CASE when purchased_suggestion.product_id = '13' then '8.2.7'
                     ELSE '' END AS UpgradeSKU,                     
              '1' AS upgradeQuantity,                    
              CASE WHEN purchased_suggestion.price > 0 then purchased_suggestion.price
                  WHEN deal_code.shipping>0 then deal_code.shipping
              else '' END AS Price, 
              CASE WHEN purchased_suggestion.price > 0 THEN purchased_suggestion.product_name
                ELSE '' END AS Upgrade_Product_Name FROM customer          
	  LEFT JOIN deal_code ON deal_code.deal_id = customer.deal_id
	  LEFT JOIN product ON product.product_id = deal_code.product_id
          LEFT JOIN purchased_suggestion ON purchased_suggestion.code = customer.code          
	  WHERE customer.code = '$orderId'");
        }
        
	$bill_to = array();
        $bill_to[] = array(
            'Name' =>  $order['shipname'],           
            'Phone' => $order['phone'],
            'Email' => $order['email']
        );
        $ship_to = array();
        $ship_to[] = array('Name' => $order['shipname'],
            'Address1' => $order['address1'],
            'Address2' => $order['address2'],
            'City' => $order['city'],
            'State' => $order['state'],
            'PostalCode' => $order['zip'],
            'Country' => $order['COUNTRY'],
            'Phone' => $order['phone']
        );
        $customer = array();
	$customer[] = array(
            'CustomerCode' => $order['email'],
            'ShipTo' => $ship_to,
            'BillTo' => $bill_to,            
        );
        $products = array();
        $products[] = array(
					'SKU'         => $order['bb_number'],
					'Name'        => $order['description'],					
					'Quantity'    => 1,
					'UnitPrice'   => 0,					
                            );
        
        if($order['Upgrade'] && $order['Upgrade'] != 'Shipping') {
           /* if($order['Upgrade'] == 'Extra Kit') {
                $upgradeSKU = '8.2.3';
            } elseif($order['Upgrade'] == 'Extra Pen') {
                $upgradeSKU = '60.1';
            } elseif($order['Upgrade'] == 'Cheek Retractor') {
                 $upgradeSKU = '7030';
            } */            
            $products[] = array(	'SKU'         => $order['UpgradeSKU'],				
					'Name'        => $order['Upgrade_Product_Name'],					
					'Quantity'    => (int)$order['upgradeQuantity'],
					'UnitPrice'   => round($order['Price']/$order['upgradeQuantity'],2),					
                            );
        }        
        $order_data[] = array(
            'OrderID'=> $order['id'],
            'OrderNumber' => $orderId,
            'OrderDate' => date('n/j/Y g:i A', strtotime($order['datetime'])),
            'OrderStatus' => 'paid',
            'LastModified' => date('n/j/Y g:i A', strtotime($order['datetime'])),
            'ShippingMethod' => 'Smartmail Parcels Ground',
            'ShippingAmount' => $order['Upgrade'] == 'Shipping'?$order['Price']:0,
            'OrderTotal' => $order['Price'] == ''?0:$order['Price'],              
            'Source' => $site,
            'CustomField1'=> $site,
            'Customer' => $customer,
            'Items' => $products
        );
        return $order_data;
    }
       
    public function getOrders($site, $data=array())            
    {           
        $timeRange = '';

        if (isset($data['startdate'])) {
            $timeRange = " AND datetime >= '{$data['startdate']}'" ;
        }
        if (isset($data['enddate'])) {
            $timeRange .= " AND datetime <= '{$data['enddate']}'";
        }        
            
        $sql = "select code as order_id from customer WHERE customer.first_name IS NOT NULL 
            AND (customer.order_status = 1 OR customer.order_status = 99) AND customer.shipped != 1 AND country = 'USA' $timeRange ORDER BY datetime";
        $writer = new Zend_Log_Writer_Stream('log/shipstation/' . date("Ymd") . '.txt');
        $logger = new Zend_Log($writer);     
        $logger->info($sql);
        if ($site == 'WSC') {
            $orders = $this->_wscdb->fetchAll($sql);       
        } elseif($site == 'TSC') {
            $orders = $this->_tscdb->fetchAll($sql);       
        } elseif($site == 'GLW') {
            $orders = $this->_glwdb->fetchAll($sql);
        }  elseif($site == 'DVW') {
            $orders = $this->_dvwdb->fetchAll($sql);
        }
        return $orders;
    }
    public function getWscLandingOrders()
    {
         return $this->_wscdb->fetchAll("select * from free_gel WHERE order_status = 1 AND shipped = 0 AND country = 'US'");        
    }
    public function getGlwFreegelOrders()
    {
         return $this->_glwdb->fetchAll("select * from gel_order WHERE order_status = 1 AND shipped = 0 AND country = 'USA'");        
    }
   
    public function getWscLandingOrder($order)
    {
        $bill_to = array();
        $bill_to[] = array(
            'Name' => $order['first_name'] . ' ' . $order['last_name'],
            'Phone' => $order['phone'],
            'Email' => $order['email']
        );
        $ship_to = array();
        $ship_to[] = array('Name' => $order['first_name'] . ' ' . $order['last_name'],
            'Address1' => $order['address1'],
            'Address2' => $order['address2'],
            'City' => $order['city'],
            'State' => $order['state'],
            'PostalCode' => $order['zip'],
            'Country' => $order['country'],
            'Phone' => $order['phone']
        );
        $customer = array();
        $customer[] = array(
            'CustomerCode' => $order['email'],
            'ShipTo' => $ship_to,
            'BillTo' => $bill_to,
        );
        $products = array();
        $products[] = array(
            'SKU' => '8.2.3',
            'Name' => 'Premium Take-Home Kit',
            'Quantity' => 1,
            'UnitPrice' => $order['price'],
        );

        $order_data[] = array(
            'OrderID' => $order['order_id'],
            'OrderNumber' => 'WSCL-' . $order['order_id'],
            'OrderDate' => date('n/j/Y g:i A', strtotime($order['order_date'])),
            'OrderStatus' => 'paid',
            'LastModified' => date('n/j/Y g:i A', strtotime($order['order_date'])),
            'ShippingMethod' => 'Smartmail Parcels Ground',
            'ShippingAmount' => 0,
            'OrderTotal' => $order['price'],
            'Source' => 'WSCLanding',
            'CustomField1' => 'WSCLanding',
            'Customer' => $customer,
            'Items' => $products
        );
        return $order_data;        
    }
    
    public function getGlwFreegelOrder($order)
    {
        $bill_to = array();
        $bill_to[] = array(
            'Name' => $order['first_name'] . ' ' . $order['last_name'],
            'Phone' => $order['phone'],
            'Email' => $order['email']
        );
        $ship_to = array();
        $ship_to[] = array('Name' => $order['first_name'] . ' ' . $order['last_name'],
            'Address1' => $order['address1'],
            'Address2' => $order['address2'],
            'City' => $order['city'],
            'State' => $order['state'],
            'PostalCode' => $order['zip'],
            'Country' => 'US',
            'Phone' => $order['phone']
        );
        $customer = array();
        $customer[] = array(
            'CustomerCode' => $order['email'],
            'ShipTo' => $ship_to,
            'BillTo' => $bill_to,
        );
        $products = array();
        $products[] = array(
            'SKU' => '17070',
            'Name' => 'GlamWhite 10mL Whitening Gel',
            'Quantity' => 1,
            'UnitPrice' => 0,
        );

        $order_data[] = array(
            'OrderID' => $order['order_id'],
            'OrderNumber' => 'GLWF-' . $order['order_id'],
            'OrderDate' => date('n/j/Y g:i A', strtotime($order['order_date'])),
            'OrderStatus' => 'paid',
            'LastModified' => date('n/j/Y g:i A', strtotime($order['order_date'])),
            'ShippingMethod' => 'Smartmail Parcels Ground',
            'ShippingAmount' => 0,
            'OrderTotal' => 0,
            'Source' => 'GLWFreeGel',
            'CustomField1' => 'GLWFreeGel',
            'Customer' => $customer,
            'Items' => $products
        );
        return $order_data; 
    }
    
    public function buildXml($order_data) 
    {
        $xml = '	<Order>' . "\n";
	        $xml .= '		<OrderID><![CDATA[' . $order_data['OrderID'] . ']]></OrderID>' . "\n";
			$xml .= '		<OrderNumber><![CDATA[' . $order_data['OrderNumber'] . ']]></OrderNumber>' . "\n";
			$xml .= '		<OrderDate>' . $order_data['OrderDate'] . '</OrderDate>' . "\n";
			$xml .= '		<OrderStatus><![CDATA[' . $order_data['OrderStatus'] . ']]></OrderStatus>' . "\n";
			$xml .= '		<LastModified>' . $order_data['LastModified'] . '</LastModified>' . "\n";
			$xml .= '		<ShippingMethod><![CDATA[' . $order_data['ShippingMethod'] . ']]></ShippingMethod>' . "\n";
			$xml .= '		<OrderTotal>' . $order_data['OrderTotal'] . '</OrderTotal>' . "\n";
			$xml .= '		<ShippingAmount>' . $order_data['ShippingAmount'] . '</ShippingAmount>' . "\n";
			$xml .= '		<CustomField1><![CDATA[' . $order_data['Source'] . ']]></CustomField1>' . "\n";
			$xml .= '		<Source><![CDATA[' . $order_data['Source'] . ']]></Source>' . "\n";
			$xml .= '		<Customer>' . "\n";
			foreach ($order_data['Customer'] as $customer) {
				$xml .= '			<CustomerCode>' . $customer['CustomerCode'] . '</CustomerCode>' . "\n";
				$xml .= '			<BillTo>' . "\n";
				foreach ($customer['BillTo'] as $billing) {
					$xml .= '				<Name><![CDATA[' . $billing['Name'] . ']]></Name>' . "\n";
					$xml .= '				<Phone>' . $billing['Phone'] . '</Phone>' . "\n";
					$xml .= '				<Email>' . $billing['Email'] . '</Email>' . "\n";
				}
				$xml .= '			</BillTo>' . "\n";
				$xml .= '			<ShipTo>' . "\n";
				foreach ($customer['ShipTo'] as $shipping) {
					$xml .= '				<Name><![CDATA[' . $shipping['Name'] . ']]></Name>' . "\n";
					$xml .= '				<Address1><![CDATA[' . $shipping['Address1'] . ']]></Address1>' . "\n";
					$xml .= '				<Address2><![CDATA[' . $shipping['Address2'] . ']]></Address2>' . "\n";
					$xml .= '				<City><![CDATA[' . $shipping['City'] . ']]></City>' . "\n";
					$xml .= '				<State><![CDATA[' . $shipping['State'] . ']]></State>' . "\n";
					$xml .= '				<PostalCode><![CDATA[' . $shipping['PostalCode'] . ']]></PostalCode>' . "\n";
					$xml .= '				<Country><![CDATA[' . $shipping['Country'] . ']]></Country>' . "\n";
					$xml .= '				<Phone>' . $shipping['Phone'] . '</Phone>' . "\n";
				}
				$xml .= '			</ShipTo>' . "\n";
			}
			$xml .= '		</Customer>' . "\n";
			$xml .= '		<Items>' . "\n";
			foreach ($order_data['Items'] as $item) {
				$xml .= '			<Item>' . "\n";
				$xml .= '				<SKU><![CDATA[' . $item['SKU'] . ']]></SKU>' . "\n";
				$xml .= '				<Name><![CDATA[' . $item['Name'] . ']]></Name>' . "\n";
				$xml .= '				<Quantity>' . $item['Quantity'] . '</Quantity>' . "\n";
				$xml .= '				<UnitPrice>' . $item['UnitPrice'] . '</UnitPrice>' . "\n";
				$xml .= '			</Item>' . "\n";
			}
			$xml .= '		</Items>' . "\n";
			$xml .= '	</Order>' . "\n";
        return $xml;        
    }
    
    public function getTSCorders() {
        $orders = $this->_dvwdb->fetchAll("select code as order_id from customer WHERE customer.first_name IS NOT NULL 
            AND (customer.order_status = 1 OR customer.order_status = 99) AND customer.shipped != 1 AND country = 'USA'");
        var_dump($orders);
        if($orders) {
            echo "here";
        }
        die();
        return $orders; //was return orders; not return $orders; - Mychael
    }
    
    public function update($code, $trackingNumber)
    {
        $data = array('import' => $trackingNumber);
        if (strpos($code,'WSCL-') !== false) {
            $landingOrder = preg_split("/WSCL-/i", $code);
            $update = $this->_wscdb->update('free_gel', $data, $this->_wscdb->quoteInto("order_id = ?", (int)$landingOrder[1]));
            return $update;  
        }
        $update = $this->_wscdb->update('customer', $data, $this->_wscdb->quoteInto("code = ?", $code));
        if ($update == 0) {
            $update = $this->_tscdb->update('customer', $data, $this->_tscdb->quoteInto("code = ?", $code));
        }
        if ($update == 0) {
            $update = $this->_glwdb->update('customer', $data, $this->_glwdb->quoteInto("code = ?", $code));
        }
        if ($update == 0) {
            $update = $this->_dvwdb->update('customer', $data, $this->_dvwdb->quoteInto("code = ?", $code));
        }
        return $update;        
    }
   
}