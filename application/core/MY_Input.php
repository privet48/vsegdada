<?php
defined('BASEPATH')||exit('No direct script access allowed');
class MY_Input extends CI_Input{
	public function __construct(){
		parent::__construct();
	}
	public function set_cookie($name,$value='',$expire=0,$domain='',$path='/',$prefix='',$secure=false,$httponly=false){
		if(is_array($name)){
			foreach (array('value','expire','domain','path','prefix','secure','httponly','name') as $item){
				if(isset($name[$item])){
					$$item=$name[$item];
				}
			}
		}
		if($prefix===''&&config_item('cookie_prefix')!==''){
			$prefix=config_item('cookie_prefix');
		}
		if($domain==''&&config_item('cookie_domain')!=''){
			$domain=config_item('cookie_domain');
		}
		if($path==='/'&&config_item('cookie_path')!=='/'){
			$path=config_item('cookie_path');
		}
		if($httponly===false&&config_item('cookie_httponly')!==false){
			$httponly=config_item('cookie_httponly');
		}
		if(! is_numeric($expire)||$expire < 0){
			$expire=1;
		}else{
			$expire=($expire > 0)?time()+$expire:0;
		}
		if(config_item('encrypt_all_cookies')===true){
			$CI =& get_instance();
			$value=$CI->encryption->encrypt($value);
		}
		setcookie($prefix.$name,$value,$expire,$path,$domain,$secure,$httponly);
	}
	function cookie($index=NULL,$xss_clean=NULL){
		$value=$this->_fetch_from_array($_COOKIE,$index,$xss_clean);
		if(config_item('encrypt_all_cookies')===true&&$index!=config_item('sess_cookie_name')){
			$CI =& get_instance();
			$value=$CI->encryption->decrypt($value);
		}
		return $value;
	}
	public function getIsPostRequest(){
		return $this->server('REQUEST_METHOD')=='POST';
	}
	function isMobile(){
		return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
	}	
}