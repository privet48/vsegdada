<?php  
defined('BASEPATH') or exit('No direct script access allowed');
function sendRequest($data,$method){
	$curl=curl_init();
	curl_setopt_array($curl,[
	  CURLOPT_URL => config_item('apiUrl').$method,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => json_encode(array_merge(['accessKey' => config_item('accessKey'),'secretKey' => config_item('secretKey')],$data)),
	  CURLOPT_HTTPHEADER => [
		"Content-Type: application/json",
	  ],
	]);
	$response=curl_exec($curl);
	curl_close($curl);
	return json_decode($response);	
}
function sendRequestPhoto($data,$method){
	$curl=curl_init();
	curl_setopt_array($curl,[
	  CURLOPT_URL => config_item('apiUrl').$method,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => array_merge(['accessKey' => config_item('accessKey'),'secretKey' => config_item('secretKey')],$data),
	  CURLOPT_HTTPHEADER => [
		"Content-Type: multipart/form-data"
	  ],
	]);
	$response=curl_exec($curl);
	curl_close($curl);
	return json_decode($response);	
}
function requestVerifyPhone($data){
	$curl=curl_init();
	curl_setopt_array($curl,[
	  CURLOPT_URL => config_item('apiUrl')."verifyPhoneNew",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => json_encode(array_merge(['accessKey' => config_item('accessKey'),'secretKey' => config_item('secretKey')],$data)),
	  CURLOPT_HTTPHEADER => [
		"Content-Type: application/json",
	  ],
	]);
	$response=curl_exec($curl);
	curl_close($curl);
	return json_decode($response);
}
function validatePhone($phone){
	if(preg_match('/\\Q7 (\\E\\d{3}\\)\\s\\d{3}-\\d{2}-\\d{2}|\d{11}/',$phone,$matches)){
		return true;
	}
	return false;
}
function validateDateTimestamp($timestamp){
	if(1!==preg_match( '~^[1-9][0-9]*$~',$timestamp)){
		return false;
	}
	$date=date('Y-m-d H:i:s',$timestamp);
	$date=new DateTime($date);
	if($date->format('Y')>date('Y')){
		return false;
	}
	return true;
}
function validateFullName($text){
	if(preg_match('/^[а-я]{2,35}$/iu',$text)){
		return true;
	}
	return false;
}
function validateDocument($num,$type){
	if($type==config_item('documentTypes')['identificationNumber']){
		return validateInn($num);
	}elseif($type==config_item('documentTypes')['insuranceNumber']){
		return validateSnils($num);
	}
}
function validateInn($inn,&$error_message=null,&$error_code=null){
	$result=false;
	$inn=(string)$inn;
	if(!$inn){
		$error_code=1;
	}elseif(preg_match('/[^0-9]/',$inn)){
		$error_code=2;
	}elseif(!in_array($inn_length=strlen($inn),[10,12])){
		$error_code=3;
	}else{
		$check_digit=function($inn,$coefficients){
			$n=0;
			foreach($coefficients as $i => $k){
				$n += $k *(int) $inn{$i};
			}
			return $n % 11 % 10;
		};
		switch($inn_length){
			case 10:
				$n10=$check_digit($inn,[2,4,10,3,5,9,4,6,8]);
				if($n10===(int) $inn{9}){
					$result=true;
				}
				break;
			case 12:
				$n11=$check_digit($inn,[7,2,4,10,3,5,9,4,6,8]);
				$n12=$check_digit($inn,[3,7,2,4,10,3,5,9,4,6,8]);
				if(($n11===(int) $inn{10}) &&($n12===(int) $inn{11})){
					$result=true;
				}
				break;
		}
		if(!$result){
			$error_code=4;
		}
	}
	return $result;
}
function validateSnils($snils,&$error_message=null,&$error_code=null){
	$result=false;
	$snils =(string) $snils;
	if(!$snils){
		$error_code=1;
	}elseif(preg_match('/[^0-9]/',$snils)){
		$error_code=2;
	}elseif(strlen($snils) !== 11){
		$error_code=3;
	}else{
		$sum=0;
		for($i=0; $i<9; $i++){
			$sum +=(int) $snils{$i}*(9 - $i);
		}
		$check_digit=0;
		if($sum<100){
			$check_digit=$sum;
		}elseif($sum > 101){
			$check_digit=$sum % 101;
			if($check_digit===100){
				$check_digit=0;
			}
		}
		if($check_digit===(int) substr($snils,-2)){
			$result=true;
		}else{
			$error_code=4;
		}
	}
	return $result;
}