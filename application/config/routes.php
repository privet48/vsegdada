<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$route['default_controller'] = 'main';
$route['404_override'] = 'errors/error_404';
$route['/'] = 'main';
$route['assets/(:any)'] = 'assets/$1';
$route['translate_uri_dashes'] = false;
$route['login'] = 'auth/login';
$route['ajax_login'] = 'auth/ajax_login';
$route['logout'] = 'auth/logout';
$route['ajax_attempt_login'] = 'auth/ajax_attempt_login';
$route['keycreator'] = 'key_creator';
$route['keycreator/create/(:num)'] = 'key_creator/create/$1';
$route['create_user'] = 'auth/create_user';
$route['auth'] = 'auth';
$route['scr'] = 'scr';
$route['registration/photo/(:any)'] = 'registration/photo/$1';
$route['registration/(:num)/([A-Za-z0-9]{9,10})'] = 'registration/sendSms/$1/$2';
$route['registration/(:num)/(:any)/(:any)/(:any)/(:any)/(:any)/(:any)?'] = 'registration/checkUser/$1/$2/$3/$4/$5/$6/$7';
$route['registration/(:num)/(:num)/sms'] = 'registration/sms/$1/$2';
$route['registration/(:num)/(:num)/resendCode'] = 'registration/resendCode/$1/$2';
$route['registration/testAuth'] = 'registration/testAuth';
$route['main/resendCode'] = 'main/resendCode';
$route['main'] = 'main/index';
$route['main/password'] = 'main/changePassword';
$route['main/recovery/([a-zA-Z\d\.\-]{32})'] = 'main/recovery/$1';
$route['registration/password/(:num)/(:num)'] = 'registration/changePassword/$1/$2';
$route['registration/recovery/([a-zA-Z\d\.\-]{32})'] = 'registration/recovery/$1';
$route['api/createPersonalCabinet'] = 'api/createPersonalCabinet';
$route['api/createPersonalCabinet/applicationId/(:any)'] = 'api/createPersonalCabinet/$1';
$route['api/(getDocs|getStatus)/applicationId/(:any)'] = function($method,$appId){
	if(isset($_GET['type'])){
		return 'api/getDoc/'.$appId.'/'.$_GET['type'];
	}
	return 'api/'.$method.'/' .$appId;
};
$route['main/address']='main/getAddressSuggestions';
$route['main/photo']='main/generatePhotoLink';
