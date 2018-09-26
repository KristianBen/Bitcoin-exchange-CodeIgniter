<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Portfolio extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$userAuth = $this->authentication->getUser();
		if(empty($userAuth)) {
        	redirect('/admin/login/');
        }

        $this->data['cname'] = $this->router->fetch_class();
		$this->data['cmethod'] = $this->router->fetch_method();
		$this->load->model('portfolio_model');
		$this->load->model('clients_model');

	}

	public function index() {
		$this->load->helper('paginationConfig');
		$this->load->library('pagination');
		$page = $this->uri->segment(4);
    	if(!$page){
    		$page = 0;
    	}
		$count_all = $this->portfolio_model->get_count_all();
		$count_portfolio = 10;
		$base_url = base_url()."admin/portfolio/index/";
		add_pagination($base_url, $count_all, $count_portfolio, $config);
		$this->pagination->initialize($config);
		if($count_all>$count_portfolio){
			$this->data['pagination_links'] = $this->pagination->create_links();
		}

		$this->load->library('form_validation');
		$this->load->helper(array('form'));
		$this->template->setTitle('Portfolio');
		$this->data['records'] = $this->portfolio_model->find_in_limit($page,$count_portfolio);
		$this->load->admin_vew('portfolio/index',$this->data);
	}

	public function add() {
		$this->template->setTitle('Portfolio add');
		$this->load->library('form_validation');
		$this->load->helper(array('form'));
		$this->load->model('categories_model');
		$this->load->model('image_model');
		$this->data['res'] = new stdClass();
		$this->data['res']->title = '';
		$this->data['res']->url = '';
		$this->data['res']->img = '';
		$this->data['res']->category_id = '';
		$this->data['res']->client_id = '';
		$this->data['clients'] = $this->clients_model->findAllForAdmin();
		$this->data['categories'] = $this->categories_model->findAll('*',array('type'=>'portfolio'));
		if ($this->input->post()) {
			$this->form_validation->set_rules('title', 'Title', 'required|trim');
			$this->form_validation->set_rules('url', 'Url', 'required|trim');
			$this->form_validation->set_rules('category_id', 'Category', 'required|trim|is_natural_no_zero');
			if (!$this->form_validation->run() == FALSE) {
				$this->load->library('upload');
				$config['upload_path'] = 'media/portfolio/';
				$config['allowed_types'] = 'gif|jpg|png';
				$this->upload->initialize($config);

				if($this->upload->do_upload('img') == true) {
					$title = $this->input->post('title',true);
					$url = $this->input->post('url',true);
					$category_id = $this->input->post('category_id',true);
					$client_id = $this->input->post('client_id', true);
					
					$upload_data = $this->upload->data();
					$data = array (
						'title' => $title,
						'url' => $url,
						'category_id' => $category_id,
						'client_id'=> $client_id,
						'img' => $upload_data['file_name']
					);

					$this->portfolio_model->insert($data);

					$this->image_model->create_thumb($config['upload_path'].$upload_data['file_name'], $config['upload_path'], '238', '238', $upload_data['image_width'], $upload_data['image_height']);
					redirect(base_url('admin/portfolio'));
				} else {
					echo $this->upload->display_errors();
				}
			}
		}
		$this->load->admin_vew('portfolio/add_edit',$this->data);
	}
	public function edit($id) {
		$this->template->setTitle('Portfolio edit');
		$this->data['res'] = $this->portfolio_model->find('*', array('id'=> $id));
		$this->load->library('form_validation');
		$this->load->helper(array('form'));
		$this->load->model('categories_model');
		$this->load->model('image_model');
		$this->data['clients'] = $this->clients_model->findAllForAdmin();
		$this->data['categories'] = $this->categories_model->findAll('*',array('type'=>'portfolio'));
		if ($this->input->post()) {
			$this->form_validation->set_rules('title', 'Title', 'required|trim');
			$this->form_validation->set_rules('url', 'Url', 'required|trim');
			$this->form_validation->set_rules('category_id', 'Category', 'required|trim|is_natural_no_zero');

			if (!$this->form_validation->run() == FALSE) {
				$title = $this->input->post('title',true);
				$url = $this->input->post('url',true);
				$category_id = $this->input->post('category_id',true);
				$client_id = $this->input->post('client_id',true);
				$hidden = $this->input->post('hidden',true);

				$data = array (
					'title' => $title,
					'url' => $url,
					'category_id' => $category_id,
					'client_id' => $client_id
				);

				$this->portfolio_model->update($id,$data);

				$this->load->library('upload');
				$config['upload_path'] = 'media/portfolio/';
				$config['allowed_types'] = 'gif|jpg|png';
				$this->upload->initialize($config);
				if ($this->upload->do_upload('img') == true) {
					$upload_data = $this->upload->data();
					$path = realpath($config['upload_path']) .'/'. $hidden;
					unlink($path);
					
					$up = array (
						'img' => $upload_data['file_name']
					);
					$this->portfolio_model->update($id,$up);
					$this->image_model->create_thumb($config['upload_path'].$upload_data['file_name'], $config['upload_path'], '238', '238', $upload_data['image_width'], $upload_data['image_height']);
				}
				redirect('admin/portfolio');
			}
		}
		$this->load->admin_vew('portfolio/add_edit',$this->data);
	}
	public function delete($id) {
		$img = $this->portfolio_model->find('img', array('id'=> $id));
		if(!empty($img)) {
			$path = realpath('media/portfolio/') . '/' . $img->img;
			unlink($path);
		}
		$where = array (
			'id' => $id
		);
	    $this->portfolio_model->delete($where);
		redirect('admin/portfolio');
	}

	public function sorting(){
		if($this->input->post('data',true)){
			$data = $this->input->post('data',true);
			$this->portfolio_model->update_position($data);
			
		}
		else{
			$records = $this->portfolio_model->findAllForAdmin();
			$this->output
				->set_content_type('application/json')
				->set_output(json_encode($records));
		}
	}
}