<?php
defined('BASEPATH')||exit('No direct script access allowed');
class MY_Session extends CI_Session{
	public $pre_regenerated_session_id=NULL;
	public $regenerated_session_id=NULL;
	public function __construct(array $params=[]){
		parent::__construct($params);
	}
	protected function _configure(&$params){
		$expiration=config_item('sess_expiration');
		if (isset($params['cookie_lifetime'])){
			$params['cookie_lifetime']=(int) $params['cookie_lifetime'];
		}else{
			$params['cookie_lifetime']=(!isset($expiration)&&config_item('sess_expire_on_close'))
				? 0:(int) $expiration;
		}
		$CI=& get_instance();
		$remember_me=(
			$CI->input->post('remember_me')||
			isset($_COOKIE[$CI->config->item('remember_me_cookie_name')]) 
		)?true:false;

		if($CI->config->item('allow_remember_me')&&$remember_me){
			$params['cookie_lifetime']=$CI->config->item('remember_me_expiration');
		}
		isset($params['cookie_name'])||$params['cookie_name']=config_item('sess_cookie_name');
		if (empty($params['cookie_name'])){
			$params['cookie_name']=ini_get('session.name');
		}else{
			ini_set('session.name',$params['cookie_name']);
		}
		isset($params['cookie_path'])||$params['cookie_path']=config_item('cookie_path');
		isset($params['cookie_domain'])||$params['cookie_domain']=config_item('cookie_domain');
		isset($params['cookie_secure'])||$params['cookie_secure']=(bool) config_item('cookie_secure');
		session_set_cookie_params(
			$params['cookie_lifetime'],
			$params['cookie_path'],
			$params['cookie_domain'],
			$params['cookie_secure'],
			true
		);
		if (empty($expiration)){
			$params['expiration']=(int) ini_get('session.gc_maxlifetime');
		}else{
			$params['expiration']=(int) $expiration;
			ini_set('session.gc_maxlifetime',$expiration);
		}
		$params['match_ip']=(bool) (isset($params['match_ip'])?$params['match_ip']:config_item('sess_match_ip'));
		isset($params['save_path'])||$params['save_path']=config_item('sess_save_path');
		$this->_config=$params;
		ini_set('session.use_trans_sid',0);
		ini_set('session.use_strict_mode',1);
		ini_set('session.use_cookies',1);
		ini_set('session.use_only_cookies',1);
		if(version_compare(CI_VERSION,'3.1.2','<')){
			ini_set('session.hash_function',1);
			ini_set('session.hash_bits_per_character',4);
		}else{
			$this->_configure_sid_length();
		}
	}
	public function sess_regenerate($destroy=false){
		$this->pre_regenerated_session_id=$this->session_id;
		$_SESSION['__ci_last_regenerate']=time();
		session_regenerate_id($destroy);
		$this->regenerated_session_id=$this->session_id;
		return $this->session_id;
	}
}