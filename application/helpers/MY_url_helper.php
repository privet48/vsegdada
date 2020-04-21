<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
if(!function_exists('login_anchor')){
	function login_anchor($login_redirect = '', $login_title = 'Login', $login_attributes = ''){
		$link_protocol = USE_SSL ? 'https' : null;
		if($login_redirect)
			$login_redirect = '?' . AUTH_REDIRECT_PARAM . '=' . urlencode($login_redirect);
		return anchor(site_url(LOGIN_PAGE . $login_redirect, $link_protocol), $login_title, $login_attributes);
	}
}
if(!function_exists('logout_anchor')){
	function logout_anchor($logout_uri, $logout_title = 'Logout', $logout_attributes = ''){
		$link_protocol = USE_SSL ? 'https' : null;
		return anchor(site_url($logout_uri, $link_protocol), $logout_title, $logout_attributes);
	}
}