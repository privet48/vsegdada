<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
if(!function_exists('form_open')){
	function form_open($action='',$attributes=array(),$hidden=array()){
		$CI=& get_instance();
		$link_protocol=(USE_SSL&&is_https()) 
			? 'https' 
			: null;
		if (!$action){
			$action=$CI->config->site_url($CI->uri->uri_string());
			if(is_https()&&parse_url($action,PHP_URL_SCHEME)== 'http')
				$action=substr($action,0,4) . 's' . substr($action,4);
			$action=($_SERVER['QUERY_STRING'])
				? $action . '?' . $_SERVER['QUERY_STRING']
				: $action;
		}elseif (strpos($action,'://')===false){
			$action=$CI->config->site_url($action);
		}
		$attributes=_attributes_to_string($attributes);
		if (stripos($attributes,'method=')===false){
			$attributes .= ' method="post"';
		}
		if (stripos($attributes,'accept-charset=')===false){
			$attributes .= ' accept-charset="'.strtolower(config_item('charset')).'"';
		}
		$form='<form action="'.$action.'"'.$attributes.">\n";
		if ($CI->config->item('csrf_protection')===true&&strpos($action,$CI->config->base_url('',$link_protocol))!==false &&!stripos($form,'method="get"')){
			$hidden[$CI->security->get_csrf_token_name()]=$CI->security->get_csrf_hash();
		}
		if($CI->load->is_loaded('tokens')&&strpos($action,$CI->config->base_url('',$link_protocol))!==false&&!stripos($form,'method="get"'))
			$hidden[$CI->tokens->name]=$CI->tokens->token();
		if (is_array($hidden)){
			foreach ($hidden as $name=>$value){
				$form .= '<input type="hidden" name="'.$name.'" value="'.html_escape($value).'" />'."\n";
			}
		}
		return $form;
	}
}