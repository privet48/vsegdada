<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Auth_Controller extends CI_Controller {
	public $auth_user_id;
	public $auth_username;
	public $auth_userlastname;
	public $auth_usermiddlename;
	public $auth_user_passport;
	public $auth_user_birth;
	public $auth_user_sex;
	public $auth_user_birth_place;
	public $auth_user_passport_issue_date;
	public $auth_user_passport_subdivision_code;
	public $auth_user_passport_issuing_authority;
	public $auth_current_step;
	public $auth_level;
	public $auth_role;
    public $auth_email;
    public $auth_phone;
	protected $auth_data;
	public $acl = NULL;
	public $protocol = 'http';
	public function __construct(){
		parent::__construct();
		$this->_load_dependencies();
	 	header('Expires: Wed, 13 Dec 1972 18:37:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		header('Pragma: no-cache');
		if(is_https())
			$this->protocol = 'https';
		if(get_cookie(config_item('http_user_cookie_name'))){
			$http_user_data = unserialize_data(get_cookie(config_item('http_user_cookie_name')));
			$this->load->vars($http_user_data);
		}
	}
	protected function _load_dependencies(){
		$this->load->database();
		$this->config->load('db_tables');
		$this->config->load('authentication');
		$this->load->library([
			'session','tokens','Authentication'
		])->helper([
			'serialization','cookie'
		])->model('auth_model');
		if(config_item('declared_auth_model') != 'auth_model')
			$this->load->model(config_item('declared_auth_model'));
	}
	protected function require_min_level($level){
		if(!is_null($this->auth_level)&&$this->auth_level >= $level){
			return true;
		}
		$this->auth_data = $this->authentication->user_status($level);
		if($this->auth_data)
			$this->_set_user_variables();
		$this->post_auth_hook();
		if($this->auth_data){
			return true;
		}else if($this->uri->uri_string() != LOGIN_PAGE){
			$this->_redirect_to_login_page();
		}
		return false;
	}
	private function _redirect_to_login_page(){
		$redirect = $this->input->get(AUTH_REDIRECT_PARAM)
			? urlencode($this->input->get(AUTH_REDIRECT_PARAM)) 
			: urlencode($this->uri->uri_string());
		$redirect_protocol = USE_SSL ? 'https' : NULL;
		$this->load->helper('url');
		header(
			'Location: ' . site_url(LOGIN_PAGE . '?' . AUTH_REDIRECT_PARAM . '=' . $redirect, $redirect_protocol),
			true,
			302
		);
		exit;
	}
	protected function is_logged_in(){
		return $this->verify_min_level(1);
	}
	protected function verify_min_level($level){
		if(!is_null($this->auth_level)&&$this->auth_level >= $level){
			return true;
		}
		$this->auth_data = $this->authentication->check_login($level);
		if($this->auth_data)
			$this->_set_user_variables();
		$this->post_auth_hook();
		if($this->auth_data)
			return true;
		return false;
	}
	protected function _set_user_variables(){
		$this->auth_user_id  = $this->auth_data->user_id;
		$this->auth_username = $this->auth_data->username;
		$this->auth_userlastname = $this->auth_data->userlastname;
		$this->auth_usermiddlename = $this->auth_data->usermiddlename;
		$this->auth_user_passport = $this->auth_data->passport_id;
		$this->auth_user_birth = $this->auth_data->birth_date;
		$this->auth_user_sex = $this->auth_data->sex;
		$this->auth_user_birth_place = $this->auth_data->birth_place;
		$this->auth_user_passport_issue_date = $this->auth_data->passport_issue_date;
		$this->auth_user_passport_subdivision_code = $this->auth_data->passport_subdivision_code;
		$this->auth_user_passport_issuing_authority = $this->auth_data->passport_issuing_authority;
		$this->auth_current_step = $this->auth_data->current_step;
		$this->auth_level = $this->auth_data->auth_level;
		$this->auth_role = $this->authentication->roles[$this->auth_data->auth_level];
		$this->auth_email = $this->auth_data->email;
		$this->auth_phone = $this->auth_data->phone;
		$data = [
			'auth_user_id' => $this->auth_user_id,
			'auth_username' => $this->auth_username,
			'auth_userlastname' => $this->auth_userlastname,
			'auth_usermiddlename' => $this->auth_usermiddlename,
			'auth_user_passport' => $this->auth_user_passport,
			'auth_user_birth' => $this->auth_user_birth,
			'auth_user_sex' => $this->auth_user_sex,
			'auth_user_birth_place' => $this->auth_user_birth_place,
			'auth_user_passport_issue_date' => $this->auth_user_passport_issue_date,
			'auth_user_passport_subdivision_code' => $this->auth_user_passport_subdivision_code,
			'auth_user_passport_issuing_authority' => $this->auth_user_passport_issuing_authority,
			'auth_level' => $this->auth_level,
			'auth_role' => $this->auth_role,
			'auth_email' => $this->auth_email,
			'auth_phone' => $this->auth_phone,
			'auth_current_step' => $this->auth_current_step,
		];
		$this->config->set_item('auth_user_id', $this->auth_user_id);
		$this->config->set_item('auth_username', $this->auth_username);
		$this->config->set_item('auth_userlastname', $this->auth_userlastname);
		$this->config->set_item('auth_usermiddlename', $this->auth_usermiddlename);
		$this->config->set_item('auth_user_passport', $this->auth_user_passport);
		$this->config->set_item('auth_user_birth', $this->auth_user_birth);
		$this->config->set_item('auth_user_sex', $this->auth_user_sex);
		$this->config->set_item('auth_user_birth_place', $this->auth_user_birth_place);
		$this->config->set_item('auth_user_passport_issue_date', $this->auth_user_passport_issue_date);
		$this->config->set_item('auth_user_passport_subdivision_code', $this->auth_user_passport_subdivision_code);
		$this->config->set_item('auth_user_passport_issuing_authority', $this->auth_user_passport_issuing_authority);
		$this->config->set_item('auth_current_step', $this->auth_current_step);
		$this->config->set_item('auth_level', $this->auth_level);
		$this->config->set_item('auth_role', $this->auth_role);
		$this->config->set_item('auth_email', $this->auth_email);
		$this->config->set_item('auth_phone', $this->auth_phone);
		if(config_item('add_acl_query_to_auth_functions')){
			$this->acl   = $this->auth_data->acl;
			$data['acl'] = $this->acl;
			$this->config->set_item('acl', $this->acl);
		}
		$this->load->vars($data);
	}
	protected function setup_login_form($optional_login = false){
		$this->tokens->name = config_item('login_token_name');
		$this->tokens->token();
		if($this->authentication->on_hold === true){
			$view_data['on_hold_message'] = 1;
		}
		else if(($on_hold=$this->authentication->get_current_hold())!==false){
			$this->lang->load('form','ru');
			$leftTime=date('Y-m-d H:i:s',strtotime($on_hold->time)+config_item('seconds_on_hold'));
			$view_data['on_hold_message'] = sprintf($this->lang->line('Due to incorrect data entry more than %d times, we are forced to block access to this account for 24 hours in order to ensure the security of your data. Your account will be distributed through: %s at %s'),config_item('max_allowed_attempts'),date('Y-m-d',strtotime($leftTime)),date('H:i',strtotime($leftTime)));
		}
		if($this->authentication->login_error === true){
			$view_data['login_error_mesg'] = 1;
		}
		$redirect = $this->input->get(AUTH_REDIRECT_PARAM)
			? '?' . AUTH_REDIRECT_PARAM . '=' . $this->input->get(AUTH_REDIRECT_PARAM) 
			: '?' . AUTH_REDIRECT_PARAM . '=' . config_item('default_login_redirect');
		if($optional_login){
			$redirect = '?' . AUTH_REDIRECT_PARAM . '=' . urlencode($this->uri->uri_string());
			$view_data['optional_login'] = true;
		}
		$link_protocol = USE_SSL ? 'https' : NULL;
		$this->load->helper('url');
		$view_data['login_url'] = site_url(LOGIN_PAGE . $redirect, $link_protocol);
		$this->load->vars($view_data);
	}
	protected function is_role($role = ''){
		$auth_model = $this->authentication->auth_model;
		return $this->$auth_model->is_role($role);
	}
	public function acl_permits($str){
		$auth_model = $this->authentication->auth_model;
		$bool = $this->$auth_model->acl_permits($str);
		if(is_null($this->acl))
			$this->acl = $this->$auth_model->acl;
		return $bool;
	}
	protected function post_auth_hook(){
		return;
	}
}