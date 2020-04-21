<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
if ( ! function_exists('asset_url()')){
	function asset_url(){
		$protocol=stripos($_SERVER['SERVER_PROTOCOL'],'https')===0?'https':'http';
		return base_url('assets/',$protocol);
	}
}