<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class MY_Encryption extends CI_Encryption{
	public $saved_settings=[];
	public function __construct(){
		parent::__construct();
		$this->_cipher='blowfish';
	}
	public function save_settings(){
		$this->saved_settings=[
			'_key' => $this->_key,
			'_cipher' => $this->_cipher,
			'_mode' => $this->_mode
		];
	}
	public function restore_settings(){
		if(!empty($this->saved_settings)){
			foreach($this->saved_settings as $k => $v){
				$this->$k=$v;
			}
		}
	}
	public function use_defaults(){
		$this->_key=config_item('encryption_key');
		$this->_cipher='blowfish';
		$this->_mode='cbc';
	}
}