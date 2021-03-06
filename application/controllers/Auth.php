<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->helper(array('url', 'form'));	
		$this->load->library("session");
	}
	public function index()
	{
		
		if ($this->session->has_userdata('id_user')) {
			$this->load->model('m_auth');
			$model = new M_Auth();

			$this->load->view('v_auth');
			$view = new V_Auth();

			// Show owner courses
			$this->load->model('m_auth');
			$model = new M_Auth();
			$owner = $model->show_owner_course();

			$data = $model->show_once();

			if ($this->input->post('changeinfo') == 'changeinfo') {
				$id = $this->session->userdata('id_user');
				$name = $this->input->post('name_user');
				$job = $this->input->post('job_user');
				$about = $this->input->post('about_user');

				$model->changeinfo($id, $name, $job, $about);
			}
			if ($this->input->post('changepass') == 'changepass') {
				$id = $this->session->userdata('id_user');
				$oldpass = $this->input->post('oldpass');
				$newpass = $this->input->post('newpass');

				$model->changepass($id, $oldpass, $newpass);
			}
			if ($this->input->post('change_image') == 'submit') {
				$config['upload_path']          = './res/uploads/';
                $config['allowed_types']        = 'jpeg|jpg|png';
                $config['max_size']             = 10240;
                // $config['max_width']            = 1024;
                // $config['max_height']           = 768;

                $this->load->library('upload', $config);

                if ( ! $this->upload->do_upload('image'))
                {
                	$this->session->set_flashdata('error', $this->upload->display_errors());
                }
                else
                {
                    $upload_data = array('upload_data' => $this->upload->data());
                    foreach ($upload_data as $key => $value) {
                    	$file_name = $value['file_name'];
                    }
                    $id_user = $this->session->userdata['id_user'];
                    $model->change_image($file_name, $id_user);
                    $this->session->set_flashdata('error', 'Thay ?????i ???nh ?????i di???n th??nh c??ng!');
                    redirect(base_url('auth'));
                }
			}
			$view->show_info($data, $owner);
		}
		else{
			redirect(base_url('auth/login'));
		}
	}
	public function login()
	{
		if ($this->session->has_userdata('id_user')) {
			redirect(base_url('auth'));
		}
		else{
			$this->load->library('form_validation');
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
			$this->form_validation->set_rules('pass', 'M???t kh???u', 'required|min_length[6]');
			if($this->form_validation->run() == FALSE){
				$this->load->view('v_auth');
				$view = new V_Auth();
				$view->show_login();
			}
			else{
				$this->login_submit();
			}
		}
		
	}
	public function login_submit()
	{
		$this->load->model('m_auth');
		$model = new M_Auth();

		if ($this->input->post('login') == 'login') {
			$email = $this->input->post('email');
			$pass = md5($this->input->post('pass'));
			$error = $model->login($email, $pass);
			if ($error == 2) {
				$this->session->set_flashdata('error', 'T??i kho???n c???a b???n ch??a ???????c k??ch ho???t!<br>- Vui l??ng ki???m tra l???i h???p th?? ?????n trong Email!');
			}
			if ($error == 1) {
				$this->session->set_flashdata('error', '????ng nh???p th??nh c??ng!');
				redirect(base_url('auth'));
			}
			if ($error == 0){
				$this->session->set_flashdata('error', 'T??i kho???n ho???c m???t kh???u kh??ng ????ng!<br>- Vui l??ng nh???p l???i!');
			}
			redirect(base_url('auth/login'));
		}
		else{
			redirect(base_url('auth/login'));
		}
	}
	public function login_fb()
	{
		$this->load->library('facebook', array('appId' => '236587060565211', 'secret' => '14195e4f595a015e30bdd201c6c93240'));
		$user = $this->facebook->getUser();
		if ($user) {
			echo $data['user_profile'] = $this->facebook->api('/me/'); die();
		}
		else{
			echo $data['login_url'] = $this->facebook->getLoginUrl(); die();
		}
	}
	public function register()
	{
		if ($this->session->has_userdata('id_user')) {
			redirect(base_url('auth'));
		}
		else{
			$this->load->library('form_validation');
			$this->form_validation->set_rules('username', 'H??? v?? t??n', 'required');
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
			$this->form_validation->set_rules('pass', 'M???t kh???u', 'required|min_length[6]');
			if($this->form_validation->run() == FALSE){
				$this->load->view('v_auth');
				$view = new V_Auth();
				$view->show_register();
			}
			else{
				$this->register_submit();
			}
		}
		
		
	}
	public function register_submit()
	{
		
		if ($this->input->post('register') == 'register') {
			$username = $this->input->post('username');
			$email = $this->input->post('email');
			$pass = md5($this->input->post('pass'));
			$type_account = $this->input->post('type_account');
			$job_user = $this->input->post('job');
			$date = date("Y-m-d");
			$code = $this->generateRandomString();

			// T???o d??? li???u g???i Email

			$link = base_url('auth/request').'?type=active&email='.$email.'&code='.$code;
			if ($type_account == 2) {
				$link = base_url('auth/request').'?type=active_teacher&email='.$email.'&code='.$code;
			}
			$message = 'Xin ch??o '.$username.' !<br>Email c???a b???n ???? ???????c s??? d???ng ????? k??ch ho???t t??i kho???n tr??n h??? th???ng Edumall.<br>N???u b???n th???c hi???n vi???c n??y, h??y b???m v??o <a href="'.$link.'">????y</a> ????? k??ch ho???t!<br>Ho???c ???????ng li??n k???t sau: '.$link.'<br>- N???u b???n kh??ng th???c hi???n vi???c n??y, h??y b??? qua th?? c???a ch??ng t??i.<br>C???m ??n b???n!';
			$result = $this->sendMail($email, $subject, $message);

			// ???? g???i Email

			if ($result == 1) {
				$this->session->set_flashdata('error', '<b>G???i Email th??nh c??ng!</b>!<br>H??y ki???m tra l???i h???p th?? ?????n trong Email ????? x??c nh???n!<br>????y l?? ???????ng link k??ch ho???t c???a b???n: <a href="'.$link.'">'.$link.'</a>');
			}
			if ($result == 0) {
				$this->session->set_flashdata('error', '<b>L???i g???i Email!</b><br>????y l?? l???i c???a ch??ng t??i.<br>Li??n h??? <a href="mailto:khanhtit113@gmail.com">Admin</a> ????? b??o l???i.<br>????y l?? ???????ng link k??ch ho???t c???a b???n: <a href="'.$link.'">'.$link.'</a>');
			}
			$this->load->model('m_auth');
			$model = new M_Auth();
			$model->register($username, $email, $pass, $type_account, $job_user, $date, $code);
		}
		redirect(base_url('auth/register'));
	}
	public function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	public function request()
	{
		$type = $this->input->get('type');
		$email = $this->input->get('email');
		$code = $this->input->get('code');
		if ($type == NULL || $email == NULL || $code == NULL) {
			redirect(base_url('auth'));
		}
		else{
			$this->load->model('m_auth');
			$model = new M_Auth();

			if ($type == 'active') {
				$result = $model->active($email, $code);
				if ($result == 1) {
					$this->session->set_flashdata('error', 'K??ch ho???t t??i kho???n th??nh c??ng!');
				}
				else{
					$this->session->set_flashdata('error', 'K??ch ho???t t??i kho???n kh??ng th??nh c??ng ho???c b???n ???? k??ch ho???t tr?????c ????!');
				}
				redirect(base_url('auth/login'));
			}
			if ($type == 'active_teacher') {
				$result = $model->active_teacher($email, $code);
				if ($result == 1) {
					$this->session->set_flashdata('error', 'K??ch ho???t t??i kho???n th??nh c??ng!');
				}
				else{
					$this->session->set_flashdata('error', 'K??ch ho???t t??i kho???n kh??ng th??nh c??ng ho???c b???n ???? k??ch ho???t tr?????c ????!');
				}
				redirect(base_url('auth/login'));
			}
			if ($type == 'forgot_password') {
				$result = $model->check_code($email, $code);
				if ($result == 1) {
					if ($this->input->post('newpass')) {
						$newpass = md5($this->input->post('newpass'));
						$model->reset_pass($email, $newpass, $code);
						$this->session->set_flashdata('error', 'M???t kh???u c???a b???n ???? ???????c ?????i!');
						redirect(base_url('auth/login'));
					}
					$this->load->view('v_auth');
					$view = new V_Auth();
					$view->reset_pass();
				}
				else{
					$this->session->set_flashdata('error', 'Thay ?????i m???t kh???u th???t b???i ho???c sai m?? b?? m???t!');
					redirect(base_url('auth/login'));
				}
			}
		}
	}
	public function forgot_password()
	{
		$email = $this->input->post('email');
		if ($email == NULL) {
			$this->session->set_flashdata('error', 'T??m l???i m???t kh???u tr???ng!');
		}
		else{
			$this->load->model('m_auth');
			$model = new M_Auth();	
			$result = $model->forgot_pass($email);

			// N???u nh???p ????ng Email c?? trong h??? th???ng
			if ($result == 1) {

				// T???o d??? li???u g???i Email
				$code = $this->generateRandomString();
				$link = base_url('auth/request/').'?type=forgot_password&email='.$email.'&code='.$code;
				$message = 'Xin ch??o !<br>B???n ???? y??u c???u c???p l???i m???t kh???u t??i kho???n c???a b???n tr??n h??? th???ng Edumall.<br>N???u b???n th???c hi???n vi???c n??y, h??y b???m v??o <a href="'.$link.'">????y</a> ????? ?????t l???i m???t kh???u!<br>Ho???c ???????ng li??n k???t sau: '.$link.'<br>- N???u b???n kh??ng th???c hi???n vi???c n??y, h??y b??? qua th?? c???a ch??ng t??i.<br>C???m ??n b???n!';
				$result_email = $this->sendMail($email, $subject, $message);

				// ???? g???i Email

				if ($result_email == 1) {
					$this->session->set_flashdata('error', '<b>G???i Email th??nh c??ng!</b>!<br>H??y ki???m tra l???i h???p th?? ?????n trong Email ????? x??c nh???n!<br>????y l?? ???????ng link k??ch ho???t c???a b???n: <a href="'.$link.'">'.$link.'</a>');
				}
				if ($result_email == 0) {
					$this->session->set_flashdata('error', '<b>L???i g???i Email!</b><br>????y l?? l???i c???a ch??ng t??i.<br>Li??n h??? <a href="mailto:khanhtit113@gmail.com">Admin</a> ????? b??o l???i.<br>????y l?? ???????ng link k??ch ho???t c???a b???n: <a href="'.$link.'">'.$link.'</a>');
				}
				$model->set_code($email, $code);
			}
			else{
				$this->session->set_flashdata('error', '<b>L???i!</b><br>Email b???n nh???p v??o kh??ng c?? trong h??? th???ng c???a ch??ng t??i!');
			}
		}
		redirect(base_url('auth/login'));
	}
	private function sendMail($email, $subject, $message)
	{
		$config = Array(
			'protocol' => 'smtp',
			'smtp_host' => 'ssl://smtp.googlemail.com',
			'smtp_port' => 465,
  			'smtp_user' => 'khanhtitwebdev@gmail.com',
  			'smtp_pass' => 'jigbqrllpxwgdgdo',
  			'mailtype' => 'html',
  			'charset' => 'UTF-8',
  			'wordwrap' => TRUE
  		);
		
		$this->load->library('email', $config);
		$this->email->set_newline("\r\n");
     	$this->email->from('khanhtitwebdev@gmail.com');
    	$this->email->to($email);
    	$this->email->subject($subject);
    	$this->email->message($message);
    	if($this->email->send())
    	{
    		$result = 1;
    	}
    	else
    	{
    		$result = 0;
    		// show_error($this->email->print_debugger());
    	}
    	return $result;

    }
    public function money()
	{
		if ($this->session->has_userdata('id_user') == FALSE) {
			redirect(base_url('auth'));
		}
		$this->load->view('v_auth');
		$view = new V_Auth();
		$this->load->model('m_auth');
		$model = new M_Auth();
		if ($this->input->post('nap_the') == 'submit') {
			$menh_gia = $this->input->post('menh_gia');
			$ma_nap = $this->input->post('ma_nap');
			$model->add_money($menh_gia, $ma_nap);
			$this->session->set_flashdata('error', 'N???p th??m <b>'.number_format($menh_gia).'</b> VND th??nh c??ng!');
			redirect(base_url('auth'));
		}
		$view->add_money();
		
	}
	public function logout()
	{
		// Clear all SESSION value
		$this->session->sess_destroy();
		redirect(base_url('auth/login'));
	}
	
}