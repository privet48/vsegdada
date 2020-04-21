<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Validation_callables extends MY_Model{
	public function __construct(){
		parent::__construct();
		$this->config->load('auth/password_strength');
	}
	public function _check_password_equal($cpassword){
		if($cpassword!==$this->input->post('password')){
			$this->form_validation->set_message('_check_password_equal',$this->lang->line('Passwords are note equal'));
			return false;
		}
	}
	public function _check_password_strength($password){
		$max = config_item('max_chars_for_password') > 0
			? config_item('max_chars_for_password') 
			: '';
		$regex = '(?=.{' . config_item('min_chars_for_password') . ',' . $max . '})';
		$error = '<span>'.$this->lang->line('At least').' '.config_item('min_chars_for_password') . ' '.$this->lang->line('characters').'</span>';
		if(config_item('max_chars_for_password') > 0)
			$error .= ''.$this->lang->line('Not more than').' ' . config_item('max_chars_for_password') . ' '.$this->lang->line('characters').'';
		if(config_item('min_digits_for_password') > 0){
			$regex .= '(?=(?:.*[0-9].*){' . config_item('min_digits_for_password') . ',})';
			$plural = config_item('min_digits_for_password') > 1 ? 's' : '';
			$error .= '<span>' . config_item('min_digits_for_password') . ' '.$this->lang->line('number').' '.$plural . '</span>';
		}
		if(config_item('min_lowercase_chars_for_password') > 0){
			$regex .= '(?=(?:.*[a-z].*){' . config_item('min_lowercase_chars_for_password') . ',})';
			$plural = config_item('min_lowercase_chars_for_password') > 1 ? 's' : '';
			$error .= '<span>' . config_item('min_lowercase_chars_for_password') .' '.$this->lang->line('lower case letter').' ' . $plural . '</span>';
		}
		if(config_item('min_uppercase_chars_for_password') > 0){
			$regex .= '(?=(?:.*[A-Z].*){' . config_item('min_uppercase_chars_for_password') . ',})';
			$plural = config_item('min_uppercase_chars_for_password') > 1 ? 's' : '';
			$error .= '<span>' . config_item('min_uppercase_chars_for_password') .' '.$this->lang->line('upper case letter').' ' . $plural . '</span>';
		}
		if(config_item('min_non_alphanumeric_chars_for_password') > 0){
			$regex .= '(?=(?:.*[^a-zA-Z0-9].*){' . config_item('min_non_alphanumeric_chars_for_password') . ',})';
			$plural = config_item('min_non_alphanumeric_chars_for_password') > 1 ? 's' : '';
			$error .= '<span>' . config_item('min_non_alphanumeric_chars_for_password') . ' non-alphanumeric character' . $plural . '</span>';
		}		
		if(preg_match('/^' . $regex . '.*$/', $password)){
			return true;
		}		
		$this->form_validation->set_message(
			'_check_password_strength', 
			'<span class="redfield">'.$this->lang->line('Password').'</span> '.$this->lang->line('must contain').':<span class="validation-error-description-block">
					' . $error .'</span>'
		);
		return false;
	}
}