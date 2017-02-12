<?php

class ShipStationController extends Zend_Controller_Action {
 
    public function indexAction() 
    {        
       
        $writer = new Zend_Log_Writer_Stream('log/shipstation/' . date("Ymd") . '.txt');
        $logger = new Zend_Log($writer);     
                   
        if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] == 'teethwhite' && $_SERVER['PHP_AUTH_PW'] == ']dy=T9B>') {
            
            //$this->getRequest()->isGet()
            if (isset($_REQUEST)) {                               
                $text = serialize($_REQUEST);
                $logger->info($text);
            }
            header("Content-Type: application/xml; charset=utf-8");
            if ($_GET['action'] == 'export') {
                $this->ordersAction();
            } if($_GET['action'] == 'shipnotify' && $_GET['order_number'] && $_GET['tracking_number']) {               
                $this->_shipStation = new Application_Model_Shipstation;
                $update = $this->_shipStation->update($_GET['order_number'], $_GET['tracking_number']);
                header("HTTP/1.1 200 OK");             
                echo "Updated Successfully\n";
            }
        } else {
            $text = 'Authentication Failed||||||'.serialize($_GET);
            $logger->info($text);
            header('WWW-Authenticate: Basic realm="ShipStation"');
            header('HTTP/1.0 401 Unauthorized');
            echo "Authentiction Failed\n";
            exit;
        }
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
    public function ordersAction() { 
              
         $this->_shipStation = new Application_Model_Shipstation;                 
         $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<Orders>' . "\n";                
                
                $data = array();
		if (isset($_GET['start_date'])) {
			$start_date_time = explode(' ', $_GET['start_date']);
			$startdate = explode('/', $start_date_time[0]);
			$starttime = explode(':', $start_date_time[1]);
			$data['startdate'] = date('Y-m-d H:i:s', mktime($starttime[0], $starttime[1], 0, $startdate[0], $startdate[1], $startdate[2]));
		}
		if (isset($_GET['end_date'])) {
			$end_date_time = explode(' ', $_GET['end_date']);
			$enddate = explode('/', $end_date_time[0]);
			$endtime = explode(':', $end_date_time[1]);
			$data['enddate'] = date('Y-m-d H:i:s', mktime($endtime[0], $endtime[1], 0, $enddate[0], $enddate[1], $enddate[2]));
		}                
                $wscOrders = $this->_shipStation->getOrders('WSC', $data);
		if ($wscOrders) {
			foreach ($wscOrders as $order) {
				$order_info = $this->_shipStation->getOrder('WSC',$order['order_id']);
				if ($order_info) {
					foreach ($order_info as $order_data) {                                            
                                            $xml .= $this->_shipStation->buildXml($order_data);						
					}
				}
			}
		}       
                
                $wscLandingOrders = $this->_shipStation->getWscLandingOrders();
		if ($wscLandingOrders) {
			foreach ($wscLandingOrders as $order) {
				$order_info = $this->_shipStation->getWscLandingOrder($order);
				if ($order_info) {
					foreach ($order_info as $order_data) {                                            
                                            $xml .= $this->_shipStation->buildXml($order_data);
                                        }
                                }
                        }
                }
                
                $tscOrders = $this->_shipStation->getOrders('TSC', $data);
		if ($tscOrders) {
			foreach ($tscOrders as $order) {
				$order_info = $this->_shipStation->getOrder('TSC',$order['order_id']);
				if ($order_info) {
					foreach ($order_info as $order_data) {                                            
                                            $xml .= $this->_shipStation->buildXml($order_data);						
					}
				}
			}
		} 
                
                $glwOrders = $this->_shipStation->getOrders('GLW', $data);
		if ($glwOrders) {
			foreach ($glwOrders as $order) {
				$order_info = $this->_shipStation->getOrder('GLW',$order['order_id']);
				if ($order_info) {
					foreach ($order_info as $order_data) {                                            
                                            $xml .= $this->_shipStation->buildXml($order_data);						
					}
				}
			}
		} 
                $glwFreeGel = $this->_shipStation->getGlwFreegelOrders();
		if ($glwFreeGel) {
			foreach ($glwFreeGel as $order) {
				$order_info = $this->_shipStation->getGlwFreegelOrder($order);
				if ($order_info) {
					foreach ($order_info as $order_data) {                                            
                                            $xml .= $this->_shipStation->buildXml($order_data);						
					}
				}
			}
		} 
                $dvwOrders = $this->_shipStation->getOrders('DVW', $data);
		if ($dvwOrders) {
			foreach ($dvwOrders as $order) {
				$order_info = $this->_shipStation->getOrder('DVW',$order['order_id']);
				if ($order_info) {
					foreach ($order_info as $order_data) {                                            
                                            $xml .= $this->_shipStation->buildXml($order_data);						
					}
				}
			}
		} 
                
		$xml .= '</Orders>';
		echo $xml;
                              
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);         
    }
   
    public function tscAction() {
        $this->_shipStation = new Application_Model_Shipstation;            
        $this->_shipStation->getTSCorders();
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);   
    }
}