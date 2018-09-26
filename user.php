<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Controller {


	var $page_title;
	var $page_description;
	var $image_data = array();


	function __construct(){

		parent::__construct();
		$this->load->helper("csrf_prevent");
		$this->load->model(array('commonmodel'));
	}

	// site home page handler

	public function index() {
		$this->page_title = "SRI GURU GRANTH SAHIB";
		$this->load->view('header');
		$this->load->view('home');
		$this->load->view('footer');
	}// end of the index function

	// login page for any user
	public function login($redirect_url = '') {
		
		$login_redirect_url = $this->session->userdata("login_redirect_url");
		if($login_redirect_url=='')
		{
			$login_redirect_url='';
			if(!empty($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='')
			{
				$explode = explode(base_url(),$_SERVER['HTTP_REFERER']);
				if(sizeof($explode)>1) $login_redirect_url = $explode[1];
			}
			$this->session->set_userdata('login_redirect_url', $login_redirect_url);
		}

		$this->page_title = "LOGIN";

		if( ! isLoggedIn()){
			$this->load->library('form_validation');

			$_POST = $this->security->xss_clean($_POST);

			$this->form_validation->set_rules('email', 'E-mail', 'trim|required|valid_email|callback_check_login');
			$this->form_validation->set_rules('pass', 'Password', 'trim|required');

			if ( ! $this->form_validation->run()) {
					die(json_encode(array('status' => 'error')));
					$data = array();
					$this->load->view('common_header');
					$this->load->view('user/login', $data);
					$this->load->view('footer');
			} else {

				$year = time() + 31536000;
				if(!empty($_POST['rememberme'])) {
					setcookie('remember_me', $_POST['email'], $year);
				}
				elseif(empty($_POST['rememberme'])) {
					if(isset($_COOKIE['remember_me'])) {
						$past = time() - 100;
						setcookie('remember_me', 'gone', $past);
					}
				}

				$this->session->unset_userdata('login_redirect_url');
				die(json_encode(array('status' => 'ok')));
				//redirect("/");
				exit;

				$url = explode( 'slash_http:', $redirect_url );
				if( isset( $url[1] ) ){
					$url[0] = $url[0].'slash_';
					$redirect_url = str_replace('_slash_', '/', $url[0]).'http:'.$url[1];
					if(strpos($redirect_url,'search-result')!==false || strpos($redirect_url,'advance-search')!==false)  $redirect_url='';

					$this->session->unset_userdata('login_redirect_url');
					redirect( $login_redirect_url );
					exit;
				}else{
					if(strpos($redirect_url,'search-result')!==false || strpos($redirect_url,'advance-search')!==false)  $redirect_url='';

					$this->session->unset_userdata('login_redirect_url');
					redirect(str_replace('_slash_', '/', $login_redirect_url));
					exit;
				}
			}
		}else{
			if(strpos($redirect_url,'search-result')!==false || strpos($redirect_url,'advance-search')!==false)  $redirect_url='';

			$this->session->unset_userdata('login_redirect_url');
			redirect(str_replace('_slash_', '/', $login_redirect_url));
			exit;
		}
	}


	// login page for any user
	public function forgetPassword() {
		$this->page_title = "Forgot Password";
		$is_login = $this->commonmodel->is_login();
		if(!$is_login){
			$this->load->library('form_validation');

			$this->form_validation->set_rules('email', 'E-mail', 'trim|required|valid_email|callback_check_user_email');

			if ($this->form_validation->run() == FALSE) {
					$data = array();
					$this->load->view('common_header');
					$this->load->view('user/forget-password', $data);
					$this->load->view('footer');
			} else {
					$post = $this->input->post();
					$user = $this->commonmodel->getRecords('user','user_id, name',array("email"=>$post['email']),'',true);
					if(!empty($user)){

							$token = md5(time());
							$member_data = array(
								'reset_code'=> $token,
								'updated'=>date("Y-m-d H:i:s"),
							);
							$this->commonmodel->commonAddEdit('user', $member_data, array("user_id" => $user['user_id']));
							$this->commonmodel->setMailConfig();
							$reset_link = site_url().'user/resetPassword?token='.$token;
							$subject = "Your account on Khojgurbani";
							$data['name'] = $user['name'];
							$data['reset_link'] = $reset_link;
							$message =	$this->load->view('emailer/user-reset-password',$data, true);
							$this->email->from(FROM_EMAIL, 'Khojgurbani');
							$this->email->reply_to(REPLY_TO);
							$this->email->to($post['email']);
							$this->email->subject($subject);
							$this->email->message($message);
							$this->commonmodel->sendEmail();
							redirect('user/check_email');
					}
			}
		}else{
			redirect();
		}
	}

	/**
	@param : user_name , password
	@return : create session of user_name, email, user_id.
	@detail : Checking for Login restrictions.
	*/
	public function check_user_email($str)
	{
		$user = $this->commonmodel->getRecords('user','user_id, is_active, is_block',array("email"=>$str),'',true);
		//print_r($user);
		if(!empty($user)){
			if($user['is_active'] && !$user['is_block']){
				return true;
			}else{
				if($user['is_block']){
					$this->form_validation->set_message('check_user_email', 'Your account is blocked by admin.');
				}else{
					$this->form_validation->set_message('check_user_email', 'Account is not active.');
				}
				return false;
			}
		}else{
			$this->form_validation->set_message('check_user_email', 'No user found with this email address.');
			return false;
		}
	}


	function resetPassword() {

		$this->page_title = "Reset Password";
		$is_login = $this->commonmodel->is_login();
		if(!$is_login){
			$this->load->library('form_validation');

			$this->form_validation->set_rules('pass', 'Password', 'trim|required|matches[passconf]');
			$this->form_validation->set_rules('passconf', 'Password Confirmation', 'trim|required');

			if ($this->form_validation->run() == FALSE) {
					$data = array();
					$data['token'] = $_GET['token'];
					$this->load->view('header');
					$this->load->view('user/reset-password', $data);
					$this->load->view('footer');
			} else {
					$post = $this->input->post();
					$user = $this->commonmodel->getRecords('user','user_id',array("reset_code"=>$post['token']),'',true);
					if(!empty($user)){
						$member_data = array(
							'password'=>md5($post['pass']),
							'reset_code'=>'',
							'updated'=>date("Y-m-d H:i:s"),
							);
						$this->commonmodel->commonAddEdit('user', $member_data, array('user_id'=>$user['user_id']));
						$sesMsgArray = array('status'=>1,'msg'=>"Thanks! You have reset your password.");

					}
					redirect();
			}
		}else{
			redirect();
		}
	}

	/**
	@param : user_name , password
	@return : create session of user_name, email, user_id.
	@detail : Checking for Login restrictions.
	*/
	public function check_login($str){
		$user = $this->commonmodel->getRecords('user','user_id, name, is_active, email, approved, count, blocked, role_id',array("email"=>$str,"password"=>md5($this->input->post('pass'))),'',true);
		if(!empty($user)){
			if($user['is_active']){
				$this->commonmodel->set_login($user);
				return true;
			}else{
				$this->form_validation->set_message('check_login', 'Account is not active.');
				return false;
			}
		}else{
			$this->form_validation->set_message('check_login', 'Invalid email or password.');
			return false;
		}
	}

	// login page for any user
	public function register()
	{
		$this->page_title = "JOIN";
		$is_login = $this->commonmodel->is_login();
		
		if(!$is_login){

			$this->load->library('form_validation');

			$_POST = $this->security->xss_clean($_POST);

			$this->form_validation->set_rules('name', 'Name', 'trim|required');

			$this->form_validation->set_rules('email', 'E-mail', 'trim|required|valid_email|is_unique[user.email]');
			$this->form_validation->set_rules('emailconf', 'E-mail', 'trim|required');

			$this->form_validation->set_rules('pass', 'Password', 'trim|required|matches[passconf]');
			$this->form_validation->set_rules('passconf', 'Password Confirmation', 'required');

			$this->form_validation->set_rules('captcha', 'Security code', 'trim|required|md5|matches[goto]');
			$this->form_validation->set_rules('goto', 'Verification Code', 'trim');

			$this->form_validation->set_rules('dob', 'Date of Birth', 'trim|exact_length[10]');

			if(isset($_FILES['picture']) && $_FILES['picture']['error']!=4){
				$this->form_validation->set_rules('picture', 'Profile picture', 'trim|callback_uploadProfilePicture');
			}

			$this->form_validation->set_message('is_unique', '%s you have entered is already in use.');
			
			if ($this->form_validation->run() == FALSE) {
					$this->load->helper('captcha');
					$data = array();
					$vals = array(
								'word'		 => '',
								'img_path'	 => './captcha/',
								'img_url'	 => base_url().'captcha/',
								'font_path'	 => './verdana.ttf',
								'img_width'	 => '196',
								'img_height' => 51,
								'expiration' => 7200
							);

					$captcha = create_captcha($vals);
					$data['captcha']=$captcha;

					if(!empty($this->image_data)){
						unlink($this->image_data['full_path']);
					}
					
					
					$this->load->view('common_header');
					$this->load->view('user/register', $data);
					$this->load->view('footer');
			} else {
					$veryFyToken = md5(uniqid());
					$registerDoneToken = uniqid();
					$post = $this->input->post();
					$user_data = array(
						'name'=>$post['name'],
						'password'=>md5($post['pass']),
						'activation_code'=>$veryFyToken,
						'reset_code'=>$registerDoneToken,
						'role_id'=>2,
						'gender'=>$post['gender'],
						'location'=>$post['location'],
						'occupation'=>$post['occupation'],
						'email'=>$post['email'],
						'is_block'=>0,
						'is_email_verified'=>0,
						'fb_id'=>0,
						'created'=>date("Y-m-d H:i:s"),
						'updated'=>date("Y-m-d H:i:s"),
						'is_active'=>0,
						'user_dob'=>date("Y-m-d", strtotime($post["dob"]))
					);

					if(!empty($this->image_data)){
						$user_data['picture']="uploads/profile-pic/".$this->image_data['file_name'];
					}
					$this->commonmodel->commonAddEdit('user', $user_data);

					$this->commonmodel->setMailConfig();
					$this->email->from(FROM_EMAIL, 'Khojgubani');
					$this->email->reply_to(REPLY_TO);
					$this->email->to($post['email']);
					$this->email->subject('Welcome to Khojgubani!');
					$link = site_url("user/verifyuserlink/".$veryFyToken);
					$message = 'Welcome,
					<br />
					<br />
					Please click the following link to verify your account:
					<br />
					<br /><a href="'.$link.'">'.$link.'</a>
					<br />
					<br />
					Thank You.';
					$this->email->message($message);
					$this->commonmodel->sendEmail();
					//@mail($user_data['email'],"Welcome to Khojgubani!",$message);
					redirect('user/register_done/'.$registerDoneToken);
			}
		}else{
			redirect();
		}
	}

	function register_done($registerDoneToken=""){

		$newUser = $this->commonmodel->getRecords("user", "*", array('reset_code'=>$registerDoneToken),'',true);
		if(!empty($newUser) && $registerDoneToken!=''){
			$this->commonmodel->commonAddEdit('user', array('reset_code'=>""),array('user_id'=>$newUser['user_id']));
			$this->page_title = "Registration Success";
			$data = array();
			$data['user'] = $newUser;
			$this->load->view('common_header');
			$this->load->view('user/register-done', $data);
			$this->load->view('footer');
		}else{
			redirect('');
		}
	}

	function check_email(){

		$this->page_title = "Check Email";
		$data = array();
		$this->load->view('header');
		$this->load->view('user/check-email', $data);
		$this->load->view('footer');
	}

	function verifyuserlink($veriFyToken = '')
	{
		// load models
		$this->load->model('user_model');
		$new_user = $this->commonmodel->getRecords("user", "*", array('activation_code' => $veriFyToken),'',true);
		if ( ! empty($new_user) && $veriFyToken != '')
		{
			$this->commonmodel->commonAddEdit('user', array('activation_code'=>"",'is_active'=>1,'is_email_verified'=>1),array('user_id'=>$new_user['user_id']));
			$this->page_title = "Email verification success";
			$data = array();
			$data['user'] = $new_user;
			// tell users with role >= 3 that we have a new user! hurrah!
			$mod_users = $this->user_model->get_by_field_value('role_id >=', 3);
			$this->load->library('email');
			$this->email->initialize(array('mailtype' => 'html'));
			$this->email->subject('We have a new user - Khojgurbani.org');
			foreach ($mod_users as $mod_user)
			{
				$email_data = array(
					'new_user' => $new_user,
					'mod_user' => $mod_user,
					'link' => site_url('admin/user_post_permissions')
					);
				$this->email->from(FROM_EMAIL, 'The Khojgurbani Team');
				$this->email->reply_to(REPLY_TO);
				$this->email->to($mod_user['email']);
				$this->email->subject('We have a new user - Khojgurbani.org');
				$this->email->message($this->load->view('emailer/user-successful-registration-notify-admins', $email_data, TRUE));
				$this->email->send();
			}
			// load view files
			$this->load->view('header');
			$this->load->view('user/veryfication-done', $data);
			$this->load->view('footer');
		}
		else
		{
			redirect('');
		}
	}

	function uploadProfilePicture(){

		$config = array('allowed_types' => 'jpg|jpeg|gif|png','upload_path' => "./uploads/profile-pic/",'max_size' => 2000);
    	$this->load->library('upload', $config);
    	//$this->upload->do_upload();
    	if ( ! $this->upload->do_upload('picture')){
			$this->form_validation->set_message('uploadProfilePicture', $this->upload->display_errors());
			return false;
		}else{
			$this->image_data = $this->upload->data();
			return true;
		}


	}
	// site home page handler

	public function faq() {
		$this->page_title = "FAQ";
		$this->load->view('header');
		$this->load->view('pages/faq');
		$this->load->view('footer');
	}// end of the index function


	// site home page handler

	public function about() {

	}// end of the index function


	// site home page handler

	public function worddetail($language=false, $query=false) {

		$this->load->library('form_validation');
		$this->page_title = "DICTIONARY";
		$this->load->view('common_header');

    $data = array(
      'posting' => isset($query),
      'query' => urldecode($query?$query:""),
      'language' => $language?$language:"gurmukhi"
    );

    $this->load->view('pages/worddetail', $data);
		$this->load->view('footer');
	}// end of the index function


	// site home page handler

	public function feedback() {
		$this->page_title = "FEEDBACK";
		$this->load->view('common_header');
		$this->load->view('pages/feedback');
		$this->load->view('footer');
	}// end of the index function

	function contactSubmit(){
        $post = $this->input->post();

		$post = $this->security->xss_clean($post);

        $this->commonmodel->setMailConfig();

        $user_subject = "Thank you for writing to Khoj Gurbani";
        $admin_subject = "Contact From Khoj Gurbani";
        $data['post'] = $post;

        $admin_message = $this->load->view('emailer/contact-us-admin',$data, true);
        $user_message =	$this->load->view('emailer/contact-us-user',$data, true);

        $this->email->from(FROM_EMAIL, 'Khoj Gurbani');
        $this->email->reply_to(REPLY_TO);
        $this->email->to($post['c_email']);
        $this->email->subject($user_subject);
        $this->email->message($user_message);
        $this->commonmodel->sendEmail();

        $this->email->from(FROM_EMAIL, 'Khoj Gurbani');
        $this->email->reply_to(REPLY_TO);
        $this->email->to("info@khojgurbani.org");
        $this->email->subject($admin_subject);
        $this->email->message($admin_message);
        $this->commonmodel->sendEmail();

		$this->email->from(FROM_EMAIL, 'Khoj Gurbani');
		$this->email->reply_to(REPLY_TO);
        $this->email->to("khojgurbani@gmail.com");
        $this->email->subject($admin_subject);
        $this->email->message($admin_message);
        $this->commonmodel->sendEmail();

        echo "success";
        exit;
    }

	public function logout(){
		$this->commonmodel->set_logout();
		if(strlen($this->input->get("continue")) > 0 && strpos($this->input->get("continue"), base_url('admin')) === false)
		{
			redirect($this->input->get("continue"));
		}
		else
			redirect('');
	}

	public function isFbUser(){
		if ($this->input->is_ajax_request()) {
			$post = $this->input->post();
			$userRs = $this->db->select('u.user_id, u.is_active, u.is_block')
							->from('user as u')
							->where('u.fb_id', $post['fb_id'])
							->or_where('u.email', $post['fb_email'])
							->get();
			$user = $userRs->row_array();
			if(!empty($user)){
				if($user['is_active']==1 && $user['is_block']==0){
					$this->commonmodel->set_login($user);
					echo json_encode(array('status'=>1,'msg'=>"Account found and active. login done!"));
					exit;
				}else{
					if($user['is_block']==1){
						echo json_encode(array('status'=>2,'msg'=>"Account is blocked by admin. login fail!"));
						exit;
					}
					if($user['is_active']==0){
						echo json_encode(array('status'=>3,'msg'=>"Account is not active. login fail!"));
						exit;
					}
				}
			}else{
				$user_data = array(
					'name'=>$post['fb_name'],
					'role_id'=>2,
					'email'=>$post['fb_email'],
					'is_block'=>0,
					'is_email_verified'=>1,
					'fb_id'=>$post['fb_id'],
					'created'=>date("Y-m-d H:i:s"),
					'updated'=>date("Y-m-d H:i:s"),
					'is_active'=>1
				);
				$this->commonmodel->commonAddEdit('user', $user_data);
				$user_id = $this->db->insert_id();
				$nuser = array('user_id'=>$user_id,'is_active'=>1,'is_block'=>0);
				$this->commonmodel->set_login($nuser);
				echo json_encode(array('status'=>4,'msg'=>"Account created and active. login done!"));
				exit;
			}
		}
	}
	
	public function isGpUser()
	{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/token');
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);  
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 3);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'code=' . urlencode($_GET['code']) . '&client_id=828626942566-udij1g812u2fbrjcgog317qnp5k5pmpn.apps.googleusercontent.com&client_secret=rpacufN8i3UzWFvGU9VN85FD&redirect_uri=' . base_url('user/isGpUser') . '&grant_type=authorization_code');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type:application/x-www-form-urlencoded"));
			$data = json_decode(curl_exec($ch));
			curl_close($ch);
			
			$token = $data->access_token;
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $token);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);  
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 3);
			$userData = json_decode(curl_exec($ch));
			curl_close($ch);
			
			if(!$userData || !is_object($userData))
				die("Login fail!");
			
			$userRs = $this->db->select('u.user_id, u.is_active, u.is_block')
							->from('user as u')
							->where('u.gp_id', $userData->id)
							->or_where('u.email', $userData->email)
							->get();
			$user = $userRs->row_array();
			//var_dump($userData);die;
			if(!empty($user)){
				if($user['is_active']==1 && $user['is_block']==0){
					$this->commonmodel->set_login($user);
					redirect(base_url());
					exit;
				}else{
					if($user['is_block']==1){
						echo "Account is blocked by admin. login fail!";
						exit;
					}
					if($user['is_active']==0){
						echo "Account is not active. login fail!";
						exit;
					}
				}
			}else{
				$user_data = array(
					'name'=>$userData->given_name . ' ' . $userData->family_name,
					'role_id'=>2,
					'email'=>$userData->email,
					'is_block'=>0,
					'is_email_verified'=>1,
					'gender'=>$userData->gender,
					'fb_id'=>0,
					'gp_id'=>$userData->id,
					'created'=>date("Y-m-d H:i:s"),
					'updated'=>date("Y-m-d H:i:s"),
					'is_active'=>1
				);
				//var_dump($user_data); die;
				$this->commonmodel->commonAddEdit('user', $user_data);
				$user_id = $this->db->insert_id();
				$nuser = array('user_id'=>$user_id,'is_active'=>1,'is_block'=>0);
				$this->commonmodel->set_login($nuser);
				redirect(base_url());
				exit;
			}
	}


	public function profile(){
	
		// load models
		$this->load->model('QnA_Model');
		$this->load->model('comments_model');
		$this->load->model('news_model');
		
		$this->page_title = "Profile";
		$is_login = $this->commonmodel->is_login();
		$data['segment3'] = $this->uri->segment(3);
		if($is_login){
			$user = $this->commonmodel->cur_user_info();



			if( $data['segment3'] == 'edit' ){
				$this->load->library('form_validation');

				$this->form_validation->set_rules('name', 'Name', 'trim|required');

				if(isset($_FILES['picture']) && $_FILES['picture']['error']!=4){
					$this->form_validation->set_rules('picture', 'Profile picture', 'trim|callback_uploadProfilePicture');
				}
				if ($this->form_validation->run() == FALSE) {

						if(!empty($this->image_data)){
							unlink($this->image_data['full_path']);
						}
						$data['user'] = $user;
						$data['user_info'] = $user;
						$data['questions'] = $this->QnA_Model->getQuestionsByUser( $data['user']['user_id'], 'created desc' );
						$data['answers'] = $this->QnA_Model->getAnswersByUser( $data['user']['user_id'], 'created desc' );
						$data['comments'] = $this->QnA_Model->getCommentsByUser( $data['user']['user_id'] );
						$data['blog_comments'] = $this->comments_model->comments_get_by_author($data['user']['user_id'], 'comment_date desc');
						$data['blog_posts'] = $this->news_model->news_get_by_field(array('news_author' => $data['user']['name']), 'news_date desc');
						$data['count_blog_comments'] = $this->comments_model->comments_count($data['user']['user_id']);
						$this->load->view('common_header');
						$this->load->view('user/profile', $data);
						$this->load->view('footer');
				}else {

						$post = $this->input->post();
						$post = $this->security->xss_clean($post);
						$user_data = array(
							'name'=>$post['name'],
							'gender'=>$post['gender'],
							'location'=>$post['location'],
							'occupation'=>$post['occupation'],
							'updated'=>date("Y-m-d H:i:s")
						);

						if(!empty($this->image_data)){
							if($post['old_picture']!='images/default.jpeg'){
								unlink($post['old_picture']);
							}
							$user_data['picture']="uploads/profile-pic/".$this->image_data['file_name'];
						}
						$this->commonmodel->commonAddEdit('user', $user_data,array('user_id'=>$user['user_id']));
						redirect('user/profile/edit');
				}

			}else if( $data['segment3'] == '' ){
				$data['user'] = $user;
				$data['user_info'] = $user;
				$data['questions'] = $this->QnA_Model->getQuestionsByUser( $data['user']['user_id'], 'created desc' );
				$data['answers'] = $this->QnA_Model->getAnswersByUser( $data['user']['user_id'], 'created desc' );
				$data['comments'] = $this->QnA_Model->getCommentsByUser( $data['user']['user_id'] );
				$data['blog_posts'] = $this->news_model->news_get_by_field(array('news_author' => $data['user']['name']), 'news_date desc');
				$data['blog_comments'] = $this->comments_model->comments_get_by_author($data['user']['user_id'], 'comment_date desc');
				$data['count_blog_comments'] = $this->comments_model->comments_count($data['user']['user_id']);
				$this->load->view('common_header');
				$this->load->view('user/profile', $data);
				$this->load->view('footer');
			}else if( is_numeric( $data['segment3'] ) ){
				
				$data['user'] = $user;
				$data['user_info'] = $this->commonmodel->get_user_info( $data['segment3'] );
				if(isset($data["user_info"]["user_id"]) && $data["user"]["user_id"] != $data["user_info"]["user_id"]){
					$this->commonmodel->add_hit($data["user_info"]["user_id"]);
				}
				//$data['questions'] = $this->QnA_Model->getQuestionsByUser( $data['segment3'], 'created desc' );
				//$data['answers'] = $this->QnA_Model->getAnswersByUser( $data['segment3'], 'created desc' );
				$data['comments'] = $this->QnA_Model->getCommentsByUser( $data['segment3'] );
				$data['blog_posts'] = $this->news_model->news_get_by_field(array('news_author' => $data['user_info']['name']), 'news_date desc');
				$data['blog_comments'] = $this->comments_model->comments_get_by_author($data['segment3'], 'comment_date desc');
				$data['count_blog_comments'] = $this->comments_model->comments_count($data['segment3']);
				$this->load->view('common_header');
				$this->load->view('user/profile', $data);
				$this->load->view('footer');
			}else{
				redirect();
			}

		}else if( is_numeric( $data['segment3'] ) ){
			$data['user'] = $this->commonmodel->get_user_info( $data['segment3'] );
			$data['user_info'] = $this->commonmodel->get_user_info( $data['segment3'] );
			$this->commonmodel->add_hit($data["user_info"]["user_id"]);
			$data['questions'] = $this->QnA_Model->getQuestionsByUser( $data['segment3'] );
			$data['answers'] = $this->QnA_Model->getAnswersByUser( $data['segment3'] );
			$data['comments'] = $this->QnA_Model->getCommentsByUser( $data['segment3'] );
			$data['blog_posts'] = $this->news_model->news_get_by_field(array('news_author' => $data['user_info']['name']), 'news_date desc');
			$data['blog_comments'] = $this->comments_model->comments_get_by_author($data['segment3'], 'comment_date desc');
			$data['count_blog_comments'] = $this->comments_model->comments_count($data['segment3']);
			$this->load->view('common_header');
			$this->load->view('user/profile', $data);
			$this->load->view('footer');
		}else{
			redirect();
		}
	}


	function changePassword($changed = false){
		$this->page_title = "Change Password";
		$is_login = $this->commonmodel->is_login();
		if($is_login){
			$this->load->library('form_validation');
			$user = $this->commonmodel->cur_user_info();
			$this->form_validation->set_rules('oldpass', 'Old password', 'trim|required|callback_checkoldpass');
			$this->form_validation->set_rules('pass', 'Password', 'trim|required|matches[passconf]');
			$this->form_validation->set_rules('passconf', 'Password Confirmation', 'required');

			if ($this->form_validation->run() == FALSE) {
					$data = array();
					if($changed){
						$data["message"] = "Your password has been changed.";
					}
					$this->load->view('common_header');
					$this->load->view('user/change-password', $data);
					$this->load->view('footer');
			} else {
					$post = $this->input->post();
					$user_data = array(
						'password'=>md5($post['pass']),
						'updated'=>date("Y-m-d H:i:s"),
					);

					$this->commonmodel->commonAddEdit('user', $user_data,array('user_id'=>$user['user_id']));
					redirect('user/changePassword/finish');
			}
		}else{
			redirect();
		}
	}

	function checkoldpass($str){
		$is_login = $this->commonmodel->is_login();
		if($is_login){
			$user_id = $this->commonmodel->cur_user_id();
			$user = $this->commonmodel->getRecords('user','user_id,password',array("user_id"=>$user_id),'',true);
			if(!empty($user)){
				if($user["password"]==""){
					$this->form_validation->set_message('checkoldpass', 'You are using facebook login. please try to use forget password to get new password.');
					return false;
				}elseif($user["password"]==md5($str)){
					return true;
				}else{
					$this->form_validation->set_message('checkoldpass', 'Incorrect old password.');
					return false;
				}
			}
		}else{
			$this->form_validation->set_message('checkoldpass', 'Session not valid.');
			return false;
		}
	}

	function deleteProfilePic(){
		$is_login = $this->commonmodel->is_login();
		if($is_login){
			$user = $this->commonmodel->cur_user_info();
			unlink($user['picture']);
			$user_data['picture']="images/default.jpeg";
			$user_data['updated']=date("Y-m-d H:i:s");
			$this->commonmodel->commonAddEdit('user', $user_data,array('user_id'=>$user['user_id']));
			echo "success";
		}
	}

	function subscribe_email(){

		$post['email'] 	= trim($_POST['email']);
		$post['date'] 	= date("Y-m-d H:i:s");
		$post['ip'] 	= $_SERVER['REMOTE_ADDR'];
		$user = $this->commonmodel->getRecords('subscribers','email',array("email"=>$post['email']),'',true);
		if(!empty($user)){
				$response = array(
				'status' => $this->config->item('status_error'),
				'message' => 'email already subscribed'
				);
		}
		else
		{
			$response = array(
			'status' => $this->config->item('status_ok'),
			'message' => 'email subscribed'
			);
			$this->commonmodel->commonAddEdit('subscribers', $post);
		}
		$this->output->set_output(json_encode($response));

	}
}
/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
