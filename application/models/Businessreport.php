<?php
require_once(__DIR__ . '/ringcentral-call-log/vendor/autoload.php');
use RingCentral\SDK\SDK;

class Application_Model_Businessreport {
    # Object level variables

    protected $_db;
	public $ringcentral_access_token=null;
	public $ringcentral_token_type=null;
	public $ringcentral_accountId=null;
	public $ringcentral_extensionId=null;
	
    /**
     * Class constructor - Setup the DB connection
     */
    public function __construct() {
        # get handle on our database object        
        $this->_db = Zend_Registry::get('bwbusiness');
    }
    /* Dariuz Rubin */
    public function account_conversion($data) {
        $result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	
             
            	/*$query = "SELECT source, count(distinct user_id) as totalAccount,'{$rep_name}' as parent_user FROM `account_conversion`		            
		            WHERE action_time >= '{$data['from']} 00:00:00' AND action_time <= '{$data['to']} 23:59:59' AND parent_id={$rep_id} group by source";*/					
		        $query = "select count(distinct user_id) as totalAccount,'{$rep_name}' as parent_user from account_conversion WHERE action_time >= '{$data['from']} 00:00:00' AND action_time <= '{$data['to']} 23:59:59' AND parent_id={$rep_id}";
		        
		        				
				$result_ary[$ind] = $this->_db->fetchAll($query);	
				
			}
		}
        return $result_ary;
    }    
    
    /**
	* get leads which had been assigned in given time	
	*/
     public function leads_assigned($data) {
        $result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	$rep = " AND account_user.parent_user = '$rep_id'";  
            	
            	$query = "SELECT source, count(id) as total_leads,{$rep_id} as rep,'{$data['from']}' as from_time,'{$data['to']}' as to_time,'{$rep_name}' as parent_user FROM `user` left join account_user on user.id = account_user.user WHERE created_time >= '{$data['from']} 00:00:00' AND created_time <= '{$data['to']} 23:59:59' AND account_user.parent_user = {$rep_id}  and (role = 'customer') AND (status != 'Disabled')";
				
				/*$query = "select count(*) as total_leads,'{$rep_name}' as parent_user from user join (select user_id from user_notes where (notes like 'Change sales rep from%') and (enter_time > '{$data['from']} 00:00:00' AND enter_time <= '{$data['to']} 23:59:59') group by user_id) as a on user.id = a.user_id left join account_user on user.id = account_user.user where user.role = 'customer' AND user.status != 'Disabled' and account_user.parent_user = '{$rep_id}'";*/
				
				$res = $this->_db->fetchAll($query);
				if (isset($res) and $res[0]['total_leads']>0)
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}
				
				//$result_ary[$ind] = $this->_db->fetchAll($query);	
				
			}
		}
        return $result_ary;
    }    
    
    /**
	* get accounts which had been  been converted to accounts from the leads which had been assigned to or entered into the queue in that same date range	
	*/
     public function account_conversion_assigned($data) {
    	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	
            	/*$query = "select count(*) as total_accounts_assigned,'{$rep_name}' as parent_user from account_conversion join (select user_id from user_notes where (notes like 'Change sales rep from%') and (enter_time > '{$data['from']} 00:00:00' AND enter_time <= '{$data['to']} 23:59:59') group by user_id) as a on account_conversion.user_id = a.user_id  WHERE action_time >= '{$data['from']} 00:00:00' AND action_time <= '{$data['to']} 23:59:59' AND parent_id={$rep_id}";*/
            	$query = "select count(distinct user_id) as total_accounts_assigned,'{$rep_name}' as parent_user from account_conversion WHERE created_time >= '{$data['from']} 00:00:00' AND created_time <= '{$data['to']} 23:59:59' AND action_time >= '{$data['from']} 00:00:00' AND action_time <= '{$data['to']} 23:59:59' AND parent_id={$rep_id}";
				
				$result_ary[$ind] = $this->_db->fetchAll($query);					
			}
		}
        return $result_ary;
       
    }
       /**
	* get accounts which had been  been converted to accounts in date range	
	*/
    public function conversion_accounts($data) {
    	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	            
            	$query = "SELECT account_conversion.user_type, account_conversion.source, lead_source, u.id,action_time, u.email,u.created_time, u.businessname,concat(u.firstname, ' ', u.lastname) as name,'{$rep_name}' as parent_user,
            concat(uu.firstname, ' ', uu.lastname) as rep
            FROM `account_conversion`  
            left join user u on u.id = account_conversion.user_id 
            left join user uu on uu.id = account_conversion.parent_id 
            WHERE action_time >= '{$data['from']} 00:00:00' AND action_time <= '{$data['to']} 23:59:59' AND parent_id={$rep_id} order by action_time ASC, u.created_time, lead_source, rep";
				
				$result_ary[$ind] = $this->_db->fetchAll($query);					
			}
		}
        return $result_ary;
       
    }
    
     /**
	* get accounts which had been  been converted to accounts from the leads which had been assigned to or entered into the queue in that same date range	
	*/
     public function assigned_conversion_accounts($data) {
    	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	            
            	$query = "SELECT account_conversion.user_type, account_conversion.source, lead_source, action_time,u.id, u.email,u.created_time, u.businessname,concat(u.firstname, ' ', u.lastname) as name,'{$rep_name}' as parent_user,
            concat(uu.firstname, ' ', uu.lastname) as rep
            FROM `account_conversion`  
            left join user u on u.id = account_conversion.user_id 
            left join user uu on uu.id = account_conversion.parent_id 
            WHERE account_conversion.created_time >= '{$data['from']} 00:00:00' AND account_conversion.created_time <= '{$data['to']} 23:59:59' and action_time >= '{$data['from']} 00:00:00' AND action_time <= '{$data['to']} 23:59:59' AND parent_id={$rep_id} order by action_time ASC, u.created_time, lead_source, rep";
				
				$result_ary[$ind] = $this->_db->fetchAll($query);					
			}
		}
        return $result_ary;
       
    }
    /**
	* Dariuz Rubin
	* Get phone calls
	*/
    public function phone_calls($data) {
     	$result_ary = array();
     	if (isset($data))
     	{
     		// Login
     		
			$tokenUrl = RINGCENTRAL_APP_SERVER . '/restapi/oauth/token';
			$apiKey = base64_encode(RINGCENTRAL_APP_KEY . ':' . RINGCENTRAL_APP_SECRET);
			$values = array(
			        'grant_type'   => 'password',
			        'username'         => RINGCENTRAL_APP_USERNAME,
			        'extension'         => RINGCENTRAL_APP_EXTENSION,
			        'password'         => RINGCENTRAL_APP_PASSWORD					        
			    );
			try 
			{
				
				$ch = curl_init();
			    if (FALSE === $ch)
		        	throw new Exception('failed to initialize');

			    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
			    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			      'Accept: application/json',
			      'Content-Type: application/x-www-form-urlencoded',
			      'Authorization: Basic'.$apiKey
			    ));
			    curl_setopt($ch, CURLOPT_POST, count($values));
			    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($values));
			    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			    curl_setopt($ch, CURLOPT_VERBOSE, 1);
			    curl_setopt($ch, CURLOPT_HEADER, 1);


			    $response = curl_exec($ch);

			    if (FALSE === $response)
			        throw new Exception(curl_error($ch), curl_errno($ch));
			        
			   

			    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			    $body = substr($response, $headerSize);
		    
				curl_close($ch);		      
			      
			   
			    $body = json_decode($body, true);
				if (isset($body))
				{
					$ringcentral_access_token = $body['access_token'];
					$ringcentral_token_type = $body['token_type'];
					$ringcentral_accountId = $body['owner_id'];
					$ringcentral_extensionId = $body['owner_id'];
					$start_time = $data['from'].'T00:00:00.000Z';
			        $end_time = $data['to'].'T23:59:59.000Z';
			        
			            	
					// Get Call Log
					
					$call_log_tokenUrl = RINGCENTRAL_APP_SERVER . '/restapi/v1.0/account/'.$ringcentral_accountId.'/extension/'.$ringcentral_extensionId.'/call-log';
					
					$call_log_values = '?'.'page=1&perPage=100&views=Simple&type=Voice&dateFrom='.$start_time.'&dateTo='.$end_time;
					
					$call_log_tokenUrl .= $call_log_values;
					try {
					    $call_log_ch = curl_init();

					    if (FALSE === $call_log_ch)
					        throw new Exception('failed to initialize');

					    curl_setopt($call_log_ch, CURLOPT_URL, $call_log_tokenUrl);
					    curl_setopt($call_log_ch, CURLOPT_HTTPHEADER, array(
					      'Authorization: '.$ringcentral_token_type. ' ' . $ringcentral_access_token,
					      'Accept: application/json'	      
					    ));
					   
						curl_setopt($call_log_ch, CURLOPT_RETURNTRANSFER, 1);
					    curl_setopt($call_log_ch, CURLOPT_VERBOSE, 1);
					    curl_setopt($call_log_ch, CURLOPT_HEADER, 1);


					    $call_log_response = curl_exec($call_log_ch);

					    if (FALSE === $call_log_response)
					        throw new Exception(curl_error($call_log_ch), curl_errno($call_log_ch));
					        
					   

					    $call_log_headerSize = curl_getinfo($call_log_ch, CURLINFO_HEADER_SIZE);
					    $call_log_body = substr($call_log_response, $call_log_headerSize);

						curl_close($call_log_ch);

					    $call_log_body = json_decode($call_log_body, true);

						if (isset($call_log_body) and isset($call_log_body['records']) and count($call_log_body['records']) > 0)
						{
							foreach($data['rep'] as $ind => $rep_pair )
					        {
					        	$rep_pair_ary = explode(',',$rep_pair);
					        	$calls_made = 0;
					        	$received = 0;
					        	$total = 0;
					        	$minutes = 0;
					        	if (count($rep_pair_ary)>1)
					        	{
									$rep_id = $rep_pair_ary[0];
					            	$rep_name = $rep_pair_ary[1];
					            	$query="SELECT ringcentral_phone FROM `user` where user.id ={$rep_id} ";        
							
									$res = $this->_db->fetchRow($query);
									if (isset($res) and strlen($res['ringcentral_phone'])>0)
									{
										$ringcentral_phone =$res['ringcentral_phone'];
										
										foreach($call_log_body['records'] as $call_log)
									    {
									    	if (($call_log['from']['phoneNumber'] == $ringcentral_phone) or ($call_log['to']['phoneNumber'] == $ringcentral_phone))
									    	{
												if ($call_log['direction'] == 'Outbound')
												{
													$calls_made++;
													
												}else if ($call_log['direction'] == 'Inbound')
												{
													$received++;
												}
												$total++;
												$minutes += $call_log['duration'];;
											}
											
										}

										if ($minutes>0)
										{
											$minutes = ceil($minutes/60);
											$result_ary[$ind]['parent_user'] = $rep_name;
											$result_ary[$ind]['calls_made'] = $calls_made;
											$result_ary[$ind]['received'] = $received;
											$result_ary[$ind]['total'] = $total;
											$result_ary[$ind]['minutes'] = $minutes;
										}
									}
					            	
						
					            }
					        }
						}
					   
					    // ...process $content now
					} catch(Exception $call_log_e) {

					    $call_log_body = sprintf('Curl failed with error #%d: %s',$call_log_e->getCode(), $call_log_e->getMessage());
						
					}
				}
			    // ...process $content now
			} catch(Exception $e) {

			    $body = sprintf('Curl failed with error #%d: %s',$e->getCode(), $e->getMessage());
				
			}
		}
     
        return $result_ary;      
    }
    /*====================*/
    
    public function phone_conversion($data) {
     	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	
            	$query="SELECT count(id) as totalContacts,'{$rep_name}' as parent_user FROM `user`
            left join account_user on user.id = account_user.user 
            WHERE created_time >= '{$data['from']} 00:00:00' AND created_time <= '{$data['to']} 23:59:59' and imported = 'Phone Call' 
            AND status != 'Disabled' AND parent_user={$rep_id} 
            union 
            SELECT count(id) as totalAccounts,'{$rep_name}' as parent_user FROM `user`  
            left join account_user on user.id = account_user.user
            WHERE created_time >= '{$data['from']} 00:00:00' AND created_time <= '{$data['to']} 23:59:59' and imported = 'Phone Call' 
            AND status != 'Disabled' AND parent_user={$rep_id}  AND type = 'Account'";        
				
				$res = $this->_db->fetchAll($query);
				if (isset($res) and count($res)>1 and $res[0]['totalContacts']>0)
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}
			}
		}
        return $result_ary;      
    }
    public function phone_accounts($data) {
    	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	
            	$query="select a.*,  concat(uu.firstname, ' ', uu.lastname) as rep,'{$rep_name}' as parent_user from 
            (SELECT u.type, u.id, action_time, u.email,u.created_time, u.businessname,concat(u.firstname, ' ', u.lastname) as name, au.parent_user       
            FROM `user` u
            left join account_conversion ac on u.id = ac.user_id 
            left join account_user au on u.id = au.user 
            
            WHERE u.created_time >= '{$data['from']} 00:00:00' AND u.created_time <= '{$data['to']} 23:59:59' 
            AND imported='Phone Call' AND u.status != 'Disabled' AND au.parent_user = {$rep_id} order by u.created_time, action_time) a
            left join user uu on uu.id = a.parent_user";
          
				$result_ary[$ind] = $this->_db->fetchAll($query);				
			}
		}
        return $result_ary;      
       
    }
    public function response_time($data) {
    	        
        $notResponded = $responded = '';
        /*if($data['status'] =='notResponded') {
            $join = 'LEFT JOIN';
            $notResponded = ' b.user_id is NULL AND';
        } else {
            $join = 'JOIN';
            $responded = 'WHERE firstContact > assignTime';
        }    */
        $join = 'JOIN';
        //$responded = 'WHERE firstContact > assignTime';
            
    	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	$rep = " AND au.parent_user = '$rep_id'";      
            	      	
            	$query = "select c.*, TIMESTAMPDIFF(MINUTE,assignTime,firstContact) AS responseTime,'{$rep_name}' as parent_user from 
            (select user.id, concat(user.firstname, ' ', user.lastname) as name, 
            case when user.businessname is NULL then concat(user.firstname, ' ', user.lastname) ELSE user.businessname end businessname, user.email, a.enter_time as assignTime, b.notes,b.firstContact, concat(uu.firstname, ' ', uu.lastname) as rep
            from user join 
            (select user_id, enter_time from user_notes where notes like 'Change sales rep from%' group by user_id) 
            as a on user.id = a.user_id 
            $join (select user_id, min(enter_time) as firstContact,notes from user_notes where notes not like 'Change sales rep from%' and author != 'Request Info' group by user_id) b on user.id = b.user_id
            join account_user au on user.id = au.user
            join user uu on uu.id = au.parent_user
            where user.role = 'customer' AND user.status != 'Disabled' AND $notResponded
            a.enter_time > '{$data['from']} 00:00:00' AND a.enter_time <= '{$data['to']} 23:59:59' $rep ) c WHERE firstContact >= assignTime ORDER BY assignTime";
            /*$query = "select c.*, TIMESTAMPDIFF(MINUTE,assignTime,firstContact) AS responseTime,'{$rep_name}' as parent_user from 
            (select user.id, concat(user.firstname, ' ', user.lastname) as name, 
            case when user.businessname is NULL then concat(user.firstname, ' ', user.lastname) ELSE user.businessname end businessname, user.email, a.enter_time as assignTime, b.notes,b.firstContact, concat(uu.firstname, ' ', uu.lastname) as rep
            from user join 
            (select user_id, enter_time from user_notes where type = 'attempt' group by user_id) 
            as a on user.id = a.user_id 
            $join (select user_id, min(enter_time) as firstContact,notes from user_notes where type != 'attempt' and author != 'Request Info' group by user_id) b on user.id = b.user_id
            join account_user au on user.id = au.user
            join user uu on uu.id = au.parent_user
            where user.role = 'customer' AND user.status != 'Disabled' AND $notResponded
            a.enter_time > '{$data['from']} 00:00:00' AND a.enter_time <= '{$data['to']} 23:59:59' $rep ) c WHERE firstContact >= assignTime ORDER BY assignTime";*/
            
            
            	/*$query = "select c.*, TIMESTAMPDIFF(MINUTE,assignTime,firstContact) AS responseTime,'{$rep_name}' as parent_user from 
            (select user.id, concat(user.firstname, ' ', user.lastname) as name, 
            case when user.businessname is NULL then concat(user.firstname, ' ', user.lastname) ELSE user.businessname end businessname, user.email, a.enter_time as assignTime, b.notes,b.firstContact, concat(uu.firstname, ' ', uu.lastname) as rep
            from user join 
            (select user_id, enter_time from user_notes where type like 'attempt' group by user_id) 
            as a on user.id = a.user_id 
            $join (select user_id, min(enter_time) as firstContact,notes from user_notes where type not like 'attempt' and author != 'Request Info' group by user_id) b on user.id = b.user_id
            join account_user au on user.id = au.user
            join user uu on uu.id = au.parent_user
            where user.role = 'customer' AND user.status != 'Disabled' AND $notResponded
            a.enter_time > '{$data['from']} 00:00:00' AND a.enter_time <= '{$data['to']} 23:59:59' $rep ) c WHERE firstContact >= assignTime ORDER BY assignTime";*/
           
            
				$res = $this->_db->fetchAll($query);
				if (isset($res) and count($res)>0)
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}
			}
		}
        return $result_ary;      
        
       
        
    }
    public function avg_response_time($data) {
        $result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	$rep = " AND au.parent_user = '$rep_id'";   	
            	
            	$query = "select count(id) as total, AVG(TIMESTAMPDIFF(MINUTE,assignTime,firstContact)) AS avgResponseTime,MAX(TIMESTAMPDIFF(MINUTE,assignTime,firstContact)) AS maxResponseTime,'{$rep_name}' as parent_user  from (select user.id, concat(user.firstname, ' ', user.lastname) as name, 
            case when user.businessname is NULL then concat(user.firstname, ' ', user.lastname) ELSE user.businessname end businessname, user.email, a.enter_time as assignTime, b.firstContact, concat(uu.firstname, ' ', uu.lastname) as rep
            from user join 
            (select user_id, enter_time from user_notes where notes like 'Change sales rep from%' group by user_id) 
            as a on user.id = a.user_id 
            join (select user_id, min(enter_time) as firstContact from user_notes where notes not like 'Change sales rep from%' and author != 'Request Info' group by user_id) b on user.id = b.user_id
            join account_user au on user.id = au.user
            join user uu on uu.id = au.parent_user
            where user.role = 'customer' AND user.status != 'Disabled' AND 
            user.created_time > '{$data['from']} 00:00:00' AND user.created_time <= '{$data['to']} 23:59:59' $rep) c WHERE firstContact > assignTime";
            	
            	$res = $this->_db->fetchAll($query);
				if (isset($res) and count($res)>0 and $res[0]['total']>0)
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}				
			}
		}
        return $result_ary; 
    } 
    /*================*/
 
    public function user_source($from, $to) {        
        return $this->_db->fetchAll("SELECT count(id) as count, source_text  from user 
            where imported = 'Contact Form' and source_text like '%business%' and 
            created_time >= '$from 00:00:00' AND created_time <= '$to 23:59:59'  group by source_text 
            order by count desc");        
    }
    public function total_contacts($from, $to) {
        $result = $this->_db->fetchRow("SELECT count(id) as total from user 
            where created_time >= '$from 00:00:00' AND created_time <= '$to 23:59:59' AND type !='Internal'");
        return $result?$result['total']:0;
    }
    
    /* Dariuz Rubin */
    public function accounts($data) {
        $result_ary = array();
        if ($data['by'] == 'By Customer Status')
        {
			// get info for Rep, Accounts, Prospects, Leads
			$attempt_type = strtolower($data['type']);
			$filter = "";
			if ($attempt_type == 'email')
				$filter = "user_notes.notes='email' and";
			else if ($attempt_type == 'phone')
				$filter = "user_notes.notes='phone' and";
			
			
			foreach($data['rep'] as $ind => $rep_pair )
            {
            	$rep_pair_ary = explode(',',$rep_pair);
            	if (count($rep_pair_ary)>1)
            	{
					$rep_id = $rep_pair_ary[0];
	            	$rep_name = $rep_pair_ary[1];
					$query = "select count(id) as count,user.type,'{$rep_name}' as parent_user from user left join user_notes on user.id = user_notes.user_id left join account_user on user.id = account_user.user where {$filter} user_notes.type='attempt' and user_notes.enter_time > '{$data['from']} 00:00:00' and user_notes.enter_time <='{$data['to']} 23:59:59' and user.role = 'customer' AND user.status != 'Disabled' and account_user.parent_user={$rep_id} group by type";
					$res = $this->_db->fetchAll($query);
					if (isset($res) and count($res)>0)
					{
						$result_ary[$ind] = $this->_db->fetchAll($query);	
					}
				}
			}
			
		}else if ($data['by'] == 'By Type of Contact')
		{
			// get info for Rep, Calls,Emails			
			foreach($data['rep'] as $ind => $rep_pair )
            {
            	$rep_pair_ary = explode(',',$rep_pair);
            	if (count($rep_pair_ary)>1)
            	{
					$rep_id = $rep_pair_ary[0];
	            	$rep_name = $rep_pair_ary[1];
					$query = "select count(id) as count,user_notes.notes as type,'{$rep_name}' as parent_user from user_notes left join user on user_notes.user_id = user.id left join account_user on user_notes.user_id = account_user.user where user_notes.type ='attempt' and user_notes.enter_time > '{$data['from']} 00:00:00' and user_notes.enter_time <='{$data['to']} 23:59:59' and user.role = 'customer' AND user.status != 'Disabled' and account_user.parent_user={$rep_id} group by user_notes.notes;";
					$res = $this->_db->fetchAll($query);
					if (isset($res) and count($res)>0)
					{
						$result_ary[$ind] = $this->_db->fetchAll($query);	
					}
				}
			}
		}
        return $result_ary;
    }
    
    /**
	* Get all events which were created between 2 days
	*/
    
     public function getCreatedEvents($data,$userId) {
     	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	$query = "SELECT count(customer_id) as created_events_cnt,'{$rep_name}' as parent_user FROM `follow_up_info`
		            left join account_user on follow_up_info.customer_id = account_user.user 
		            WHERE created_time >= '{$data['from']} 00:00:00' AND created_time <= '{$data['to']} 23:59:59' and account_user.parent_user={$rep_id} ";
				
				$res = $this->_db->fetchAll($query);
				if (isset($res) and count($res)>0 and $res[0]['created_events_cnt']>0)
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}
			}
		}        
        
        return $result_ary;
    }
    
    /**
	* Get all events which were scheduled between 2 days
	*/    
    public function getScheduledEvents($data,$userId) {
     	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	$query = "SELECT count(customer_id) as scheduled_events_cnt,'{$rep_name}' as parent_user FROM `follow_up_info`
		            left join account_user on follow_up_info.customer_id = account_user.user 
		            WHERE followup_time >= '{$data['from']} 00:00:00' AND followup_time <= '{$data['to']} 23:59:59' and account_user.parent_user={$rep_id} ";
				
				$res = $this->_db->fetchAll($query);
				if (isset($res) and count($res)>0 and $res[0]['scheduled_events_cnt']>0)
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}
			}
		}        
        
        return $result_ary;
    }
    
    /**
	* Get all events which were contacted actually in scheduled day
	*/    
    public function getContactedEvents($data,$userId) {
     	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	
            
           		 $query = "SELECT count(customer_id) as contacted_events_cnt,'{$rep_name}' as parent_user FROM `events`
		            left join account_user on events.customer_id = account_user.user 
		            WHERE start >= '{$data['from']} 00:00:00' AND start <= '{$data['to']} 23:59:59' AND (active = 0) and account_user.parent_user={$rep_id}";
		            
            
            	/*$query = "SELECT count(customer_id) as contacted_events_cnt,'{$rep_name}' as parent_user FROM `events`
		            left join account_user on events.customer_id = account_user.user 
		            WHERE start >= '{$data['from']} 00:00:00' AND start <= '{$data['to']} 23:59:59' AND (user_id = '$userId' OR public = 1) and account_user.parent_user={$rep_id} and (active=2 or active=4)";*/
				
				$res = $this->_db->fetchAll($query);
				if (isset($res) and count($res)>0 and $res[0]['contacted_events_cnt']>0)
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}
			}
		}          
        return $result_ary;
    }
    
    /**
	* Get all leads which are in reps
	*/    
    public function getLeadQueues($data) {
     	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	
            	$query = "select count(id) as count,'{$rep_name}' as parent_user from user left join account_user on user.id = account_user.user where account_user.parent_user = {$rep_id} and type ='Lead'";            	
				$res = $this->_db->fetchAll($query);
				if (isset($res))
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}
			}
		}          
        return $result_ary;
    }
    
     
    /**
	* Get all prospects which are in reps
	*/    
    public function getProspectdQueues($data) {
     	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	
            	$query = "select count(id) as count,'{$rep_name}' as parent_user from user left join account_user on user.id = account_user.user where account_user.parent_user = {$rep_id} and type ='Prospect'";            	
				$res = $this->_db->fetchAll($query);
				if (isset($res))
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}
			}
		}          
        return $result_ary;
    }
     
    /**
	* Get all accounts which are in reps
	*/    
    public function getAccountQueues($data) {
     	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	
            	$query = "select count(id) as count,'{$rep_name}' as parent_user from user left join account_user on user.id = account_user.user where account_user.parent_user = {$rep_id} and type ='Account'";            	
				$res = $this->_db->fetchAll($query);
				if (isset($res))
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}
			}
		}          
        return $result_ary;
    }
    
    
    
     /**
	* get leads who were assigned by Manager
	*/    
    public function getleads_Manager($data) {     	
        $leads_cnt = 0;          	
    	$query = "SELECT count(distinct user_id) as count from user_notes where author='Cory Nielsen' and notes like 'Change sales rep from%' and enter_time > '{$data['from']}  00:00:00' AND enter_time <= '{$data['to']}  23:59:59' ;";    	
		$res = $this->_db->fetchRow($query);
		if (isset($res) and $res['count']>0)
		{
			$leads_cnt =1;
		}			       
        return $leads_cnt;
    }
    
    /**
	* check if this lead has sale enters
	*/    
    public function get_sales_leads($user_id) {     	
        $is_sale_entered = 0;          	
    	$query = "SELECT count(user_id) as count from user_notes where user_id ={$user_id} and enter_sale_amount is not null";    	
		$res = $this->_db->fetchRow($query);
		if (isset($res) and $res['count']>0)
		{
			$is_sale_entered =1;
		}			       
        return $is_sale_entered;
    }
    
    /**
	* Get sale amounts for this lead
	*/    
    public function get_sales_amounts($user_id) {
     	$sale_amounts = 0;          	
    	$query = "SELECT sum(enter_sale_amount) as sale_amounts from user_notes where user_id ={$user_id} and enter_sale_amount is not null";    	
		$res = $this->_db->fetchRow($query);
		if (isset($res) and $res['sale_amounts']>0)
		{
			$sale_amounts =$res['sale_amounts'];
		}			       
        return $sale_amounts;
    }
    
    /**
	* Get Contacts betweeen month for only one rep
	*/    
    public function response_time_for_rep($data) {
    	$result_ary = array();
 		if (isset($data))
        {
        	$rep_pair_ary = explode(',',$data['rep_month']);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	$rep = " AND au.parent_user = '$rep_id'";      
            	      	
            	$query = "select c.*, TIMESTAMPDIFF(MINUTE,assignTime,firstContact) AS responseTime,'{$rep_name}' as parent_user from 
            (select user.id, concat(user.firstname, ' ', user.lastname) as name, 
            case when user.businessname is NULL then concat(user.firstname, ' ', user.lastname) ELSE user.businessname end businessname, user.email, a.enter_time as assignTime, b.notes,b.firstContact, concat(uu.firstname, ' ', uu.lastname) as rep
            from user join 
            (select user_id, enter_time from user_notes where notes like 'Change sales rep from%' group by user_id) 
            as a on user.id = a.user_id 
            JOIN (select user_id, min(enter_time) as firstContact,notes from user_notes where notes not like 'Change sales rep from%' and author != 'Request Info' group by user_id) b on user.id = b.user_id
            join account_user au on user.id = au.user
            join user uu on uu.id = au.parent_user
            where user.role = 'customer' AND user.status != 'Disabled' and
            a.enter_time > '{$data['from_month']}-01 00:00:00' AND a.enter_time <= '{$data['to_month']}-31 23:59:59' $rep ) c WHERE firstContact >= assignTime ORDER BY assignTime";            
                  
/*$query = "select c.*, TIMESTAMPDIFF(MINUTE,assignTime,firstContact) AS responseTime,'{$rep_name}' as parent_user from 
            (select user.id, concat(user.firstname, ' ', user.lastname) as name, 
            case when user.businessname is NULL then concat(user.firstname, ' ', user.lastname) ELSE user.businessname end businessname, user.email, a.enter_time as assignTime, b.notes,b.firstContact, concat(uu.firstname, ' ', uu.lastname) as rep
            from user join 
            (select user_id, enter_time from user_notes where type = 'atttempt' group by user_id) 
            as a on user.id = a.user_id 
            JOIN (select user_id, min(enter_time) as firstContact,notes from user_notes where type != 'atttempt' and author != 'Request Info' group by user_id) b on user.id = b.user_id
            join account_user au on user.id = au.user
            join user uu on uu.id = au.parent_user
            where user.role = 'customer' AND user.status != 'Disabled' and
            a.enter_time > '{$data['from_month']}-01 00:00:00' AND a.enter_time <= '{$data['to_month']}-31 23:59:59' $rep ) c WHERE firstContact >= assignTime ORDER BY assignTime"; */			
				$res = $this->_db->fetchAll($query);
				if (isset($res) and count($res)>0)
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}
			}
		}
        return $result_ary; 
    }
    
    /**
	* Get the leads count by source	
	*/
    public function leads_by_source($data) {
        $result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
   
            	$query = "SELECT source, count(id) as leads,{$rep_id} as rep,'{$data['from']}' as from_time,'{$data['to']}' as to_time,'{$rep_name}' as parent_user FROM `user` left join account_user on user.id = account_user.user WHERE created_time >= '{$data['from']} 00:00:00' AND created_time <= '{$data['to']} 23:59:59' AND account_user.parent_user = {$rep_id} AND (length(source)>0) and (type = 'Lead') group by source";
				
				$res = $this->_db->fetchAll($query);
				if (isset($res) and count($res)>0)				
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
					
				}
				
			}
		}
        return $result_ary;
    }    
    
     /**
	* Get the Prospect count by source	
	*/
    public function prospects_by_source($data) {
        $result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
   
            	$query = "SELECT source, count(id) as leads,{$rep_id} as rep,'{$data['from']}' as from_time,'{$data['to']}' as to_time,'{$rep_name}' as parent_user FROM `user` left join account_user on user.id = account_user.user WHERE created_time >= '{$data['from']} 00:00:00' AND created_time <= '{$data['to']} 23:59:59' AND account_user.parent_user = {$rep_id} AND (length(source)>0) and type = 'Prospect' group by source";
				
				$res = $this->_db->fetchAll($query);
				if (isset($res) and count($res)>0)				
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
					
				}
				
			}
		}
        return $result_ary;
    }    
    
     /**
	* Get the Account count by source	
	*/
    public function accouns_by_source($data) {
        $result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
   
            	$query = "SELECT source, count(id) as leads,{$rep_id} as rep,'{$data['from']}' as from_time,'{$data['to']}' as to_time,'{$rep_name}' as parent_user FROM `user` left join account_user on user.id = account_user.user WHERE created_time >= '{$data['from']} 00:00:00' AND created_time <= '{$data['to']} 23:59:59' AND account_user.parent_user = {$rep_id} AND (length(source)>0) and type = 'Account' group by source";
				
				$res = $this->_db->fetchAll($query);
				if (isset($res) and count($res)>0)				
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
					
				}
				
			}
		}
        return $result_ary;
    }    
    
      /**
	* Get the Account count by source	
	*/
    public function leadsource_accounts($data) {
        $result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];  				
   				
            
            	$query = "SELECT source, email,{$rep_id} as rep,created_time,businessname,concat(firstname, ' ', lastname) as name,'{$rep_name}' as parent_user FROM `user` left join account_user on user.id = account_user.user WHERE created_time >= '{$data['from']} 00:00:00' AND created_time <= '{$data['to']} 23:59:59' AND account_user.parent_user = {$rep_id} AND (length(source)>0) and (type = 'Account' or type = 'Lead' or type = 'Prospect')";
				
				$res = $this->_db->fetchAll($query);
				if (isset($res) and count($res)>0)				
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
					
				}
				
			}
		}
		
			
        return $result_ary;
    }    
    
     /**
	* Get all customers which have opportunity values
	*/    
    public function getAmountCustomers($data) {
     	$result_ary = array();
     	foreach($data['rep'] as $ind => $rep_pair )
        {
        	$rep_pair_ary = explode(',',$rep_pair);
        	if (count($rep_pair_ary)>1)
        	{
				$rep_id = $rep_pair_ary[0];
            	$rep_name = $rep_pair_ary[1];
            	
            	/*$customer_type = strtolower($data['type']);
				$filter = "";
				if (strcasecmp($customer_type,'Account')==0)
					$filter = " and type='Account' ";
				else if (strcasecmp($customer_type,'Prospect')==0)
					$filter = " and type='Prospect' ";
				else if (strcasecmp($customer_type,'Both')==0)
					$filter = " and (type='Prospect' or type='Account') ";*/
				
				/*$query = "select '{$rep_name}' as parent_user,businessname,email,type,created_time,concat(firstname, ' ', lastname) as name,opportunity from user left join account_user on user.id = account_user.user where opportunity>0 and created_time >= '{$data['from']} 00:00:00' AND created_time <= '{$data['to']} 23:59:59' and  account_user.parent_user = {$rep_id} $filter order by opportunity desc";  */
				$query = "select '{$rep_name}' as parent_user,(select concat(firstname, ' ', lastname)  from user where user.id = user_notes.user_id limit 1) as name,(select businessname  from user where user.id = user_notes.user_id limit 1) as businessname,enter_sale_amount,enter_time from user_notes left join account_user on user_notes.user_id = account_user.user where type = 'entersale' and enter_time >= '{$data['from']} 00:00:00' AND enter_time <= '{$data['to']} 23:59:59' and enter_sale_amount>0 and  account_user.parent_user = {$rep_id} $filter order by enter_time desc";  
              	
				$res = $this->_db->fetchAll($query);
				if (isset($res))
				{
					$result_ary[$ind] = $this->_db->fetchAll($query);	
				}
			}
		}          
        return $result_ary;
    }
    
    /*==================*/
}
