<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Auth extends MY_Controller{
	public function __construct(){
		parent::__construct();
		$this->load->helper('url');
		$this->load->helper('form');
	}
	public function login(){
		if($this->input->is_ajax_request()){
			$this->authentication->current_hold_status();
			$this->config->set_item('allowed_pages_for_login',['ajax_attempt_login']);
			$string=$this->input->post('login_string');
			$password=$this->input->post('login_pass');
			$form_token=$this->input->post(config_item('login_token_name'));
			$this->load->library('form_validation');
			$this->load->helper('idx');
			$this->lang->load('form','ru');
			$postData=$this->security->xss_clean($this->input->raw_input_stream);
			parse_str($postData,$data);
			$this->form_validation->set_data($data);
			$validation_rules=[
				[
					'field' => 'login_string',
					'rules' => [
						'required',
						'validatePhone',
					],
					'errors' => [
						'validatePhone' => $this->lang->line('Wrong phone number'),
						'required' => $this->lang->line('Phone is required')
					]
				],[
					'field' => 'login_pass',
					'rules' => [
						'trim',
						'required',
					],
					'errors' => [
						'required' => $this->lang->line('Password can\'t be blank')
					]
				],[
					'field' => 'code',
					'rules' => [
						'trim',
						'required',
						['validateCode_callable',function($code){
							if(preg_match('/^[0-9]{4}$/',$code)){
								return true;
							}
							return false;
						}],
					],
					'errors' => [
						'validateCode_callable' => $this->lang->line('Wrong code')
					]
				]			
			];		
			$this->form_validation->set_rules($validation_rules);		
			if(!$this->form_validation->run()){
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => $this->form_validation->error_array(),
							'form_token' => $this->tokens->token(),
					]));			
			}
			$this->load->model('auth/au_model');
			$phone=preg_replace('/^\\+?7|\\|7|\s7|\\D/','',$this->form_validation->validation_data['login_string']);
			$user=$this->au_model->get_user($phone);
			if(!$user){
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['login_string' => $this->lang->line('User not found')],
							'form_token' => $this->tokens->token(),
					]));
			}
			if(($on_hold=$this->authentication->get_current_hold())!==false){
				$errorText='';
				$leftTime=date('Y-m-d H:i:s',strtotime($on_hold->time)+config_item('seconds_recovery'));
				if($leftTime<=date('Y-m-d H:i:s')){
					$errorText=sprintf($this->lang->line('Wrong password. %s by email'),anchor('/registration/password/'.$user->user_id.'/'.$user->passport_id,$this->lang->line('Send'),['class' => 'btn-forgot-password','data-toggle' => 'modal','data-target' => '#modal-password-forgot']));
				}else{
					$errorText=$this->lang->line('Wrong password');
				}				
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['code' => sprintf($this->lang->line('Your account will be distributed through: %s at %s'),date('Y-m-d',strtotime($leftTime)),date('H:i',strtotime($leftTime))),'login_string' => '','login_pass' => ''],
							'form_token' => $this->tokens->token(),
					]));
			}
			if(!$this->authentication->check_passwd($user->passwd,$this->form_validation->validation_data['login_pass'])){
				$this->authentication->log_error($user->phone,true);
				$errorText='';
				$leftTime=date('Y-m-d H:i:s',strtotime($user->last_recovery)+config_item('seconds_recovery'));
				if($leftTime<=date('Y-m-d H:i:s')){
					$errorText=sprintf($this->lang->line('Wrong password. %s by email'),anchor('/registration/password/'.$user->user_id.'/'.$user->passport_id,$this->lang->line('Send'),['class' => 'btn-forgot-password','data-toggle' => 'modal','data-target' => '#modal-password-forgot']));
				}else{
					$errorText=$this->lang->line('Wrong password');
				}
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['login_pass' => $errorText],
							'form_token' => $this->tokens->token(),
					]));				
			}
			if(($hold=$this->authentication->get_ip_hold())!==false){
				$leftTime=date('Y-m-d H:i:s',strtotime($hold->time)+config_item('seconds_on_hold'));
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['code' => sprintf($this->lang->line('Your account will be distributed through: %s at %s'),date('Y-m-d',strtotime($leftTime)),date('H:i',strtotime($leftTime))),'login_string' => '','login_pass' => ''],
							'form_token' => $this->tokens->token(),
					]));				
			}			
			if($user->sms_code!==$this->form_validation->validation_data['code']){
				$errors=['code' => $this->lang->line('Wrong code')];
				$this->authentication->log_error($user->phone,true);
				$loginAttempts=$this->authentication->get_login_attempts($user->phone,true);
				if($loginAttempts===false){
					// $this->authentication->log_error($user->phone,true);
					return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['code' => $this->lang->line('Maximum retries reached')],
							'form_token' => $this->tokens->token(),
					]));				
				}else{
					$leftTime=0;
					if($user->sms_time){
						if(strtotime(date('Y-m-d H:i:s'))-strtotime($user->sms_time)>60){
							$errors['code'].='. '.anchor(base_url('registration/'.$user->user_id.'/'.$user->passport_id.'/resendCode'),$this->lang->line('Resend SMS code'),['class' => 'resend-link']);
						}else{
							$leftTime=60-(strtotime(date('Y-m-d H:i:s'))-strtotime($user->sms_time));
							$errors['code'].='. '.sprintf($this->lang->line('Resend SMS code available after <span>%s</span> seconds'),$leftTime);
						}
					}
				}
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'form_token' => $this->tokens->token(),
							'errors' => $errors,
							'leftTime' => $leftTime,
							'resendText' => anchor(base_url('registration/'.$user->user_id.'/'.$user->passport_id.'/resendCode'),$this->lang->line('Resend SMS code'),['class' => 'resend-link']),
					]));			
			}			
			// $this->authentication-clear_all_login_errors();
			$this->authentication->redirect_after_login=false;
			$this->auth_data=$this->authentication->user_status(0);
			if($this->auth_data){
				$this->_set_user_variables();
			}
			// return $this->auth_data;
			$this->post_auth_hook();
			if($this->auth_data){
				$this->authentication->clear_all_login_errors();
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => true,
							'redirect' => base_url('main'),
					]));
			}else{
				$this->tokens->name=config_item('login_token_name');
				$on_hold=(
					$this->authentication->on_hold===true OR 
					$this->authentication->current_hold_status()
				)
				? 1:0;
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['login_pass' => $this->lang->line('User not found'),'login_string' => ''],
							'form_token' => $this->tokens->token(),
					]));
			}
		}else{		
			$this->is_logged_in();
			if(!empty($this->auth_role)){
				redirect('main');
			}
			if($this->uri->uri_string()=='auth/login')
				show_404();
			if(strtolower($_SERVER['REQUEST_METHOD'])=='post'){
				$this->require_min_level(1);
			}
			$this->setup_login_form();
			$this->lang->load('form','ru');
			$this->layout->viewFolder='';
			$this->layout->view='auth/login_form';
			$this->layout->render();
		}
	}
	public function logout(){
		$this->authentication->logout();
		$redirect_protocol=USE_SSL?'https':NULL;
		redirect(site_url(LOGIN_PAGE.'?'.AUTH_LOGOUT_PARAM.'=1',$redirect_protocol));
	}
	public function ajax_attempt_login(){
		if($this->input->is_ajax_request()){
			$this->config->set_item('allowed_pages_for_login',['ajax_attempt_login']);
			$string=$this->input->post('login_string');
			$password=$this->input->post('login_pass');
			$form_token=$this->input->post(config_item('login_token_name'));
			$this->load->library('form_validation');
			$this->load->helper('idx');
			$this->lang->load('form','ru');
			$postData=$this->security->xss_clean($this->input->raw_input_stream);
			parse_str($postData,$data);
			$this->form_validation->set_data($data);
			$validation_rules=[
				[
					'field' => 'login_string',
					'rules' => [
						// 'trim',
						'required',
						'validatePhone',
					],
					'errors' => [
						'validatePhone' => $this->lang->line('Wrong phone number'),
						'required' => $this->lang->line('Phone is required')
					]
				],[
					'field' => 'login_pass',
					'rules' => [
						'trim',
						'required',
					],
					'errors' => [
						'required' => $this->lang->line('Password can\'t be blank')
					]
				]			
			];		
			$this->form_validation->set_rules($validation_rules);		
			if(!$this->form_validation->run()){
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => $this->form_validation->error_array(),
							'form_token' => $this->tokens->token(),
					]));			
			}
			
			$this->authentication->redirect_after_login=false;
			$this->auth_data=$this->authentication->user_status(0);
			if($this->auth_data){
				$this->_set_user_variables();
			}
			$this->post_auth_hook();
			if($this->auth_data){
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => true,
							'redirect' => base_url('main'),
					]));
			}else{
				$this->tokens->name=config_item('login_token_name');
				$on_hold=(
					$this->authentication->on_hold===true OR 
					$this->authentication->current_hold_status()
				)
				? 1:0;
				return $this->output
					->set_content_type('application/json')
					->set_output(json_encode([
							'result' => false,
							'errors' => ['login_pass' => $this->lang->line('User not found'),'login_string' => ''],
							'form_token' => $this->tokens->token(),
					]));
			}
		}else{
			show_404();
		}
	}
	protected function auto_create_user($data){
		$this->load->model('auth/au_model');
		$this->load->helper('auth');
		$userId=$this->au_model->get_unused_id();
		$user_data['passwd']=$this->authentication->hash_passwd($this->generatePassword());
		$user_data['user_id']=$userId;
		$user_data['created_at']=date('Y-m-d H:i:s');
		$user_data['username']=$data['firstName'];
		$user_data['userlastname']=$data['lastName'];
		$user_data['usermiddlename']=$data['midName'];
		$user_data['phone']=$data['phone'];
		$user_data['id_vd']=$data['idVd'];
		$user_data['passport_id']=$data['passportId'];
		$user_data['birth_date']=date('Y-m-d',$data['birthDate']);
		$user_data['auth_level']=1;
		$this->db->set($user_data)
			->insert(db_table('user_table'));
		if($this->db->affected_rows()==1){
			return $userId;
		}
		return false;
	}
	protected function checkUserByPhone($phone){
		$this->load->helper('auth');
		$user=$this->db->get_where(
			db_table('user_table'),
			['phone' => $phone]
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;		
	}
	protected function getUserById($userId){
		$this->load->helper('auth');
		$user=$this->db->get_where(
			db_table('user_table'),
			['user_id' => $userId]
		);
		if($user->num_rows()>0)
			return $user->row();
		return false;		
	}
	protected function generatePassword() {
		$alphabet='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$pass=[];
		$alphaLength=strlen($alphabet)-1;
		for($i=0;$i<config_item('max_chars_for_password');$i++){
			$n=rand(0,$alphaLength);
			$pass[]=$alphabet[$n];
		}
		return implode($pass);
	}
	protected function setSmsCode($userId,$code){
		$this->load->model('auth/au_model');
		$this->au_model->update_user_raw_data(
			$userId,[
				'sms_code' => $code,
				'sms_time' => date('Y-m-d H:i:s'),
			]
		);		
	}
	protected function setUserPassword($userId,$password){
		$password=$this->authentication->hash_passwd($password);
		$this->load->model('auth/au_model');
		$this->au_model->update_user_raw_data(
			$userId,[
				'passwd' => $password,
			]
		);			
	}
	protected function setUserStep($userId,$step){
		$this->load->model('auth/au_model');
		$this->au_model->update_user_raw_data(
			$userId,[
				'current_step' => $step,
			]
		);			
	}
	protected function updateUserData($userId,$data){
		$this->load->model('auth/au_model');
		$this->au_model->update_user_raw_data(
			$userId,$data
		);			
	}
	protected function getUser($phone){
		$this->load->model('auth/au_model');
		return $this->au_model->get_user($phone);		
	}
	protected function getUserByIdHash($userId,$hash){
		$this->load->model('auth/au_model');
		return $this->au_model->get_user_by_id_hash($userId,$hash);		
	}
	protected function checkApplication($applicationId){
		$this->load->model('auth/au_model');
		return $this->au_model->get_application($applicationId);		
	}
	protected function check_user_sms($phone,$code){
		$this->load->model('auth/au_model');
		return $this->au_model->check_user_sms($phone,$code);
	}
	protected function get_user_sms_time($userId){
		$this->load->model('auth/au_model');
		return $this->au_model->get_user_sms_time($userId);
	}
	protected function updateUserStep($data){
		$this->load->model('auth/au_model');
		$this->au_model->update_user_step_raw_data($data);		
	}
	protected function getUserByPhonePassport($phone,$passportId){
		$this->load->model('auth/au_model');
		return $this->au_model->get_user_by_phone_passport($phone,$passportId);		
	}
}