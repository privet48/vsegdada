<?php  
defined('BASEPATH') or exit('No direct script access allowed');
class DADATA{
	public static function sendDadataRequest($data,$method){
		$curl=curl_init();
		curl_setopt_array($curl,[
		  CURLOPT_URL => config_item('dadataApiUrl').$method,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => json_encode(array_merge(['count' => config_item('dadataQueriesCount')],$data)),
		  CURLOPT_HTTPHEADER => [
			"Content-Type: application/json",
			"Authorization: Token ".config_item('dadataAccessToken'),
		  ],
		]);
		$response=curl_exec($curl);
		curl_close($curl);
		return $response;	
	}
}