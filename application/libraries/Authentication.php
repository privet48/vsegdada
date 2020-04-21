<?php
defined('BASEPATH')||exit('No direct script access allowed');
class Authentication{
	public $CI;
	public $auth_model='auth_model';
	public $roles;
	public $levels;
	public $login_error=false;
	public $login_errors_count=0;
	public $on_hold=false;
	private $auth_identifiers=[];
	public $post_system_sess_check=true;
	public $redirect_after_login=true;
	public function __construct(){
		$this->CI =& get_instance();
		$this->auth_model=config_item('declared_auth_model');
		$this->roles=config_item('levels_and_roles');
		$this->levels=array_flip($this->roles);
		$this->_set_auth_identifiers();
	}
	private function _set_auth_identifiers(){
		if($auth_identifiers=$this->CI->session->userdata('auth_identifiers')){
			if(config_item('encrypt_auth_identifiers')){
				$this->CI->load->library('encryption');
				$auth_identifiers=$this->CI->encryption->decrypt($auth_identifiers);
			}
			$this->auth_identifiers=unserialize($auth_identifiers);
		}
	}
	public function user_status($requirement){
		$string=preg_replace('/^\\+?7|\\|7|\s7|\\D/','',$this->CI->input->post('login_string'),-1);
		$password=$this->CI->input->post('login_pass');
		$form_token=$this->CI->input->post(config_item('login_token_name'));
		$token_jar=$this->CI->tokens->jar;
		if(!is_null($string)||!is_null($password)||!is_null($form_token)){
			log_message(
				'debug',
				"\n string    =".$string.
				"\n password  =".$password.
				"\n form_token=".$form_token.
				"\n token_jar =".json_encode($token_jar)
			);
		}
		if(!empty($this->auth_identifiers)){
			if($auth_data=$this->check_login($requirement)){
				return $auth_data;
			}
		}else if(!is_null($string)&&!is_null($password)&&!is_null($form_token)&&!empty($token_jar)&&$this->_login_page_is_allowed()){
			if($this->CI->tokens->token_check(config_item('login_token_name'),false)){
				$this->CI->tokens->match=false;
				$this->CI->tokens->name=config_item('token_name');
				if($auth_data=$this->login($requirement,$string,$password)){
					return $auth_data;
				}
			}
		}else if(!is_null($string)&&!is_null($password)){
			$this->log_error($string);
			$this->login_error=true;
		}
		return false;
	}
	private function login($requirement,$user_string,$passwd){
		$this->post_system_sess_check=false;
		$this->CI->load->library('form_validation');
		$this->CI->config->load(config_item('login_form_validation_file'));
		$this->CI->form_validation->set_rules(config_item('login_rules'));
		if($this->CI->form_validation->run()!==false){
			$this->on_hold=$this->current_hold_status();
			if(!$this->on_hold){
				if($auth_data=$this->CI->{$this->auth_model}->get_auth_data($user_string)){
					if(!$this->_user_confirmed($auth_data,$requirement,$passwd)){
						log_message(
							'debug',
							"\n user is banned            =".($auth_data->banned===1?'yes':'no').
							"\n password in database      =".$auth_data->passwd.
							"\n supplied password match   =".(string) $this->check_passwd($auth_data->passwd,$passwd).
							"\n required level or role    =".(is_array($requirement)?implode($requirement):$requirement).
							"\n auth level in database    =".$auth_data->auth_level.
							"\n auth level equivalant role=".$this->roles[$auth_data->auth_level]
						);
					}else{
						$this->redirect_after_login();
						$this->maintain_state($auth_data);
						return $auth_data;
					}
				}else{
					log_message(
						'debug',
						"\n NO MATCH FOR USERNAME||EMAIL DURING LOGIN ATTEMPT"
					);
				}
			}else{
				log_message(
					'debug',
					"\n IP,USERNAME,||EMAIL ADDRESS ON HOLD"
				);
			}
		}else{
			log_message(
				'debug',
				"\n LOGIN ATTEMPT DID NOT PASS FORM VALIDATION"
			);
		}
		$this->log_error($user_string);
		$this->login_error=true;
		return false;
	}
	public function check_login($requirement){
		$this->post_system_sess_check=false;
		if(empty($this->auth_identifiers))
			return false;
		$user_id=$this->auth_identifiers['user_id'];
		$login_time=$this->auth_identifiers['login_time'];
		$auth_data=$this->CI->{$this->auth_model}->check_login_status(
			$user_id,
			$login_time 
		);
		if($auth_data!==false){
			if(!$this->_user_confirmed($auth_data,$requirement)){
				log_message(
					'debug',
					"\n user is banned                 =".
						($auth_data->banned===1?'yes':'no').
					"\n required level or role         =".
						(is_array($requirement)?implode($requirement):$requirement).
					"\n auth level in database         =".
						$auth_data->auth_level.
					"\n auth level in database (string)=".
						$this->roles[$auth_data->auth_level]
				);
			}else{
				$this->CI->{$this->auth_model}->update_user_session_id($auth_data->user_id);
				return $auth_data;
			}
		}else{
			log_message(
				'debug',
				"\n user id from session   =".$user_id.
				"\n login time from session=".$login_time
			);
		}
		$this->CI->session->unset_userdata('auth_identifiers');
		return false;
	}
	public function current_hold_status($recovery=false){
		$this->CI->{$this->auth_model}->clear_expired_holds();
		return $this->CI->{$this->auth_model}->check_holds($recovery);
	}
	public function get_current_hold($recovery=false){
		$this->CI->{$this->auth_model}->clear_expired_holds();
		return $this->CI->{$this->auth_model}->get_ip_hold();
	}
	public function clear_all_login_errors(){
		$this->CI->{$this->auth_model}->clear_all_login_errors($this->CI->input->ip_address());
	}
	public function log_error($string,$isSms=false){
		$this->CI->{$this->auth_model}->clear_login_errors();
		$data=[
			'username_or_email' => $string,
			'ip_address'        => $this->CI->input->ip_address(),
			'time'              => date('Y-m-d H:i:s')
		];
		$this->CI->{$this->auth_model}->create_login_error($data);
		return $this->login_errors_count=$this->CI->{$this->auth_model}->check_login_attempts($string,$isSms);
		$this->CI->{$this->auth_model}->failed_login_attempt_hook($this->login_errors_count);
	}
	public function get_login_attempts($string,$isSms=false){
		return $this->CI->{$this->auth_model}->check_login_attempts($string,$isSms);
	}
	public function check_ip_hold(){
		return $this->CI->{$this->auth_model}->check_ip_hold();
	}
	public function get_ip_hold(){
		return $this->CI->{$this->auth_model}->get_ip_hold();
	}
	public function logout(){
		if(isset($this->auth_identifiers['user_id'])){
			$session_to_delete=is_null($this->CI->session->regenerated_session_id) 
				? $this->CI->session->session_id 
				: $this->CI->session->pre_regenerated_session_id;
			$this->CI->{$this->auth_model}->logout(
				$this->auth_identifiers['user_id'],
				$session_to_delete
			);
		}
		if(config_item('delete_session_cookie_on_logout')){
			delete_cookie(config_item('sess_cookie_name'));
		}else{
			$this->CI->session->unset_userdata('auth_identifiers');
		}
		$this->CI->load->helper('cookie');
		delete_cookie(config_item('remember_me_cookie_name'));
		delete_cookie(config_item('http_user_cookie_name'));
		delete_cookie(config_item('http_tokens_cookie'));
		delete_cookie(config_item('https_tokens_cookie'));
		if(config_item('auth_sessions_gc_on_logout'))
			$this->CI->{$this->auth_model}->auth_sessions_gc();
	}
	public function hash_passwd($password,$random_salt=''){
		if(!is_php('5.5')&&empty($random_salt))
			$random_salt=$this->random_salt();
		if(is_php('5.5')){
			return password_hash($password,PASSWORD_BCRYPT,['cost' => 11]);
		}else{
			return crypt($password,'$2y$10$'.$random_salt);
		}
	}
	public function check_passwd($hash,$password){
		if(is_php('5.5')&&password_verify($password,$hash)){
			return true;
		}else if($hash===crypt($password,$hash)){
			return true;
		}
		return false;
	}
	public function random_salt(){
		$this->CI->load->library('encryption');
		$salt=substr(bin2hex($this->CI->encryption->create_key(64)),0,22);
		return strlen($salt) != 22 
			? substr(md5(mt_rand()),0,22)
			: $salt;
	}
	public function redirect_after_login(){
		if($this->redirect_after_login){
			$redirect=$this->CI->input->get(AUTH_REDIRECT_PARAM)
				? urldecode($this->CI->input->get(AUTH_REDIRECT_PARAM)) 
				: '';
			$redirect_protocol=USE_SSL?'https':NULL;
			$this->CI->load->helper('url');
			$url=site_url($redirect,$redirect_protocol);
			header("Location: ".$url,true,302);
		}
	}
	public function maintain_state($auth_data){
		$login_time=date('Y-m-d H:i:s');
		$http_user_cookie=[
			'name' => config_item('http_user_cookie_name'),
			'domain' => config_item('cookie_domain'),
			'path' => config_item('cookie_path'),
			'prefix' => config_item('cookie_prefix'),
			'secure' => false
		];
		$http_user_cookie_elements=config_item('http_user_cookie_elements');
		if(is_array($http_user_cookie_elements)&&!empty($http_user_cookie_elements)){
			foreach($http_user_cookie_elements as $element){
				if(isset($auth_data->$element))
					$http_user_cookie_data[ $element ]=$auth_data->$element;
			}
		}
		if(isset($http_user_cookie_data))
			$http_user_cookie['value']=serialize_data($http_user_cookie_data);
		if(config_item('allow_remember_me')&&$this->CI->input->post('remember_me')){
			$remember_me_cookie=[
				'name' => config_item('remember_me_cookie_name'),
				'value' => config_item('remember_me_expiration') + time(),
				'expire' => config_item('remember_me_expiration'),
				'domain' => config_item('cookie_domain'),
				'path' => config_item('cookie_path'),
				'prefix' => config_item('cookie_prefix'),
				'secure' => false
			];
			$this->CI->input->set_cookie($remember_me_cookie);
			$this->CI->session->sess_expire_on_close=false;
			$this->CI->session->sess_expiration=config_item('remember_me_expiration');
			$http_user_cookie['expire']=config_item('remember_me_expiration') + time();
		}else{
			$http_user_cookie['expire']=0;
		}
		if(isset($http_user_cookie_data))
			$this->CI->input->set_cookie($http_user_cookie);
		$auth_identifiers=serialize([
			'user_id' => $auth_data->user_id,
			'login_time' => $login_time
		]);
		if(config_item('encrypt_auth_identifiers'))
			$auth_identifiers=$this->CI->encryption->encrypt($auth_identifiers);
		$this->CI->session->set_userdata('auth_identifiers',$auth_identifiers);
		$session_id=$this->CI->session->sess_regenerate(config_item('sess_regenerate_destroy'));
		$this->CI->{$this->auth_model}->login_update(
			$auth_data->user_id,
			$login_time,
			$session_id
		);
	}
	protected function _user_confirmed($auth_data,$requirement,$passwd=false){
		$is_banned=($auth_data->banned==='1');
		if($passwd){
			$wrong_password=(!$this->check_passwd($auth_data->passwd,$passwd));
		}else{
			$wrong_password=false;
		}
		$wrong_level=(is_int($requirement)&&$auth_data->auth_level < $requirement);
		$wrong_role=(is_array($requirement)&&!in_array($this->roles[$auth_data->auth_level],$requirement));
		if($is_banned||$wrong_level||$wrong_role||$wrong_password)
			return false;
		return true;
	}
	protected function _login_page_is_allowed(){
		$uri_string=$this->CI->uri->uri_string();
		$allowed_pages=config_item('allowed_pages_for_login');
		$allowed_pages[]=LOGIN_PAGE;
		if(in_array($uri_string,$allowed_pages))
			return true;
		log_message(
			'debug',
			"\n URI STRING FROM LOGIN=".$uri_string
		);
		return false;
	}
}