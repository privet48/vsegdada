<?php  
defined('BASEPATH') or exit('No direct script access allowed');
function sendSms($phone,$message){
	$curl=curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => config_item('smsApiUrl'),
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_POSTFIELDS => json_encode(array_merge(config_item('smsAuth'),['method' => config_item('smsMethodSend'),'phone' => $phone,'mess' => $message])),
	  CURLOPT_HTTPHEADER => array(
		"Content-Type: application/json",
	  ),
	));
	$body=curl_exec($curl);
	curl_close($curl);
	return json_decode($body);						
}