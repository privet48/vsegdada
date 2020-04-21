<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class tokens{
	public $name;
	public $token=false;
	public $posted_value=false;
	public $jar=[];
	public $match=false;
	private $CI;
	private $scheme='http';
	private $debug=false;
	private $encrypted_tokens=true;
	public function __construct(){
		if(is_https()){
			$this->scheme='https';
		}
		$this->CI=& get_instance();
		$this->CI->load->library('encryption');
		$this->_set_jar();
		$this->token_check();
	}
	public function token_check($rename='',$dump_jar_on_match=false){
		$this->name=($rename=='')?config_item('token_name'):$rename;
		if(! empty($this->jar)){
			if($this->posted_value=$this->CI->input->post($this->name)){
				if(in_array($this->posted_value,$this->jar)){
					$this->match=true;
					if($dump_jar_on_match){
						$this->jar=[];
						$this->save_tokens_cookie();
					}else{
						$matching_key=array_search($this->posted_value,$this->jar);
						unset($this->jar[ $matching_key ]);
						$this->generate_form_token();
					}
					if($this->debug){
						log_message('debug',count($this->jar).'@token_check');
						log_message('debug',json_encode($this->jar));
					}
					return true;
				}
			}
		}
		return false;
	}
	public function generate_form_token(){
		if(! $this->token){
			$this->token=substr(md5(uniqid().microtime().rand()),0,8);
			$this->jar[]=$this->token;
			while(count($this->jar) > config_item('token_jar_size')){
				array_shift($this->jar);
			}
			if($this->debug){
				log_message('debug',count($this->jar).'@generate_form_token');
				log_message('debug',json_encode($this->jar));
			}
			$this->save_tokens_cookie();
		}
		return $this->token;
	}
	public function token(){
		return $this->generate_form_token();
	}
	public function save_tokens_cookie(){
		$token_cookie_name=($this->scheme=='http')
			? config_item('http_tokens_cookie')
			: config_item('https_tokens_cookie');
		$cookie_secure=($this->scheme=='http')?false:true;
		if($this->debug){
			log_message('debug',count($this->jar).'@save_tokens_cookie');
			log_message('debug',json_encode($this->jar));
		}
		setcookie(
			$token_cookie_name,
			$this->pack_tokens(),
			0,
			config_item('cookie_path'),
			config_item('cookie_domain'),
			$cookie_secure
		);
	}
	protected function _set_jar(){
		$token_cookie_name=$this->scheme=='http' 
			? config_item('http_tokens_cookie') 
			: config_item('https_tokens_cookie');
		if(empty($this->jar)){
			$this->jar=(isset($_COOKIE[ $token_cookie_name ])) 
				? $this->unpack_tokens($token_cookie_name)
				: [];
		}
		if($this->debug){
			log_message('debug',count($this->jar).'@_set_jar');
			log_message('debug',json_encode($this->jar));
		}
		return $this->jar;
	}
	protected function unpack_tokens($token_cookie_name){
		$tokens=$_COOKIE[ $token_cookie_name ];
		if($this->encrypted_tokens){
			$this->CI->encryption->save_settings();
			$this->CI->encryption->use_defaults();
			$tokens=$this->CI->encryption->decrypt($tokens);
			$this->CI->encryption->restore_settings();
		}
		$tokens=explode('|',$tokens);
		return $tokens;
	}
	protected function pack_tokens(){
		foreach($this->jar as $token){
			if(! empty($token)){
				$tokens[]=$token;
			}
		}
		$tokens=isset($tokens)?implode('|',$tokens):'';
		if($this->encrypted_tokens){
			$this->CI->encryption->save_settings();
			$this->CI->encryption->use_defaults();
			$tokens=$this->CI->encryption->encrypt($tokens);
			$this->CI->encryption->restore_settings();
		}
		return $tokens;
	}
}