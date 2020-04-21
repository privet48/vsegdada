<?php
defined('BASEPATH') OR exit('No direct script access allowed');
function auth_constants(){
	define('USE_SSL', 0);
	define('LOGIN_PAGE', 'login');
	define('AUTH_REDIRECT_PARAM', 'redirect');
	define('AUTH_LOGOUT_PARAM', 'logout');
}