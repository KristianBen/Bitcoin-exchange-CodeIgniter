<?php if(!defined('BASEPATH')) exit('No direct access allowed');

class  Clients extends Auth_Controller{
	public function __construct(){
		parent::__construct();
	}


public function editClientData($client_id = null)
{
	$this->load->helper('form');

	$this->load->model('clients_mdl'); 
	$this->data['client'] = $this->clients_mdl->get_client_data($client_id); 

	$this->data['users_types'] = $this->clients_mdl->get_users_types();

	if(!$this->input->post()){ 
		parent::render('clients/edit_client_data', 'admin');
	} 
	else{
		$this->load->library('form_validation'); 

		$this->form_validation->set_rules('first_name', 'First name', 'trim|required');
		$this->form_validation->set_rules('last_name', 'Last name', 'trim|required');
		$this->form_validation->set_rules('company', 'Company', 'trim|required');
		$this->form_validation->set_rules('phone', 'Phone', 'trim|required');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
		if($this->form_validation->run() == FALSE){ 
			parent::render('clients/edit_client_data', 'admin');
		}
		else{ 
		    $client_id = $this->input->post('client_id'); 
				
		    if($this->input->post('is_active') === "0"){
			$this->ion_auth->deactivate($client_id); 
		    } 

		    $this->clients_mdl->updateClientData(); 
		    $this->edit_client();
		}
	}
}

public function addClient(){ 
	$this->load->helper('form');

	if($this->input->post()){ 
		$this->load->library('form_validation'); 

		$this->form_validation->set_rules('first_name', 'First name', 'trim|required');
		$this->form_validation->set_rules('last_name', 'Last name', 'trim|required');
		$this->form_validation->set_rules('company', 'Company', 'trim|required');
		$this->form_validation->set_rules('phone', 'Phone', 'trim|required');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|is_unique[users.email]');
		$this->form_validation->set_rules('password','Password','required|min_length[8]|max_length[20]');
		$this->form_validation->set_rules('password_confirm','Password confirmation','required|matches[password]');

		$this->form_validation->set_rules('brands_nb', 'Number of brands', 'required|is_natural_no_zero');

		if($this->form_validation->run() === FALSE ){ 
			parent::render('clients/add_client', 'admin');		
		}
		// Validation OK, save data
		else{ 

		    $this->load->model('clients_mdl');

		    $username = "none";
		    $email = $this->input->post('email');
		    $password = $this->input->post('password');

		    $additional_data = array(
			'first_name' => $this->input->post('first_name'),
			'last_name'  => $this->input->post('last_name'),
			'company'    => $this->input->post('company'),
			'phone'      => $this->input->post('phone')
		    );

		    // form_validation = OK
		    // create new user in 'users' table
		    if($this->ion_auth->register($username, $password, $email, $additional_data)){
			    // get the id of the latest user registered
			    $this->load->model('admin_mdl');
			    $res = $this->admin_mdl->getUserIdByEmail();

			    $latest_id = $res[0]->id;
			    $brands_nb = $this->input->post('brands_nb'); 
			    $is_active = $this->input->post('is_active'); 

			    // Unless configured to use manual/email activation
			    // Ion_auth automatically activates the new user
			    // That is why, if our administrator, mentioned a user as 
			    // not_active, we should launch the ion_auth->deactive($id) 
			    if($this->input->post('is_active') === "0"){
			   	$this->ion_auth->deactivate($latest_id); 
			    } 

			    // after the registration of the new client
			    // allocate records for the nb of registered brands
			    $this->load->model('brands_mdl');
			    if($this->brands_mdl->clientAllocateBrands($latest_id, $brands_nb, $is_active)){

				// assign the client role (role_data.id = 2)
				    /*
			        $this->load->model('admin_mdl');
				$this->admin_mdl->assign_role($latest_id, 2);				
				     */

			   	// registration succeeded -> go home
				 parent::render(null, 'admin'); 
				 /**/
			    }
		    }

		}
	}
	// No post received, reditect to form
	else{ 
		parent::render('clients/add_client', 'admin');		
	} 
}

public function editClient()
{
	// load a list of all clients
	$this->load->model('clients_mdl'); 
	$this->data['clients'] = $this->clients_mdl->getAllClients(); 

	parent::render('clients/list_all_clients', 'admin');
}

public function listClientRoles($client_id)
{
	$this->data['client_id'] = $client_id;


	$data = array('userID' => $client_id);
	$this->load->library('acl');
	$this->acl->buildACL($data); 

	$this->data['user_roles_ids'] = $this->acl->getUserRoles();

	/* gather all roles' names */
	$this->data['all_roles_id'] = $this->acl->getAllRoles();

	$this->data['all_roles_names'] = array(); 
	foreach($this->data['all_roles_id'] as $v){
		$this->data['all_roles_names'][] = $this->acl->getRoleNameFromID($v);
	} 

	parent::render('clients/edit_client_roles', 'admin');
}

public function updateClientRoles()
{
	$this->load->model('clients_mdl');

	$client_id = $this->input->post('clientID');
	$role_id = $this->input->post('roleID');
	$update_val = $this->input->post('das_val'); 

	if($update_val == 1){
		if($this->clients_mdl->addClientRole($client_id, $role_id)){
			echo "Role has been added";
		}
	}
	else{
		if($this->clients_mdl->removeClientRole($client_id, $role_id)){
			echo "Role has been removed";
		}	
	}

	return false;
}




public function addUserType()
{
	$this->load->helper('form');

	$this->load->model('roles_mdl');
	$this->data['roles'] = $this->roles_mdl->getAllRoles(); 

	if($this->input->post()){
		$this->load->model('clients_mdl');
		$this->clients_mdl->addUserType(); 
		
		parent::render(null, 'admin');
	}
	else{ 
		parent::render('clients/add_user_type', 'admin');
	} 
}

} 
