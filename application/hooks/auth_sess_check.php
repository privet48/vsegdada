<?php
defined('BASEPATH') OR exit('No direct script access allowed');
function auth_sess_check(){
	$CI =& get_instance();
	if($CI->load->is_loaded('authentication')){
		if($CI->authentication->post_system_sess_check){
			if(!is_null($CI->session->regenerated_session_id)){
				$CI->authentication->check_login(1);
			}
		}
	}
}