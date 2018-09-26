<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Merchant_tools extends front_template 
{

	function __construct()
	{
		parent::__construct();
		
		if($this->session->userdata('user_logged_in') != TRUE)
		{
			$this->session->set_userdata('last_uri', current_url());
			redirect('login');			
			exit;
		}
		redirect('market');			
		exit;
		$this->user_id = $this->session->userdata('user_id');	
	}

	function index()
	{	
		$data['title']  	= 'Merchant Tour';
		$data['section']	= 'merchant_tour';
		$this->main_view('merchant_tools/merchant_tour_view', $data);
	}
	
	function merchant_account()
	{
		$data['title']  		= 'Merchant Account';
		$data['section']		= 'view_merchant_account';
		$data['account_data'] 	= $this->merchant_account_model->get_single_record("user_id=".$this->user_id);
		$this->main_view('merchant_tools/merchant_tour_view', $data);
		
	}
	
	function add_merchant_account()
	{
		$data['title']   = 'Merchant Account';
		$data['section'] = 'add_merchant_account';
		
		if(!empty($_POST))
		{	
			$_POST 	= $this->security->xss_clean($_POST);
			
			//buisiness_name,website, person_responsible,buisiness_address,comp_reg_no,vat_no,mobile_no, office_no, buisiness_purpose
			
			$this->form_validation->set_rules('buisiness_name', 'buisiness name', 'required');
			$this->form_validation->set_rules('website', 'website', 'required|callback_valid_url_format');
			$this->form_validation->set_rules('person_responsible', 'person full name', 'required');
			$this->form_validation->set_rules('buisiness_address', 'buisiness address', 'required');
			$this->form_validation->set_rules('comp_reg_no', 'company registration number', 'required');
			$this->form_validation->set_rules('mobile_no', 'mobile number', 'required');
			$this->form_validation->set_rules('office_no', 'office number', 'required');
			$this->form_validation->set_rules('buisiness_purpose', 'buisiness purpose', 'required');
			if($this->form_validation->run() == TRUE)
			{
				$_POST['user_id'] = $this->user_id;
				$_POST['created_date'] = date('Y-m-d H:i:s');
				$_POST['updated_date'] = date('Y-m-d H:i:s');
				$_POST['ip_address'] = $_SERVER['REMOTE_ADDR'];
				$_POST['status'] = 0; 
				$this->merchant_account_model->add($_POST);
				$this->session->set_flashdata(array('success_msg' => 'Your merchant account created successfully. Site is verifying your account with sent by activation email'));
				redirect('merchant_tools/merchant_account');			
				exit;
			}
		}
		
		$this->main_view('merchant_tools/merchant_tour_view', $data);
	}
	
	function edit_merchant_account($merchant_account_id)
	{
		$data['title']  = 'Merchant Account';
		$data['section']='edit_merchant_account';
		
		$data['account_data'] = $this->merchant_account_model->get_single_record("md5(merchant_account_id)='".$merchant_account_id."'");
		
		if(!empty($_POST))
		{	
			$_POST 	= $this->security->xss_clean($_POST);
			
			//buisiness_name,website, person_responsible,buisiness_address,comp_reg_no,vat_no,mobile_no, office_no, buisiness_purpose
			
			$this->form_validation->set_rules('buisiness_name', 'buisiness name', 'required');
			$this->form_validation->set_rules('website', 'website', 'required|callback_valid_url_format');
			$this->form_validation->set_rules('person_responsible', 'person full name', 'required');
			$this->form_validation->set_rules('buisiness_address', 'buisiness address', 'required');
			$this->form_validation->set_rules('comp_reg_no', 'company registration number', 'required');
			$this->form_validation->set_rules('mobile_no', 'mobile number', 'required');
			$this->form_validation->set_rules('office_no', 'office number', 'required');
			$this->form_validation->set_rules('buisiness_purpose', 'buisiness purpose', 'required');
			if($this->form_validation->run() == TRUE)
			{
				$_POST['updated_date'] = date('Y-m-d H:i:s');
				$_POST['status'] = 0; 
				$this->merchant_account_model->edit($_POST,$data['account_data']->merchant_account_id);
				$this->session->set_flashdata(array('success_msg' => 'Your merchant account updated successfully. Site is verifying your account with sent by activation email'));
				redirect('merchant_tools/merchant_account');			
				exit;
			}
		}
		
		$this->main_view('merchant_tools/merchant_tour_view', $data);
	}
	
	function valid_url_format($str){
        $pattern = "|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i";
        if (!preg_match($pattern, $str)){
            $this->form_validation->set_message('valid_url_format', 'The website url you entered is not correctly formatted.');
            return FALSE;
        }
 
        return TRUE;
    } 
	
	function checkout_button()
	{
		$account_data = $this->merchant_account_model->get_single_record("user_id=".$this->user_id);
		if(empty($account_data))
		{
			$this->session->set_flashdata(array('error_msg' => 'You has not been created merchant account yet.'));
			redirect('merchant_tools/merchant_account');	
			exit;
		}
		else
		{
			if($account_data->status==0)
			{
				$this->session->set_flashdata(array('error_msg' => 'Your merchant account is not activated yet. Site is verifying your account with sent by activation email'));
				redirect('merchant_tools/merchant_account');	
				exit;
			}
		}

		$data['title']  		= 'User Accounts';
		$data['section']		= 'checkout_button';
		$data['currency_data'] 	= $this->crypto_currency_model->combox('crypto_currency','*',"status=1"); 
		
		if($_POST!=NULL)
		{
			$_POST 	= $this->security->xss_clean($_POST);
			
			$this->form_validation->set_rules('price', 'price', 'required|numeric');
			$this->form_validation->set_rules('description', 'description', 'required');
			
			if(!empty($_POST['return_url']))
				$this->form_validation->set_rules('return_url', 'return url', 'prep_url');
			
			if(!empty($_POST['cancel_url']))
				$this->form_validation->set_rules('cancel_url', 'cancel_url', 'prep_url');	
			
			if($this->form_validation->run() == TRUE)
			{
				$cryptdetails  = $this->crypto_currency_model->get_single_record("name='".$_POST['currency']."'");
				if($cryptdetails->currency==1)
				{
					$table      = "orders_btc".strtolower($_POST['currency'])."_backup";
					$coin_value = $_POST['price'];
					$price 		= $this->get_currency_value($table,$coin_value);
					if($price<=0)
					{
						$this->session->set_flashdata(array('error_msg' => 'Buy price not set to selected currency.'));
						redirect('merchant_tools/checkout_button');	
						exit;
					}
					
					$_POST['price']		= $price;
					$_POST['currency']	= "BTC";
				}
				
				$insert = $_POST;
				$insert['user_id'] 	= $this->user_id;
				$insert['date_added'] 	= date('Y-m-d H:i:s');
				$insert['ip_address'] 	= $_SERVER['REMOTE_ADDR'];
				//$insert['checkout_key'] = 
				$checkout_id = $this->merchant_checkout_model->add($insert);
				redirect('merchant_tools/get_button/');			
				exit;
			}
		}
		$this->main_view('merchant_tools/merchant_tour_view', $data);
	}
	
	function get_currency_value($table,$coin_value)
	{
		$price 		= 0;
		$details	= $this->orders_model->custom_query("SELECT price FROM $table order by order_id desc limit 0,1");
		if(!empty($details[0]->price))
			$price = $details[0]->price;
		else
		{
			$str   = strtoupper(str_replace(array("orders_","_backup"),array("",""),$table));
			$name  = substr($str,0,3)."/".substr($str,3,3);
			$details = $this->orders_model->custom_query("SELECT trading_id FROM tradings WHERE name='".$name."'");
			if(!empty($details[0]->trading_id))
			{
				$data = $this->orders_model->custom_query("SELECT buy_price FROM trading_values WHERE trading_id='".$details[0]->trading_id."'");
				if(!empty($data[0]->buy_price)) $price  = $data[0]->buy_price;
			}
		}
		if($price>0) $price = round($coin_value / $price,8); else $price = 0;
		return $price;
	}
	
	function get_button()
	{
		$data['title']  = 'User Accounts';
		$data['section']='checkout_button_view';
		
		$account_data = $this->merchant_account_model->get_single_record("user_id=".$this->user_id);
		if(empty($account_data))
		{
			$this->session->set_flashdata(array('error_msg' => 'You has not been created merchant account yet.'));
			redirect('merchant_tools/merchant_account');	
			exit;
		}
		else
		{
			if($account_data->status==0)
			{
				$this->session->set_flashdata(array('error_msg' => 'Your merchant account is not activated yet. Site is verifying your account with sent by activation email'));
				redirect('merchant_tools/merchant_account');	
				exit;
			}
		}
		
		
		$data['merchant_data'] = $this->merchant_checkout_model->get_single_record("user_id = ".$this->user_id." ORDER BY checkout_id DESC LIMIT 0,1");
		if(empty($data['merchant_data']))
		{
			redirect('merchant_tools/checkout_button/');			
			exit;
		}
		$this->main_view('merchant_tools/merchant_tour_view', $data);
		
	}
}

?>