<?php  
defined('BASEPATH') or exit('No direct script access allowed');
if(!function_exists('is_role')){
	function is_role($role=''){
		$CI=& get_instance();
		$auth_model=$CI->authentication->auth_model;
		return $CI->$auth_model->is_role($role);
	}
}
if(!function_exists('acl_permits')){
	function acl_permits($str){
		$CI=& get_instance();
		$auth_model=$CI->authentication->auth_model;
		return $CI->$auth_model->acl_permits($str);
	}
}
if(!function_exists('db_table')){
	function db_table($name){
		$CI=& get_instance();
		$auth_model=$CI->authentication->auth_model;
		return $CI->$auth_model->db_table($name);
	}
}