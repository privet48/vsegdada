<?php
defined('BASEPATH') OR exit('No direct script access allowed');
if(!function_exists('is_serialized')){
	function is_serialized($data){
		if (!is_string($data))
			return false;
		$data=trim($data);
	 	if ('N;' == $data)
			return true;
		$length=strlen($data);
		if ($length<4)
			return false;
		if (':' !== $data[1])
			return false;
		$lastc=$data[$length-1];
		if (';' !== $lastc && '}' !== $lastc)
			return false;
		$token=$data[0];
		switch ($token) {
			case 's' :
				if ('"' !== $data[$length-2])
					return false;
			case 'a' :
			case 'O' :
				return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
			case 'b' :
			case 'i' :
			case 'd' :
				return (bool) preg_match("/^{$token}:[0-9.E-]+;\$/", $data);
		}
		return false;
	}
}
if(!function_exists('serialize_data')){
	function serialize_data($data){
		if (is_array($data)){
			foreach ($data as $key => $val){
				if (is_string($val)){
					$data[$key]=str_replace('\\', '{{slash}}', $val);
				}
			}
		}else{
			if (is_string($data)){
				$data=str_replace('\\', '{{slash}}', $data);
			}
		}
		return serialize($data);
	}
}
if(!function_exists('unserialize_data')){
	function unserialize_data($data){
		if(is_serialized($data)){
			$data=unserialize(stripslashes($data));
			if (is_array($data)){
				foreach ($data as $key => $val){
					if (is_string($val)){
						$data[$key]=str_replace('{{slash}}', '\\', $val);
					}
				}
				return $data;
			}
			$data=is_string($data) 
				? str_replace('{{slash}}', '\\', $data) 
				: $data;
		}
		return $data;
	}
}