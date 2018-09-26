<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
* Class Home
*/
class Messages extends CI_Controller
{
    /**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		parent::__construct();
		ob_clean();
	}

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$this->load->view('index', ['color' => 'white']);
	}

    /**
	 * Get message
	 *
	 * @return type json
	 */
	public function getMessage()
	{
		$this->load->model('user_model');

		$ad_id    = $this->input->post('ad_id', true);
		$user_id  = $this->session->userdata('user')->id;
		$response = $this->user_model->get_message($ad_id, $user_id);

		if ($response) {
			$this->user_model->read_all_messages($ad_id, $user_id);
			$result = ['error' => false,  'message' => $response];
		} else {
			$result = ['error' => true];
		}

		$this->output
	        ->set_content_type('application/json')
	        ->set_output(json_encode($result));
	}

	/**
	 * Get all messages for current user
	 *
	 * @return type json
	 */
	public function getMessages()
	{
		$this->load->model('user_model');

		$id       = $this->session->userdata('user')->id;
		$count    = $this->user_model->getUnread_messagesCount($id)->count;
		$response = $this->user_model->get_user_messages($id);

		if ($response) {
			$result = ['error' => false, 'count' => $count, 'message' => $response];
		} else {
			$result = ['error' => true];
		}

		$this->output
	        ->set_content_type('application/json')
	        ->set_output(json_encode($result));
	}

    /**
	 * Get last message for current user
	 */
	public function getLastMessage()
	{
		if ($this->session->userdata('user')) {
			$this->load->model('user_model');

			$id       = $this->session->userdata('user')->id;
			$messages = $this->user_model->get_last_messages($id);

			$this->load->view('messages', ['messages' => $messages]);
		} else {
			redirect('home','refresh');
		}
	}

    /**
	 * Send message
	 *
	 * @return type json
	 */
	public function sendMessage()
	{
		$this->load->model('user_model');

		$ad_id       = $this->input->post('ad_id', true);
		$description = $this->input->post('message', true);
		$user_id     = $this->session->userdata('user')->id;
		$from_id     = $this->user_model->get_last_message_user($user_id, $ad_id);
		$to_user     = $from_id->from_user;
		$from_user   = $user_id;
		$realAd_id   = $from_id->ad_id;
		$response    = $this->user_model->send_message($to_user, $from_user, $description, $realAd_id);

		if ($response) {
			$result = ['error' => false, 'message' => 'Success'];
		} else {
			$result = ['error' => true];
		}

		$this->output
	        ->set_content_type('application/json')
	        ->set_output(json_encode($result));
	}

}
