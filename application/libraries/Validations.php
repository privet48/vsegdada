<?php
defined('BASEPATH')||exit('No direct script access allowed');
class Validations{
	public $CI;
	public function _check_suggestion_address($address){
		$this->CI=&get_instance();
		$this->CI->load->library('form_validation');
		$this->CI->lang->load('form','ru');
		if(strlen($address)===0){
			$this->CI->form_validation->set_message('_check_suggestion_address',$this->CI->lang->line('Registration address is required'));
			return false;
		}
		if(($addressId=$this->CI->input->post('suggestion_address_id'))===null||!is_numeric($this->CI->input->post('suggestion_address_id'))){
			$this->CI->form_validation->set_message('_check_suggestion_address',$this->CI->lang->line('Registration address filled incorrect'));
			return false;
		}
		if(($suggestions=$this->CI->session->userdata('suggestions'))===null){
			$this->CI->form_validation->set_message('_check_suggestion_address',$this->CI->lang->line('Registration address filled incorrect'));
			return false;
		}
		if(!isset($suggestions[$addressId])){
			$this->CI->form_validation->set_message('_check_suggestion_address',$this->CI->lang->line('Incorrect address'));
			return false;			
		}
		if($suggestions[$addressId]->data->city==''){
			$this->CI->form_validation->set_message('_check_suggestion_address',$this->CI->lang->line('You\'ve to fill the city'));
			return false;			
		}
		if($suggestions[$addressId]->data->street==''){
			$this->CI->form_validation->set_message('_check_suggestion_address',$this->CI->lang->line('You\'ve to fill the street'));
			return false;			
		}
		if($suggestions[$addressId]->data->house==''){
			$this->CI->form_validation->set_message('_check_suggestion_address',$this->CI->lang->line('You\'ve to fill the house'));
			return false;			
		}
	}		
}