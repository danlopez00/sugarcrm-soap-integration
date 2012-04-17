<?php

/**************************************************************************\
* This program is free software; you can redistribute it and/or modify it  *
* under the terms of the GNU General Public License as published by the    *
* Free Software Foundation; either version 2 of the License, or (at your   *
* option) any later version.                                               *
\**************************************************************************/
	
/**
 * SugarCRM Lead via SOAP class
 * This class requires PHP5 with native SOAP library
 * SugarCRM has a conflict with PHP5 and NuSOAP (so dont use it)
 * Dont use SOAP compression as SugarCRM has issue with this in v5.0
 * @example var_dump($sugar_client->__soapCall('get_entry', $get_entry_params));
 * This will return a var dump of fields to grab or insert to
 * 
 * This is an example Register class that registers a product to SugarCRM
 * The example uses the post data to put into the description
 */


class Register {
	public $errorNum = "";
	public $errorMsg = "";
	
	public function __construct(){
		//nothing here yet
	}
	
	/**
	* processForm function
	* This function takes post data from index.php
	* and will insert into db using SOAPClient (PHP5+ only)
	*
	* @param unknown_type $postdata
	* @return result of error or successful soap insert
	*/
	
	public function processForm($postdata){
		
		$combined_desc = "Model Number: ".$postdata['zvue_model']."\n--\n".
						"Serial Number: ".$postdata['serial_number']."\n--\n".
						"Purchase Date: ".$postdata['purchase_date']."\n--\n".
						"Screen Rating: ".$postdata['screen']."\n--\n".
						"Battery Life: ".$postdata['battery']."\n--\n".
						"Sound Quality: ".$postdata['sound']."\n--\n".
						"Appearance: ".$postdata['appearance']."\n--\n".
						"Performance: ".$postdata['performance']."\n--\n".
						"Gender: ".$postdata['gender']."\n--\n".
						"How did you learn about our company: ".$postdata['learn_prod']."\n--\n".
						"What do you like about your product: ".$postdata['like_prod']."\n--\n".
						"What do you dislike about your product: ".$postdata['dislike_prod'];

		// set up options array
		$sugar_client = new SoapClient(null, array(        
			'location' => 'https://crm.yourcompany.com/soap.php'
			,'uri' => 'http://www.sugarcrm.com/sugarcrm'
			,'soap_version'   => SOAP_1_1 //SOAP_1_2 - 1.2 not supported by sugar nusoap
			,'trace' => 1
			,'exceptions' => 0
			// ,'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 5
			));
		  
		//login to sugarcrm
		$login_creds = array(
			'user_auth' => array(
				'user_name' => 'my_username',
				'password' => md5('my_password'),
				'version' => '.01'
			),
			'application_name' => 'companyproducts');
	
		// grab valid session
		$session_id = $sugar_client->__soapCall('login',$login_creds,NULL,NULL);
		
		/*var_dump($session_id);
		$get_entry_params = array(
			'session' => $session_id->id,
			'module_name' => 'Leads',  
			'id'=>'7d0f25ae-11ab-4421-d67b-47fe9089da58'//lead
			//'id'=>'db77961d-f77c-364a-9ac7-4803ee562add'//account
			//'id'=>'4f8935c8-2342-f52e-cdd1-47fe9a766f14'//contact
		);
		
		var_dump($sugar_client->__soapCall('get_entry', $get_entry_params));
		*/

		$errorNo = $session_id->error;
		
		if($errorNo->number == '0'){
			$sess = $session_id->id;
			
			$set_account_params = array(
				'session' => $sess,
				'module_name' => 'Accounts',
				'name_value_list'=>array(
				array('name'=>'name','value'=>$postdata['first_name'].' '.$postdata['last_name']),
				array('name'=>'phone_office', 'value'=>$postdata['phone_work']),
				array('name'=>'email1', 'value'=>$postdata['email_address']),
				array('name'=>'billing_address_street', 'value'=>$postdata['address_l1'].' '.$postdata['apt_no']),
				array('name'=>'billing_address_city', 'value'=>$postdata['city']),
				array('name'=>'billing_address_state', 'value'=>$postdata['state']),
				array('name'=>'billing_address_postalcode', 'value'=>$postdata['postal_code']),
				array('name'=>'billing_address_country', 'value'=>$postdata['country_code']),
				array('name'=>'company_account_type_c', 'value'=>'Users'),
				array('name'=>'description','value'=> $combined_desc),
				array('name'=>'team_id','value'=> 'b26781c7-20fa-7fb3-0df5-48350096ca40'),
				array('name'=>'assigned_user_id', 'value'=>'f0bdcbe1-b7ea-d0b4-87f4-480fbb402142')
				)
			);
			
			$insertAccount = $sugar_client->__soapCall('set_entry', $set_account_params);
			
			$set_contact_params = array(
				'session' => $sess,
				'module_name' => 'Contacts',
				'name_value_list'=>array(
				array('name'=>'first_name','value'=>$postdata['first_name']),
				array('name'=>'last_name','value'=>$postdata['last_name']),
				array('name'=>'phone_work', 'value'=>$postdata['phone_work']),
				array('name'=>'email1', 'value'=>$postdata['email_address']),
				array('name'=>'primary_address_street', 'value'=>$postdata['address_l1'].' '.$_POST['apt_no']),
				array('name'=>'primary_address_city', 'value'=>$postdata['city']),
				array('name'=>'primary_address_state', 'value'=>$postdata['state']),
				array('name'=>'primary_address_postalcode', 'value'=>$postdata['postal_code']),
				array('name'=>'primary_address_country', 'value'=>$postdata['country_code']),
				array('name'=>'company_account_type_c', 'value'=>'Users'),
				array('name'=>'account_name','value'=>$postdata['first_name'].' '.$postdata['last_name']),
				array('name'=>'account_id','value'=> $insertAccount->id),
				array('name'=>'team_id','value'=> 'b26781c7-20fa-7fb3-0df5-48350096ca40'),
				array('name'=>'assigned_user_id', 'value'=>'f0bdcbe1-b7ea-d0b4-87f4-480fbb402142')
				)
			);
			
			$insertContact = $sugar_client->__soapCall('set_entry', $set_contact_params);
			
			$set_lead_params = array(
				'session' => $sess,
				'module_name' => 'Leads',
				'name_value_list'=>array(
				array('name'=>'first_name','value'=>$postdata['first_name']),
				array('name'=>'last_name','value'=>$postdata['last_name']),
				array('name'=>'status', 'value'=>'Converted'),
				array('name'=>'phone_work', 'value'=>$postdata['phone_work']),
				array('name'=>'email1', 'value'=>$postdata['email_address']),
				array('name'=>'primary_address_street', 'value'=>$_POST['address_l1'].' '.$postdata['apt_no']),
				array('name'=>'primary_address_city', 'value'=>$postdata['city']),
				array('name'=>'primary_address_state', 'value'=>$postdata['state']),
				array('name'=>'primary_address_postalcode', 'value'=>$postdata['postal_code']),
				array('name'=>'primary_address_country', 'value'=>$postdata['country_code']),
				array('name'=>'converted', 'value'=> '1'),
				array('name'=>'company_lead_type_c', 'value'=>'Users'),
				array('name'=>'account_name','value'=>$postdata['first_name'].' '.$postdata['last_name']),
				array('name'=>'lead_source','value'=>'Web Site'),
				array('name'=>'description','value'=> $combined_desc),
				array('name'=>'account_id','value'=> $insertAccount->id),
				array('name'=>'contact_id','value'=> $insertContact->id),
				array('name'=>'team_id','value'=> 'b26781c7-20fa-7fb3-0df5-48350096ca40'),
				array('name'=>'assigned_user_id', 'value'=>'f0bdcbe1-b7ea-d0b4-87f4-480fbb402142')
				)
			);
			
			$insertLead = $sugar_client->__soapCall('set_entry', $set_lead_params);
			$this->errorNum = "0";
			$this->errorMsg = "You have successfully registered your product. Enjoy!";
		}
		else{
			$this->errorNum = "1";
			$this->errorMsg = "Could not connect";
		}
		
		$result = array("errno" => $this->errorNum, "errmsg" => $this->errorMsg);
		
		return $result;
	}
}