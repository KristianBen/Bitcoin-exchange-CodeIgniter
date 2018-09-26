<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Settings extends front_template 
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
		$this->group_ids 	= $this->session->userdata('group_ids');
		$this->user_id 		= $this->session->userdata('user_id');	
		$this->wintaname  	= $this->session->userdata('wintaname');
	}

	function index()
	{	
		$data['title']  = 'User Settings';
		$user_id = $this->user_id;	
		if($_POST!=NULL)
		{
			$_POST = $this->security->xss_clean($_POST);
			$insert_data = $_POST;
			$insert_data['user_id'] = $user_id;
			$user_settings_info = $this->user_settings_model->get_single_record("user_id=".$user_id);
			if(empty($user_settings_info))
				$this->user_settings_model->add($insert_data);
			else
				$this->user_settings_model->edit($insert_data,array("user_id"=>$user_id));	
			
			$insert_user_data['def_currency'] = $_POST['def_currency'];
			$this->user_model->edit($insert_user_data, array("user_id"=>$user_id));
			
			$this->session->set_flashdata(array('success_msg' => 'Your account settings saved successfully.'));
			redirect('settings');			
			exit;
		}
		$data['section']='user_settings';
		$data['user_info'] = $this->user_model->get_single_record("user_id=".$user_id);
		$data['user_settings_info']	= $this->user_settings_model->get_single_record("user_id=".$user_id);
		$data['time_zone_data'] = $this->user_settings_model->combox('timezones','*','timezone_id!=0',"order by time_zone");
		$data['currencys'] 	= $this->crypto_currency_model->combox("crypto_currency","*","currency=1 AND status=1");
		
		$this->main_view('account/user_settings_view', $data);
	}
	
	
	function controlpanel()
	{	
		if(strpos($this->group_ids,",1,")===false && strpos($this->group_ids,",10,")===false)
		{
			redirect('settings');			
			exit;
		}
		
		$data['title']  = 'Control Panel';
		if($_POST!=NULL)
		{
			$_POST = $this->security->xss_clean($_POST);
			if($_POST['bann_user']=='bann')
			{
				if($_POST['chat_hours']<=0) $_POST['chat_hours']='';
				$this->form_validation->set_rules('chat_hours','Hours', 'required|callback_hours_check');
				$this->form_validation->set_rules('chat_nickname','Nickname', 'required');
				if($_POST['chat_nickname']!='') $this->form_validation->set_rules('chat_nickname','Nickname', 'required|callback_nickname_check');
				if($this->form_validation->run() == TRUE)
				{	
					$user_details = $this->user_model->get_single_record("chat_nickname='".$_POST['chat_nickname']."'");
					
					$update_data['chat_banned_by'] 	 = $this->user_id;
					$update_data['chat_hours'] 		 = $_POST['chat_hours'];
					$update_data['chat_banned_from'] = date("Y-m-d H:i:s");
					$this->user_model->edit_member($update_data,$user_details->user_id);
					$this->session->set_flashdata(array('success_msg' => 'Nickname "'.$_POST['chat_nickname'].'" banned for '.$_POST['chat_hours'].' hours.'));
					redirect('settings/controlpanel');			
					exit;
				}
			}
			if($_POST['bann_user']=='Add Note')
			{
				$this->form_validation->set_rules('message','Note text', 'required');
				if($this->form_validation->run() == TRUE)
				{	
					$insert_message_data['created_by'] 	= $this->user_id;
					$insert_message_data['message'] 	= $_POST['message'];
					$insert_message_data['created_on'] 	= date("Y-m-d H:i:s");
					$insert_message_data['ipaddress'] 	= $_SERVER['REMOTE_ADDR'];
					$this->messages_model->add($insert_message_data);
					$this->session->set_flashdata(array('success_msg' => 'Note text added successfully.'));
					redirect('settings/controlpanel');			
					exit;
				}
			}
		}
		$data['section'] 			= 'controlpanel';
		$data['user_info'] 			= $this->user_model->get_single_record("user_id=".$this->user_id);
		$data['user_settings_info']	= $this->user_settings_model->get_single_record("user_id=".$this->user_id);
		$data['banned_users']  		= $this->user_model->combox('users','*',"chat_hours>0 AND chat_nickname!='' order by chat_nickname");
		$data['messages']  			= $this->messages_model->combox('messages','*',"message !='' order by created_on DESC");
		
		$data['chat_users']  		= $this->user_model->combox('users','chat_nickname,first_name,last_name,user_id',"(group_ids LIKE '%,1,%' OR group_ids LIKE '%,10,%') AND user_id!='".$this->user_id."' AND status=1 order by first_name");
		
		$data['user_id_to'] = $user_id_to = '';
		$query = "select user_id_to from user_private_talks where user_id='".$this->user_id."' order by date_added DESC LIMIT 0,1";
		$data['usertalks'] = array_reverse($this->user_private_talks_model->custom_query($query));
		if($data['usertalks']!=NULL) $data['user_id_to'] = $user_id_to = $data['usertalks'][0]->user_id_to;
		
		$query = "select user_private_talks.*,username,chat_nickname,first_name,last_name,avtar,date_added,group_ids from  user_private_talks left join users on user_private_talks.user_id=users.user_id where users.user_id>0 
		AND ((user_private_talks.user_id='".$this->user_id."' AND user_id_to='".$user_id_to."') OR (user_private_talks.user_id='".$user_id_to."' AND user_id_to='".$this->user_id."')) order by user_talk_id desc limit 0,100";
		$data['usertalks'] = array_reverse($this->user_private_talks_model->custom_query($query));
		
		$this->main_view('account/control_panel_view', $data);
	}
	
	function nickname_check()
	{
		$_POST 			= $this->security->xss_clean($_POST);
		$chat_nickname  = $_POST['chat_nickname'];
		$details   	= $this->user_model->get_single_record(" chat_nickname='".$chat_nickname."'  ");
		if($details=="0")
		{
			$this->form_validation->set_message('nickname_check', 'Nickname not exists, try another.');
			return FALSE;
		}
	}
	
	function hours_check()
	{
		$_POST 		= $this->security->xss_clean($_POST);
		$chat_hours = $_POST['chat_hours'];
		$explode 	= explode(":",$chat_hours);
		$is_valid = 1;
		if(sizeof($explode)!=3) 
			$is_valid  = 0;
		else
		{
			if($explode[0]<0) $is_valid  = 0;
			if($explode[1]<0 || $explode[1]>59) $is_valid  = 0;
			if($explode[2]<0 || $explode[2]>59) $is_valid  = 0;
		}
		if($is_valid==0)
		{
			$this->form_validation->set_message('hours_check', 'Invalid format of banned hours.');
			return FALSE;
		}
	}
	
	function unbanneduser($user_id)
	{
		if($this->input->is_ajax_request()) 
		{
			$user_id = $_POST['user_id'];
			if($user_id>=0)
			{
				$details  = $this->user_model->get_single_record(" user_id='".$user_id."'  ");
				if($details!="0")
				{
					$update_data['chat_hours'] 		 = 0;
					$update_data['chat_banned_from'] = '';
					$this->user_model->edit_member($update_data,$user_id);
					exit;
				}
			}
		}
		/*if($user_id>=0)
		{
			$details  = $this->user_model->get_single_record(" user_id='".$user_id."'  ");
			if($details!="0")
			{
				$update_data['chat_hours'] 		 = 0;
				$update_data['chat_banned_from'] = '';
				$this->user_model->edit_member($update_data,$user_id);
				$this->session->set_flashdata(array('success_msg' => 'Nickname "'.$details->chat_nickname.'" unbanned successfully.'));
				redirect('settings/controlpanel');
				exit;
			}
		}	
		$this->session->set_flashdata(array('delete_error' => 'Invalid request.'));
		redirect('settings/controlpanel');
		exit;*/
	}//change_status
	
	function deletenotetext()
	{
		if($this->input->is_ajax_request()) 
		{
			$message_id = $_POST['message_id'];
			if($message_id>=0)
			{
				$details  = $this->messages_model->get_single_record(" message_id='".$message_id."'  ");
				if($details!="0")
				{
					$this->messages_model->delete('message_id',$message_id);
					//$this->session->set_flashdata(array('success_msg' => 'Message deleted successfully.'));
					//redirect('settings/controlpanel');
					//exit;
				}
			}	
		}
		//$this->session->set_flashdata(array('delete_error' => 'Invalid request.'));
		//redirect('settings/controlpanel');
		//exit;
	}//change_status
	
	function accountfundhistorys($user_id=NULL)
	{
		$currencys 	= $this->crypto_currency_model->combox("crypto_currency","*","status=1");
		$whereuser  = ''; if($user_id>0) $whereuser = "AND user_id='".$user_id."' ";
		$members 	= $this->user_model->combox('users','user_id,accnumber,email,status,wallet_name', "status=1 $whereuser");
		foreach($members as $rowmember)
		{
			$user_id 	 = $rowmember->user_id;
			$wallet_name = $rowmember->wallet_name;
			foreach($currencys as $rowcurrency)
			{
				//Initial History
				$fund_type = $rowcurrency->name;
				$history_details = $this->fund_history_model->get_single_record("user_id='".$user_id."' AND fund_type='".$fund_type."' ORDER BY fund_history_id DESC LIMIT 0,1");
				if($history_details=="0") 
				{
					$amount = 0;
					$fund_details = $this->funds_model->get_single_record("user_id='".$user_id."' AND fund_type='".$fund_type."'");
					if($fund_details!="0") $amount = $fund_details->fund;
					$insert_fund_history['user_id'] 	= $user_id;
					$insert_fund_history['type'] 		= 1;
					$insert_fund_history['fund_type'] 	= $fund_type;
					$insert_fund_history['amount'] 		= $amount;
					$insert_fund_history['note'] 		= "start#";
					$insert_fund_history['date'] 		= date("Y-m-d H:i:s");
					$insert_fund_history['txn_count'] 	= 0;
					$this->fund_history_model->add($insert_fund_history);
					
					$current_date = $insert_fund_history['date'];
				}
				else
					$current_date = $history_details->date;
				//Initial History
				
				//ORDERS TRADED
				$trading_details = $this->trading_model->combox("tradings","*","name LIKE '%".$fund_type."/%' AND status=1");
				foreach($trading_details as $rowtradings)
				{
					$table_name = "orders_".strtolower(str_replace("/","",$rowtradings->name))."_backup";
					$pair = $rowtradings->name;
					
					$history_query 	= "user_id='".$user_id."' AND fund_type='".$fund_type."' AND pair='".$pair."' AND note LIKE 'order#%' ORDER BY txn_count DESC LIMIT 0,1";
					$history_details = $this->fund_history_model->get_single_record($history_query);
					$last_order_id = 0;if($history_details!="0") $last_order_id = $history_details->txn_count;
					
					$query  = $this->db->query("SELECT * FROM $table_name WHERE user_id ='".$user_id."' 
					AND date_created>='".$current_date."' AND order_id>$last_order_id AND ref_order_id>0 ORDER BY date_created ASC");
					$orders = $query->result();
					foreach($orders as $roworder)
					{
						$amount = $roworder->amount; if($roworder->type==1) $amount = $roworder->amount - $roworder->fee;
						$insert_fund_history['user_id'] 	= $user_id;
						$insert_fund_history['type'] 		= $roworder->type;
						$insert_fund_history['fund_type'] 	= $fund_type;
						$insert_fund_history['amount'] 		= $amount;
						$insert_fund_history['note'] 		= "order#".$roworder->order_id;
						$insert_fund_history['pair'] 		= $pair;
						$insert_fund_history['date'] 		= $roworder->date_created;
						$insert_fund_history['txn_count'] 	= $roworder->order_id;
						$this->fund_history_model->add($insert_fund_history);
						
						//Who Sold or Baught
						$query  = $this->db->query("SELECT * FROM $table_name WHERE order_id ='".$roworder->ref_order_id."'");
						$parentorders = $query->result();
						if($roworder->type==1) $type = 2; else $type = 1;
						$insert_fund_history['user_id'] 	= $parentorders[0]->user_id;
						$insert_fund_history['type'] 		= $type;
						$insert_fund_history['fund_type'] 	= $fund_type;
						$insert_fund_history['amount'] 		= $roworder->amount;
						$insert_fund_history['note'] 		= "order#".$roworder->order_id;
						$insert_fund_history['pair'] 		= $pair;
						$insert_fund_history['date'] 		= $roworder->date_created;
						$insert_fund_history['txn_count'] 	= $roworder->order_id;
						$this->fund_history_model->add($insert_fund_history);
						
					}
				}
				
				$trading_details = $this->trading_model->combox("tradings","*","name LIKE '%/".$fund_type."%' AND status=1");
				foreach($trading_details as $rowtradings)
				{
					$table_name = "orders_".strtolower(str_replace("/","",$rowtradings->name))."_backup";
					$pair = $rowtradings->name;
					
					$history_query 	= "user_id='".$user_id."' AND fund_type='".$fund_type."' AND pair='".$pair."' AND note LIKE 'order#%' ORDER BY txn_count DESC LIMIT 0,1";
					$history_details = $this->fund_history_model->get_single_record($history_query);
					$last_order_id = 0;if($history_details!="0") $last_order_id = $history_details->txn_count;
					
					$query  = $this->db->query("SELECT * FROM $table_name WHERE user_id ='".$user_id."' 
					AND date_created>='".$current_date."' AND order_id>$last_order_id AND ref_order_id>0 ORDER BY date_created ASC");
					$orders = $query->result();
					foreach($orders as $roworder)
					{
						if($roworder->type==1) $type = 2; else $type = 1;
						$insert_fund_history['user_id'] 	= $user_id;
						$insert_fund_history['type'] 		= $type;
						$insert_fund_history['fund_type'] 	= $fund_type;
						$insert_fund_history['amount'] 		= $roworder->total;
						$insert_fund_history['note'] 		= "order#".$roworder->order_id;
						$insert_fund_history['pair'] 		= $pair;
						$insert_fund_history['date'] 		= $roworder->date_created;
						$insert_fund_history['txn_count'] 	= $roworder->order_id;
						$this->fund_history_model->add($insert_fund_history);
						
						//Who Sold or Baught
						$query  = $this->db->query("SELECT * FROM $table_name WHERE order_id ='".$roworder->ref_order_id."'");
						$parentorders = $query->result();
						$amount = $parentorders[0]->total; if($roworder->type==1) $amount = $parentorders[0]->total - $parentorders[0]->fee;
						$insert_fund_history['user_id'] 	= $parentorders[0]->user_id;
						$insert_fund_history['type'] 		= $roworder->type;
						$insert_fund_history['fund_type'] 	= $fund_type;
						$insert_fund_history['amount'] 		= $amount;
						$insert_fund_history['note'] 		= "order#".$roworder->order_id;
						$insert_fund_history['pair'] 		= $pair;
						$insert_fund_history['date'] 		= $roworder->date_created;
						$insert_fund_history['txn_count'] 	= $roworder->order_id;
						$this->fund_history_model->add($insert_fund_history);
						
					}
				}
				//ORDERS TRADED
				
				//TRANSACTIONS
				$where = "user_id='".$user_id."' AND fund_type='".$fund_type."' AND";
				$details = $this->fund_history_model->get_single_record("$where note LIKE 'fundtrans#%' ORDER BY txn_count DESC LIMIT 0,1");
				$last_fund_btc_trn_id = 0;if($details!="0") $last_fund_btc_trn_id = $details->txn_count;
				$query  = $this->db->query("SELECT fund_btc_trn_id,trn_type,send_amount,date_added FROM fund_btc_transactions WHERE $where fund_btc_trn_id>$last_fund_btc_trn_id AND date_added>='".$current_date."' AND (status=1 OR status=3) ORDER BY date_added ASC");
				$fundtransactions = $query->result();
				foreach($fundtransactions as $rowtrans)
				{
					$insert_fund_history['user_id'] 	= $user_id;
					$insert_fund_history['type'] 		= $rowtrans->trn_type;
					$insert_fund_history['fund_type'] 	= $fund_type;
					$insert_fund_history['amount'] 		= $rowtrans->send_amount;
					$insert_fund_history['note'] 		= "fundtrans#".$rowtrans->fund_btc_trn_id;
					$insert_fund_history['date'] 		= $rowtrans->date_added;
					$insert_fund_history['txn_count'] 	= $rowtrans->fund_btc_trn_id;
					$this->fund_history_model->add($insert_fund_history);
				}
				//TRANSACTIONS
				
				//BF CODES
				$where = "user_id='".$user_id."' AND fund_type='".$fund_type."' AND";
				$details = $this->fund_history_model->get_single_record("$where note LIKE 'bfcode#%' ORDER BY txn_count DESC LIMIT 0,1");
				$last_bf_code_id = 0;if($details!="0") $last_bf_code_id = $details->txn_count;
				$query  = $this->db->query("SELECT bf_code_id,amount,confirmed_date,user_id_to FROM  user_bfcodes WHERE $where bf_code_id>$last_bf_code_id AND confirmed_date>='".$current_date."' AND status=2 ORDER BY confirmed_date ASC");
				$bfcodes = $query->result();
				foreach($bfcodes as $rowcodes)
				{
					$insert_fund_history['user_id'] 	= $user_id;
					$insert_fund_history['type'] 		= 2;
					$insert_fund_history['fund_type'] 	= $fund_type;
					$insert_fund_history['amount'] 		= $rowcodes->amount;
					$insert_fund_history['note'] 		= "bfcode#".$rowcodes->bf_code_id;
					$insert_fund_history['date'] 		= $rowcodes->confirmed_date;
					$insert_fund_history['txn_count'] 	= $rowcodes->bf_code_id;
					$this->fund_history_model->add($insert_fund_history);
					
					//Who REDEEMED
					$insert_fund_history['user_id'] 	= $rowcodes->user_id_to;
					$insert_fund_history['type'] 		= 1;
					$insert_fund_history['fund_type'] 	= $fund_type;
					$insert_fund_history['amount'] 		= $rowcodes->amount;
					$insert_fund_history['note'] 		= "bfcode#".$rowcodes->bf_code_id;
					$insert_fund_history['date'] 		= $rowcodes->confirmed_date;
					$insert_fund_history['txn_count'] 	= $rowcodes->bf_code_id;
					$this->fund_history_model->add($insert_fund_history);
					
				}
				//BF CODES
				
				
				//WALLET MOVES
				$where 	 = "user_id='".$user_id."' AND fund_type='".$fund_type."' AND ";
				$details = $this->fund_history_model->get_single_record("$where note LIKE 'move#%' ORDER BY txn_count DESC LIMIT 0,1");
				$last_move_id = 0;if($details!="0") $last_move_id = $details->txn_count;
				$where .= "move_id>$last_move_id AND move_date>='".$current_date."'";
				$query  = $this->db->query("SELECT move_id,amount,move_date,user_id_to FROM user_moves WHERE $where ORDER BY move_date ASC");
				$walletmoves = $query->result();
				foreach($walletmoves as $rowmoves)
				{
					$insert_fund_history['user_id'] 	= $user_id;
					$insert_fund_history['type'] 		= 2;
					$insert_fund_history['fund_type'] 	= $fund_type;
					$insert_fund_history['amount'] 		= $rowmoves->amount;
					$insert_fund_history['note'] 		= "move#".$rowmoves->move_id;
					$insert_fund_history['date'] 		= $rowmoves->move_date;
					$insert_fund_history['txn_count'] 	= $rowmoves->move_id;
					$this->fund_history_model->add($insert_fund_history);
					
					//Who REDEEMED
					$insert_fund_history['user_id'] 	= $rowmoves->user_id_to;
					$insert_fund_history['type'] 		= 1;
					$insert_fund_history['fund_type'] 	= $fund_type;
					$insert_fund_history['amount'] 		= $rowmoves->amount;
					$insert_fund_history['note'] 		= "move#".$rowmoves->move_id;
					$insert_fund_history['date'] 		= $rowmoves->move_date;
					$insert_fund_history['txn_count'] 	= $rowmoves->move_id;
					$this->fund_history_model->add($insert_fund_history);
				}
				//WALLET MOVES
				
				
				//WALLET TRANSACTIONS
				if($rowcurrency->currency==0) $this->getaccounttransactions($user_id,$wallet_name,$fund_type,$current_date);
				//WALLET TRANSACTIONS
			}
		}
	}
	
	function getaccounttransactions($user_id=NULL,$wallet_name=NULL,$fund_type=NULL,$history_date=NULL)
	{
		if($user_id!=NULL && $wallet_name!=NULL && $fund_type!=NULL && $history_date!=NULL)
		{
			$cryptdetails = $this->crypto_currency_model->get_single_record("name='".$fund_type."'");
			if($cryptdetails->currency==0)
			{
				$history_details = $this->fund_history_model->get_single_record("user_id='".$this->user_id."' AND fund_type='".$fund_type."' AND note LIKE 'txid#%' ORDER BY txn_count DESC LIMIT 0,1");
				$txn_count = 0; if($history_details!="0") $txn_count = $history_details->txn_count;
				
				$model 	 			= "wallet_".strtolower($fund_type)."_model"; 
				$curr_txn_count 	= $txn_count;
				$listtransactions 	= $this->$model->connect("listtransactions",array($wallet_name,50));
				if(is_array($listtransactions))
				{
					for($i=$txn_count;$i<sizeof($listtransactions);$i++)
					{
						$curr_txn_count = $curr_txn_count + 1;
						$datetime 	 	= date('r', $listtransactions[$i]['time']);
						$amount   	 	= $listtransactions[$i]['amount'];
						$txid		 	= $listtransactions[$i]['txid'];
						$txndatetime 	= strtotime($datetime);
						if($txndatetime>=strtotime($history_date))
						{
							if($listtransactions[$i]['category']=="receive" || $listtransactions[$i]['category']=="send")
							{
								if($listtransactions[$i]['category']=="receive") $type = 1;
								if($listtransactions[$i]['category']=="send")	 $type = 2;
								$note = 'order#'; if($txid!='') $note = 'txid#'.$txid;
								
								$insert_fund_history['user_id'] 	= $user_id;
								$insert_fund_history['type'] 		= $type;
								$insert_fund_history['fund_type'] 	= $fund_type;
								$insert_fund_history['amount'] 		= $amount;
								$insert_fund_history['note'] 		= $note;
								$insert_fund_history['date'] 		= date("Y-m-d H:i:s",strtotime($datetime));
								$insert_fund_history['txn_count'] 	= $curr_txn_count;
								$this->fund_history_model->add($insert_fund_history);
							}
						}//if
					}//for
				}//if wallet error
			}
		}//if wallet_name
	}
	
	function statement()
	{	
		if(strpos($this->group_ids,",1,")===false && strpos($this->group_ids,",10,")===false)
		{
			redirect('settings');			
			exit;
		}
		
		$this->accountfundhistorys($this->user_id);
		
		$fund_type 	= (!empty($_GET['fund_type'])) ? $_GET['fund_type'] : 'BTC';
		$type 		= (!empty($_GET['type'])) ? $_GET['type'] : '';
		$where 		= "AND fund_type='".str_replace("'","\'",$fund_type)."' ";
		if($type!='') $where .= "AND type='".str_replace("'","\'",$type)."'";
		
		$data['title']  			= 'Statement';
		$data['section'] 			= 'statement';
		$data['user_info'] 			= $this->user_model->get_single_record("user_id=".$this->user_id);
		$data['user_settings_info']	= $this->user_settings_model->get_single_record("user_id=".$this->user_id);
		$data['banned_users']  		= $this->user_model->combox('users','*',"chat_hours>0 AND chat_nickname!='' order by chat_nickname");
		$data['messages']  			= $this->messages_model->combox('messages','*',"message !='' order by created_on ASC");
		$data['transactions']  		= $this->fund_history_model->combox('fund_history','*',"user_id='".$this->user_id."' AND note NOT LIKE 'start#%' $where order by date ASC");
		$data['currencys'] = $this->crypto_currency_model->combox("crypto_currency","*","status=1 ORDER BY currency ASC,name ASC");
		$this->main_view('account/statement_view', $data);
	}
	
	
	function email_remove()
	{
		$user_email_id = $this->uri->segment(3);
		$emails	= $this->user_emails_model->get_single_record("user_id='".$this->user_id."' AND user_email_id='".$user_email_id."'");
		if($emails!=NULL)
		{
			$this->user_emails_model->delete('user_email_id',$emails->user_email_id);
			$this->session->set_flashdata(array('success_msg' => 'Email removed successfully.'));
			redirect('settings/details');			
			exit;
		}
		else
		{
			$this->session->set_flashdata(array('error_msg' => 'Invalid request.'));
			redirect('settings/details');			
			exit;
		}
	}
	
	function password_check($value)
	{
		$user_info = $this->user_model->get_single_record("user_id=".$this->user_id);
		if($user_info->password != md5($value) )
		{
			$this->form_validation->set_message('password_check', 'Enter valid current password');
			return FALSE;
		}
	}
	function email_check()
	{
		$email 			= $_POST['email'];
		$confirm_email 	= $_POST['confirm_email'];
		if($email!=$confirm_email)
		{
			$this->form_validation->set_message('email_check', 'Email and confirm email must be same');
			return FALSE;
		}
	}
	
	function password_confirm_check()
	{
		$password 			= $_POST['password'];
		$confirm_password 	= $_POST['confirm_password'];
		if($password!=$confirm_password)
		{
			$this->form_validation->set_message('password_confirm_check', 'Password and confirm password must be same');
			return FALSE;
		}
	}
	
	function Old_security_pin_check()
	{
		$this->form_validation->set_message('Old_security_pin_check', 'Old security pin is incorrect');
		return FALSE;
	}
	
	function Old_mem_phrase_check()
	{
		$this->form_validation->set_message('Old_mem_phrase_check', 'Old memorable phrase is incorrect');
		return FALSE;
	}
	
	function security_pin()
	{
		$data['title']  = 'Change Settings';
		$user_id = $this->user_id;	
		$data['user_info'] = $this->user_model->get_single_record("user_id=".$user_id);
		
		$data['section'] = "security_pin";
		
		if($_POST!=NULL)
		{
			$_POST 		= $this->security->xss_clean($_POST);
			$this->form_validation->set_rules('current_password', 'current password', 'required|callback_password_check');
			
			if($data['user_info']->security_pin!=NULL) {
				$this->form_validation->set_rules('old_security_pin', 'Old Security Pin', 'required|min_length[4]|max_length[4]');
			}
			$this->form_validation->set_rules('security_pin', 'New Security Pin', 'required|min_length[4]|max_length[4]');
			
			if(!empty($_POST['old_security_pin']))
			{
				if($data['user_info']->security_pin != md5($_POST['old_security_pin']))
					$this->form_validation->set_rules('old_security_pin', 'Old security pin is incorrect', 'callback_Old_security_pin_check');
			}
			
			if($this->form_validation->run() == TRUE)
			{
				$insert_data['security_pin'] = md5($_POST['security_pin']);
				$this->user_model->edit($insert_data,array("user_id"=>$user_id));
				if($data['user_info']->security_pin!=NULL) {
					$this->session->set_flashdata(array('success_msg' => 'Security pin changed successfully.'));
				}	
				else
					$this->session->set_flashdata(array('success_msg' => 'Security pin created successfully.'));
				redirect('settings/security_pin');			
				exit;
			}	
			
			
		}
		
		$data['view'] = 'security_pin_view';
		$this->main_view('account/user_settings_view', $data);
	}
	
	function change_memphrase()
	{
		$data['title']  = 'Change Settings';
		$user_id = $this->user_id;	
		$data['user_info'] = $this->user_model->get_single_record("user_id=".$user_id);
		$data['section'] = "mem_phrase";
		if($_POST!=NULL)
		{
			$_POST 		= $this->security->xss_clean($_POST);
			$this->form_validation->set_rules('current_password', 'current password', 'required|callback_password_check');
			$this->form_validation->set_rules('old_mem_phrase', 'Old memorable phrase', 'required|alpha_numeric|alpha_numeric|min_length[3]');
			$this->form_validation->set_rules('mem_phrase', 'New memorable phrase', 'required|alpha_numeric|alpha_numeric|min_length[3]');
			if(!empty($_POST['old_mem_phrase']))
			{
				if($data['user_info']->mem_phrase != $_POST['old_mem_phrase'])
					$this->form_validation->set_rules('old_mem_phrase', 'Old memorable phrase is incorrect', 'callback_Old_mem_phrase_check');
			}
			
			if($this->form_validation->run() == TRUE)
			{
				$insert_data['mem_phrase'] = $_POST['mem_phrase'];
				$this->user_model->edit($insert_data,array("user_id"=>$user_id));
				$this->session->set_flashdata(array('success_msg' => 'Memorable phrase changed successfully.'));
				redirect('settings/change_memphrase');			
				exit;
			}	
		}
		
		$data['view'] = 'change_mem_phrase_view';
		$this->main_view('account/user_settings_view', $data);
	}
	
	function change_settings()
	{	
		$data['title']  = 'Change Settings';

		$user_id = $this->user_id;	
		$data['user_info'] = $this->user_model->get_single_record("user_id=".$user_id);
		
		if($_POST!=NULL)
		{
			//$_POST = $this->security->xss_clean($_POST);
			$this->form_validation->set_rules('current_password', 'current password', 'required|callback_password_check');
			if(!empty($_POST['change_email']))
			{
				//$this->session->set_userdata(array('change_email' => $_POST['email'],'change_confirm_email' => $_POST['confirm_email'] ));
						
				unset($_POST['password']);
				unset($_POST['confirm_password']);
				$this->form_validation->set_rules('email', 'email', 'required|valid_email|is_unique[users.email]');
				$this->form_validation->set_rules('confirm_email', 'email', 'required|valid_email|callback_email_check');
				if($this->form_validation->run() == TRUE)
				{
					if($_POST['email']!=$data['user_info']->email)
					{
						$token = md5($_POST['email']).md5($user_id);
						$insert_data['email'] 			= $_POST['email'];
						$insert_data['email_token'] 	= $token;
						$insert_data['user_id'] 		= $user_id;
						$details  = $this->user_changes_model->get_single_record(' user_id="'.$user_id.'" ');
						if($details==NULL)
							$this->user_changes_model->add($insert_data);
						else
							$this->user_changes_model->edit($insert_data,array("user_id"=>$user_id));
						
						$admin_row  = $this->site_setting_model->get_single_record(1);
						$email_row  = $this->email_template_model->get_single_record(' title = "email change" ');
						$name 		= $data['user_info']->first_name." ".$data['user_info']->last_name;
						$email 		= $data['user_info']->email;
						$this->email->subject($email_row->subject);
						$this->email->from($admin_row->adm_support_email,$admin_row->adm_name);
						$this->email->to($email);
						
						$link 			 = siteUri.'account/email_activation?id='.$user_id.'&token='.$token;
						$activation_link = '<a href="'.$link.'">'.$link.'</a>';
						$sub_message 	 = $email_row->msg;
						$sub_pattern 	 = array('/{USERNAME}/','/{EMAIL}/','/{SUPPORT_EMAIL}/','/{EMAIL_LINK}/');
						$sub_replacement = array($name,$email,$admin_row->adm_support_email,$activation_link);
						$message 		 = preg_replace($sub_pattern,$sub_replacement,$sub_message);
						
						$this->email->message($message);
						$this->email->send();
						$this->session->set_flashdata(array('success_msg' => 'Email activation link sent successfully, to your email address.'));
						
						//$this->session->unset_userdata('change_email');
						//$this->session->unset_userdata('change_confirm_email');
						
					}
					else
						$this->session->set_flashdata(array('error_msg' => 'Your are requesting same email address, try another.'));
						
					redirect('settings/change_settings');			
					exit;
				}
			}
			if(!empty($_POST['change_password']))
			{
				unset($_POST['email']);
				unset($_POST['confirm_email']);
				
				//$this->session->set_userdata(array('change_password' => $_POST['password'],'change_confirm_password' => $_POST['confirm_password'] ));
				
				//$this->form_validation->set_rules('password', 'password', 'required|min_length[7]|regex_match[((?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%]))]');
				$this->form_validation->set_rules('password', 'password', 'required|min_length[7]');
				$this->form_validation->set_rules('confirm_password', 'confirm password', 'required|callback_password_confirm_check');
				if($this->form_validation->run() == TRUE)
				{
					if(md5($_POST['password'])!=$data['user_info']->password)
					{
						$token = md5($_POST['password']).md5($user_id);
						$insert_data['password'] 		= $this->set_password($_POST['password']);
						$insert_data['password_token'] 	= $token;
						$insert_data['user_id'] 		= $user_id;
							
						$details  = $this->user_changes_model->get_single_record(' user_id="'.$user_id.'" ');
						if($details==NULL)
							$this->user_changes_model->add($insert_data);
						else
							$this->user_changes_model->edit($insert_data,array("user_id"=>$user_id));
						
						$admin_row  = $this->site_setting_model->get_single_record(1);
						$email_row  = $this->email_template_model->get_single_record(' title = "password change" ');
						$name 		= $data['user_info']->first_name." ".$data['user_info']->last_name;
						$email 		= $data['user_info']->email;
						$this->email->subject($email_row->subject);
						$this->email->from($admin_row->adm_support_email,$admin_row->adm_name);
						$this->email->to($email);
						
						$link 			 = siteUri.'account/password_activation?id='.$user_id.'&token='.$token;
						$activation_link = '<a href="'.$link.'">'.$link.'</a>';
						$sub_message 	 = $email_row->msg;
						$sub_pattern 	 = array('/{USERNAME}/','/{EMAIL}/','/{SUPPORT_EMAIL}/','/{PASSWORD_LINK}/');
						$sub_replacement = array($name,$email,$admin_row->adm_support_email,$activation_link);
						$message 		 = preg_replace($sub_pattern,$sub_replacement,$sub_message);
						
						//echo $message;exit;
						$this->email->message($message);
						$this->email->send();
						$this->session->set_flashdata(array('success_msg' => 'Password activation link sent successfully, to your email address.'));
						//$this->session->unset_userdata('change_password');
						//$this->session->unset_userdata('change_confirm_password');
					}
					else
						$this->session->set_flashdata(array('error_msg' => 'Your are requesting same password, try another.'));
					redirect('settings/change_settings');			
					exit;
				}
			}
		}
		$data['user_settings_info']	= $this->user_settings_model->get_single_record("user_setting_id=".$user_id);
		$data['section'] = 'change_settings';
		$data['view'] = 'change_settings_view';
		$this->main_view('account/user_settings_view', $data);
	}
	
	function close_account()		//close_account_status
	{	
		$data['title']  	= 'User Settings';
		$user_id 			= $this->user_id;	
		$data['user_info'] 	= $this->user_model->get_single_record("user_id=".$user_id);
		$email = $data['user_info']->email;
		if($_POST!=NULL)
		{	
			$this->form_validation->set_rules('password', 'password', 'required');
			if($this->input->post('password')!=NULL) 
			{	
				$password 		= $this->input->post('password');
				$user_info_data = $this->user_model->get_single_record("user_id='".$this->user_id."' and password='".md5($password)."'");
				if($user_info_data=="0")
				{		
					$this->session->set_flashdata(array('error_msg' => 'Incorrect Password.'));
					redirect('settings/close_account');			
					exit;
				}	
			 }
			 
			if($this->form_validation->run() == TRUE)
			{
				$data['order_status'] = $this->orders_model->get_single_record("user_id='".$user_id."' AND (status=0 or status=1) ");
				if($data['order_status']!=NULL)
				{
					if($data['order_status']->status == 0)		//Wating Dues member account
					{	
						$change = $this->user_model->close_account_status_wait($user_id);
						if($change)
						{
							//mail user to admin
							$admin_row  = $this->site_setting_model->get_single_record(1);
							$email_row  = $this->email_template_model->get_single_record(' title = "closed account request user to admin" ');
							$name 		= $data['user_info']->username;
							$email 		= $data['user_info']->email;
							$this->email->subject($email_row->subject);
							$this->email->from($email,$name);
							$this->email->to($admin_row->adm_support_email);
				
							$sub_message 	 = $email_row->msg;
							$sub_pattern 	 = array('/{USERNAME}/','/{EMAIL}/');
							$sub_replacement = array($name,$email,$admin_row->adm_support_email);
							$mail_user_to_admin 	= preg_replace($sub_pattern,$sub_replacement,$sub_message);
							$this->email->message($mail_user_to_admin);
							$mail_user_to_admin = $this->email->send();	
							//mail user to admin
							
							
							//mail Admin To User
							$admin_row  = $this->site_setting_model->get_single_record(1);
							$email_row  = $this->email_template_model->get_single_record(' title = "closed account request reply admin to user" ');
							$name 		= $data['user_info']->username;
							$email 		= $data['user_info']->email;
							$this->email->subject($email_row->subject);
							$this->email->from($admin_row->adm_support_email);
							$this->email->to($email,$name);
							$sub_message 	 = $email_row->msg;
							$sub_pattern 	 = array('/{USERNAME}/','/{EMAIL}/','/{SUPPORT_EMAIL}/');
							$sub_replacement = array($name,$email,$admin_row->adm_support_email);
							$message_admin_TO_user 	= preg_replace($sub_pattern,$sub_replacement,$sub_message);
							$this->email->message($message_admin_TO_user);
							$mail_admin_to_user = $this->email->send();								
							//mail Admin To User
							
							$this->session->set_flashdata(array('success_msg' => 'Your request has been sent successfully.'));
							redirect('settings/close_account');	
							exit;
						}
						
						$this->session->set_flashdata(array('delete_error' => 'Unable to send your request try again later.'));
						redirect('settings/close_account');	
						exit;
					}		
					elseif($data['order_status']->status == 1)		//closed sucessfull 		No dues 
					{
						$change = $this->user_model->close_account_status_wait($user_id);	//$this->user_model->close_account_status_closed
						if($change)
						{
							//mail user to admin
							$admin_row  = $this->site_setting_model->get_single_record(1);
							$email_row  = $this->email_template_model->get_single_record(' title = "closed account request user to admin" ');
							$name 		= $data['user_info']->username;
							$email 		= $data['user_info']->email;
							$this->email->subject($email_row->subject);
							$this->email->from($email,$name);
							$this->email->to($admin_row->adm_support_email);
					
							$sub_message 	 = $email_row->msg;
							$sub_pattern 	 = array('/{USERNAME}/','/{EMAIL}/');
							$sub_replacement = array($name,$email,$admin_row->adm_support_email);
							$message_userTOadmin = preg_replace($sub_pattern,$sub_replacement,$sub_message);
							$this->email->message($message_userTOadmin);
							$mail_user_to_admin = $this->email->send();	
							//mail user to admin
							
							
							//mail Admin To User
							$admin_row  = $this->site_setting_model->get_single_record(1);
							$email_row  = $this->email_template_model->get_single_record(' title = "closed account request reply admin to user" ');
							$name 		= $data['user_info']->username;
							$email 		= $data['user_info']->email;
							$this->email->subject($email_row->subject);
							$this->email->from($admin_row->adm_support_email);
							$this->email->to($email,$name);
							$sub_message 	 = $email_row->msg;
							$sub_pattern 	 = array('/{USERNAME}/','/{EMAIL}/','/{SUPPORT_EMAIL}/');
							$sub_replacement = array($name,$email,$admin_row->adm_support_email);
							$message_admin_TO_user 	= preg_replace($sub_pattern,$sub_replacement,$sub_message);
							$this->email->message($message_admin_TO_user);
							$mail_admin_to_user = $this->email->send();								
							//mail Admin To User

							$this->session->set_flashdata(array('success_msg' => 'Your request has been sent successfully.'));
							redirect('settings/close_account');	
							exit;
						}
						$this->session->set_flashdata(array('delete_error' => 'Unable to send your request try again later.'));
						redirect('settings/close_account');	
						exit;
					}
				}
				else
				{
					$change = $this->user_model->close_account_status_wait($user_id);		//closed sucessfull    No dues 
					if($change)
					{		//mail user to admin
						$admin_row  = $this->site_setting_model->get_single_record(1);
						$email_row  = $this->email_template_model->get_single_record(' title = "closed account request user to admin" ');
						$name 		= $data['user_info']->username;
						$email 		= $data['user_info']->email;
						$this->email->subject($email_row->subject);
						$this->email->from($email,$name);
						$this->email->to($admin_row->adm_support_email);
				
						$sub_message 	 = $email_row->msg;
						$sub_pattern 	 = array('/{USERNAME}/','/{EMAIL}/');
						$sub_replacement = array($name,$email);
						$message_userTOadmin  = preg_replace($sub_pattern,$sub_replacement,$sub_message);
						//echo $message_userTOadmin;
						$this->email->message($message_userTOadmin);
						$this->email->send();	
						//mail user to admin
						
						
						//mail Admin To User
						$admin_row  = $this->site_setting_model->get_single_record(1);
						$email_row  = $this->email_template_model->get_single_record(' title = "closed account request reply admin to user" ');
						$name 		= $data['user_info']->username;
						$email 		= $data['user_info']->email;
						$this->email->subject($email_row->subject);
						$this->email->from($admin_row->adm_support_email);
						$this->email->to($email,$name);
						$sub_message 	 = $email_row->msg;
						$sub_pattern 	 = array('/{USERNAME}/','/{EMAIL}/','/{SUPPORT_EMAIL}/');
						$sub_replacement = array($name,$email,$admin_row->adm_support_email);
						$message_admin_TO_user 	= preg_replace($sub_pattern,$sub_replacement,$sub_message);
						//echo $message_admin_TO_user;exit;
						$this->email->message($message_admin_TO_user);
						$this->email->send();
						//mail Admin To User
					$this->session->set_userdata(array('close_account_status' => '2'));
					$this->session->set_flashdata(array('success_msg' => 'Your request has been sent successfully.'));					
					redirect('settings/close_account');	
				}
				else
				{
					$this->session->set_flashdata(array('delete_error' => 'Unable to send your request.'));
					redirect('settings/close_account');	
				}
				redirect('settings/close_account');	
			 }
			}
		 }
		 
		$data['site_ifo_details']  = $this->site_setting_model->get_single_record(1);
		$data['section'] = 'close_account';
		$data['view'] = 'close_account_view';
		$this->main_view('account/user_settings_view', $data);			
		//redirect('/home');
	}	//close_account_status
	
	function date_of_birth_check()
	{
		$status='';
		$date_of_birth 	= $_POST['date_of_birth'];
		$date_of_birth  = str_replace("-","",$date_of_birth );
		if(!is_numeric($date_of_birth ))
		{
			$this->form_validation->set_message('date_of_birth_check', 'Enter valid date of birth');
			return FALSE;
			$status = 1;
		}
		else
		{
			$explode = explode("-",$_POST['date_of_birth']);
			if(sizeof($explode)!=3)
				$status = 1;
			else
			{
				if(strlen($explode[0])!=2) $status = 1;
				if(strlen($explode[1])!=2) $status = 1;
				if(strlen($explode[2])!=4) $status = 1;
			}
		}
		if($status == 1)
		{
			$this->form_validation->set_message('date_of_birth_check', 'Enter valid date of birth');
			return FALSE;
		}
		
	}
	
	function verification()
	{	
		$data['title']  = 'Change Account Information';
		$user_id = $this->user_id;	
		$data['user_info'] = $this->user_model->get_single_record("user_id=".$user_id);
		
		if($data['user_info']->suspended==1)
		{
			$this->session->set_flashdata(array('error_msg' => 'Your account suspended'));
			redirect('settings');			
			exit;
		}
		if($data['user_info']->verified==1)
		{
			redirect('settings/trusted');			
			exit;
		}
		if($data['user_info']->verified==2)
		{
			redirect('settings');			
			exit;
		}
		if($_POST!=NULL)
		{
			$_POST = $this->security->xss_clean($_POST);
			$this->form_validation->set_rules('first_name', 'first name', 'required');
			$this->form_validation->set_rules('last_name', 'last name', 'required');
			$this->form_validation->set_rules('date_of_birth', 'date of birth', 'required|callback_date_of_birth_check');
			$this->form_validation->set_rules('country_of_birth', 'country of birth', 'required');
			$this->form_validation->set_rules('address', 'address', 'required');
			$this->form_validation->set_rules('zipcode', 'Postcode', 'required');		//POSTCODE			
			$this->form_validation->set_rules('city', 'city', 'required');
			$this->form_validation->set_rules('state', 'state', 'required');
			$this->form_validation->set_rules('country_of_res', 'country of residence', 'required');
			if($this->form_validation->run() == TRUE)
			{
				$update_data = $_POST;
				//if(empty($update_data['corporate_account'])) $update_data['corporate_account'] = 0;
				$this->user_model->edit($update_data,array("user_id"=>$user_id));
				$this->session->set_flashdata(array('success_msg' => 'Your information has been saved successfully.'));
				redirect('settings/verification');			
				exit;
			}
		}
		
		
		$data['identity_info'] 	= $this->user_identity_model->custom_query("SELECT * FROM user_identities WHERE user_id='".$user_id."' AND status!=2 ORDER BY user_identity_id ASC");
		$data['residency_info'] = $this->user_residency_model->custom_query("SELECT * FROM user_residencies WHERE user_id='".$user_id."' AND status!=2 ORDER BY user_residency_id ASC");
		if($data['residency_info']!=NULL && $data['identity_info']!=NULL) $this->session->set_flashdata(array('success_msg' => 'You have submited awaiting admin response.'));
		
		
		$data['user_settings_info']	= $this->user_settings_model->get_single_record("user_id=".$user_id);
		$data['country_info'] = $this->country_model->combox();
		$data['section'] = 'get_verified';
		
		$data['view'] = 'change_verification_view';
		$this->main_view('account/user_settings_view', $data);
	}
	
	function check_upload_file()
	{
		$document_setting_data = $this->document_setting_model->get_single_record("id=1");
		$photo 	= $_POST['photo'];
		$ext    = end(explode(".",$photo));
		
		if(!empty($document_setting_data))
		{
			$file_formats = $document_setting_data->format_file;
			$file_explode = explode(',',$file_formats);
			if(in_array(strtolower($ext),$file_explode))
			{
				//if($this->user_id==1) { echo $_FILES['photo']['size'];exit; }
				if($_FILES['photo']['size'] > ($document_setting_data->size*1024))
				{
					$this->form_validation->set_message('check_upload_file', 'Photo size must be or less than '.$document_setting_data->size.' kb');
					return FALSE;
				}
				else 
					return TRUE;	
			}
			else
			{
				$this->form_validation->set_message('check_upload_file', 'Photo must be either in '.str_replace(',',', ',$file_formats).' format.');
				return FALSE;
			}
		}
		
		/*if($ext!='jpg' && $ext!='JPG' && $ext!='jpeg' && $ext!='JPEG' && $ext!='png' && $ext!='PNG' && $ext!='pdf' && $ext!='PDF')
		{
			$this->form_validation->set_message('check_upload_file', 'Photo must be either in JPEG (.jpg), PNG (.png) or PDF (.pdf) format.');
			return FALSE;
		}*/
	}
	
	function php_info()
	{
		echo phpinfo();
	}
	
	function verification_identity()
	{	
		$data['title']  		= 'Proof Of Identity Information';
		$user_id 				= $this->user_id;	
		$data['user_info'] 		= $this->user_model->get_single_record("user_id=".$user_id);
		$data['section'] = 'verification_identity';
		if($data['user_info']->first_name==NULL)
		{
			$this->session->set_flashdata(array('error_msg' => 'Fill verification information first.'));
			redirect('settings/verification');			
			exit;
		}
		if($data['user_info']->verified==1)
		{
			redirect('settings/trusted');			
			exit;
		}
		if($data['user_info']->verified==2)
		{
			redirect('settings');			
			exit;
		}
		
		if($_POST!=NULL)
		{
			//ini_set('upload_max_filesize', '10M');
			$_POST['photo'] = $_FILES['photo']['name'];
			$_POST = $this->security->xss_clean($_POST);
			$this->form_validation->set_rules('type_of_document', 'type of ID document', 'required');
			$this->form_validation->set_rules('id_number', 'ID number', 'required');
			if($_POST['photo']=='') $this->form_validation->set_rules('photo', 'photo', 'required');
			else $this->form_validation->set_rules('photo', 'photo', 'callback_check_upload_file');
			if($this->form_validation->run() == TRUE)
			{
				if(!empty($_FILES['photo']['name']))
				{
					//Upload
					set_time_limit(0);
					ini_set('memory_limit', '2048M');
					$file_name 	= $_FILES['photo']['name'];
					//if($this->user_id==1) { print_r($_FILES);exit; }
					if(move_uploaded_file($_FILES["photo"]["tmp_name"],getcwd()."/media/uploads/$file_name"))
					{
						unset($_POST['photo']);
						$update_data = $_POST;
						$update_data['user_id'] = $user_id;
						$update_data['requested_date'] = date('Y-m-d');
						$user_identity_id = $this->user_identity_model->add($update_data);
						
						$ext = end(explode(".",$file_name));
						$update_photo_data['photo'] = strtotime("now").$user_identity_id.$user_id.mt_rand().".$ext";
						$this->user_identity_model->edit($update_photo_data,array("user_identity_id"=>$user_identity_id));
						rename(getcwd()."/media/uploads/$file_name",getcwd()."/media/uploads/".$update_photo_data['photo']);
						
						$this->session->set_flashdata(array('success_msg' => 'Your identity information has been saved successfully.'));
					}
					else
						$this->session->set_flashdata(array('error_msg' => 'Error while uploading document.'));
				}
				redirect('settings/verification_identity');			
				exit;
			}
		}
		
		
		$data['identity_info'] 	= $this->user_identity_model->custom_query("SELECT * FROM user_identities WHERE user_id='".$user_id."' AND status!=2 ORDER BY user_identity_id ASC");
		if($data['identity_info']!=NULL) $this->session->set_flashdata(array('success_msg' => 'You have submited awaiting admin response.'));
		$data['document_setting_data'] = $this->document_setting_model->get_single_record("id=1");
		$data['country_info'] = $this->country_model->get_single_record("code='".$data['user_info']->country_of_birth."'");
		$data['view'] = 'change_verification_view';
		$this->main_view('account/user_settings_view', $data);
	}
	
	function verification_residency()
	{	
		$data['title']  		= 'Proof Of Identity Information';
		$user_id 				= $this->user_id;	
		$data['user_info'] 		= $this->user_model->get_single_record("user_id=".$user_id);
		$data['section'] = 'verification_residency';
		if($data['user_info']->first_name==NULL)
		{
			$this->session->set_flashdata(array('error_msg' => 'Fill verification information first.'));
			redirect('settings/verification');			
			exit;
		}
		if($data['user_info']->verified==1)
		{
			redirect('settings/trusted');			
			exit;
		}
		if($data['user_info']->verified==2)
		{
			redirect('settings');			
			exit;
		}
		if($_POST!=NULL)
		{
			$_POST['photo'] = $_FILES['photo']['name'];
			$_POST = $this->security->xss_clean($_POST);
			$this->form_validation->set_rules('type_of_document', 'type of ID document', 'required');
			if($_POST['photo']=='') $this->form_validation->set_rules('photo', 'photo', 'required');
			else $this->form_validation->set_rules('photo', 'photo', 'callback_check_upload_file');
			if($this->form_validation->run() == TRUE)
			{
				if(!empty($_FILES['photo']['name']))
				{
					//Upload
					set_time_limit(0);
					ini_set('memory_limit', '2048M');
					$file_name 	= $_FILES['photo']['name'];
					if(move_uploaded_file($_FILES["photo"]["tmp_name"],getcwd()."/media/uploads/$file_name"))
					{
						unset($_POST['photo']);
						$update_data = $_POST;
						$update_data['user_id'] = $user_id;
						$user_residency_id = $this->user_residency_model->add($update_data);
						
						$ext    	= end(explode(".",$file_name));
						$update_photo_data['photo'] = strtotime("now").$user_residency_id.$user_id.mt_rand()."res.$ext";
						$this->user_residency_model->edit($update_photo_data,array("user_residency_id"=>$user_residency_id));
						rename(getcwd()."/media/uploads/$file_name",getcwd()."/media/uploads/".$update_photo_data['photo']);
						//Upload
						$this->session->set_flashdata(array('success_msg' => 'Your residency information has been saved successfully.'));
					}
					else
						$this->session->set_flashdata(array('error_msg' => 'Error while uploading document.'));
				}
				redirect('settings/verification_residency');			
				exit;
			}
		}
		
		$data['residency_info'] = $this->user_residency_model->custom_query("SELECT * FROM user_residencies WHERE user_id='".$user_id."' AND status!=2 ORDER BY user_residency_id ASC");
		if($data['residency_info']!=NULL) $this->session->set_flashdata(array('success_msg' => 'You have submited awaiting admin response.'));
		$data['document_setting_data'] = $this->document_setting_model->get_single_record("id=1");
		$data['view'] = 'change_verification_view';
		$this->main_view('account/user_settings_view', $data);
	}
	
	function trusted()
	{	
		$data['title']  	= 'Trusted Account Information';
		$user_id 			= $this->user_id;	
		$data['user_info'] 	= $this->user_model->get_single_record("user_id='".$user_id."'");
		if($data['user_info']->suspended==1)
		{
			$this->session->set_flashdata(array('error_msg' => 'Your account suspended'));
			redirect('settings');			
			exit;
		}
		if($data['user_info']->verified==0)
		{
			redirect('settings/verification');			
			exit;
		}
		if($data['user_info']->verified==2)
		{
			redirect('settings');			
			exit;
		}
		if($_POST!=NULL)
		{
			//
		}
		$data['user_settings_info']	= $this->user_settings_model->get_single_record("user_id=".$user_id);
		$data['company_info']	= $this->company_model->get_single_record("status=1 ORDER BY comp_id DESC LIMIT 0,1");
		
		$data['country_info'] 		= $this->country_model->combox();
		$data['section'] 			= 'trusted';
		$data['view'] = 'user_trusted_view';
		$this->main_view('account/user_settings_view', $data);
	}
	
	function set_password($password)
	{
		$rand1=chr(rand(97,122)).rand(0,8).chr(rand(97,122)).chr(rand(97,122)).rand(0,5).chr(rand(97,122)).chr(33).chr(rand(40,42)).chr(rand(40,42));
		$rand2=chr(rand(97,122)).rand(0,8).chr(rand(97,122)).chr(rand(97,122)).rand(0,5).chr(rand(97,122)).chr(33).chr(rand(40,42)).chr(rand(40,42));
		$rand3=chr(rand(40,42)).chr(rand(40,42)).chr(rand(97,122)).rand(0,4).chr(rand(97,122)).chr(rand(97,122)).chr(rand(40,42)).chr(rand(40,42)).rand(0,6);
		$rand4=chr(rand(40,42)).chr(rand(40,42)).chr(rand(97,122)).rand(0,4).chr(rand(97,122)).chr(rand(97,122)).chr(rand(40,42)).chr(rand(40,42)).chr(rand(40,42));
		$verification_password=$rand1.$rand2.$password.$rand3.$rand4;
		return $verification_password;
	}
	
	function check_activetime()
	{
		if(!empty($_POST['method']) && $_POST['method']=="ajax")
		{
			$user_settings_info  = $this->user_settings_model->get_single_record("user_id='".$this->user_id."'");
			$session_expiration  = (!empty($user_settings_info->session_expiration)) ? $user_settings_info->session_expiration : 0;
			if($session_expiration==1)  $session_expiration_minutes = 1;
			if($session_expiration==2)  $session_expiration_minutes = 5;
			if($session_expiration==3)  $session_expiration_minutes = 10;
			if($session_expiration==4 || $session_expiration==0)  $session_expiration_minutes = 15;
			if($session_expiration==5)  $session_expiration_minutes = 30;
			if($session_expiration==6)  $session_expiration_minutes = 60;
			if($session_expiration==7)  $session_expiration_minutes = 120;
			$activetime = $this->session->userdata('activetime');
			$diff 		= (strtotime(date("Y-m-d H:i:s")) - strtotime($activetime));
			$minutes	= ($diff / 60);
			//echo $activetime.'<br>'.$minutes.'<br>'.$session_expiration_minutes; exit;
			if($minutes>=$session_expiration_minutes) echo "1"; else echo "0";
		}
		else
		{
			redirect('trade');			
			exit;
		}
		//exit;
	}
	
	
	function reset_pin()
	{
		$user_id = $this->user_id;
		$check_user = $this->user_model->get_single_data('users',"user_id=$user_id");
		//Mail
		$first_name = $check_user->first_name;
		$last_name 	= $check_user->last_name;
		$email 		= $check_user->email;
		$name = "User";if($first_name!='' && $last_name!='') $name = $first_name." ".$last_name;
		
		//update user table
		$security_pin = rand(0,9).rand(0,9).rand(0,9).rand(0,9);
		$update_user_data['security_pin'] 	= md5($security_pin);
		$this->user_model->edit($update_user_data,array("user_id"=>$user_id));
		
		$admin_row  	 = $this->site_setting_model->get_single_record(1);
		$email_row  	 = $this->email_template_model->get_single_record(' title = "Reset Pin" ');
		$sub_message 	 = $email_row->msg;
		$sub_pattern 	 = array('/{NAME}/','/{EMAIL}/','/{PIN}/','/{SUPPORT_EMAIL}/');
		$sub_replacement = array($name,$email,$security_pin,$admin_row->adm_support_email);
		$message 		 = preg_replace($sub_pattern,$sub_replacement,$sub_message);
		$subject 		 = $email_row->subject;
		$subject 		= str_replace("Administrator","You",$subject);
	
		$this->email->subject($subject);
		$this->email->from($admin_row->adm_support_email,$admin_row->adm_name);
		$this->email->to($email);
		$this->email->message($message);
		$this->email->send();
		//Mail
		
		$this->session->set_flashdata(array('success_msg' => 'Pin has been reset successfully.'));
		redirect('settings/security_pin');
		exit;
	}
	
	function reset_memoable_prase()
	{
		$user_id = $this->user_id;
		$check_user = $this->user_model->get_single_data('users',"user_id=$user_id");
		//Mail
		$first_name = $check_user->first_name;
		$last_name 	= $check_user->last_name;
		$email 		= $check_user->email;
		$name = "User";if($first_name!='' && $last_name!='') $name = $first_name." ".$last_name;
		
		//update user table
		$mem_phrase = rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);
		$update_user_data['mem_phrase'] 	= $mem_phrase;
		$this->user_model->edit($update_user_data,array("user_id"=>$user_id));
		
		$admin_row  	 = $this->site_setting_model->get_single_record(1);
		$email_row  	 = $this->email_template_model->get_single_record(' title = "Reset Memorable Phrase" ');
		$sub_message 	 = $email_row->msg;
		$sub_pattern 	 = array('/{NAME}/','/{EMAIL}/','/{MEM_PHRASE}/','/{SUPPORT_EMAIL}/');
		$sub_replacement = array($name,$email,$mem_phrase,$admin_row->adm_support_email);
		$message 		 = preg_replace($sub_pattern,$sub_replacement,$sub_message);
		$subject 		 = $email_row->subject;
		$subject 		 = str_replace("Administrator","You",$subject);
	
		$this->email->subject($subject);
		$this->email->from($admin_row->adm_support_email,$admin_row->adm_name);
		$this->email->to($email);
		$this->email->message($message);
		$this->email->send();
		//Mail
		
		$this->session->set_flashdata(array('success_msg' => 'Memorable phrase has been reset successfully.'));
		redirect('settings/change_memphrase');
		exit;
	}
	
	function logout()
	{
		$this->logged_hist_id = $this->session->userdata('logged_hist_id');
		$details = $this->user_login_history_model->get_single_record("hist_id='".$this->logged_hist_id."'");
		if($details!="0")
		{
			$updatehistdata['logged_out_date'] = date("Y-m-d H:i:s");
			$this->user_login_history_model->edit_history($updatehistdata,$this->logged_hist_id);
		}
		
		
		$unset_array = array('user_logged_in' => '','user_id' => '','email' => '','username' => '','currency' => '','last_uri' => '','activetime' => '','logged_hist_id' => '','ad_ahow' => '','not_see_again' => '','go_to_url' => '','auto_detect' => '','payment_type' => '');
		$this->session->unset_userdata($unset_array);
		
		/*$this->session->unset_userdata(array('user_logged_in');
		$this->session->unset_userdata('user_id');
		$this->session->unset_userdata('email');
		$this->session->unset_userdata('username');
		$this->session->unset_userdata('currency');
		$this->session->unset_userdata('last_uri');
		$this->session->unset_userdata('activetime');
		$this->session->unset_userdata('logged_hist_id');
		$this->session->unset_userdata('ad_ahow');
		$this->session->unset_userdata('not_see_again');
		$this->session->unset_userdata('go_to_url');
		$this->session->unset_userdata('auto_detect');*/
		
		$this->session->unset_userdata('payment_type');
		
		$this->session->set_userdata(array('logout_success_msg' => 'Logout Successfully.'));
		redirect('login');			
		exit;
	}	
	
	
	function delete_document($id,$type)
	{
		$user_id = $this->user_id;
		$data['user'] = $this->user_model->get_single_data('users',"user_id='".$user_id."'");
		if($data['user']=="0")
		{
			$this->session->set_flashdata(array('delete_error' => 'Unable to find user.'));
			redirect('settings/verification/');
			exit;
		}
		$delete_flag = 0;
		if($id!=NULL)
		{
			if($type=="identity") { $model = "user_identity_model"; $field="user_identity_id"; } else { $model = "user_residency_model"; $field="user_residency_id"; }
			$details = $this->$model->get_single_record("$field='".$id."'");
			if($details!="0")
			{
				$delete = $this->$model->delete($field,$id);
				if($delete)
				{
					//remove image
					if(file_exists(getcwd()."/media/uploads/".$details->photo)) unlink(getcwd()."/media/uploads/".$details->photo);
					$delete_flag = 1;
				}
			}
		}	
		if($delete_flag==1) 
			$this->session->set_flashdata(array('success_msg' => 'Document deleted successfully.'));
		else
			$this->session->set_flashdata(array('delete_error' => 'Unable to delete document'));
			
		if($type=="identity")
			redirect('settings/verification_identity');
		else
			redirect('settings/verification_residency');
	}
	
	
	function download_document($id,$type,$sequence)
	{
		$user_id = $this->user_id;
		$data['user'] = $this->user_model->get_single_data('users',"user_id='".$user_id."'");
		if($data['user']=="0")
		{
			$this->session->set_flashdata(array('delete_error' => 'Unable to find user.'));
			redirect('settings/verification/');
			exit;
		}
		
		if($id!=NULL)
		{
			if($type=="identity") { $model = "user_identity_model"; $field="user_identity_id"; } else { $model = "user_residency_model"; $field="user_residency_id"; }
			$details = $this->$model->get_single_record("$field='".$id."'");
			if($details!="0")
			{
				include(APPPATH."views/account/member_download_view.php");
				
				set_time_limit(0);
				ini_set("memory_limit","1024M");
				
				require_once(getcwd()."/media/pdf/dompdf_config.inc.php");
				$dompdf = new DOMPDF();
				$dompdf->load_html($html);
				$dompdf->set_paper('a4','landscape');
				$dompdf->render();
				$pdf = $dompdf->output();
				
				//create
				if($sequence<10) $sequence = "0$sequence";
				$file_name = $data['user']->accnumber."_"."$sequence.pdf";
				$fp=fopen(getcwd()."/media/uploads/tmp/$file_name",'w+');
				fclose($fp);
		
				chmod(getcwd()."/media/uploads/tmp/$file_name",0777);
				//put
				file_put_contents(getcwd()."/media/uploads/tmp/$file_name", $pdf);
				$file_dir 	= getcwd()."/media/uploads/tmp/$file_name";
				$dir 		= getcwd()."/media/uploads/tmp/$file_name";
				//download
				if((isset($file_name))&&(file_exists($file_dir))) 
				{
					header("Content-type: application/force-download");
					header("Content-Transfer-Encoding: Binary");
					header("Content-length: ".filesize($file_dir));
					header('Content-Type: application/octet-stream');
					header('Content-Disposition:attachment; filename="'.$file_name.'"');
					ob_clean();
					flush(); 
					readfile("$file_dir");
					exit;
				} 
			}
		}
	}
	
	
	function lasttalking()
	{	
		if($this->input->is_ajax_request()) 
		{
			$user_id_to = $_POST['chat_user'];
			
			include(getcwd()."/application/views/trade/smileys.php");
			
			$details = $this->user_model->get_single_record("user_id='".$this->user_id."'");
			$_POST 	= $this->security->xss_clean($_POST);
			if($_POST['talking']!='' && $this->user_id>0)
			{
				$userlasttalk=$this->user_private_talks_model->custom_query("SELECT date_added FROM user_talks WHERE user_id='".$this->user_id."' ORDER BY date_added DESC limit 0,1");
				$_POST['talking'] = str_replace($uncensored_pattern,$uncensored_replacement,$_POST['talking']);
				$_POST['talking'] = str_replace($uncensored_upper_pattern,$uncensored_replacement,$_POST['talking']);
				
				$name_style = '';
				if(trim($details->chat_nickname)!='')
					$name 	 = $details->chat_nickname;
				else
					$name 	 = $details->first_name." ".ucwords(substr($details->last_name,0,1));
				
				$name = strip_tags($name);
				if(strpos($this->group_ids,",1,")!==false)	
					$name_style='style="color:#FF5703"';
				else if(strpos($this->group_ids,",10,")!==false)	
					$name_style='style="color:#729E25"';
				else
					$name_style='style="color:#666666"';
				if($details->avtar!='') $name = '<img src="'.siteMediaUri.'icons/'.$details->avtar.'.png" alt="" width="16"  /> '.$name;
				if(trim($_POST['talking'])!='')
				{
					$insert['user_id'] 		= $this->user_id;
					$insert['user_id_to'] 	= $user_id_to;
					$insert['talking'] 		= $_POST['talking'];
					$insert['date_added']   = date("Y-m-d H:i:s");
					$insert['ipaddress']   	= $_SERVER['REMOTE_ADDR'];
					$user_talk_id 			= $this->user_private_talks_model->add($insert);
				
					$date 	 = date("H:i:s, d-m-Y",strtotime($insert['date_added']));
				echo '<dd style="word-wrap: break-word;"><strong title="'.$date.'" '.$name_style.' onclick=\'sendmessageto("'.$details->chat_nickname.'");\'>'.$name.'</strong> : 
					<span title="'.$date.'">'.$_POST['talking'].'</span></dd>####'.$user_talk_id.'####1';
					exit;
				}
				echo "";exit;
			}
			
			$lasttalking = $_POST['lasttalking'];
			$query = "select user_private_talks.*,username,first_name,last_name,avtar,date_added,chat_nickname,group_ids from user_private_talks left join users on user_private_talks.user_id=users.user_id where ((user_private_talks.user_id='".$this->user_id."' AND user_id_to='".$user_id_to."') OR (user_private_talks.user_id='".$user_id_to."' AND user_id_to='".$this->user_id."')) AND users.user_id>0  order by user_talk_id desc limit 0,100";
			$usertalks 	= array_reverse($this->user_private_talks_model->custom_query($query));
			
			if($usertalks!=NULL)
			{
				$chat_string='';
				foreach($usertalks as $rowchat)
				{
					$talking = $rowchat->talking;
					$talking = str_replace($uncensored_pattern,$uncensored_replacement,$talking);
					$talking = str_replace($uncensored_upper_pattern,$uncensored_replacement,$talking);
					//$talking = strip_tags($talking);
					
					$name_style='';
					if(trim($rowchat->chat_nickname)!='')
						$name 	 = $rowchat->chat_nickname;
					else
						$name 	 = $rowchat->first_name." ".ucwords(substr($rowchat->last_name,0,1));
					
					$name = strip_tags($name);
					if(strpos($rowchat->group_ids,",1,")!==false)	
						$name_style='style="color:#FF5703"';
					else if(strpos($rowchat->group_ids,",10,")!==false)	
						$name_style='style="color:#729E25"';
					else
						$name_style='style="color:#666666"';
					
					if($rowchat->avtar!='') $name = '<img src="'.siteMediaUri.'icons/'.$rowchat->avtar.'.png" alt="" width="16"  /> '.$name;
					$date 	 = date("H:i:s, d-m-Y",strtotime($rowchat->date_added));
					
					$chat_string.= '<dd style="word-wrap: break-word;"><strong title="'.$date.'" '.$name_style.' onclick=\'sendmessageto("'.$rowchat->chat_nickname.'");\'>'.$name.'</strong> : <span title="'.$date.'">'.$talking.'</span></dd>';
					$user_talk_id = $rowchat->user_talk_id;
				}
				if($chat_string!='') 
					echo $chat_string.'####'.$user_talk_id;
				else
					echo '########';
			}
			else 
				echo '########';
			exit;
		}
	}
	
		
}

?>